<?php
/**
 * Stripe Helper functions for SureForms Payments.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Payments\Stripe;

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
	 * http://localhost:10008/wp-admin/admin.php?page=sureforms_form_settings&tab=general-settings
	 * The site URL is dynamic and will adapt to the current WordPress installation.
	 *
	 * @since x.x.x
	 * @return string The URL to the Stripe settings page.
	 */
	public static function get_stripe_settings_url() {
		return admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' );
	}

	/**
	 * Make a request to the Stripe API.
	 *
	 * @param string       $endpoint    The API endpoint to call.
	 * @param string       $method      The HTTP method (GET, POST, PUT, PATCH, DELETE). Default 'POST'.
	 * @param array<mixed> $data        The data to send with the request. Default empty array.
	 * @param string       $resource_id The resource ID to append to the endpoint. Default empty string.
	 * @since x.x.x
	 * @return array<mixed> Response array with 'success' boolean and either 'data' or 'error' key.
	 */
	public static function stripe_api_request( string $endpoint, string $method = 'POST', array $data = [], string $resource_id = '' ): array {
		if ( ! self::is_stripe_connected() ) {
			// TODO: Handle proper error handling.
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
		$secret_key   = (string) self::get_stripe_secret_key();

		if ( empty( $secret_key ) ) {
			// TODO: Handle proper error handling.
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
			// TODO: Handle proper error handling.
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
	 * Get the middleware base URL.
	 *
	 * @since x.x.x
	 * @return string The middleware base URL.
	 */
	public static function middle_ware_base_url() {
		// phpcs:ignoreFile -- Disable phpstan strict comparison warning about environment strings
		return SRFM_PAYMENTS_ENV === 'prod' ? SRFM_PAYMENTS_PROD : SRFM_PAYMENTS_LOCAL;
	}
}
