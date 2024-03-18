<?php
/**
 * Sureforms Filesystem
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spec_Filesystem.
 */
class Spec_Filesystem {

	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get an instance of WP_Filesystem.
	 *
	 * @since 0.0.1
	 */
	public function get_filesystem() {

		global $wp_filesystem;

		if ( ! $wp_filesystem || 'direct' !== $wp_filesystem->method ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			/**
			 * Context for filesystem, default false.
			 *
			 * @see request_filesystem_credentials_context
			 */
			$context = apply_filters( 'request_filesystem_credentials_context', false );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook

			add_filter( 'filesystem_method', [ $this, 'filesystem_method' ] );
			add_filter( 'request_filesystem_credentials', [ $this, 'request_filesystem_credentials' ] );

			$creds = request_filesystem_credentials( site_url(), '', true, $context, null );

			WP_Filesystem( $creds, $context );

			remove_filter( 'filesystem_method', [ $this, 'filesystem_method' ] );
			remove_filter( 'request_filesystem_credentials', [ $this, 'request_filesystem_credentials' ] );
		}

		// Set the permission constants if not already set.
		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			// phpcs:ignore
			define( 'FS_CHMOD_DIR', 0755 ); // ignore statement added to avoid WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, it's pre defined constant in bootstrap.php file
			// phpcs:ignoreEnd
		}
		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			// phpcs:ignore
			define( 'FS_CHMOD_FILE', 0644 ); // ignore statement added to avoid WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, it's pre defined constant in bootstrap.php file
			// phpcs:ignoreEnd
		}

		return $wp_filesystem;
	}

	/**
	 * Method to direct.
	 *
	 * @since 0.0.1
	 */
	public function filesystem_method() {
		return 'direct';
	}

	/**
	 * Sets credentials to true.
	 *
	 * @since 0.0.1
	 */
	public function request_filesystem_credentials() {
		return true;
	}
}

/**
 *  Prepare if class 'Spec_Filesystem' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spec_Filesystem::get_instance();

/**
 * Filesystem class
 *
 * @since 0.0.1
 */
function spec_filesystem() {
	return Spec_Filesystem::get_instance()->get_filesystem();
}
