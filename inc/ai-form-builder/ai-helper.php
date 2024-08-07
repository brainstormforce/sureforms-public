<?php
/**
 * SureForms AI Form Builder - Helper.
 *
 * This file contains the helper functions of SureForms AI Form Builder.
 * Helpers are functions that are used throughout the library.
 *
 * @package sureforms
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Helper Class.
 */
class AI_Helper {
	use Get_Instance;

	/**
	 * Get the Zip AI Response from the Zip Credit Server.
	 *
	 * @param array<mixed> $body The data to be passed as the request body, if any.
	 * @param array<mixed> $extra_args Extra arguments to be passed to the request, if any.
	 * @since x.x.x
	 * @return array<array<array<array<mixed>>>|string>|mixed The Zip AI Response.
	 */
	public static function get_chat_completions_response( $body = [], $extra_args = [] ) {
		// Set the API URL.
		$api_url = SRFM_AI_MIDDLEWARE . 'chat/completions';

		$api_args = [
			'headers' => [
				'X-Token'      => 'aHR0cHM6Ly93d3cudGhlc2F1cnVzLmNvbQ==', // For now, this is a dummy token. Once we go live, we will replace this with the actual token. That will be user's SureCart license or base64 encoded site URL.
				'Content-Type' => 'application/json',
			],
			'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for open ai responses
		];

		// If the data array was passed, add it to the args.
		if ( ! empty( $body ) && is_array( $body ) ) {
			$api_args['body'] = wp_json_encode( $body );
		}

		// If there are any extra arguments, then we can overwrite the required arguments.
		if ( ! empty( $extra_args ) && is_array( $extra_args ) ) {
			$api_args = array_merge( $api_args, $extra_args );
		}

		// Get the response from the endpoint.
		$response = wp_remote_post( $api_url, $api_args );

		// If the response was an error, or not a 200 status code, then abandon ship.
		if ( is_wp_error( $response ) || empty( $response['response'] ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return [
				'error' => __( 'The Zip AI Middleware is not responding.', 'sureforms' ),
			];
		}

		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );

		// If the response body is not a JSON, then abandon ship.
		if ( empty( $response_body ) || ! json_decode( $response_body ) ) {
			return [
				'error' => __( 'The Zip AI Middleware encountered an error.', 'sureforms' ),
			];
		}

		// Return the response body.
		return json_decode( $response_body, true );
	}

	/**
	 * Get the SureForms Token from the Zip AI Settings.
	 *
	 * @since x.x.x
	 * @return array<mixed>|void The SureForms Token.
	 */
	public static function get_current_usage_details() {
		$current_usage_details = [];

		// Get the response from the endpoint.
		$response = self::get_usage_response();

		// check if response is an array if not then send error.
		if ( ! is_array( $response ) ) {
			return;
		}

		// If the response is not an error, then use it - else create an error response array.
		if ( empty( $response['error'] ) && is_array( $response ) ) {
			$current_usage_details = $response;
			if ( empty( $current_usage_details['status'] ) ) {
				$current_usage_details['status'] = 'ok';
			}
		} else {
			$current_usage_details['status'] = 'error';
			if ( ! empty( $response['error'] ) ) {
				$current_usage_details['error'] = $response['error'];
			}
		}

		return $current_usage_details;

	}

	/**
	 * Get a response from the SureForms API server.
	 *
	 * @since x.x.x
	 * @return array<mixed>|mixed The SureForms API Response.
	 */
	public static function get_usage_response() {
		// Set the API URL.
		$api_url = SRFM_AI_MIDDLEWARE . 'usage';

		// Get the response from the endpoint.
		$response = wp_remote_post(
			$api_url,
			[
				'headers' => [
					'X-Token'      => 'aHR0cHM6Ly93d3cuZnJlZS5vcmc=', // For now, this is a dummy token. Once we go live, we will replace this with the actual token. That will be user's SureCart license or base64 encoded site URL.
					'Content-Type' => 'application/json',
				],
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for the SureForms API response
			]
		);

		// If the response was an error, or not a 200 status code, then abandon ship.
		if ( is_wp_error( $response ) || empty( $response['response'] ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return [
				'error' => __( 'The SureForms API server is not responding.', 'sureforms' ),
			];
		}

		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );

		// If the response body is not a JSON, then abandon ship.
		if ( empty( $response_body ) || ! json_decode( $response_body ) ) {
			return [
				'error' => __( 'The SureForms API server encountered an error.', 'sureforms' ),
			];
		}

		// Return the response body.
		return json_decode( $response_body, true );
	}

}
