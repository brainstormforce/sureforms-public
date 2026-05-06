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

		return self::decode_json_response(
			$response_body,
			wp_remote_retrieve_response_code( $response ),
			'generate/form'
		);
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

		return self::decode_json_response(
			$response_body,
			wp_remote_retrieve_response_code( $response ),
			'usage',
			__( 'The SureForms API server encountered an error.', 'sureforms' )
		);
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
				$message = __( 'Unable to connect to SureForms API. Please check your connection.', 'sureforms' );
				break;
			case 'license_verification_failed':
				$title   = __( 'License Verification Failed', 'sureforms' );
				$message = __( 'Unable to verify license. Please check your license key.', 'sureforms' );
				break;
			case 'user_verification_failed':
				$title   = __( 'User Verification Failed', 'sureforms' );
				$message = __( 'An error occurred while trying to verify your email. Please check your email you have used to log in or sign up on billing.sureforms.com.', 'sureforms' );
				break;
			case 'referer_mismatch':
				$title   = __( 'Referer Mismatch', 'sureforms' );
				$message = __( 'Unable to verify referer. Please check your referer.', 'sureforms' );
				break;
			case 'invalid_token':
				$title   = __( 'Invalid Website URL', 'sureforms' );
				$message = __( 'AI Form Builder does not work on localhost. Please try on a live website.', 'sureforms' );
				break;
			case 'domain_verification_failed':
				$title   = __( 'Domain Verification Failed', 'sureforms' );
				$message = __( 'Domain Verification Failed on current site. Please try again on another website.', 'sureforms' );
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
	 * Sanitize an upstream error message before returning it to the client.
	 *
	 * The OpenAI / SureForms middleware sometimes echoes infrastructure details
	 * (URLs, request IDs, model names, organization/user IDs, raw API keys)
	 * inside error messages. The endpoints surfacing these messages are
	 * capability-gated, but contributors-and-up shouldn't see infra leaks.
	 *
	 * Pass-through behaviour is preserved when the message has no sensitive
	 * tokens — only matched patterns are stripped. Returns an empty string
	 * if nothing useful remains, so callers can fall back to a canonical
	 * translated message.
	 *
	 * @param mixed      $raw         Raw upstream message; non-strings are coerced.
	 * @param string     $endpoint    Optional endpoint label; when set, the raw input
	 *                                is passed through {@see self::log_ai_response_failure()}
	 *                                so the unredacted form is preserved server-side
	 *                                (subject to the usual WP_DEBUG / WP_DEBUG_LOG gates).
	 * @param int|string $status_code Optional HTTP status, forwarded to the logger.
	 * @since 2.8.2
	 * @return string Sanitized message safe to return to the client.
	 */
	public static function sanitize_ai_error_message( $raw, $endpoint = '', $status_code = '' ) {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		if ( '' !== $endpoint ) {
			self::log_ai_response_failure( $endpoint, $status_code, 'upstream_error', $raw );
		}

		$patterns = [
			// URLs (http / https / protocol-relative).
			'#https?://\S+#i',
			'#(?<=\s)//\S+#i',
			// OpenAI-shape opaque IDs: org-/user-/key-/sess-/req-/file-/chatcmpl-/asst-/run-/thread-.
			// Both '-' and '_' separators are observed in the wild (e.g. req-… and req_…).
			'/\b(?:org|user|key|sess|req|file|chatcmpl|asst|run|thread)[-_][A-Za-z0-9_]{6,}/i',
			// Generic "request id: …" / "request-id …" trailers — require a separator and a substantive id.
			'/\brequest[_\s-]?id[:\s]+[A-Za-z0-9_-]{4,}/i',
			// Bearer / API-key shapes.
			'/\bsk-[A-Za-z0-9_-]{12,}/i',
			'/\bBearer\s+[A-Za-z0-9._-]+/i',
			// Model identifiers that would otherwise leak the underlying provider.
			'/\bgpt-[A-Za-z0-9.-]+/i',
		];
		$cleaned  = (string) preg_replace( $patterns, '', $raw );
		// Collapse the gaps left by removed tokens.
		$cleaned = (string) preg_replace( '/\s+/', ' ', $cleaned );
		return trim( $cleaned, " \t\n\r\0\x0B.,;:" );
	}

	/**
	 * Decode the response body from the SureForms AI Middleware, returning
	 * a structured error payload when the body is empty or invalid JSON.
	 *
	 * @param string      $response_body  Raw HTTP response body.
	 * @param int|string  $status_code    HTTP status code, used for debug logging.
	 * @param string      $endpoint       Short endpoint label, used for debug logging.
	 * @param string|null $error_fallback Translated fallback message on decode failure.
	 * @since 2.8.2
	 * @return array<mixed>
	 */
	protected static function decode_json_response( $response_body, $status_code, $endpoint, $error_fallback = null ) {
		if ( null === $error_fallback ) {
			$error_fallback = __( 'The SureForms AI Middleware encountered an error.', 'sureforms' );
		}

		if ( '' === $response_body || null === $response_body ) {
			self::log_ai_response_failure( $endpoint, $status_code, 'empty_body', '' );
			return [ 'error' => $error_fallback ];
		}

		$decoded = json_decode( $response_body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			self::log_ai_response_failure( $endpoint, $status_code, 'invalid_json', $response_body );
			return [ 'error' => $error_fallback ];
		}

		if ( ! is_array( $decoded ) ) {
			self::log_ai_response_failure( $endpoint, $status_code, 'non_array_json', $response_body );
			return [ 'error' => $error_fallback ];
		}

		return $decoded;
	}

	/**
	 * Log an AI middleware response failure when WP_DEBUG and WP_DEBUG_LOG are both enabled.
	 *
	 * Newlines are collapsed to prevent log injection, and known sensitive JSON keys
	 * (email, token, license_key, prompt, query) are redacted before logging.
	 *
	 * @param string     $endpoint    Short endpoint label.
	 * @param int|string $status_code HTTP status code.
	 * @param string     $reason      Failure reason identifier.
	 * @param string     $body        Raw response body (will be truncated).
	 * @since 2.8.2
	 * @return void
	 */
	protected static function log_ai_response_failure( $endpoint, $status_code, $reason, $body ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Only write to the debug log file when WP_DEBUG_LOG is also enabled.
		// Without this guard, error_log() falls back to the host's PHP error log.
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		$snippet = is_string( $body ) ? substr( $body, 0, 500 ) : '';
		// Collapse all whitespace (including CR/LF) to a single space to prevent log injection.
		$snippet = (string) preg_replace( '/\s+/', ' ', $snippet );
		// Redact known sensitive keys if echoed in the body.
		$snippet = (string) preg_replace(
			'/("(?:email|token|license_key|prompt|query)"\s*:\s*")[^"]*"/i',
			'$1[redacted]"',
			$snippet
		);

		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only logging behind WP_DEBUG && WP_DEBUG_LOG.
			sprintf(
				'[SureForms AI] %s %s status=%s body=%s',
				$endpoint,
				$reason,
				(string) $status_code,
				$snippet
			)
		);
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
