<?php
/**
 * Admin Notice Manager Class.
 *
 * This class manages admin notices and bridges them to React admin pages.
 * It collects notices from various sources (including existing PHP notices)
 * and exports them to React via wp_localize_script.
 *
 * @package sureforms
 * @since 2.5.0
 */

namespace SRFM\Admin;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Notice Manager class.
 *
 * Handles collection and distribution of admin notices to both PHP and React contexts.
 *
 * @since 2.5.0
 */
class Notice_Manager {
	use Get_Instance;

	/**
	 * Registered notices.
	 *
	 * @var array
	 * @since 2.5.0
	 */
	private static $notices = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 2.5.0
	 */
	public function __construct() {
		// Hook to add notices to localized data.
		add_filter( 'srfm_admin_filter', [ $this, 'add_notices_to_localized_data' ] );
	}

	/**
	 * Register a notice for display in React admin pages.
	 *
	 * This method allows PHP code to register notices that will be displayed
	 * in React admin pages via the AdminNotice component.
	 *
	 * @param array $notice_args {
	 *     Notice configuration arguments.
	 *
	 *     @type string   $id          Required. Unique notice identifier.
	 *     @type string   $variant     Notice type: 'error', 'warning', 'info', 'success'. Default 'info'.
	 *     @type string   $message     Required. Notice message (can contain HTML).
	 *     @type string   $title       Optional. Notice title.
	 *     @type array    $actions     Optional. Array of action button configurations.
	 *     @type bool     $dismissible Optional. Whether notice can be dismissed. Default true.
	 *     @type array    $pages       Optional. Page slugs where notice should appear. Default ['all'].
	 * }
	 *
	 * Action button structure:
	 * {
	 *     @type string $label    Required. Button text.
	 *     @type string $url      Optional. URL to navigate to.
	 *     @type string $target   Optional. Link target: '_blank' or '_self'. Default '_self'.
	 *     @type string $variant  Optional. Button variant: 'primary', 'secondary', 'link'. Default 'primary'.
	 *     @type string $size     Optional. Button size: 'sm', 'md', 'lg'. Default 'sm'.
	 *     @type string $className Optional. Additional CSS classes.
	 * }
	 *
	 * @return void
	 * @since 2.5.0
	 */
	public static function register_notice( $notice_args ) {
		// Validate required fields.
		if ( empty( $notice_args['id'] ) || empty( $notice_args['message'] ) ) {
			return;
		}

		// Set defaults.
		$notice = wp_parse_args(
			$notice_args,
			[
				'id'          => '',
				'variant'     => 'info',
				'message'     => '',
				'title'       => '',
				'actions'     => [],
				'dismissible' => true,
				'pages'       => [ 'all' ],
			]
		);

		// Ensure pages is an array.
		if ( ! is_array( $notice['pages'] ) ) {
			$notice['pages'] = [ $notice['pages'] ];
		}

		// Store the notice.
		self::$notices[ $notice['id'] ] = $notice;
	}

	/**
	 * Get all registered notices.
	 *
	 * @return array Array of notice configurations.
	 * @since 2.5.0
	 */
	public static function get_notices() {
		return array_values( self::$notices );
	}

	/**
	 * Remove a registered notice.
	 *
	 * @param string $notice_id The notice ID to remove.
	 * @return void
	 * @since 2.5.0
	 */
	public static function remove_notice( $notice_id ) {
		if ( isset( self::$notices[ $notice_id ] ) ) {
			unset( self::$notices[ $notice_id ] );
		}
	}

	/**
	 * Clear all registered notices.
	 *
	 * @return void
	 * @since 2.5.0
	 */
	public static function clear_notices() {
		self::$notices = [];
	}

	/**
	 * Add notices to localized data for React.
	 *
	 * This filter callback adds the notices array to the localized script data
	 * that gets passed to React via window.srfm_admin.
	 *
	 * @param array $localization_data Existing localization data.
	 * @return array Modified localization data with notices.
	 * @since 2.5.0
	 */
	public function add_notices_to_localized_data( $localization_data ) {
		$localization_data['notices'] = self::get_notices();
		return $localization_data;
	}

	/**
	 * Helper method to register a notice from existing PHP admin_notices hooks.
	 *
	 * This method simplifies converting existing PHP notices to work with React.
	 * Call this method from your existing admin_notices callback to also register
	 * the notice for React pages.
	 *
	 * Example usage:
	 * ```php
	 * public function my_admin_notice() {
	 *     $message = '<p>' . esc_html__( 'Important notice!', 'sureforms' ) . '</p>';
	 *
	 *     // Display in PHP (existing behavior - continues to work)
	 *     echo '<div class="notice notice-error">' . wp_kses_post( $message ) . '</div>';
	 *
	 *     // Also register for React pages (new behavior)
	 *     Notice_Manager::register_from_php_notice(
	 *         'my-notice-id',
	 *         'error',
	 *         $message,
	 *         [
	 *             [
	 *                 'label' => __( 'Fix Now', 'sureforms' ),
	 *                 'url'   => admin_url( 'admin.php?page=settings' ),
	 *             ]
	 *         ]
	 *     );
	 * }
	 * ```
	 *
	 * @param string $id      Unique notice identifier.
	 * @param string $variant Notice type: 'error', 'warning', 'info', 'success'.
	 * @param string $message Notice message (HTML allowed).
	 * @param array  $actions Optional. Array of action button configurations.
	 * @param array  $pages   Optional. Page slugs where notice should appear. Default ['all'].
	 * @return void
	 * @since 2.5.0
	 */
	public static function register_from_php_notice( $id, $variant, $message, $actions = [], $pages = [ 'all' ] ) {
		self::register_notice(
			[
				'id'      => $id,
				'variant' => $variant,
				'message' => $message,
				'actions' => $actions,
				'pages'   => $pages,
			]
		);
	}
}
