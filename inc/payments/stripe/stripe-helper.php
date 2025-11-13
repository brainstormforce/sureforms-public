<?php
/**
 * Stripe Helper functions for SureForms Payments.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Payments\Stripe;

use SRFM_Pro\Admin\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe Helper functions for SureForms Payments.
 *
 * @since x.x.x
 */
class Stripe_Helper {
	/**
	 * Static cache for webhook verification results during the same request.
	 *
	 * @since x.x.x
	 * @var array<string, bool>
	 */
	private static $webhook_verification_cache = [];

	/**
	 * Check if Stripe is connected.
	 *
	 * @since x.x.x
	 * @return bool True if Stripe is connected, false otherwise.
	 */
	public static function is_stripe_connected() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return is_array( $payment_settings ) && isset( $payment_settings['stripe_connected'] ) && is_bool( $payment_settings['stripe_connected'] ) ? $payment_settings['stripe_connected'] : false;
	}

	/**
	 * Get the current Stripe mode (test or live).
	 *
	 * @since x.x.x
	 * @return string The current payment mode ('test' or 'live').
	 */
	public static function get_stripe_mode() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return is_array( $payment_settings ) && isset( $payment_settings['payment_mode'] ) && is_string( $payment_settings['payment_mode'] ) ? $payment_settings['payment_mode'] : 'test';
	}

	/**
	 * Check if webhook is configured.
	 *
	 * Checks if webhooks are properly configured based on the current payment mode.
	 * Can optionally verify the webhook connection with Stripe API.
	 *
	 * @param string|null $mode   The payment mode ('test' or 'live'). If null, uses current mode.
	 * @param bool        $verify Whether to verify with Stripe API. Default false (checks local settings only).
	 * @since x.x.x
	 * @return bool True if webhook is configured, false otherwise.
	 */
	public static function is_webhook_configured( $mode = null, $verify = false ) {
		// Get current payment mode.
		$payment_mode = is_string( $mode ) && in_array( $mode, [ 'test', 'live' ], true ) ? $mode : self::get_stripe_mode();

		// Get webhook settings.
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		if ( ! is_array( $payment_settings ) ) {
			return false;
		}

		// Check webhook secret exists based on mode.
		$webhook_secret_key = 'webhook_' . $payment_mode . '_secret';
		$has_secret         = ! empty( $payment_settings[ $webhook_secret_key ] );

		// If no secret found, webhook is not configured.
		if ( ! $has_secret ) {
			return false;
		}

		// If verification is not requested, return true (secret exists).
		if ( ! $verify ) {
			return true;
		}

		// Verify with Stripe API (returns boolean).
		return self::verify_webhook_connection( $payment_mode );
	}

	/**
	 * Get Stripe secret key for the specified mode.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @since x.x.x
	 * @return string The secret key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_secret_key( $mode = null ) {
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		return is_array( $payment_settings ) && isset( $payment_settings[ 'stripe_' . $mode . '_secret_key' ] ) && is_string( $payment_settings[ 'stripe_' . $mode . '_secret_key' ] ) ? $payment_settings[ 'stripe_' . $mode . '_secret_key' ] : '';
	}

	/**
	 * Get Stripe publishable key for the specified mode.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @since x.x.x
	 * @return string The publishable key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_publishable_key( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		$payment_settings = get_option( 'srfm_payments_settings', [] );

		return is_array( $payment_settings ) && isset( $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] ) && is_string( $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] ) ? $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] : '';
	}

	/**
	 * Get the default currency from payment settings.
	 *
	 * @since x.x.x
	 * @return string The currency code (e.g., 'USD').
	 */
	public static function get_currency() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return is_array( $payment_settings ) && isset( $payment_settings['currency'] ) && is_string( $payment_settings['currency'] ) ? $payment_settings['currency'] : 'USD';
	}

	/**
	 * Get the Stripe settings page URL.
	 *
	 * This returns the URL to the SureForms Stripe settings page in the admin.
	 * As of now, the URL is:
	 * http://localhost:10008/wp-admin/admin.php?page=sureforms_form_settings&tab=payments-settings
	 * The site URL is dynamic and will adapt to the current WordPress installation.
	 *
	 * @since x.x.x
	 * @return string The URL to the Stripe settings page.
	 */
	public static function get_stripe_settings_url() {
		return admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings' );
	}

	/**
	 * Make a request to the Stripe API.
	 *
	 * @param string       $endpoint    The API endpoint to call.
	 * @param string       $method      The HTTP method (GET, POST, PUT, PATCH, DELETE). Default 'POST'.
	 * @param array<mixed> $data        The data to send with the request. Default empty array.
	 * @param string       $resource_id The resource ID to append to the endpoint. Default empty string.
	 * @param array<mixed> $extra_args        Additional arguments to pass to the request. Default empty array.
	 * @since x.x.x
	 * @return array<mixed> Response array with 'success' boolean and either 'data' or 'error' key.
	 */
	public static function stripe_api_request( string $endpoint, string $method = 'POST', array $data = [], string $resource_id = '', $extra_args = [] ): array {
		if ( ! self::is_stripe_connected() ) {
			return [
				'success' => false,
				'error'   => [
					'code'         => 'stripe_not_connected',
					'message'      => __( 'Stripe is not connected.', 'sureforms' ),
					'type'         => 'auth',
					'raw_response' => null,
				],
			];
		}

		$payment_mode = (string) self::get_stripe_mode();

		if ( ! empty( $extra_args ) && is_array( $extra_args ) ) {
			$payment_mode = isset( $extra_args['mode'] ) && is_string( $extra_args['mode'] ) && in_array( $extra_args['mode'], [ 'test', 'live' ], true ) ? $extra_args['mode'] : $payment_mode;
		}

		$secret_key = (string) self::get_stripe_secret_key( $payment_mode );

		if ( empty( $secret_key ) ) {
			return [
				'success' => false,
				'error'   => [
					'code'         => 'missing_secret_key',
					'message'      => sprintf(
						/* translators: %s: payment mode (test/live) */
						__( 'Stripe %s secret key is missing.', 'sureforms' ),
						$payment_mode
					),
					'type'         => 'auth',
					'raw_response' => null,
				],
			];
		}

		$url = 'https://api.stripe.com/v1/' . $endpoint;
		if ( ! empty( $resource_id ) ) {
			$url .= '/' . $resource_id;
		}

		$headers = [
			'Authorization' => 'Bearer ' . $secret_key,
			'Content-Type'  => 'application/x-www-form-urlencoded',
		];

		$args = [
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		];

		if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
			$args['body'] = http_build_query( self::flatten_stripe_data( $data ) );
		} elseif ( ! empty( $data ) && 'GET' === $method ) {
			$url .= '?' . http_build_query( self::flatten_stripe_data( $data ) );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return [
				'success' => false,
				'error'   => [
					'code'         => $response->get_error_code(),
					'message'      => sprintf(
						/* translators: %s: network error message */
						__( 'Network error: %s', 'sureforms' ),
						$error_message
					),
					'type'         => 'network',
					'raw_response' => $response,
				],
			];
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		// Try to decode the response body.
		$decoded_body = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return [
				'success' => false,
				'error'   => [
					'code'         => 'invalid_response',
					'message'      => __( 'Invalid response format from Stripe API.', 'sureforms' ),
					'type'         => 'invalid_response',
					'raw_response' => $body,
				],
			];
		}

		if ( $code >= 400 ) {
			$stripe_error  = is_array( $decoded_body ) && isset( $decoded_body['error'] ) && is_array( $decoded_body['error'] ) ? $decoded_body['error'] : [];
			$error_code    = isset( $stripe_error['code'] ) ? (string) $stripe_error['code'] : 'unknown_error';
			$error_message = isset( $stripe_error['message'] ) ? (string) $stripe_error['message'] : 'Unknown Stripe API error';
			$error_type    = isset( $stripe_error['type'] ) ? (string) $stripe_error['type'] : 'api_error';

			return [
				'success' => false,
				'error'   => [
					'code'              => $error_code,
					'message'           => $error_message,
					'type'              => 'stripe_api',
					'stripe_error_type' => $error_type,
					'http_status'       => $code,
					'raw_response'      => $decoded_body,
				],
			];
		}

		// Success case - return the decoded response with success indicator.
		return [
			'success' => true,
			'data'    => $decoded_body,
		];
	}

	/**
	 * Get the middleware base URL.
	 *
	 * Returns the production middleware URL that handles secure Stripe API communication.
	 * The middleware acts as a secure proxy between the plugin and Stripe's API.
	 *
	 * For local development and debugging:
	 * - Developers can set up the payments middleware app locally (e.g., http://sureforms-payments-middleware.test/)
	 * - Modify this return value temporarily or use a filter hook to override the URL
	 * - This allows debugging, testing, and modifying payment flows in a local environment
	 *
	 * @since x.x.x
	 * @return string The middleware base URL.
	 */
	public static function middle_ware_base_url() {
		return 'https://payments.sureforms.com/';
	}

	/**
	 * Get comprehensive currency data for all supported currencies.
	 *
	 * This is the single source of truth for all currency-related data.
	 * Contains currency name, symbol, and decimal places.
	 *
	 * @since x.x.x
	 * @return array<string, array<string, mixed>> Array of currency data keyed by currency code.
	 */
	public static function get_all_currencies_data() {
		return [
			'USD' => [
				'name'           => __( 'US Dollar', 'sureforms' ),
				'symbol'         => '$',
				'decimal_places' => 2,
			],
			'EUR' => [
				'name'           => __( 'Euro', 'sureforms' ),
				'symbol'         => '€',
				'decimal_places' => 2,
			],
			'GBP' => [
				'name'           => __( 'British Pound', 'sureforms' ),
				'symbol'         => '£',
				'decimal_places' => 2,
			],
			'JPY' => [
				'name'           => __( 'Japanese Yen', 'sureforms' ),
				'symbol'         => '¥',
				'decimal_places' => 0,
			],
			'AUD' => [
				'name'           => __( 'Australian Dollar', 'sureforms' ),
				'symbol'         => 'A$',
				'decimal_places' => 2,
			],
			'CAD' => [
				'name'           => __( 'Canadian Dollar', 'sureforms' ),
				'symbol'         => 'C$',
				'decimal_places' => 2,
			],
			'CHF' => [
				'name'           => __( 'Swiss Franc', 'sureforms' ),
				'symbol'         => 'CHF',
				'decimal_places' => 2,
			],
			'CNY' => [
				'name'           => __( 'Chinese Yuan', 'sureforms' ),
				'symbol'         => '¥',
				'decimal_places' => 2,
			],
			'SEK' => [
				'name'           => __( 'Swedish Krona', 'sureforms' ),
				'symbol'         => 'kr',
				'decimal_places' => 2,
			],
			'NZD' => [
				'name'           => __( 'New Zealand Dollar', 'sureforms' ),
				'symbol'         => 'NZ$',
				'decimal_places' => 2,
			],
			'MXN' => [
				'name'           => __( 'Mexican Peso', 'sureforms' ),
				'symbol'         => 'MX$',
				'decimal_places' => 2,
			],
			'SGD' => [
				'name'           => __( 'Singapore Dollar', 'sureforms' ),
				'symbol'         => 'S$',
				'decimal_places' => 2,
			],
			'HKD' => [
				'name'           => __( 'Hong Kong Dollar', 'sureforms' ),
				'symbol'         => 'HK$',
				'decimal_places' => 2,
			],
			'NOK' => [
				'name'           => __( 'Norwegian Krone', 'sureforms' ),
				'symbol'         => 'kr',
				'decimal_places' => 2,
			],
			'KRW' => [
				'name'           => __( 'South Korean Won', 'sureforms' ),
				'symbol'         => '₩',
				'decimal_places' => 0,
			],
			'TRY' => [
				'name'           => __( 'Turkish Lira', 'sureforms' ),
				'symbol'         => '₺',
				'decimal_places' => 2,
			],
			'RUB' => [
				'name'           => __( 'Russian Ruble', 'sureforms' ),
				'symbol'         => '₽',
				'decimal_places' => 2,
			],
			'INR' => [
				'name'           => __( 'Indian Rupee', 'sureforms' ),
				'symbol'         => '₹',
				'decimal_places' => 2,
			],
			'BRL' => [
				'name'           => __( 'Brazilian Real', 'sureforms' ),
				'symbol'         => 'R$',
				'decimal_places' => 2,
			],
			'ZAR' => [
				'name'           => __( 'South African Rand', 'sureforms' ),
				'symbol'         => 'R',
				'decimal_places' => 2,
			],
			'AED' => [
				'name'           => __( 'UAE Dirham', 'sureforms' ),
				'symbol'         => 'د.إ',
				'decimal_places' => 2,
			],
			'PHP' => [
				'name'           => __( 'Philippine Peso', 'sureforms' ),
				'symbol'         => '₱',
				'decimal_places' => 2,
			],
			'IDR' => [
				'name'           => __( 'Indonesian Rupiah', 'sureforms' ),
				'symbol'         => 'Rp',
				'decimal_places' => 2,
			],
			'MYR' => [
				'name'           => __( 'Malaysian Ringgit', 'sureforms' ),
				'symbol'         => 'RM',
				'decimal_places' => 2,
			],
			'THB' => [
				'name'           => __( 'Thai Baht', 'sureforms' ),
				'symbol'         => '฿',
				'decimal_places' => 2,
			],
			'BIF' => [
				'name'           => __( 'Burundian Franc', 'sureforms' ),
				'symbol'         => 'FBu',
				'decimal_places' => 0,
			],
			'CLP' => [
				'name'           => __( 'Chilean Peso', 'sureforms' ),
				'symbol'         => '$',
				'decimal_places' => 0,
			],
			'DJF' => [
				'name'           => __( 'Djiboutian Franc', 'sureforms' ),
				'symbol'         => 'Fdj',
				'decimal_places' => 0,
			],
			'GNF' => [
				'name'           => __( 'Guinean Franc', 'sureforms' ),
				'symbol'         => 'FG',
				'decimal_places' => 0,
			],
			'KMF' => [
				'name'           => __( 'Comorian Franc', 'sureforms' ),
				'symbol'         => 'CF',
				'decimal_places' => 0,
			],
			'MGA' => [
				'name'           => __( 'Malagasy Ariary', 'sureforms' ),
				'symbol'         => 'Ar',
				'decimal_places' => 0,
			],
			'PYG' => [
				'name'           => __( 'Paraguayan Guaraní', 'sureforms' ),
				'symbol'         => '₲',
				'decimal_places' => 0,
			],
			'RWF' => [
				'name'           => __( 'Rwandan Franc', 'sureforms' ),
				'symbol'         => 'FRw',
				'decimal_places' => 0,
			],
			'UGX' => [
				'name'           => __( 'Ugandan Shilling', 'sureforms' ),
				'symbol'         => 'USh',
				'decimal_places' => 0,
			],
			'VND' => [
				'name'           => __( 'Vietnamese Đồng', 'sureforms' ),
				'symbol'         => '₫',
				'decimal_places' => 0,
			],
			'VUV' => [
				'name'           => __( 'Vanuatu Vatu', 'sureforms' ),
				'symbol'         => 'VT',
				'decimal_places' => 0,
			],
			'XAF' => [
				'name'           => __( 'Central African CFA Franc', 'sureforms' ),
				'symbol'         => 'FCFA',
				'decimal_places' => 0,
			],
			'XOF' => [
				'name'           => __( 'West African CFA Franc', 'sureforms' ),
				'symbol'         => 'CFA',
				'decimal_places' => 0,
			],
			'XPF' => [
				'name'           => __( 'CFP Franc', 'sureforms' ),
				'symbol'         => '₣',
				'decimal_places' => 0,
			],
		];
	}

	/**
	 * Get currency names for all supported currencies.
	 *
	 * @since x.x.x
	 * @return array<string, mixed> Array of currency names keyed by currency code.
	 */
	public static function get_currency_names() {
		$currencies = self::get_all_currencies_data();
		$names      = [];

		foreach ( $currencies as $code => $data ) {
			$names[ $code ] = $data['name'];
		}

		return $names;
	}

	/**
	 * Get currency symbol.
	 *
	 * @param string $currency Currency code.
	 * @return string
	 * @since x.x.x
	 */
	public static function get_currency_symbol( $currency ) {
		// If $currency is not a valid string or is empty, return empty string.
		if ( empty( $currency ) || ! is_string( $currency ) ) {
			return '';
		}

		$currency      = strtoupper( $currency );
		$currencies    = self::get_all_currencies_data();
		$currency_data = $currencies[ $currency ] ?? null;

		$symbol = ! empty( $currency_data ) ? $currency_data['symbol'] : '';
		return is_string( $symbol ) ? $symbol : '';
	}

	/**
	 * Get list of zero-decimal currencies.
	 *
	 * Zero-decimal currencies don't use decimal points in Stripe's API.
	 * For these currencies, amounts are passed as-is without multiplying/dividing by 100.
	 *
	 * @since x.x.x
	 * @return array<string> Array of zero-decimal currency codes.
	 */
	public static function get_zero_decimal_currencies() {
		$currencies         = self::get_all_currencies_data();
		$zero_decimal_codes = [];

		foreach ( $currencies as $code => $data ) {
			if ( 0 === $data['decimal_places'] ) {
				$zero_decimal_codes[] = $code;
			}
		}

		return $zero_decimal_codes;
	}

	/**
	 * Check if currency is zero-decimal.
	 *
	 * @param string $currency Currency code.
	 * @since x.x.x
	 * @return bool True if zero-decimal currency.
	 */
	public static function is_zero_decimal_currency( $currency ) {
		if ( empty( $currency ) || ! is_string( $currency ) ) {
			return false;
		}

		$currency      = strtoupper( $currency );
		$currencies    = self::get_all_currencies_data();
		$currency_data = $currencies[ $currency ] ?? null;

		return $currency_data && 0 === $currency_data['decimal_places'];
	}

	/**
	 * Convert amount to Stripe's smallest currency unit.
	 *
	 * For two-decimal currencies (USD, EUR, etc.): multiplies by 100
	 * For zero-decimal currencies (JPY, KRW, etc.): returns as-is
	 *
	 * @param float|string|int $amount   Amount in major currency unit (can contain commas).
	 * @param string           $currency Currency code.
	 * @since x.x.x
	 * @return int Amount in smallest currency unit (cents for 2-decimal, whole for 0-decimal).
	 */
	public static function amount_to_stripe_format( $amount, $currency ) {
		$amount = self::clean_amount( $amount );
		return self::is_zero_decimal_currency( $currency )
			? (int) round( $amount )
			: (int) round( $amount * 100 );
	}

	/**
	 * Convert amount from Stripe's smallest currency unit to major unit.
	 *
	 * For two-decimal currencies (USD, EUR, etc.): divides by 100
	 * For zero-decimal currencies (JPY, KRW, etc.): returns as-is
	 *
	 * @param int|string|float $amount   Amount in smallest currency unit (can contain commas).
	 * @param string           $currency Currency code.
	 * @since x.x.x
	 * @return float Amount in major currency unit.
	 */
	public static function amount_from_stripe_format( $amount, $currency ) {
		$amount = self::clean_amount( $amount );
		return self::is_zero_decimal_currency( $currency )
			? $amount
			: $amount / 100;
	}

	/**
	 * Generate unique payment ID using base36 encoding and random string, always 14 characters.
	 *
	 * Format: {base36_encoded_id}{random_chars}
	 * Example: 3F7B9A1E4C7D2A (exactly 14 chars)
	 *
	 * @param int $auto_increment_id The database auto-increment ID.
	 * @since x.x.x
	 * @return string Generated unique payment ID (always 14 characters).
	 */
	public static function generate_unique_payment_id( int $auto_increment_id ): string {
		// Convert the auto-increment ID to base36.
		$encoded_id = base_convert( (string) $auto_increment_id, 10, 36 );
		// Calculate the length of random part needed to make the ID exactly 14 chars.
		$random_length = 14 - strlen( $encoded_id );
		if ( $random_length < 1 ) {
			$random_length = 1; // Always leave at least 1 random char for collision prevention.
		}
		// Generate random part using only valid base36 (alphanumeric) chars.
		// bin2hex gives 2 chars per byte, so we need ceil($random_length / 2) bytes.
		$bytes_needed = max( 1, (int) ceil( $random_length / 2 ) ); // Ensure at least 1 byte.
		$random_bytes = bin2hex( random_bytes( $bytes_needed ) );
		$random_part  = substr( $random_bytes, 0, $random_length );
		$unique_id    = strtoupper( $encoded_id . $random_part );
		// Ensure exactly 14 chars.
		return substr( $unique_id, 0, 14 );
	}

	/**
	 * Get the SureForms Pro License Key.
	 *
	 * @since x.x.x
	 * @return string The SureForms Pro License Key.
	 */
	public static function get_license_key() {
		$licensing = self::get_licensing_instance();
		if ( ! $licensing ||
		! method_exists( $licensing, 'licensing_setup' ) || ! method_exists( $licensing->licensing_setup(), 'settings' ) ) {
			return '';
		}
		// Check if the SureForms Pro license is active.
		$is_license_active = self::is_pro_license_active();
		// If the license is active, get the license key.
		$license_setup = $licensing->licensing_setup();
		return ! empty( $is_license_active ) && is_object( $license_setup ) && method_exists( $license_setup, 'settings' ) ? $license_setup->settings()->license_key : '';
	}

	/**
	 * Check if the SureForms Pro license is active.
	 *
	 * @since x.x.x
	 * @return bool|string True if the SureForms Pro license is active, false otherwise.
	 */
	public static function is_pro_license_active() {
		$licensing = self::get_licensing_instance();
		if ( ! $licensing || ! method_exists( $licensing, 'is_license_active' )
		) {
			return '';
		}
		// Check if the SureForms Pro license is active.
		return $licensing->is_license_active();
	}

	/**
	 * Get all payment-related translatable strings for frontend use.
	 *
	 * This is the single source of truth for all payment UI strings.
	 * Each string has a unique key (slug) for easy reference in JavaScript.
	 *
	 * @since x.x.x
	 * @return array<string, string> Array of translatable strings keyed by slug.
	 */
	public static function get_payment_strings() {
		return [
			'unknown_error'                     => __( 'An unknown error occurred. Please try again or contact the site administrator.', 'sureforms' ),
			// Payment validation messages.
			'payment_unavailable'               => __( 'Payment is currently unavailable. Please contact the site administrator.', 'sureforms' ),
			'payment_amount_not_configured'     => __( 'Payment is currently unavailable. Please contact the site administrator to configure the payment amount.', 'sureforms' ),
			'invalid_variable_amount'           => __( 'Please enter a valid payment amount greater than zero.', 'sureforms' ),
			'amount_below_minimum'              => __( 'Payment amount must be at least {symbol}{amount}.', 'sureforms' ),

			// Field mapping validation.
			'payment_name_not_mapped'           => __( 'Payment is currently unavailable. Please contact the site administrator to configure the customer name field.', 'sureforms' ),
			'payment_email_not_mapped'          => __( 'Payment is currently unavailable. Please contact the site administrator to configure the customer email field.', 'sureforms' ),
			'payment_name_required'             => __( 'Please enter your name.', 'sureforms' ),
			'payment_email_required'            => __( 'Please enter your email.', 'sureforms' ),

			// Payment processing messages.
			'payment_failed'                    => __( 'Payment failed', 'sureforms' ),
			'payment_successful'                => __( 'Payment successful', 'sureforms' ),
			'payment_could_not_be_completed'    => __( 'Payment could not be completed. Please try again or contact the site administrator.', 'sureforms' ),

			// Stripe decline codes - Card declined errors.
			'generic_decline'                   => __( 'Your card was declined. Please try a different payment method or contact your bank.', 'sureforms' ),
			'card_declined'                     => __( 'Your card was declined. Please try a different payment method or contact your bank.', 'sureforms' ),
			'insufficient_funds'                => __( 'Your card has insufficient funds. Please use a different payment method.', 'sureforms' ),
			'lost_card'                         => __( 'Your card was declined because it has been reported as lost. Please contact your bank.', 'sureforms' ),
			'stolen_card'                       => __( 'Your card was declined because it has been reported as stolen. Please contact your bank.', 'sureforms' ),
			'expired_card'                      => __( 'Your card has expired. Please use a different payment method.', 'sureforms' ),
			'pickup_card'                       => __( 'Your card was declined. Please contact your bank for more information.', 'sureforms' ),
			'restricted_card'                   => __( 'Your card was declined due to restrictions. Please contact your bank.', 'sureforms' ),
			'security_violation'                => __( 'Your card was declined due to a security violation. Please contact your bank.', 'sureforms' ),
			'service_not_allowed'               => __( 'Your card does not support this type of purchase. Please use a different payment method.', 'sureforms' ),
			'stop_payment_order'                => __( 'A stop payment order has been placed on this card. Please contact your bank.', 'sureforms' ),
			'testmode_decline'                  => __( 'A test card was used in a live environment. Please use a real card.', 'sureforms' ),
			'withdrawal_count_limit_exceeded'   => __( 'Your card has exceeded its withdrawal limit. Please contact your bank.', 'sureforms' ),
			'incorrect_cvc'                     => __( 'Your card\'s security code is incorrect. Please check and try again.', 'sureforms' ),
			'incorrect_number'                  => __( 'Your card number is incorrect. Please check and try again.', 'sureforms' ),
			'invalid_cvc'                       => __( 'Your card\'s security code is invalid. Please check and try again.', 'sureforms' ),
			'invalid_expiry_month'              => __( 'Your card\'s expiration month is invalid. Please check and try again.', 'sureforms' ),
			'invalid_expiry_year'               => __( 'Your card\'s expiration year is invalid. Please check and try again.', 'sureforms' ),
			'invalid_number'                    => __( 'Your card number is invalid. Please check and try again.', 'sureforms' ),
			'processing_error'                  => __( 'An error occurred while processing your card. Please try again.', 'sureforms' ),
			'reenter_transaction'               => __( 'The transaction could not be processed. Please try again.', 'sureforms' ),
			'card_not_supported'                => __( 'Your card is not supported for this transaction. Please use a different payment method.', 'sureforms' ),
			'currency_not_supported'            => __( 'Your card does not support the currency used for this transaction. Please use a different payment method.', 'sureforms' ),
			'duplicate_transaction'             => __( 'A transaction with identical details was submitted recently. Please wait a moment and try again.', 'sureforms' ),
			'invalid_account'                   => __( 'The account associated with your card is invalid. Please contact your bank.', 'sureforms' ),
			'invalid_amount'                    => __( 'The payment amount is invalid. Please contact the site administrator.', 'sureforms' ),
			'issuer_not_available'              => __( 'Your card issuer could not be reached. Please try again later.', 'sureforms' ),
			'merchant_blacklist'                => __( 'Your card was declined. Please contact your bank for more information.', 'sureforms' ),
			'new_account_information_available' => __( 'Your card information needs to be updated. Please contact your bank.', 'sureforms' ),
			'no_action_taken'                   => __( 'The card cannot be used for this transaction. Please contact your bank.', 'sureforms' ),
			'not_permitted'                     => __( 'The transaction is not permitted. Please contact your bank.', 'sureforms' ),
			'offline_pin_required'              => __( 'Your card requires offline PIN authentication. Please try again.', 'sureforms' ),
			'online_or_offline_pin_required'    => __( 'Your card requires PIN authentication. Please try again.', 'sureforms' ),
			'pin_try_exceeded'                  => __( 'You have exceeded the maximum number of PIN attempts. Please contact your bank.', 'sureforms' ),
			'revocation_of_all_authorizations'  => __( 'All authorizations for this card have been revoked. Please contact your bank.', 'sureforms' ),
			'revocation_of_authorization'       => __( 'The authorization for this transaction has been revoked. Please try again.', 'sureforms' ),
			'transaction_not_allowed'           => __( 'This transaction is not allowed. Please contact your bank.', 'sureforms' ),
			'try_again_later'                   => __( 'The transaction could not be processed. Please try again later.', 'sureforms' ),

			// Default values and placeholders.
			'sureforms_subscription'            => __( 'SureForms Subscription', 'sureforms' ),
			'sureforms_payment'                 => __( 'SureForms Payment', 'sureforms' ),
			'subscription_plan'                 => __( 'Subscription Plan', 'sureforms' ),
			'sureforms_customer'                => __( 'SureForms Customer', 'sureforms' ),
			'customer_example_email'            => 'customer@example.com', // Not translatable - example email.
			'amount_placeholder'                => __( 'Amount will appear here', 'sureforms' ),
		];
	}

	/**
	 * Get the webhook URL for Stripe.
	 *
	 * Returns the dynamic webhook URL based on the site's REST API endpoint.
	 * Example: http://localhost:10008/wp-json/sureforms/webhook
	 *
	 * @param string $mode The payment mode ('test' or 'live'). Default is 'test'.
	 *
	 * @since x.x.x
	 * @return string The webhook URL.
	 */
	public static function get_webhook_url( $mode = 'test' ) {
		return 'test' === $mode ? rest_url( 'sureforms/webhook_test' ) : rest_url( 'sureforms/webhook_live' );
	}

	/**
	 * Verify webhook connection with Stripe.
	 *
	 * Checks if the webhook endpoint exists and is enabled in Stripe
	 * based on the current payment mode. Uses static cache for same request.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @since x.x.x
	 * @return bool True if webhook is enabled, false otherwise.
	 */
	public static function verify_webhook_connection( $mode = null ) {
		// Get current payment mode.
		$payment_mode = is_string( $mode ) && in_array( $mode, [ 'test', 'live' ], true ) ? $mode : self::get_stripe_mode();

		// Check static cache first to avoid repeated API calls in same request.
		$cache_key = 'webhook_' . $payment_mode;
		if ( isset( self::$webhook_verification_cache[ $cache_key ] ) ) {
			return self::$webhook_verification_cache[ $cache_key ];
		}

		// Get webhook settings.
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		if ( ! is_array( $payment_settings ) ) {
			self::$webhook_verification_cache[ $cache_key ] = false;
			return false;
		}

		// Get webhook ID based on mode.
		$webhook_id_key = 'webhook_' . $payment_mode . '_id';
		$webhook_id     = isset( $payment_settings[ $webhook_id_key ] ) && is_string( $payment_settings[ $webhook_id_key ] ) ? $payment_settings[ $webhook_id_key ] : '';

		if ( empty( $webhook_id ) ) {
			self::$webhook_verification_cache[ $cache_key ] = false;
			return false;
		}

		// Make API request to verify webhook.
		$response = self::stripe_api_request( 'webhook_endpoints', 'GET', [], $webhook_id, [ 'mode' => $payment_mode ] );

		// If API call failed (webhook not found, deleted, or error), clear webhook data.
		if ( ! $response['success'] ) {
			self::clear_webhook_data( $payment_mode, $payment_settings );
			self::$webhook_verification_cache[ $cache_key ] = false;
			return false;
		}

		// Check webhook status and mode match.
		$webhook_data = $response['data'];
		$is_enabled   = isset( $webhook_data['status'] ) && 'enabled' === $webhook_data['status'];

		// Verify the livemode matches the current mode.
		$webhook_livemode  = isset( $webhook_data['livemode'] ) && is_bool( $webhook_data['livemode'] ) ? $webhook_data['livemode'] : false;
		$expected_livemode = 'live' === $payment_mode;
		$mode_matches      = $webhook_livemode === $expected_livemode;

		// Webhook is connected only if enabled and mode matches.
		$is_connected = $is_enabled && $mode_matches;

		// If webhook is not connected, clear the webhook data from settings.
		if ( ! $is_connected ) {
			self::clear_webhook_data( $payment_mode, $payment_settings );
		}

		// Cache result for this request.
		self::$webhook_verification_cache[ $cache_key ] = $is_connected;

		return $is_connected;
	}

	/**
	 * Clear webhook data from settings for a specific mode.
	 *
	 * Removes webhook_secret, webhook_id, and webhook_url for the specified mode.
	 *
	 * @param string               $mode             The payment mode ('test' or 'live').
	 * @param array<string, mixed> $payment_settings The payment settings array.
	 * @since x.x.x
	 * @return void
	 */
	private static function clear_webhook_data( $mode, $payment_settings ) {
		$updated_settings = $payment_settings;

		if ( 'live' === $mode ) {
			$updated_settings['webhook_live_secret'] = '';
			$updated_settings['webhook_live_id']     = '';
			$updated_settings['webhook_live_url']    = '';
		} else {
			$updated_settings['webhook_test_secret'] = '';
			$updated_settings['webhook_test_id']     = '';
			$updated_settings['webhook_test_url']    = '';
		}

		update_option( 'srfm_payments_settings', $updated_settings );
	}

	/**
	 * Clean up amount to float.
	 *
	 * Removes commas, spaces, and ensures a numeric float value.
	 *
	 * @param float|string|int $amount Amount to clean up.
	 * @since x.x.x
	 * @return float Clean float value.
	 */
	private static function clean_amount( $amount ) {
		if ( is_string( $amount ) ) {
			$amount = str_replace( [ ',', ' ' ], '', $amount );
		}
		return is_numeric( $amount ) ? (float) $amount : 0.0;
	}

	/**
	 * Flattens a multidimensional array into a single-level array using Stripe's bracket notation.
	 *
	 * This is useful for preparing data to be sent to the Stripe API, which expects
	 * nested parameters to be formatted as key[subkey]=value.
	 *
	 * @param array<mixed> $data   The multidimensional array to flatten.
	 * @param string       $prefix (Optional) The prefix for nested keys. Default is an empty string.
	 * @since x.x.x
	 * @return array<mixed> The flattened array with bracket notation keys.
	 */
	private static function flatten_stripe_data( array $data, string $prefix = '' ): array {
		$result = [];

		foreach ( $data as $key => $value ) {
			$new_key = $prefix ? $prefix . '[' . $key . ']' : $key;

			if ( is_array( $value ) ) {
				$result = array_merge( $result, self::flatten_stripe_data( $value, $new_key ) );
			} else {
				$result[ $new_key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Get the Licensing Instance.
	 *
	 * @since x.x.x
	 * @return object|null The Licensing Instance.
	 */
	private static function get_licensing_instance() {
		if ( ! class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			return null;
		}
		return Licensing::get_instance();
	}
}
