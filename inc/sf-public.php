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
	}

	/**
	 * Load Font Awesome Icons Classes.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function enqueue_scripts() {
		$is_set_v2_site_key = get_option( 'sureforms_v2_invisible_site' );

		// Font Awesome icons.
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], SUREFORMS_VER );

		// SureForms frontend CSS.
		wp_enqueue_style( 'srfm-frontend', SUREFORMS_URL . 'assets/src/public/styles/sureforms-frontend.css', [], SUREFORMS_VER );

		if ( has_block( 'sureforms/sf-form' ) || has_block( 'sureforms/form' ) || has_shortcode( get_the_content(), 'sureforms' ) || wp_is_block_theme() ) {
			wp_enqueue_style( 'srfm-frontend-styles', SUREFORMS_URL . 'assets/build/sureforms_frontend_styles.css', [], SUREFORMS_VER, 'all' );
		}

		// SureForms frontend JS.
		wp_enqueue_script( 'srfm-frontend-script', SUREFORMS_URL . 'assets/src/public/scripts/frontend.js', [], SUREFORMS_VER, true );
		wp_enqueue_script( 'srfm-form-submit', SUREFORMS_URL . 'assets/src/public/scripts/form-submit.js', [], SUREFORMS_VER, true );

		// Google reCaptcha.
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SUREFORMS_VER, true );
		if ( ! empty( $is_set_v2_site_key ) ) {
			wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', [], SUREFORMS_VER, true );
		}
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

