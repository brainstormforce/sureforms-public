<?php
/**
 * SureForms Public Class.
 *
 * Class file for public functions.
 *
 * @package SureForms
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Public Class
 *
 * @since 0.0.1
 */
class SF_Public {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_filter( 'template_include', [ $this, 'page_template' ], PHP_INT_MAX );
		add_filter( 'the_content', [ $this, 'print_form' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'render_block', array( $this, 'generate_render_script' ), 10, 2 );
	}

	/**
	 * Load Font Awesome Icons Classes.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function enqueue_scripts() {
		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		$css_uri = SUREFORMS_URL . 'assets/css/' . $dir_name . '/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		$is_set_v2_site_key = get_option( 'sureforms_v2_invisible_site' );

		// Font Awesome icons.
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], SUREFORMS_VER );

		// SureForms Theme styles CSS.
		wp_enqueue_style( SUREFORMS_SLUG . '-sureforms-frontend', $css_uri . 'srfm_theme_styles' . $file_prefix . '.css', [], SUREFORMS_VER );

		// Extra.
		wp_enqueue_style( SUREFORMS_SLUG . '-frontend-styles', $css_uri . 'sureforms-frontend-ui-styles' . $file_prefix . '.css', array(), SUREFORMS_VER );

		// Flatpickr CSS.
		wp_enqueue_style( 'flatpickr', SUREFORMS_URL . 'assets/build/flatpickr_css.css', [], SUREFORMS_VER );

		// Flatpickr JS.
		wp_enqueue_script( 'flatpickr', SUREFORMS_URL . 'assets/build/flatpickr_js.js', [], SUREFORMS_VER, true );

		// Int-tel-input CSS.
		wp_enqueue_style( 'intlTelInput', SUREFORMS_URL . 'assets/src/public/styles/dependencies/intlTelInput.min.css', [], SUREFORMS_VER );

		// Int-tel-input JS.
		wp_enqueue_script( 'intlTelInput', SUREFORMS_URL . 'assets/src/public/scripts/dependencies/intTellnput.min.js', [], SUREFORMS_VER, true );
		wp_enqueue_script( 'intlTelInputUtils', SUREFORMS_URL . 'assets/src/public/scripts/dependencies/intTelUtils.min.js', [], SUREFORMS_VER, true );
		wp_enqueue_script( 'srfm-frontend-script', SUREFORMS_URL . 'assets/src/public/scripts/frontend.js', [], SUREFORMS_VER, true );
		wp_enqueue_script( 'srfm-form-submit', SUREFORMS_URL . 'assets/src/public/scripts/form-submit.js', [], SUREFORMS_VER, true );

		// Google reCaptcha.
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SUREFORMS_VER, true );
		if ( ! empty( $is_set_v2_site_key ) ) {
			wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', [], SUREFORMS_VER, true );
		}

		$is_rtl = is_rtl();

		wp_localize_script(
			SUREFORMS_SLUG . '-frontend-script',
			SUREFORMS_LOC,
			array(
				'isRTL' => $is_rtl,
			)
		);
		wp_localize_script(
			'srfm-form-submit',
			'sureforms_submit',
			array(
				'site_url' => site_url(),
			)
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
		$block_name        = str_replace( 'sureforms/', '', $block_type );
		$script_dep_blocks = [ 'rating', 'upload', 'switch', 'address', 'date-time-picker', 'checkbox', 'dropdown', 'multi-choice', 'number-slider', 'number', 'textarea', 'url', 'password', 'phone' ];
		if ( in_array( $block_name, $script_dep_blocks, true ) ) {
			$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
			$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
			$js_uri      = SUREFORMS_URL . 'assets/src/public/scripts/' . $dir_name . '/blocks/';
			wp_enqueue_script( SUREFORMS_SLUG . "-{$block_name}-js", $js_uri . $block_name . $file_prefix . '.js', [], SUREFORMS_VER, true );
		}
	}

	/**
	 * Render function.
	 *
	 * @param string $block_content Entire Block Content.
	 * @param array  $block Block Properties As An Array.
	 * @return string
	 * @phpstan-ignore-next-line
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
		if ( is_singular( SUREFORMS_FORMS_POST_TYPE ) ) {
			$file_name = 'single-form.php';
			$template  = locate_template( $file_name ) ? locate_template( $file_name ) : SUREFORMS_DIR . '/templates/' . $file_name;
			$template  = apply_filters( 'sureforms_form_template', $template );
		}
		return $template;
	}

	/**
	 * Print form template.
	 *
	 * @param string $content Content.
	 * @since 0.0.1
	 * @return string Modified Content.
	 */
	public function print_form( $content ) {
		if ( get_post_type() === SUREFORMS_FORMS_POST_TYPE ) {
			if ( has_blocks() ) {
				$blocks = parse_blocks( get_the_content() );
			}
		}
		return $content;
	}
}

