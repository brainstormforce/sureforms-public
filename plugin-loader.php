<?php
/**
 * Plugin Loader.
 *
 * @package sureforms
 * @since 0.0.1
 */

namespace SureForms;

use SureForms\Inc\Post_Types;
use SureForms\Admin\Admin;
use SureForms\Admin\Gutenberg_Hooks;

/**
 * Plugin_Loader
 *
 * @since X.X.X
 */
class Plugin_Loader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since X.X.X
	 */
	private static $instance = null;

	/**
	 * Initiator
	 *
	 * @since X.X.X
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class class name.
	 * @return void
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$filename = preg_replace(
			[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$class
		);

		if ( is_string( $filename ) ) {

			$filename = strtolower( $filename );

			$file = SUREFORMS_DIR . $filename . '.php';

			// if the file is readable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}

	}

	/**
	 * Constructor
	 *
	 * @since X.X.X
	 */
	public function __construct() {

		spl_autoload_register( [ $this, 'autoload' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'load_classes' ] );
		Post_Types::get_instance();
	}

	/**
	 * Load Classes.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function load_classes() {
		if ( is_admin() ) {
			Admin::get_instance();
			Gutenberg_Hooks::get_instance();
		}
	}

	/**
	 * Load Plugin Text Domain.
	 * This will load the translation textdomain depending on the file priorities.
	 *      1. Global Languages /wp-content/languages/sureforms/ folder
	 *      2. Local directory /wp-content/plugins/sureforms/languages/ folder
	 *
	 * @since X.X.X
	 * @return void
	 */
	public function load_textdomain() {
		// Default languages directory.
		$lang_dir = SUREFORMS_DIR . 'languages/';

		/**
		 * Filters the languages directory path to use for plugin.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'sureforms_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter.
		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Language Locale for plugin
		 *
		 * @var string $get_locale The locale to use.
		 * Uses get_user_locale()` in WordPress 4.7 or greater,
		 * otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'sureforms' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'sureforms', $locale );

		// Setup paths to current locale file.
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = $lang_dir . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/sureforms/ folder.
			load_textdomain( 'sureforms', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/sureforms/languages/ folder.
			load_textdomain( 'sureforms', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'sureforms', false, $lang_dir );
		}
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Plugin_Loader::get_instance();
