<?php
/**
 * SureForms Public Class.
 *
 * Class file for public functions.
 *
 * @package SureForms
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Public Class
 *
 * @since 0.0.1
 */
class Frontend_Assets {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_filter( 'template_include', [ $this, 'page_template' ], PHP_INT_MAX );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'render_block', [ $this, 'generate_render_script' ], 10, 2 );
	}

	/**
	 * Enqueue Script.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function enqueue_scripts() {
		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
		$js_uri      = SRFM_URL . 'assets/js/' . $dir_name . '/';
		$css_uri     = SRFM_URL . 'assets/css/' . $dir_name . '/';
		$css_vendor  = SRFM_URL . 'assets/css/minified/deps/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		$security_setting_options = get_option( 'srfm_security_settings_options' );
		$is_set_v2_site_key       = false;
		if ( is_array( $security_setting_options ) && isset( $security_setting_options['srfm_v2_invisible_site_key'] ) && ! empty( $security_setting_options['srfm_v2_invisible_site_key'] ) ) {
			$is_set_v2_site_key = true;
		}

		// Styles based on meta style.
		wp_enqueue_style( SRFM_SLUG . '-frontend-default', $css_uri . '/blocks/default/frontend' . $file_prefix . '.css', [], SRFM_VER );

		// Common styles for all meta styles.
		wp_enqueue_style( SRFM_SLUG . '-common', $css_uri . 'common' . $file_prefix . '.css', [], SRFM_VER, 'all' );
		wp_enqueue_style( SRFM_SLUG . '-form', $css_uri . 'frontend/form' . $file_prefix . '.css', [], SRFM_VER, 'all' );

		if ( is_single() ) {
			wp_enqueue_style( SRFM_SLUG . '-single', $css_uri . 'single' . $file_prefix . '.css', [], SRFM_VER );
		}

		// Dependencies
		// Nice Select CSS.
		wp_enqueue_style( SRFM_SLUG . '-tom-select', $css_vendor . 'tom-select.css', [], SRFM_VER );
		// Int-tel-input CSS.
		wp_enqueue_style( SRFM_SLUG . '-intl-tel-input', $css_vendor . 'intl/intlTelInput.min.css', [], SRFM_VER );

		wp_enqueue_script( SRFM_SLUG . '-form-submit', SRFM_URL . 'assets/build/formSubmit.js', [], SRFM_VER, true );
		// Frontend common and validation before submit.
		wp_enqueue_script( SRFM_SLUG . '-frontend', $js_uri . 'frontend.min.js', [], SRFM_VER, true );

		wp_localize_script(
			SRFM_SLUG . '-form-submit',
			SRFM_SLUG . '_submit',
			[
				'site_url' => site_url(),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Enqueue block scripts
	 *
	 * @param string $block_type block name.
	 * @since 0.0.1
	 * @return void
	 */
	public function enqueue_srfm_script( $block_type ) {
		$block_name        = str_replace( 'srfm/', '', $block_type );
		$script_dep_blocks = [ 'address-compact', 'checkbox', 'dropdown', 'multi-choice', 'number', 'textarea', 'url', 'phone' ];

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		if ( in_array( $block_name, $script_dep_blocks, true ) ) {
			$js_uri        = SRFM_URL . 'assets/js/' . $dir_name . '/blocks/';
			$js_vendor_uri = SRFM_URL . 'assets/js/minified/deps/';

			if ( 'phone' === $block_name ) {
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}-intl-input-deps", $js_vendor_uri . 'intl/intTelInput.min.js', [], SRFM_VER, true );
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}-intl-utils-deps", $js_vendor_uri . 'intl/intTelUtils.min.js', [], SRFM_VER, true );
			}

			if ( 'dropdown' === $block_name || 'address-compact' === $block_name ) {
				wp_enqueue_script( SRFM_SLUG . '-dropdown', $js_uri . 'dropdown' . $file_prefix . '.js', [], SRFM_VER, true );
				wp_enqueue_script( SRFM_SLUG . '-tom-select', $js_vendor_uri . 'tom-select.min.js', [], SRFM_VER, true );
			}

			if ( 'dropdown' !== $block_name ) {
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}", $js_uri . $block_name . $file_prefix . '.js', [], SRFM_VER, true );
			}
		}
	}

	/**
	 * Render function.
	 *
	 * @param string        $block_content Entire Block Content.
	 * @param array<string> $block Block Properties As An Array.
	 * @return string
	 */
	public function generate_render_script( $block_content, $block ) {

		if ( isset( $block['blockName'] ) ) {
			self::enqueue_srfm_script( $block['blockName'] );
		}
		return $block_content;
	}

	/**
	 * Form Template filter.
	 *
	 * @param string $template Template.
	 * @return string Template.
	 * @since 0.0.1
	 */
	public function page_template( $template ) {
		if ( is_singular( SRFM_FORMS_POST_TYPE ) ) {
			$file_name = 'single-form.php';
			$template  = locate_template( $file_name ) ? locate_template( $file_name ) : SRFM_DIR . '/templates/' . $file_name;
			$template  = apply_filters( 'srfm_form_template', $template );
		}
		return $template;
	}

}
