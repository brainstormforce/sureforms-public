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
	 * JS Assets.
	 *
	 * @since 0.0.11
	 * @var array<string>
	 */
	public static $js_assets = [
		'form-submit' => 'formSubmit',
		'frontend'    => 'frontend',
	];

	/**
	 * CSS Assets.
	 *
	 * @since 0.0.11
	 * @var array<string>
	 */
	public static $css_assets = [
		'frontend-default' => 'blocks/default/frontend',
		'common'           => 'common',
		'form'             => 'frontend/form',
		'single'           => 'single',
	];

	/**
	 * External CSS Assets.
	 *
	 * @since 0.0.11
	 * @var array<string>
	 */
	public static $css_external_assets = [
		'tom-select'     => 'tom-select',
		'intl-tel-input' => 'intl/intlTelInput.min',
	];

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_filter( 'template_include', [ $this, 'page_template' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_filter( 'render_block', [ $this, 'generate_render_script' ], 10, 2 );
	}

	/**
	 * Enqueue Script.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_scripts() {
		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
		$js_uri      = SRFM_URL . 'assets/js/' . $dir_name . '/';
		$css_uri     = SRFM_URL . 'assets/css/' . $dir_name . '/';
		$css_vendor  = SRFM_URL . 'assets/css/minified/deps/';
		$is_rtl      = is_rtl();
		$rtl         = $is_rtl ? '-rtl' : '';

		$security_setting_options = get_option( 'srfm_security_settings_options' );
		$is_set_v2_site_key       = false;
		if ( is_array( $security_setting_options ) && isset( $security_setting_options['srfm_v2_invisible_site_key'] ) && ! empty( $security_setting_options['srfm_v2_invisible_site_key'] ) ) {
			$is_set_v2_site_key = true;
		}

		// Styles based on meta style.
		foreach ( self::$css_assets as $handle => $path ) {
			wp_register_style( SRFM_SLUG . '-' . $handle, $css_uri . $path . $file_prefix . $rtl . '.css', [], SRFM_VER );
		}

		// External styles.
		foreach ( self::$css_external_assets as $handle => $path ) {
			wp_register_style( SRFM_SLUG . '-' . $handle, $css_vendor . $path . '.css', [], SRFM_VER );
		}

		// Scripts.
		foreach ( self::$js_assets as $handle => $name ) {
			if ( 'form-submit' === $handle ) {
				wp_register_script(
					SRFM_SLUG . '-' . $handle,
					SRFM_URL . 'assets/build/' . $name . '.js',
					[ 'wp-api-fetch' ],
					SRFM_VER,
					true
				);
			} else {
				wp_register_script(
					SRFM_SLUG . '-' . $handle,
					$js_uri . $name . $file_prefix . '.js',
					[],
					SRFM_VER,
					true
				);
			}
		}

		wp_localize_script(
			SRFM_SLUG . '-form-submit',
			SRFM_SLUG . '_submit',
			[
				'site_url' => site_url(),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'messages' => array_merge(
					Translatable::get_frontend_validation_messages(),
					[
						'srfm_turnstile_error_message' => __( 'Turnstile sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
						'srfm_google_captcha_error_message' => __( 'Google Captcha sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
						'srfm_captcha_h_error_message' => __( 'HCaptcha sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
					]
				),
				'is_rtl'   => $is_rtl,
			]
		);

		$current_post = get_post();

		// Let's conditionally load form assets if current requested page has our forms.
		if ( $current_post instanceof \WP_Post ) {
			// Handles condition for Instant Form, Block Embedded, and Shortcode Embedded forms.
			$load_assets = ( SRFM_FORMS_POST_TYPE === $current_post->post_type || ( false !== strpos( $current_post->post_content, 'wp:srfm/form' ) || has_shortcode( $current_post->post_content, 'sureforms' ) ) );

			if ( $load_assets ) {
				// Load needed styles in head tag if current requested page has SureForms form.
				self::enqueue_scripts_and_styles();
			}
		}
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 * @since 0.0.11
	 */
	public static function enqueue_scripts_and_styles() {
		// Load the styles.
		foreach ( self::$css_assets as $handle => $path ) {

			// Skip single form styles if not on single form page.
			if ( 'single' === $handle && ! is_singular( SRFM_FORMS_POST_TYPE ) ) {
				continue;
			}

			wp_enqueue_style( SRFM_SLUG . '-' . $handle );
		}

		// Load the external styles. Like Phone and Tom Select.
		foreach ( self::$css_external_assets as $handle => $path ) {
			wp_enqueue_style( SRFM_SLUG . '-' . $handle );
		}

		// Load the scripts.
		foreach ( self::$js_assets as $handle => $path ) {
			wp_enqueue_script( SRFM_SLUG . '-' . $handle );
		}
	}

	/**
	 * Enqueue block scripts
	 *
	 * @param string $block_type block name.
	 * @since 0.0.1
	 * @return void
	 */
	public function enqueue_srfm_script( $block_type ) {
		$block_name = str_replace( 'srfm/', '', $block_type );
		// associative array to keep the count of block that requires scripts to work.
		$script_dep_blocks = [
			'dropdown'     => 0,
			'multi-choice' => 0,
			'number'       => 0,
			'textarea'     => 0,
			'url'          => 0,
			'phone'        => 0,
			'input'        => 0,
		];

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		// Check if block is in the array and check if block is already enqueued.
		if (
			in_array( $block_name, array_keys( $script_dep_blocks ), true ) &&
			0 === $script_dep_blocks[ $block_name ]
		) {
			$script_dep_blocks[ $block_name ] += 1;
			$js_uri                            = SRFM_URL . 'assets/js/' . $dir_name . '/blocks/';
			$js_vendor_uri                     = SRFM_URL . 'assets/js/minified/deps/';

			if ( 'phone' === $block_name
			) {
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}-intl-input-deps", $js_vendor_uri . 'intl/intTelInputWithUtils.min.js', [], SRFM_VER, true );
			}

			if ( 'dropdown' === $block_name ) {
				// if the dropdown / address-compact block is after any other block, then we need to dequeue the srfm-form-submit script and enqueue it again and load it with tom-select dependency.
				wp_dequeue_script( SRFM_SLUG . '-form-submit' );
				wp_enqueue_script( SRFM_SLUG . '-dropdown', $js_uri . 'dropdown' . $file_prefix . '.js', [ 'wp-a11y' ], SRFM_VER, true );
				wp_enqueue_script( SRFM_SLUG . '-tom-select', $js_vendor_uri . 'tom-select.min.js', [], SRFM_VER, true );
				// frontend utils using dropdown dependency.
				wp_enqueue_script(
					SRFM_SLUG . '-form-submit',
					SRFM_URL . 'assets/build/formSubmit.js',
					[
						'srfm-tom-select',
						'srfm-dropdown',
						'wp-api-fetch',
					],
					SRFM_VER,
					true
				);
			}

			if ( 'dropdown' !== $block_name ) {
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}", $js_uri . $block_name . $file_prefix . '.js', [], SRFM_VER, true );
			}

			if ( 'input' === $block_name ) {
				// Input mask JS.
				wp_enqueue_script( SRFM_SLUG . '-inputmask', $js_vendor_uri . 'inputmask.min.js', [], SRFM_VER, true );
			}
		}
		/**
		 * Enqueueing the input mask JS for input and date-picker blocks.
		 * This is a workaround for the input mask JS to work with the date-picker block.
		 * Not adding in the above existing condition because code only runs when free block are added in the form.
		 * Aim is to reduce redundant code and library file duplication.
		 */
		if ( 'date-picker' === $block_name ) {
			// Input mask JS.
			wp_enqueue_script( SRFM_SLUG . '-inputmask', SRFM_URL . 'assets/js/minified/deps/inputmask.min.js', [], SRFM_VER, true );
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

		if ( isset( $block['attrs']['isEditing'] ) ) {
			// Only load block assets on the frontend.
			return $block_content;
		}

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

		if ( ! is_singular( SRFM_FORMS_POST_TYPE ) ) {
			// Bail if not SureForms post type.
			return $template;
		}

		$file_name = 'single-form.php';
		$template  = locate_template( $file_name );

		/**
		 * Hook: srfm_form_template filter.
		 *
		 * @since 0.0.1
		 */
		return apply_filters( 'srfm_form_template', $template ? $template : SRFM_DIR . '/templates/' . $file_name );
	}

}
