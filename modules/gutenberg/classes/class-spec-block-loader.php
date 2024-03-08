<?php
/**
 * Sureforms Blocks Loader.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Block_Loader' ) ) {

	/**
	 * Class Spec_Block_Loader.
	 */
	final class Spec_Block_Loader {

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

			require_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-gb-helper.php';
			require_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-init-blocks.php';

			require_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-spectra-compatibility.php';

			require_once SRFM_DIR . 'modules/gutenberg/dist/blocks/advanced-heading/class-advanced-heading.php';
			require_once SRFM_DIR . 'modules/gutenberg/dist/blocks/icon/class-spec-icon.php';
			require_once SRFM_DIR . 'modules/gutenberg/dist/blocks/image/class-spec-image.php';
			require_once SRFM_DIR . 'modules/gutenberg/dist/blocks/separator/class-spec-separator.php';
			require_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-filesystem.php';
		}

	}
	Spec_Block_Loader::get_instance();
}

