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

class Stripe_Helper {
	/**
	 * Check if Stripe is connected.
	 *
	 * @return bool True if Stripe is connected, false otherwise.
	 */
	public static function is_stripe_connected() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return ! empty( $payment_settings['stripe_connected'] );
	}

	/**
	 * Get the current Stripe mode (test or live).
	 *
	 * @return string The current payment mode ('test' or 'live').
	 */
	public static function get_stripe_mode() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return $payment_settings['payment_mode'] ?? 'test';
	}

	/**
	 * Get Stripe secret key for the specified mode.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @return string The secret key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_secret_key( $mode = null ) {
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		if ( 'live' === $mode ) {
			return $payment_settings['stripe_live_secret_key'] ?? '';
		}

		return $payment_settings['stripe_test_secret_key'] ?? '';
	}

	/**
	 * Get Stripe publishable key for the specified mode.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses current mode.
	 * @return string The publishable key for the specified mode, or empty string if not found.
	 */
	public static function get_stripe_publishable_key( $mode = null ) {
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		if ( null === $mode ) {
			$mode = self::get_stripe_mode();
		}

		if ( 'live' === $mode ) {
			return $payment_settings['stripe_live_publishable_key'] ?? '';
		}

		return $payment_settings['stripe_test_publishable_key'] ?? '';
	}

	/**
	 * Get the default currency from payment settings.
	 *
	 * @return string The currency code (e.g., 'USD').
	 */
	public static function get_currency() {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		return $payment_settings['currency'] ?? 'USD';
	}

	/**
	 * Make a request to the Stripe API.
	 *
	 * @param string $endpoint    The API endpoint to call.
	 * @param string $method      The HTTP method (GET, POST, PUT, PATCH, DELETE). Default 'POST'.
	 * @param array  $data        The data to send with the request. Default empty array.
	 * @param string $resource_id The resource ID to append to the endpoint. Default empty string.
	 * @return array Response array with 'success' boolean and either 'data' or 'error' key.
	 */
	public static function stripe_api_request( $endpoint, $method = 'POST', $data = [], $resource_id = '' ) {
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

		$payment_mode = self::get_stripe_mode();
		$secret_key   = self::get_stripe_secret_key();

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

		if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
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
			$stripe_error  = $decoded_body['error'] ?? [];
			$error_code    = $stripe_error['code'] ?? 'unknown_error';
			$error_message = $stripe_error['message'] ?? 'Unknown Stripe API error';
			$error_type    = $stripe_error['type'] ?? 'api_error';

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
	 * @param array  $data   The multidimensional array to flatten.
	 * @param string $prefix (Optional) The prefix for nested keys. Default is an empty string.
	 *
	 * @return array The flattened array with bracket notation keys.
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
}
