<?php
/**
 * Page Builder.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders;

use SRFM\Inc\Frontend_Assets;
use SRFM\Inc\Page_Builders\Bricks\Service_Provider as Bricks_Service_Provider;
use SRFM\Inc\Page_Builders\Elementor\Service_Provider as Elementor_Service_Provider;
use SRFM\Inc\Traits\Get_Instance;

/**
 * Class to add SureForms widget in other page builders.
 */
class Page_Builders {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * Load all Page Builders form widgets.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function __construct() {
		Elementor_Service_Provider::get_instance();
		Bricks_Service_Provider::get_instance();
	}

	/**
	 * Enqueue common fields assets for the dropdown and phone fields.
	 *
	 * @since 0.0.10
	 * @return void
	 */
	public static function enqueue_common_fields_assets() {
		$file_prefix   = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name      = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
		$js_uri        = SRFM_URL . 'assets/js/' . $dir_name . '/blocks/';
		$js_vendor_uri = SRFM_URL . 'assets/js/minified/deps/';

		wp_enqueue_script( SRFM_SLUG . '-phone', $js_uri . 'phone' . $file_prefix . '.js', [], SRFM_VER, true );
		wp_enqueue_script( SRFM_SLUG . '-phone-intl-input-deps', $js_vendor_uri . 'intl/intTelInputWithUtils.min.js', [], SRFM_VER, true );

		wp_enqueue_script( SRFM_SLUG . '-dropdown', $js_uri . 'dropdown' . $file_prefix . '.js', [ 'wp-a11y' ], SRFM_VER, true );
		wp_enqueue_script( SRFM_SLUG . '-tom-select', $js_vendor_uri . 'tom-select.min.js', [], SRFM_VER, true );

		if ( defined( 'SRFM_PRO_VER' ) && defined( 'SRFM_PRO_URL' ) && defined( 'SRFM_PRO_SLUG' ) ) {
			$css_uri = SRFM_PRO_URL . 'assets/css/' . $dir_name . '/';

			wp_enqueue_style( SRFM_PRO_SLUG . '-frontend-default', $css_uri . '/blocks/default/frontend' . $file_prefix . '.css', [], SRFM_PRO_VER );
		}

		Frontend_Assets::enqueue_scripts_and_styles();

		// Action to enqueue common field assets.
		do_action( 'srfm_enqueue_common_field_assets' );
	}

}
