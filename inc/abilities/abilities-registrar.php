<?php
/**
 * Abilities Registrar.
 *
 * Orchestrates registration of all SureForms abilities
 * with the WordPress Abilities API (WP 6.9+).
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities;

use SRFM\Inc\Abilities\Analytics\Get_Form_Analytics;
use SRFM\Inc\Abilities\Entries\Bulk_Get_Entries;
use SRFM\Inc\Abilities\Entries\Delete_Entry;
use SRFM\Inc\Abilities\Entries\Get_Entry;
use SRFM\Inc\Abilities\Entries\List_Entries;
use SRFM\Inc\Abilities\Entries\Update_Entry_Status;
use SRFM\Inc\Abilities\Forms\Create_Form;
use SRFM\Inc\Abilities\Forms\Delete_Form;
use SRFM\Inc\Abilities\Forms\Duplicate_Form as Duplicate_Form_Ability;
use SRFM\Inc\Abilities\Forms\Get_Form;
use SRFM\Inc\Abilities\Forms\Get_Form_Stats;
use SRFM\Inc\Abilities\Forms\Get_Shortcode;
use SRFM\Inc\Abilities\Forms\List_Forms;
use SRFM\Inc\Abilities\Forms\Update_Form;
use SRFM\Inc\Abilities\Settings\Get_Global_Settings;
use SRFM\Inc\Abilities\Settings\Update_Global_Settings;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abilities_Registrar class.
 *
 * @since x.x.x
 */
class Abilities_Registrar {
	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * Bails early if wp_register_ability() is not available (WP < 6.9).
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		// Graceful degradation for WP < 6.9.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', [ $this, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );

		if ( self::mcp_adapter_enabled() ) {
			add_action( 'mcp_adapter_init', [ $this, 'register_mcp_server' ] );
		}
	}

	/**
	 * Check if the MCP adapter integration should be active.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function mcp_adapter_enabled() {
		return function_exists( 'wp_register_ability' ) &&
			class_exists( 'WP\MCP\Plugin' ) &&
			(bool) get_option( 'srfm_mcp_server', false );
	}

	/**
	 * Register a dedicated SureForms MCP server with the MCP adapter.
	 *
	 * Creates endpoint: {site_url}/wp-json/sureforms/v1/mcp
	 *
	 * @param \WP\MCP\Adapter\Adapter $adapter The MCP adapter instance.
	 * @since x.x.x
	 * @return void
	 */
	public function register_mcp_server( $adapter ) {
		$abilities = wp_get_abilities();
		$tools     = [];

		foreach ( $abilities as $ability ) {
			$meta = $ability->get_meta();
			$mcp  = $meta['mcp'] ?? [];
			if ( is_array( $mcp ) && ! empty( $mcp['public'] ) && 0 === strpos( $ability->get_name(), 'sureforms/' ) ) {
				$tools[] = $ability->get_name();
			}
		}

		$transport_class = class_exists( '\WP\MCP\Transport\HttpTransport' )
			? \WP\MCP\Transport\HttpTransport::class
			: \WP\MCP\Transport\Http\RestTransport::class;

		$adapter->create_server(
			'sureforms',
			'sureforms/v1',
			'mcp',
			__( 'SureForms MCP Server', 'sureforms' ),
			__( 'SureForms MCP Server for form building and management.', 'sureforms' ),
			SRFM_VER,
			[ $transport_class ],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$tools,
			[],
			[]
		);
	}

	/**
	 * Register the sureforms ability category.
	 *
	 * Uses wp_has_ability_category() guard to avoid collision with zipwp-mcp.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_category() {
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'sureforms' ) ) {
			return;
		}

		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				'sureforms',
				[
					'label'       => __( 'SureForms', 'sureforms' ),
					'description' => __( 'Form building and management abilities powered by SureForms.', 'sureforms' ),
				]
			);
		}
	}

	/**
	 * Register all SureForms abilities.
	 *
	 * Uses the srfm_register_abilities filter to allow third-party plugins
	 * to add their own abilities that extend Abstract_Ability.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_abilities() {
		$abilities = [
			new List_Forms(),
			new Create_Form(),
			new Get_Form(),
			new Get_Shortcode(),
			new Delete_Form(),
			new Duplicate_Form_Ability(),
			new Update_Form(),
			new Get_Form_Stats(),
			new List_Entries(),
			new Get_Entry(),
			new Bulk_Get_Entries(),
			new Update_Entry_Status(),
			new Delete_Entry(),
			new Get_Global_Settings(),
			new Update_Global_Settings(),
			new Get_Form_Analytics(),
		];

		/**
		 * Filters the list of abilities to register.
		 *
		 * Third-party plugins can add their own abilities by hooking into this filter.
		 * Each ability must extend SRFM\Inc\Abilities\Abstract_Ability.
		 *
		 * @param array<Abstract_Ability> $abilities Array of ability instances.
		 * @since x.x.x
		 */
		$abilities = apply_filters( 'srfm_register_abilities', $abilities );

		foreach ( $abilities as $ability ) {
			if ( ! $ability instanceof Abstract_Ability ) {
				continue;
			}

			// Enforce minimum capability policy — reject abilities with caps weaker than manage_options.
			if ( ! $ability->meets_capability_policy() ) {
				continue;
			}

			// Skip abilities already registered by zipwp-mcp.
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability->get_id() ) ) {
				continue;
			}

			$ability->register();
		}
	}
}
