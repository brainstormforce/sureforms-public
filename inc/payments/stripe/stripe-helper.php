<?php
/**
 * Stripe Helper functions for SureForms Payments.
 *
 * @package sureforms
 * @since 2.0.0
 */

namespace SRFM\Inc\Payments\Stripe;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM_Pro\Admin\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe Helper functions for SureForms Payments.
 *
 * @since 2.0.0
 */
class Stripe_Helper {
	/**
	 * Static cache for webhook verification results during the same request.
	 *
	 * @since 2.0.0
	 * @var array<string, bool>
	 */
	private static $webhook_verification_cache = [];

	/**
	 * Check if Stripe is connected.
	 *
	 * @since 2.0.0
	 * @return bool True if Stripe is connected, false otherwise.
	 */
	public static function is_stripe_connected() {
		$payment_settings = self::get_all_stripe_settings();
		return is_array( $payment_settings ) && isset( $payment_settings['stripe_connected'] ) && is_bool( $payment_settings['stripe_connected'] ) ? $payment_settings['stripe_connected'] : false;
	}

	/**
	 * Get the current Stripe mode (test or live).
	 *
	 * @since 2.0.0
	 * @return string The current payment mode ('test' or 'live').
	 */
	public static function get_stripe_mode() {
		return Payment_Helper::get_payment_mode();
	}

	/**
	 * Check if webhook is configured.
	 *
	 * Checks if webhooks are properly configured based on the current payment mode.
	 * Can optionally verify the webhook connection with Stripe API.
	 *
	 * @param string|null $mode   The payment mode ('test' or 'live'). If null, uses current mode.
	 * @param bool        $verify Whether to verify with Stripe API. Default false (checks local settings only).
	 * @since 2.0.0
	 * @return bool True if webhook is configured, false otherwise.
	 */
	public static function is_webhook_configured( $mode = null, $verify = false ) {
		// Get current payment mode.
		$payment_mode = is_string( $mode ) && in_array( $mode, [ 'test', 'live' ], true ) ? $mode : self::get_stripe_mode();

		// Get webhook settings.
		$payment_settings = self::get_all_stripe_settings();

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
	 * @since 2.0.0
	 * @return string The secret key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_secret_key( $mode = null ) {
		$payment_settings = self::get_all_stripe_settings();

		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		return is_array( $payment_settings ) && isset( $payment_settings[ 'stripe_' . $mode . '_secret_key' ] ) && is_string( $payment_settings[ 'stripe_' . $mode . '_secret_key' ] ) ? $payment_settings[ 'stripe_' . $mode . '_secret_key' ] : '';
	}

	/**
	 * Get Stripe publishable key for the specified mode.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @since 2.0.0
	 * @return string The publishable key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_publishable_key( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		$payment_settings = self::get_all_stripe_settings();

		return is_array( $payment_settings ) && isset( $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] ) && is_string( $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] ) ? $payment_settings[ 'stripe_' . $mode . '_publishable_key' ] : '';
	}

	/**
	 * Get the default currency from payment settings.
	 *
	 * @since 2.0.0
	 * @return string The currency code (e.g., 'USD').
	 */
	public static function get_currency() {
		return Payment_Helper::get_currency();
	}

	/**
	 * Get the Stripe settings page URL.
	 *
	 * This returns the URL to the SureForms Stripe settings page in the admin.
	 * As of now, the URL is:
	 * http://localhost:10008/wp-admin/admin.php?page=sureforms_form_settings&tab=payments-settings
	 * The site URL is dynamic and will adapt to the current WordPress installation.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 * @return array<mixed> Response array with 'success' boolean and either 'data' or 'error' key.
	 */
	public static function stripe_api_request( $endpoint, $method = 'POST', $data = [], $resource_id = '', $extra_args = [] ) {
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
	 * Retrieve the middleware base URL for Stripe API communication.
	 *
	 * By default, returns the production middleware URL that securely proxies requests
	 * between the plugin and Stripe's API.
	 *
	 * Developers working in local or staging environments can override the SRFM_MIDDLEWARE_BASE_URL
	 * constant (for example, set it to "http://sureforms-payments-middleware.test") to point
	 * to a locally running payments middleware app (e.g., http://sureforms-payments-middleware.test/payments/stripe/).
	 *
	 * You can also modify the return value or use a filter hook to customize the URL as needed
	 * for testing, debugging, or customizing payment flows during development.
	 *
	 * @since 2.0.0
	 * @return string The middleware base URL.
	 */
	public static function middle_ware_base_url() {
		return SRFM_MIDDLEWARE_BASE_URL . 'payments/stripe/';
	}

	/**
	 * Get currency symbol.
	 *
	 * @param string $currency Currency code.
	 * @return string
	 * @since 2.0.0
	 */
	public static function get_currency_symbol( $currency ) {
		return Payment_Helper::get_currency_symbol( $currency );
	}

	/**
	 * Check if currency is zero-decimal.
	 *
	 * @param string $currency Currency code.
	 * @since 2.0.0
	 * @return bool True if zero-decimal currency.
	 */
	public static function is_zero_decimal_currency( $currency ) {
		return Payment_Helper::is_zero_decimal_currency( $currency );
	}

	/**
	 * Convert amount to Stripe's smallest currency unit.
	 *
	 * For two-decimal currencies (USD, EUR, etc.): multiplies by 100
	 * For zero-decimal currencies (JPY, KRW, etc.): returns as-is
	 *
	 * @param float|string|int $amount   Amount in major currency unit (can contain commas).
	 * @param string           $currency Currency code.
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 * @return string Generated unique payment ID (always 14 characters).
	 */
	public static function generate_unique_payment_id( $auto_increment_id ) {
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * Get the webhook URL for Stripe.
	 *
	 * Returns the dynamic webhook URL based on the site's REST API endpoint.
	 * Example: http://localhost:10008/wp-json/sureforms/webhook
	 *
	 * @param string $mode The payment mode ('test' or 'live'). Default is 'test'.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
		$payment_settings = self::get_all_stripe_settings();

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
	 * Check if any transaction is present in the payments table.
	 *
	 * @since 2.0.0
	 * @return bool True if at least one transaction exists, false otherwise.
	 */
	public static function is_transaction_present() {
		global $wpdb;

		// Get payments table name.
		$payments_table = Payments::get_instance()->get_tablename();

		if ( empty( $payments_table ) || ! is_string( $payments_table ) ) {
			return false;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query to check transaction existence, table name is validated and cannot be parameterized with prepare().
		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$payments_table} LIMIT 1"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return ! empty( $count ) && absint( $count ) > 0;
	}

	/**
	 * Get all Stripe settings from srfm_options.
	 *
	 * Retrieves the complete Stripe settings array from the nested structure:
	 * srfm_options -> payment_settings -> stripe
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> The Stripe settings array, or default settings if not found.
	 */
	public static function get_all_stripe_settings() {
		$stripe_settings = Payment_Helper::get_gateway_settings( 'stripe' );

		// Return default settings if empty.
		return ! empty( $stripe_settings ) ? $stripe_settings : self::get_default_stripe_settings();
	}

	/**
	 * Update all Stripe settings in srfm_options.
	 *
	 * Stores the complete Stripe settings array in the nested structure:
	 * srfm_options -> payment_settings -> stripe
	 *
	 * @param array<string, mixed> $settings The Stripe settings array to save.
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function update_all_stripe_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}

		return Payment_Helper::update_gateway_settings( 'stripe', $settings );
	}

	/**
	 * Get a specific Stripe setting value by key.
	 *
	 * @param string $key     The setting key to retrieve.
	 * @param mixed  $default The default value to return if key doesn't exist.
	 * @since 2.0.0
	 * @return mixed The setting value or default if not found.
	 */
	public static function get_stripe_setting( $key, $default = '' ) {
		if ( ! is_string( $key ) || empty( $key ) ) {
			return $default;
		}

		$settings = self::get_all_stripe_settings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update a specific Stripe setting value by key.
	 *
	 * @param string $key   The setting key to update.
	 * @param mixed  $value The value to set.
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function update_stripe_setting( $key, $value ) {
		if ( ! is_string( $key ) || empty( $key ) ) {
			return false;
		}

		$settings         = self::get_all_stripe_settings();
		$settings[ $key ] = $value;

		return self::update_all_stripe_settings( $settings );
	}

	/**
	 * Get default Stripe settings structure.
	 *
	 * Note: currency and payment_mode are now stored in global settings.
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> Default Stripe settings array.
	 */
	public static function get_default_stripe_settings() {
		return [
			'stripe_connected'            => false,
			'stripe_account_id'           => '',
			'stripe_account_email'        => '',
			'stripe_live_publishable_key' => '',
			'stripe_live_secret_key'      => '',
			'stripe_test_publishable_key' => '',
			'stripe_test_secret_key'      => '',
			'payment_mode'                => 'test',
			'webhook_test_secret'         => '',
			'webhook_test_url'            => '',
			'webhook_test_id'             => '',
			'webhook_live_secret'         => '',
			'webhook_live_url'            => '',
			'webhook_live_id'             => '',
			'account_name'                => '',
		];
	}

	/**
	 * Get Stripe Connect URL
	 *
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public static function get_stripe_connect_url() {
		// Stripe client ID from checkout-plugins-stripe-woo.
		$client_id = 'ca_KOXfLe7jv1m4L0iC4KNEMc5fT8AXWWuL';

		// Use the same redirect URI pattern as checkout-plugins-stripe-woo.
		$redirect_url        = admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings' );
		$nonce               = wp_create_nonce( 'stripe-connect' );
		$redirect_with_nonce = add_query_arg( 'srfm_stripe_connect_nonce', $nonce, $redirect_url );

		// Store our own callback data.
		set_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id(), $nonce, HOUR_IN_SECONDS );

		// Create state parameter exactly like checkout-plugins-stripe-woo.
		$state_param = wp_json_encode(
			[
				'redirect' => $redirect_with_nonce,
			]
		);
		$state       = '';
		if ( is_string( $state_param ) ) {
			$state = base64_encode( $state_param ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		$connect_url = add_query_arg(
			[
				'response_type'  => 'code',
				'client_id'      => $client_id,
				'stripe_landing' => 'login',
				'always_prompt'  => 'true',
				'scope'          => 'read_write',
				'state'          => $state,
			],
			'https://connect.stripe.com/oauth/authorize'
		);

		return rest_ensure_response( [ 'url' => $connect_url ] );
	}

	/**
	 * Clear webhook data from settings for a specific mode.
	 *
	 * Removes webhook_secret, webhook_id, and webhook_url for the specified mode.
	 *
	 * @param string               $mode             The payment mode ('test' or 'live').
	 * @param array<string, mixed> $payment_settings The payment settings array.
	 * @since 2.0.0
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

		self::update_all_stripe_settings( $updated_settings );
	}

	/**
	 * Clean up amount to float.
	 *
	 * Removes commas, spaces, and ensures a numeric float value.
	 *
	 * @param float|string|int $amount Amount to clean up.
	 * @since 2.0.0
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
	 * @since 2.0.0
	 * @return array<mixed> The flattened array with bracket notation keys.
	 */
	private static function flatten_stripe_data( $data, $prefix = '' ) {
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
	 * @since 2.0.0
	 * @return object|null The Licensing Instance.
	 */
	private static function get_licensing_instance() {
		if ( ! class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			return null;
		}
		return Licensing::get_instance();
	}
}
