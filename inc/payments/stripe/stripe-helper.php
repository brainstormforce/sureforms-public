<?php

namespace SRFM\Inc\Payments\Stripe;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Stripe_Helper {
	public static function stripe_api_request( $endpoint, $method = 'POST', $data = [], $resource_id = '' ) {
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		if ( empty( $payment_settings['stripe_connected'] ) ) {
			error_log( 'SureForms: Stripe is not connected' );
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

		$payment_mode = $payment_settings['payment_mode'] ?? 'test';
		$secret_key   = 'live' === $payment_mode
			? $payment_settings['stripe_live_secret_key'] ?? ''
			: $payment_settings['stripe_test_secret_key'] ?? '';

		if ( empty( $secret_key ) ) {
			error_log( 'SureForms: Stripe secret key not found' );
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
			error_log( 'SureForms Stripe API Network Error: ' . $error_message );
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

		// Try to decode the response body
		$decoded_body = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'SureForms Stripe API Invalid JSON Response: ' . $body );
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

			error_log( 'SureForms Stripe API Error (' . $code . '): ' . $error_message );

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

		// Success case - return the decoded response with success indicator
		return [
			'success' => true,
			'data'    => $decoded_body,
		];
	}

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
