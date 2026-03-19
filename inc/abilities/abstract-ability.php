<?php
/**
 * Abstract Ability Base Class.
 *
 * Provides the foundation for all SureForms abilities registered
 * with the WordPress Abilities API (WP 6.9+).
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract_Ability class.
 *
 * All SureForms abilities must extend this class and implement
 * the abstract methods: get_input_schema(), get_output_schema(), and execute().
 *
 * @since 2.5.2
 */
abstract class Abstract_Ability {
	/**
	 * Minimum required capability for registration policy enforcement.
	 *
	 * @since 2.5.2
	 */
	private const MIN_CAPABILITY = 'manage_options';
	/**
	 * Unique ability identifier.
	 *
	 * @var string
	 * @since 2.5.2
	 */
	protected $id = '';

	/**
	 * Human-readable label.
	 *
	 * @var string
	 * @since 2.5.2
	 */
	protected $label = '';

	/**
	 * Ability description.
	 *
	 * @var string
	 * @since 2.5.2
	 */
	protected $description = '';

	/**
	 * Ability category.
	 *
	 * @var string
	 * @since 2.5.2
	 */
	protected $category = 'sureforms';

	/**
	 * Required WordPress capability.
	 *
	 * @var string
	 * @since 2.5.2
	 */
	protected $capability = 'manage_options';

	/**
	 * Option gate key.
	 *
	 * When non-empty, the ability is disabled only if the option is explicitly set to '0'.
	 * Abilities default to enabled — they are only gated off when an admin explicitly
	 * disables them via the AI settings page.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $gated = '';

	/**
	 * Get the JSON Schema for ability input.
	 *
	 * @since 2.5.2
	 * @return array<string,mixed>
	 */
	abstract public function get_input_schema();

	/**
	 * Get the JSON Schema for ability output.
	 *
	 * @since 2.5.2
	 * @return array<string,mixed>
	 */
	abstract public function get_output_schema();

	/**
	 * Execute the ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.5.2
	 * @return array<string,mixed>|\WP_Error
	 */
	abstract public function execute( $input );

	/**
	 * Check whether this ability is enabled based on its option gate.
	 *
	 * Returns false when the ability has a gate key and the corresponding
	 * option is falsy. Used by the registrar to skip registration of
	 * disabled abilities so they don't appear in MCP listings.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! empty( $this->gated ) && ! get_option( $this->gated, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Permission callback.
	 *
	 * Delegates to current_user_can() with the configured capability.
	 *
	 * @since 2.5.2
	 * @return bool
	 */
	public function permission_callback() {
		if ( ! get_option( 'srfm_abilities_api', false ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		return current_user_can( $this->capability );
	}

	/**
	 * Check if this ability meets the minimum capability policy.
	 *
	 * Prevents third-party abilities registered via srfm_register_abilities
	 * from downgrading the required capability below manage_options.
	 *
	 * @since 2.5.2
	 * @return bool
	 */
	public function meets_capability_policy() {
		return self::MIN_CAPABILITY === $this->capability;
	}

	/**
	 * Get ability annotations.
	 *
	 * Returns MCP-compatible annotations for readonly, destructive, idempotent,
	 * priority, and openWorldHint flags. Subclasses should override to customize.
	 *
	 * @since 2.5.2
	 * @return array<string,bool|float|string>
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.0,
			'openWorldHint' => false,
		];
	}

	/**
	 * Execution wrapper with pre/post hooks.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.5.2
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute_wrapper( $input ) {
		/**
		 * Fires before an ability is executed.
		 *
		 * @param string              $id    Ability ID.
		 * @param array<string,mixed> $input Input data.
		 * @since 2.5.2
		 */
		do_action( 'srfm_before_ability_execute', $this->id, $input );

		$output = $this->execute( $input );

		/**
		 * Fires after an ability is executed.
		 *
		 * @param string                        $id     Ability ID.
		 * @param array<string,mixed>           $input  Input data.
		 * @param array<string,mixed>|\WP_Error $output Output data.
		 * @since 2.5.2
		 */
		do_action( 'srfm_after_ability_execute', $this->id, $input, $output );

		return $output;
	}

	/**
	 * Register this ability with the WordPress Abilities API.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$annotations = $this->get_annotations();

		wp_register_ability(
			$this->id,
			[
				'label'               => $this->label,
				'description'         => $this->description,
				'category'            => $this->category,
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'permission_callback' => [ $this, 'permission_callback' ],
				'execute_callback'    => [ $this, 'execute_wrapper' ],
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => $annotations,
					'mcp'          => [
						'public' => false,
					],
				],
			]
		);
	}

	/**
	 * Get the ability ID.
	 *
	 * @since 2.5.2
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}
