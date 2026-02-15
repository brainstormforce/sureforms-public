<?php
/**
 * SureForms AI Form Builder - Helper.
 *
 * This file contains the helper functions of SureForms AI Form Builder.
 * Helpers are functions that are used throughout the library.
 *
 * @package sureforms
 * @since 0.0.8
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;
use SRFM_Pro\Admin\Licensing;

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
	 * Get the SureForms AI Response from the SureForms Credit Server.
	 *
	 * @param array<mixed> $body The data to be passed as the request body, if any.
	 * @param array<mixed> $extra_args Extra arguments to be passed to the request, if any.
	 * @since 0.0.8
	 * @return array<array<array<array<mixed>>>|string>|mixed The SureForms AI Response.
	 */
	public static function get_chat_completions_response( $body = [], $extra_args = [] ) {
		// Set the API URL.
		$api_url = SRFM_AI_MIDDLEWARE . 'generate/form';

		$api_args = [
			'headers' => [
				'X-Token'      => base64_encode( self::get_user_token() ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This is not for obfuscation.
				'Content-Type' => 'application/json',
				'Referer'      => site_url(),
			],
			'timeout' => 90, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 90 seconds is required sometime for open ai responses
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
			return self::get_error_message( $response );
		}

		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );

		// If the response body is not a JSON, then abandon ship.
		if ( empty( $response_body ) || ! json_decode( $response_body ) ) {
			return [
				'error' => __( 'The SureForms AI Middleware encountered an error.', 'sureforms' ),
			];
		}

		// Return the response body.
		return json_decode( $response_body, true );
	}

	/**
	 * Get the SureForms Token from the SureForms AI Settings.
	 *
	 * @since 0.0.8
	 * @return array<mixed>|void The SureForms Token.
	 */
	public static function get_current_usage_details() {
		$current_usage_details = [];

		// Get the response from the endpoint.
		$response = self::get_usage_response();

		// check if response is an array if not then send error.
		if ( ! is_array( $response ) ) {
			wp_send_json_error( [ 'message' => __( 'Unable to get usage response.', 'sureforms' ) ] );
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
	 * @since 0.0.8
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
					'X-Token'      => base64_encode( self::get_user_token() ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This is not for obfuscation.
					'Content-Type' => 'application/json',
					'Referer'      => site_url(),
				],
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for the SureForms API response
			]
		);

		// If the response was an error, or not a 200 status code, then abandon ship.
		if ( is_wp_error( $response ) || empty( $response['response'] ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return self::get_error_message( $response );
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

	/**
	 * Get the Error Message.
	 *
	 * @param array<string,mixed>|array<int|string,mixed>|\WP_Error $response The response from the SureForms API server.
	 * @since 0.0.10
	 * @return array<string, mixed> The Error Message.
	 */
	public static function get_error_message( $response ) {
		$errors = $response->errors ?? [];

		if ( empty( $errors )
		&& is_array( $response ) && isset( $response['body'] ) && is_string( $response['body'] )
		) {
			$errors    = json_decode( $response['body'], true );
			$error_key = is_array( $errors ) && isset( $errors['code'] ) ? $errors['code'] : '';
		} else {
			$error_key = array_key_first( $errors );
			if ( empty( $errors[ $error_key ] ) ) {
				$message = __( 'An unknown error occurred.', 'sureforms' );
			}
		}

		// Error Codes with Messages.
		switch ( $error_key ) {
			case 'http_request_failed':
				$title   = __( 'HTTP Request Failed', 'sureforms' );
				$message = __( 'An error occurred while trying to connect to the SureForms API server. Please check your connection', 'sureforms' );
				break;
			case 'license_verification_failed':
				$title   = __( 'License Verification Failed', 'sureforms' );
				$message = __( 'An error occurred while trying to verify your license. Please check your license key', 'sureforms' );
				break;
			case 'user_verification_failed':
				$title   = __( 'User Verification Failed', 'sureforms' );
				$message = __( 'An error occurred while trying to verify your email. Please check your email you have used to log in/ sign up on billing.sureforms.com.', 'sureforms' );
				break;
			case 'referer_mismatch':
				$title   = __( 'Referer Mismatch', 'sureforms' );
				$message = __( 'An error occurred while trying to verify your referer. Please check your referer.', 'sureforms' );
				break;
			case 'invalid_token':
				$title   = __( 'Invalid Website URL', 'sureforms' );
				$message = __( 'AI Form Builder does not work on localhost. Please try on a live website.', 'sureforms' );
				break;
			case 'domain_verification_failed':
				$title   = __( 'Domain Verification Failed', 'sureforms' );
				$message = __( 'Domain Verification Failed on current site. Please try again on any another website.', 'sureforms' );
				break;
			default:
				$title   = __( 'Unknown Error', 'sureforms' );
				$message = __( 'An unknown error occurred.', 'sureforms' );
		}

		return [
			'code'    => $error_key,
			'title'   => $title,
			'message' => $message,
		];
	}

	/**
	 * Check if the SureForms Pro license is active.
	 *
	 * @since 0.0.10
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
	 * Get the User Token.
	 *
	 * @since 0.0.8
	 * @return string The User Token.
	 */
	private static function get_user_token() {
		// if the license is active then use the license key as the token.
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$license_key = self::get_license_key();
			if ( ! empty( $license_key ) ) {
				return $license_key;
			}
		}

		$user_email = get_option( 'srfm_ai_auth_user_email' );

		// if the license is not active then use the user email/site url as the token.
		return ! empty( $user_email ) && is_array( $user_email ) ? $user_email['user_email'] : site_url();
	}

	/**
	 * Get the Licensing Instance.
	 *
	 * @since 0.0.10
	 * @return object|null The Licensing Instance.
	 */
	private static function get_licensing_instance() {
		if ( ! class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			return null;
		}
		return Licensing::get_instance();
	}

	/**
	 * Get the SureForms Pro License Key.
	 *
	 * @since 0.0.10
	 * @return string The SureForms Pro License Key.
	 */
	private static function get_license_key() {
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

}
