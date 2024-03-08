<?php
/**
 * Plugin Loader.
 *
 * @package sureforms
 * @since 0.0.1
 */

namespace SRFM;

use SRFM\Inc\SRFM_Post_Types;
use SRFM\Inc\SRFM_Submit;
use SRFM\Inc\SRFM_Gutenberg_Hooks;
use SRFM\API\Block_Patterns;
use SRFM\Inc\SRFM_Forms_Data;
use SRFM\Admin\Admin;
use SRFM\Inc\Blocks\SRFM_Register;
use SRFM\Inc\SRFM_Public;
use SRFM\Inc\SRFM_Helper;
use SRFM\Inc\SRFM_Activator;
use SRFM\Inc\SRFM_Admin_Ajax;
use SRFM\Inc\SRFM_Export;
use SRFM\Inc\SRFM_Smart_Tags;
use SRFM\Inc\SRFM_Generate_Form_Markup;
use SRFM\Inc\SRFM_Create_New_Form;
use SRFM\Inc\Global_Settings\SRFM_Global_Settings;
use SRFM\Inc\Global_Settings\SRFM_Email_Summary;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin_Loader
 *
 * @since 0.0.1
 */
class SRFM_Plugin_Loader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 0.0.1
	 */
	private static $instance = null;

	/**
	 * Initiator
	 *
	 * @since 0.0.1
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();

			/**
			 * SureForms loaded.
			 *
			 * Fires when SureForms was fully loaded and instantiated.
			 *
			 * @since 0.0.1
			 */
			do_action( 'srfm_core_loaded' );
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

			$file = SRFM_DIR . $filename . '.php';

			// if the file is readable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}

	}

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		// Load the action scheduler before plugin loads.
		require_once SRFM_DIR . 'inc/lib/action-scheduler/action-scheduler.php';

		spl_autoload_register( [ $this, 'autoload' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin' ], 99 );
		add_action( 'init', [ $this, 'load_classes' ] );
		add_action( 'admin_init', [ $this, 'activation_redirect' ] );
		SRFM_Post_Types::get_instance();
		SRFM_Submit::get_instance();
		Block_Patterns::get_instance();
		SRFM_Gutenberg_Hooks::get_instance();
		SRFM_Register::get_instance();
		SRFM_Public::get_instance();
		SRFM_Helper::get_instance();
		SRFM_Activator::get_instance();
		SRFM_Admin_Ajax::get_instance();
		SRFM_Forms_Data::get_instance();
		SRFM_Export::get_instance();
		SRFM_Smart_Tags::get_instance();
		SRFM_Generate_Form_Markup::get_instance();
		SRFM_Create_New_Form::get_instance();
		SRFM_Global_Settings::get_instance();
		SRFM_Email_Summary::get_instance();

		/**
		 * The code that runs during plugin activation
		 */
		register_activation_hook(
			SRFM_FILE,
			function () {
				SRFM_Activator::activate();
			}
		);

		register_deactivation_hook(
			SRFM_FILE,
			function () {
				update_option( '__sureforms_do_redirect', false );
				SRFM_Email_Summary::unschedule_events( 'srfm_weekly_scheduled_events' );
			}
		);
	}

	/**
	 * Activation Reset
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function activation_redirect() {

		$do_redirect = apply_filters( 'srfm_enable_redirect_activation', get_option( '__srfm_do_redirect' ) );

		if ( $do_redirect ) {

			update_option( '__srfm_do_redirect', false );

			if ( ! is_multisite() ) {
				wp_safe_redirect(
					add_query_arg(
						[
							'page'                     => 'sureforms_menu',
							'srfm-activation-redirect' => true,
						],
						admin_url( 'admin.php' )
					)
				);
				exit();
			}
		}
	}

	/**
	 * Load Classes.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function load_classes() {
		if ( is_admin() ) {
			Admin::get_instance();
		}
	}

	/**
	 * Load Plugin Text Domain.
	 * This will load the translation textdomain depending on the file priorities.
	 *      1. Global Languages /wp-content/languages/sureforms/ folder
	 *      2. Local directory /wp-content/plugins/sureforms/languages/ folder
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function load_textdomain() {
		// Default languages directory.
		$lang_dir = SRFM_DIR . 'languages/';

		/**
		 * Filters the languages directory path to use for plugin.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'srfm_languages_directory', $lang_dir );

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
		$locale = apply_filters( 'plugin_locale', $get_locale, 'sureforms' );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
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


	/**
	 * Loads plugin files.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function load_plugin() {
		$this->load_core_files();
	}

	/**
	 * Load Core Files.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function load_core_files() {
		include_once SRFM_DIR . 'modules/gutenberg/classes/class-srfm-spec-block-loader.php';
	}
}



/**
 * Kicking this off by calling 'get_instance()' method
 */
SRFM_Plugin_Loader::get_instance();
