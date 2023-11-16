<?php
/**
 * Cartflows Blocks Loader.
 *
 * @package Cartflows
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Cartflows_Block_Loader' ) ) {

	/**
	 * Class Cartflows_Block_Loader.
	 */
	final class Cartflows_Block_Loader {

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

			define( 'CF_TABLET_BREAKPOINT', '976' );
			define( 'CF_MOBILE_BREAKPOINT', '767' );

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

			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-cartflows-gb-helper.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-cartflows-gutenberg-editor.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-cartflows-init-blocks.php';

			if ( ! $is_spectra_active ) {
				require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-cartflows-spectra-compatibility.php';
			}
			
			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/advanced-heading/class-advanced-heading.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/icon/class-icon.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/image/class-image.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/dist/blocks/separator/class-separator.php';
			require_once SUREFORMS_DIR . 'modules/gutenberg/classes/class-uagb-filesystem.php';
		}

	}
	Cartflows_Block_Loader::get_instance();
}

