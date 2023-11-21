<?php
/**
 * Sureforms Blocks Loader.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sureforms_Spec_Block_Loader' ) ) {

	/**
	 * Class Sureforms_Spec_Block_Loader.
	 */
	final class Sureforms_Spec_Block_Loader {

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
		 * Constructor
		 */
		public function __construct() {

			define( 'SRFM_TABLET_BREAKPOINT', '976' );
			define( 'SRFM_MOBILE_BREAKPOINT', '767' );

			$this->load_plugin();
		}

		/**
		 * Loads plugin files.
		 *
		 * @since 0.0.1
		 *
		 * @return void
		 */
		public function load_plugin() {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$is_spectra_active = is_plugin_active( 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php' );

			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-sureforms-spec-gb-helper.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-sureforms-spec-init-blocks.php';

			if ( ! $is_spectra_active ) {
				require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-sureforms-spec-spectra-compatibility.php';
			}

			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/icon/class-sureforms-spec-icon.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/separator/class-sureforms-spec-separator.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-sureforms-spec-filesystem.php';
		}

	}
	Sureforms_Spec_Block_Loader::get_instance();
}

