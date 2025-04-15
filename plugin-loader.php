<?php
/**
 * Plugin Loader.
 *
 * @package sureforms
 * @since 0.0.1
 */

namespace SRFM;

use SRFM\Admin\Admin;
use SRFM\Admin\Analytics;
use SRFM\API\Block_Patterns;
use SRFM\Inc\Activator;
use SRFM\Inc\Admin_Ajax;
use SRFM\Inc\AI_Form_Builder\AI_Auth;
use SRFM\Inc\AI_Form_Builder\AI_Form_Builder;
use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
use SRFM\Inc\Background_Process;
use SRFM\Inc\Blocks\Register;
use SRFM\Inc\Compatibility\Themes\Astra;
use SRFM\Inc\Create_New_Form;
use SRFM\Inc\Database\Register as DatabaseRegister;
use SRFM\Inc\Events_Scheduler;
use SRFM\Inc\Export;
use SRFM\Inc\Form_Submit;
use SRFM\Inc\Forms_Data;
use SRFM\Inc\Frontend_Assets;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Global_Settings\Email_Summary;
use SRFM\Inc\Global_Settings\Global_Settings;
use SRFM\Inc\Gutenberg_Hooks;
use SRFM\Inc\Helper;
use SRFM\Inc\Lib\SRFM_Nps_Survey;
use SRFM\Inc\Nps_Notice;
use SRFM\Inc\Page_Builders\Page_Builders;
use SRFM\Inc\Post_Types;
use SRFM\Inc\Rest_Api;
use SRFM\Inc\Single_Form_Settings\Compliance_Settings;
use SRFM\Inc\Smart_Tags;
use SRFM\Inc\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin_Loader
 *
 * @since 0.0.1
 */
class Plugin_Loader {
	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 0.0.1
	 */
	private static $instance = null;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		if ( ! defined( 'SRFM_DIR' ) || ! defined( 'SRFM_FILE' ) ) {
			return;
		}
		// Load the action scheduler before plugin loads.
		require_once SRFM_DIR . 'inc/lib/action-scheduler/action-scheduler.php';

		spl_autoload_register( [ $this, 'autoload' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin' ], 99 );
		add_action( 'init', [ $this, 'load_classes' ] );
		add_action( 'admin_init', [ $this, 'activation_redirect' ] );
		Analytics::get_instance();
		/**
		 * The code that runs during plugin activation
		 */
		register_activation_hook(
			SRFM_FILE,
			static function () {
				Activator::activate();
			}
		);

		register_deactivation_hook(
			SRFM_FILE,
			static function () {
				update_option( '__sureforms_do_redirect', false );
				Events_Scheduler::unschedule_events( 'srfm_weekly_scheduled_events' );
				Events_Scheduler::unschedule_events( 'srfm_daily_scheduled_action' );
			}
		);
	}

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
	 * Activation Reset
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function activation_redirect() {
		// Avoid redirection in case of WP_CLI calls.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			return;
		}

		// Avoid redirection in case of ajax calls.
		if ( wp_doing_ajax() ) {
			return;
		}

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
				exit;
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

		Register::get_instance();
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
		Post_Types::get_instance();
		Form_Submit::get_instance();
		Block_Patterns::get_instance();
		Gutenberg_Hooks::get_instance();
		Frontend_Assets::get_instance();
		Helper::get_instance();
		Activator::get_instance();
		Admin_Ajax::get_instance();
		Forms_Data::get_instance();
		Export::get_instance();
		Smart_Tags::get_instance();
		Generate_Form_Markup::get_instance();
		Create_New_Form::get_instance();
		Global_Settings::get_instance();
		Email_Summary::get_instance();
		Compliance_Settings::get_instance();
		Events_Scheduler::get_instance();
		AI_Form_Builder::get_instance();
		Field_Mapping::get_instance();
		Background_Process::get_instance();
		Page_Builders::get_instance();
		Rest_Api::get_instance();
		AI_Helper::get_instance();
		AI_Auth::get_instance();
		Updater::get_instance();
		DatabaseRegister::init();
		// Initializing Compatibilities.
		Astra::get_instance();
		/**
		 * Required to add the if check for the class existence to resolve phpstan error,
		 * as the phpstan configuration ignores the inc/lib directory which gives error
		 * unknown class.
		 */
		if ( class_exists( 'SRFM\Inc\Lib\SRFM_Nps_Survey' ) && ! apply_filters( 'srfm_disable_nps_survey', false ) ) {
			SRFM_Nps_Survey::get_instance(); // Inits the NPS Survey class for which inits the NPS Survey plugin.
			Nps_Notice::get_instance(); // Responsible for displaying the NPS Survey: keeping the line out of the check will also work.
		}

		/**
		 * Load core files necessary for the Spectra block.
		 * This method is called in the Spectra block loader to ensure core files are loaded during the 'plugins_loaded' action.
		 *
		 * Note: This code is added at the bottom to ensure the form block is loaded first,
		 * followed by the Spectra blocks such as heading, image, and icon blocks.
		 */
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
		include_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-block-loader.php';
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Plugin_Loader::get_instance();
