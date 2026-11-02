<?php
/**
 * SureForms Public Class.
 *
 * Class file for public functions.
 *
 * @package SureForms
 */

namespace SRFM\Inc;

use SRFM\Inc\Compatibility\Multilingual\String_Translator;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;

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
				// No 'wp-api-fetch' dependency: the script talks to the REST API via a
				// plain fetch() against the URLs localized below, not wp.apiFetch(),
				// so submissions no longer depend on that second script having loaded
				// and executed correctly. See the wp_localize_script() call below for
				// why wp.apiFetch's middleware (root-URL resolution, nonce injection)
				// isn't needed for either endpoint this script calls.
				//
				// 'wp-i18n' and 'wp-hooks' ARE required and must stay. The bundle imports
				// __() and applyFilters(), which @wordpress/scripts externalises to the
				// wp.i18n / window.wp.hooks globals instead of inlining — the generated
				// assets/build/formSubmit.asset.php is the authority on this list. They
				// used to arrive for free because 'wp-api-fetch' pulled them in through
				// its own dependency graph; dropping that above removed them, and an
				// undeclared wp.hooks is undefined under a JS-combining optimizer, which
				// kills every submission with the same TypeError this change prevents.
				wp_register_script(
					SRFM_SLUG . '-' . $handle,
					SRFM_URL . 'assets/build/' . $name . '.js',
					[ 'wp-i18n', 'wp-hooks' ],
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

		$validation_messages = array_merge(
			Translatable::get_frontend_validation_messages(),
			[
				'srfm_turnstile_error_message'      => __( 'Turnstile sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
				'srfm_google_captcha_error_message' => __( 'Google Captcha sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
				'srfm_captcha_h_error_message'      => __( 'HCaptcha sitekey verification failed. Please contact your site administrator.', 'sureforms' ),
			]
		);

		// Translate each validation message through the active provider so
		// admin-supplied translations from WPML String Translation win over the
		// .mo-file fallback. When no provider is active, this is a pass-through.
		$validation_messages = String_Translator::get_instance()->translate_validation_messages( $validation_messages );

		wp_localize_script(
			SRFM_SLUG . '-form-submit',
			SRFM_SLUG . '_submit',
			[
				'site_url'          => site_url(),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				// Fully resolved REST endpoint URL, so the frontend can call it with a
				// plain fetch() instead of wp.apiFetch(). rest_url() already accounts
				// for pretty vs. plain permalinks (the latter needs a `?rest_route=`
				// query var rather than a path segment), subdirectory installs, and
				// multisite domain mapping — the same resolution wp.apiFetch's root-URL
				// middleware would otherwise do from a second, independently-loaded
				// script. submit-form's auth does not depend on that script either: it
				// is guarded by the X-WP-Submit-Token header (Submit_Token::verify()).
				//
				// The after-submission URL is deliberately NOT localized. It needs the
				// submission id and a per-submission nonce, so it is built server-side
				// and returned in the submit response instead (see Form_Submit). A base
				// URL here invited the client to concatenate those on, which silently
				// produced an unroutable URL wherever rest_url() returns a
				// `?rest_route=` form.
				'submit_form_url'   => esc_url_raw( rest_url( 'sureforms/v1/submit-form' ) ),
				'messages'          => $validation_messages,
				'is_rtl'            => $is_rtl,
				// Resolved RFC 5321 email limits so the client honors the
				// srfm_email_field_char_limits filter instead of hardcoding 64/255.
				'email_char_limits' => Field_Validation::get_email_char_limits(),
				// Hint only. This value is baked into cached HTML and can be a full
				// cache TTL out of date, so the server re-checks on every write --
				// see Form_Submit::client_error_log_permissions_check(). Its job is
				// to keep the browser from posting when logging is plainly off.
				'logging_enabled'   => Client_Logger::is_enabled(),
				'log_error_url'     => esc_url_raw( rest_url( 'sureforms/v1/log-client-error' ) ),
			]
		);

		$current_post = get_post();

		// Let's conditionally load form assets if current requested page has our forms.
		if ( $current_post instanceof \WP_Post ) {
			// Handles condition for Instant Form, Block Embedded, and Shortcode Embedded forms.
			$load_assets = ( SRFM_FORMS_POST_TYPE === $current_post->post_type || ( false !== strpos( $current_post->post_content, 'wp:srfm/form' ) || has_shortcode( $current_post->post_content, 'sureforms' ) ) );

			if ( $load_assets ) {
				// Load needed styles in head tag if current requested page has SureForms form.
				// Skip the SureForms stylesheets when every form on the page has default styling disabled.
				self::enqueue_scripts_and_styles( Form_Styling::should_skip_frontend_styles( $current_post ) );
			}
		}
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param bool $skip_form_styles When true, the SureForms stylesheets are not enqueued
	 *                               (default styling disabled for the form). External library
	 *                               styles and scripts are always loaded so advanced fields
	 *                               like Dropdown and Phone keep working.
	 * @return void
	 * @since 0.0.11
	 */
	public static function enqueue_scripts_and_styles( $skip_form_styles = false ) {
		// Load the styles.
		if ( ! $skip_form_styles ) {
			foreach ( self::$css_assets as $handle => $path ) {

				// Skip single form styles if not on single form page.
				if ( 'single' === $handle && ! is_singular( SRFM_FORMS_POST_TYPE ) ) {
					continue;
				}

				wp_enqueue_style( SRFM_SLUG . '-' . $handle );
			}
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
	 * @param string               $block_type block name.
	 * @param array<string, mixed> $attr Array of block attributes.
	 * @since 0.0.1
	 * @return void
	 */
	public function enqueue_srfm_script( $block_type, $attr ) {
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
			$css_vendor_uri                    = SRFM_URL . 'assets/css/minified/deps/';
			if ( 'phone' === $block_name ) {
				// Enqueue main intl-tel-input library.
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}-intl-input-deps", $js_vendor_uri . 'intl/intTelInputWithUtils.min.js', [], SRFM_VER, true );

				// Enqueue i18n translations if available for current locale.
				self::enqueue_intl_tel_input_i18n(
					SRFM_SLUG . "-{$block_name}-intl-i18n",
					SRFM_SLUG . "-{$block_name}-intl-input-deps"
				);
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

			$is_not_dropdown = 'dropdown' !== $block_name;
			$is_not_textarea = 'textarea' !== $block_name;

			if ( $is_not_dropdown && $is_not_textarea ) {
				// Set dependencies for phone block to ensure intl-tel-input loads first.
				$block_dependencies = [];
				if ( 'phone' === $block_name ) {
					// Phone.js depends on intl-tel-input library (and i18n if loaded).
					$block_dependencies = [ SRFM_SLUG . "-{$block_name}-intl-input-deps" ];
				}
				wp_enqueue_script( SRFM_SLUG . "-{$block_name}", $js_uri . $block_name . $file_prefix . '.js', $block_dependencies, SRFM_VER, true );

				if ( 'phone' === $block_name ) {
					// Geo-country endpoint for per-visitor auto country detection.
					// Built with rest_url() so it is correct regardless of permalink structure.
					wp_localize_script(
						SRFM_SLUG . "-{$block_name}",
						'srfm_phone_data',
						[
							'geo_endpoint' => esc_url_raw( rest_url( 'sureforms/v1/geo-country' ) ),
						]
					);
				}
			}

			if ( 'input' === $block_name && isset( $attr['inputMask'] ) && 'none' !== $attr['inputMask'] ) {
				// Input mask JS - only load when inputMask is configured.
				wp_enqueue_script( SRFM_SLUG . '-inputmask', $js_vendor_uri . 'inputmask.min.js', [], SRFM_VER, true );
			}

			// Adding js for the input textarea block.
			if ( 'textarea' === $block_name && ! empty( $attr['isRichText'] ) ) {
				wp_enqueue_script( SRFM_SLUG . '-quill-editor', $js_vendor_uri . '/quill.min.js', [], SRFM_VER, true );

				wp_enqueue_style( SRFM_SLUG . '-quill-editor', $css_vendor_uri . 'quill/quill.snow.css', [], SRFM_VER );

				wp_enqueue_script( SRFM_SLUG . '-textarea', $js_uri . 'textarea' . $file_prefix . '.js', [], SRFM_VER, true );

				wp_localize_script(
					SRFM_SLUG . '-textarea',
					'srfm_quill_i18n',
					[
						'normal'     => _x( 'Normal', 'Quill heading picker: default paragraph style', 'sureforms' ),
						'heading_1'  => __( 'Heading 1', 'sureforms' ),
						'heading_2'  => __( 'Heading 2', 'sureforms' ),
						'heading_3'  => __( 'Heading 3', 'sureforms' ),
						'heading_4'  => __( 'Heading 4', 'sureforms' ),
						'heading_5'  => __( 'Heading 5', 'sureforms' ),
						'heading_6'  => __( 'Heading 6', 'sureforms' ),
						'visit_url'  => _x( 'Visit URL:', 'Quill link tooltip label', 'sureforms' ),
						'enter_link' => _x( 'Enter link:', 'Quill link tooltip label', 'sureforms' ),
						'edit'       => _x( 'Edit', 'Quill link tooltip action', 'sureforms' ),
						'save'       => _x( 'Save', 'Quill link tooltip action', 'sureforms' ),
						'remove'     => _x( 'Remove', 'Quill link tooltip action', 'sureforms' ),
					]
				);
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

		if ( 'payment' === $block_name ) {
			// Register Stripe.js library from CDN.
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Stripe CDN ignores version; version param is included to keep linter happy.
			wp_enqueue_script(
				'stripe-js',
				'https://js.stripe.com/v3/',
				[],
				SRFM_VER,
				true
			);

			wp_enqueue_script(
				SRFM_SLUG . '-stripe-payment',
				SRFM_URL . 'assets/js/stripe-payment.js',
				[ 'stripe-js' ],
				SRFM_VER,
				true
			);

			// Enqueue Payment Manager for payment method switching.
			wp_enqueue_script(
				SRFM_SLUG . '-payment-manager',
				SRFM_URL . 'assets/js/payment-manager.js',
				[ SRFM_SLUG . '-stripe-payment' ],
				SRFM_VER,
				true
			);

			// Localize script for Stripe payment functionality.
			wp_localize_script(
				SRFM_SLUG . '-stripe-payment',
				'srfm_ajax',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				]
			);

			// Localize Stripe payment data for frontend.
			wp_localize_script(
				SRFM_SLUG . '-stripe-payment',
				'srfmStripe',
				[
					'zeroDecimalCurrencies' => Payment_Helper::get_zero_decimal_currencies(),
					'currenciesData'        => Payment_Helper::get_all_currencies_data(),
					'strings'               => Payment_Helper::get_payment_strings(),
					'currencySignPosition'  => Payment_Helper::get_currency_sign_position(),
					// Apple Pay / Google Pay as switched in the Stripe dashboard.
					'wallets'               => Stripe_Helper::get_wallet_settings(),
				]
			);
		}

		// Trigger custom action hook to allow third-party plugins or add-ons
		// to enqueue additional scripts/styles for specific blocks (e.g., payment providers).
		do_action(
			'srfm_enqueue_block_scripts',
			[
				'block_name' => $block_name,
				'attr'       => $attr,
			]
		);
	}

	/**
	 * Maps WordPress locale to intl-tel-input language code.
	 *
	 * Converts WordPress locale format (e.g., fr_FR, de_DE, pt_BR)
	 * to intl-tel-input language codes (e.g., fr, de, pt).
	 * Returns null if the language is not supported by intl-tel-input.
	 *
	 * @since 2.5.0
	 * @param string|null $locale WordPress locale string. If null, uses get_locale().
	 * @return string|null Language code if supported, null if not supported or English.
	 */
	public static function get_intl_tel_input_locale( $locale = null ) {
		// Get WordPress locale if not provided.
		if ( null === $locale ) {
			$locale = get_locale();
		}

		// Extract language code from WordPress locale (e.g., fr_FR -> fr, pt_BR -> pt).
		$lang_code = substr( $locale, 0, 2 );

		// List of supported languages - available translation files in /assets/js/minified/deps/intl/i18n/.
		// Reference: https://unpkg.com/browse/intl-tel-input@24.5.1/build/js/i18n/.
		$supported_languages = [
			'de', // German - Deutsch.
			'es', // Spanish - Español.
			'fr', // French - Français.
			'it', // Italian - Italiano.
			'nl', // Dutch - Nederlands.
			'pl', // Polish - Polski.
			'pt', // Portuguese - Português.
		];

		// Return language code only if supported and not English (English is default).
		if ( in_array( $lang_code, $supported_languages, true ) && 'en' !== $lang_code ) {
			return $lang_code;
		}

		// Return null for unsupported languages or English (uses default English).
		return null;
	}

	/**
	 * Enqueues intl-tel-input i18n script for the phone field.
	 *
	 * This method handles conditional loading of language files based on WordPress locale.
	 * Only enqueues if a supported non-English language is detected and the file exists.
	 *
	 * @since 2.5.0
	 * @param string $handle       Script handle to enqueue.
	 * @param string $dependencies Optional. Script handle that this i18n depends on. Default empty.
	 * @return bool True if i18n was enqueued, false otherwise.
	 */
	public static function enqueue_intl_tel_input_i18n( $handle, $dependencies = '' ) {
		$intl_locale = self::get_intl_tel_input_locale();

		// Return early if no locale or English.
		if ( empty( $intl_locale ) ) {
			return false;
		}

		$i18n_file_path = SRFM_DIR . "assets/js/minified/deps/intl/i18n/{$intl_locale}/index.min.js";

		// Only enqueue if the language file exists.
		if ( ! file_exists( $i18n_file_path ) ) {
			return false;
		}

		$deps = ! empty( $dependencies ) ? [ $dependencies ] : [];

		wp_enqueue_script(
			$handle,
			SRFM_URL . "assets/js/minified/deps/intl/i18n/{$intl_locale}/index.min.js",
			$deps,
			SRFM_VER,
			true
		);

		return true;
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
			$attr = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];

			self::enqueue_srfm_script( $block['blockName'], $attr );
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

		self::reset_printed_assets();

		$file_name = 'single-form.php';
		$template  = locate_template( $file_name );

		/**
		 * Hook: srfm_form_template filter.
		 *
		 * @since 0.0.1
		 */
		return apply_filters( 'srfm_form_template', $template ? $template : SRFM_DIR . '/templates/' . $file_name );
	}

	/**
	 * Let the Instant Form template print assets a discarded render already claimed.
	 *
	 * `page_template()` runs on `template_include` at PHP_INT_MAX and returns the
	 * Instant Form template regardless of what earlier filters returned. A page
	 * builder that renders the whole page inside its own `template_include`
	 * filter has therefore already run `wp_head()` and `wp_footer()` into an
	 * output buffer that is about to be thrown away.
	 *
	 * The buffer goes, but `WP_Styles::$done` and `WP_Scripts::$done` still hold
	 * every handle it claimed, so `do_items()` skips them when `single-form.php`
	 * calls `wp_head()` and `wp_footer()` for real. The form arrives with no
	 * stylesheets, and -- because `srfm-form-submit` is registered for the footer
	 * -- no submit handler either.
	 *
	 * Clearing both `done` lists wholesale is the right scope rather than an
	 * over-broad one: only the render that begins after this filter returns
	 * reaches the browser, so nothing recorded before it was ever delivered. The
	 * handles are re-enqueued by the second `wp_enqueue_scripts` pass, so they
	 * print normally once `done` stops shadowing them.
	 *
	 * Guarded on `wp_head` having already fired, so this is inert on the ordinary
	 * path where no builder rendered first and nothing has been printed yet.
	 *
	 * @since 2.12.7
	 * @return void
	 */
	private static function reset_printed_assets() {
		// No earlier wp_head() means no discarded render, so there is nothing to
		// forget. Deny is the fallthrough: act only on the state this repairs.
		if ( ! did_action( 'wp_head' ) ) {
			return;
		}

		wp_styles()->done  = [];
		wp_scripts()->done = [];
	}

}
