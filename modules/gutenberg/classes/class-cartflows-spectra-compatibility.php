<?php
/**
 * Spectra theme compatibility
 *
 * @package CartFlows
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Cartflows_Spectra_Compatibility' ) ) :

	/**
	 * Class for Spectra compatibility
	 */
	class Cartflows_Spectra_Compatibility {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.5.7
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.5.7
		 */
		public function __construct() {

			// Hook: Editor assets.
			add_action( 'enqueue_block_editor_assets', array( $this, 'spectra_editor_assets' ) );
		}

		/**
		 * Clear theme cached CSS if required.
		 */
		public function spectra_editor_assets() {

			wp_localize_script(
				'CF_block-cartflows-block-js',
				'uagb_blocks_info',
				array(
					'number_of_icon_chunks'         => Cartflows_Gb_Helper::$number_of_icon_chunks,
					'collapse_panels'               => 'disabled',
					'load_font_awesome_5'           => 'disabled',
					'uag_select_font_globally'      => array(),
					'uag_load_select_font_globally' => array(),
					'font_awesome_5_polyfill'       => array(),
					'spectra_custom_fonts'          => apply_filters( 'spectra_system_fonts', array() ),
				)
			);

			// Add svg icons in chunks.
			$this->add_svg_icon_assets();
		}

		/**
		 * Localize SVG icon scripts in chunks.
		 * Ex - if 1800 icons available so we will localize 4 variables for it.
		 *
		 * @since 2.7.0
		 * @return void
		 */
		public function add_svg_icon_assets() {
			$localize_icon_chunks = Cartflows_Gb_Helper::backend_load_font_awesome_icons();

			if ( ! $localize_icon_chunks ) {
				return;
			}

			foreach ( $localize_icon_chunks as $chunk_index => $value ) {
				wp_localize_script( 'CF_block-cartflows-block-js', "uagb_svg_icons_{$chunk_index}", $value );
			}
		}

	}
	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Cartflows_Spectra_Compatibility::get_instance();

endif;
