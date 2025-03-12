<?php
/**
 * Spectra theme compatibility
 *
 * @package Sureforms
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Spec_Spectra_Compatibility' ) ) :

	/**
	 * Class for Spectra compatibility
	 */
	class Spec_Spectra_Compatibility {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 0.0.1
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
		 * @since 0.0.1
		 */
		public function __construct() {

			// Hook: Editor assets.
			add_action( 'enqueue_block_editor_assets', [ $this, 'spectra_editor_assets' ] );
		}

		/**
		 * Clear theme cached CSS if required.
		 */
		public function spectra_editor_assets() {

			wp_localize_script(
				'srfm-spec-block-js',
				'srfm_spec_blocks_info',
				[
					'number_of_icon_chunks'         => Spec_Gb_Helper::$number_of_icon_chunks,
					'collapse_panels'               => 'enabled',
					'load_font_awesome_5'           => 'disabled',
					'uag_select_font_globally'      => [],
					'uag_load_select_font_globally' => [],
					'font_awesome_5_polyfill'       => [],
					'spectra_custom_fonts'          => apply_filters( 'srfm_system_fonts', [] ),
					'tablet_breakpoint'             => SRFM_TABLET_BREAKPOINT,
					'mobile_breakpoint'             => SRFM_TABLET_BREAKPOINT,
					'category'                      => 'sureforms',
					'srfm_url'                      => SRFM_URL,
				]
			);

			// Add svg icons in chunks.
			$this->add_svg_icon_assets();
		}

		/**
		 * Localize SVG icon scripts in chunks.
		 * Ex - if 1800 icons available so we will localize 4 variables for it.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function add_svg_icon_assets() {
			$localize_icon_chunks = Spec_Gb_Helper::backend_load_font_awesome_icons();

			if ( ! $localize_icon_chunks ) {
				return;
			}

			foreach ( $localize_icon_chunks as $chunk_index => $value ) {
				wp_localize_script( 'srfm-spec-block-js', "uagb_svg_icons_{$chunk_index}", $value );
			}
		}

	}
	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Spec_Spectra_Compatibility::get_instance();

endif;
