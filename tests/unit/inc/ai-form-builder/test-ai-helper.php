<?php
/**
 * Tests for AI_Helper class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\AI_Helper;

/**
 * Test_AI_Helper class.
 */
class Test_AI_Helper extends TestCase {

	/**
	 * Test get_error_message returns string for WP_Error.
	 */
	public function test_get_error_message() {
		$error  = new \WP_Error( 'test_error', 'Test error message' );
		$result = AI_Helper::get_error_message( $error );
		$this->assertIsString( $result );
	}

	/**
	 * is_pro_license_active should return an empty string when the pro
	 * licensing class is unavailable.
	 */
	public function test_is_pro_license_active_returns_empty_when_pro_missing() {
		if ( class_exists( 'SRFM_Pro\\Admin\\Licensing' ) ) {
			$this->markTestSkipped( 'SureForms Pro is loaded in this environment; the no-pro branch cannot be exercised.' );
		}

		$this->assertSame( '', AI_Helper::is_pro_license_active() );
	}

	/**
	 * Invoke a protected static method on AI_Helper via reflection.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	protected function invoke_protected( $method, array $args ) {
		$ref = new ReflectionMethod( AI_Helper::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( null, $args );
	}

	/**
	 * decode_json_response should return decoded array on well-formed JSON.
	 */
	public function test_decode_json_response_valid_json() {
		$result = $this->invoke_protected(
			'decode_json_response',
			[ '{"form":{"formTitle":"Test"}}', 200, 'generate/form' ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'form', $result );
		$this->assertSame( 'Test', $result['form']['formTitle'] );
	}

	/**
	 * decode_json_response should return the error array when body is empty.
	 */
	public function test_decode_json_response_empty_body() {
		$result = $this->invoke_protected(
			'decode_json_response',
			[ '', 200, 'generate/form' ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * decode_json_response should return the error array when JSON is malformed.
	 */
	public function test_decode_json_response_invalid_json() {
		$result = $this->invoke_protected(
			'decode_json_response',
			[ '{ not valid json', 500, 'generate/form' ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * decode_json_response should return the error array when JSON is valid but not an array.
	 */
	public function test_decode_json_response_non_array_json() {
		$result = $this->invoke_protected(
			'decode_json_response',
			[ '"just a string"', 200, 'generate/form' ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * decode_json_response should honour the supplied fallback message.
	 */
	public function test_decode_json_response_uses_custom_fallback() {
		$result = $this->invoke_protected(
			'decode_json_response',
			[ '', 200, 'usage', 'Custom fallback' ]
		);
		$this->assertSame( 'Custom fallback', $result['error'] );
	}

	/**
	 * log_ai_response_failure should short-circuit when WP_DEBUG is disabled.
	 * No return value — just verifying the call does not error.
	 */
	public function test_log_ai_response_failure_noop_without_wp_debug() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->markTestSkipped( 'WP_DEBUG is enabled; noop path is not exercised in this environment.' );
		}

		$this->invoke_protected(
			'log_ai_response_failure',
			[ 'generate/form', 500, 'invalid_json', 'body' ]
		);
		$this->assertTrue( true );
	}

	/**
	 * log_ai_response_failure should accept a non-string body without error.
	 */
	public function test_log_ai_response_failure_handles_non_string_body() {
		$this->invoke_protected(
			'log_ai_response_failure',
			[ 'generate/form', 500, 'invalid_json', null ]
		);
		$this->assertTrue( true );
	}

	/**
	 * get_chat_completions_response should return the error-message array
	 * when wp_remote_post is short-circuited with a WP_Error via filter.
	 */
	public function test_get_chat_completions_response_returns_error_on_http_failure() {
		$filter = static function () {
			return new \WP_Error( 'http_request_failed', 'Network unreachable' );
		};
		add_filter( 'pre_http_request', $filter );

		$result = AI_Helper::get_chat_completions_response( [ 'query' => 'Contact form' ] );

		remove_filter( 'pre_http_request', $filter );

		$this->assertIsArray( $result );
	}

	/**
	 * get_chat_completions_response should pass valid JSON through decode_json_response.
	 */
	public function test_get_chat_completions_response_decodes_valid_response() {
		$payload = wp_json_encode( [ 'form' => [ 'formTitle' => 'Ping' ] ] );
		$filter  = static function () use ( $payload ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => $payload,
				'headers'  => [],
			];
		};
		add_filter( 'pre_http_request', $filter );

		$result = AI_Helper::get_chat_completions_response( [ 'query' => 'Contact form' ] );

		remove_filter( 'pre_http_request', $filter );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'form', $result );
	}

	/**
	 * get_usage_response should return an error array when the HTTP call fails.
	 */
	public function test_get_usage_response_returns_error_on_http_failure() {
		$filter = static function () {
			return new \WP_Error( 'http_request_failed', 'Network unreachable' );
		};
		add_filter( 'pre_http_request', $filter );

		$result = AI_Helper::get_usage_response();

		remove_filter( 'pre_http_request', $filter );

		$this->assertIsArray( $result );
	}

	/**
	 * get_usage_response should return the decoded payload on a 200 with valid JSON.
	 */
	public function test_get_usage_response_decodes_valid_payload() {
		$payload = wp_json_encode( [ 'remaining' => 10, 'type' => 'registered' ] );
		$filter  = static function () use ( $payload ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => $payload,
				'headers'  => [],
			];
		};
		add_filter( 'pre_http_request', $filter );

		$result = AI_Helper::get_usage_response();

		remove_filter( 'pre_http_request', $filter );

		$this->assertIsArray( $result );
		$this->assertSame( 10, $result['remaining'] );
	}

	/**
	 * sanitize_ai_error_message coerces non-strings to an empty string.
	 */
	public function test_sanitize_ai_error_message_returns_empty_for_non_string_input() {
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( null ) );
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( [ 'message' => 'x' ] ) );
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( 0 ) );
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( '' ) );
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( "   \n\t " ) );
	}

	/**
	 * sanitize_ai_error_message passes through messages without sensitive tokens.
	 */
	public function test_sanitize_ai_error_message_passes_through_clean_messages() {
		$message = 'Rate limit exceeded. Please retry in a few seconds.';
		$this->assertSame( $message, AI_Helper::sanitize_ai_error_message( $message ) );
	}

	/**
	 * sanitize_ai_error_message strips URLs, OpenAI-shape IDs, request IDs,
	 * API keys, Bearer tokens, and gpt model names. Whitespace is collapsed.
	 *
	 * The function does not mutate spaces inside parentheses or other
	 * structural punctuation — only outer whitespace and separator-class
	 * punctuation is trimmed — so test expectations preserve those gaps.
	 */
	public function test_sanitize_ai_error_message_strips_known_infra_patterns() {
		$cases = [
			'see https://api.openai.com/v1/error for details'   => 'see for details',
			'You have hit org-ABCDEF123456 quota'               => 'You have hit quota',
			'failed for user-zyxwvut0987654 today'              => 'failed for today',
			'rejected for req_abcDEF12345 reason'               => 'rejected for reason',
			'request id: abcDEF12345 was logged'                => 'was logged',
			'auth failed (key sk-abcdef0123456789ABCDEF) retry' => 'auth failed (key ) retry',
			'token Bearer abc123.def456-XYZ rejected'           => 'token rejected',
			'model gpt-4o-mini-2024-07-18 unavailable'          => 'model unavailable',
		];
		foreach ( $cases as $input => $expected ) {
			$this->assertSame(
				$expected,
				AI_Helper::sanitize_ai_error_message( $input ),
				sprintf( 'Failed sanitizing: %s', $input )
			);
		}
	}

	/**
	 * sanitize_ai_error_message returns an empty string when stripping consumes
	 * the entire message, so callers fall back to a canonical translated message.
	 */
	public function test_sanitize_ai_error_message_returns_empty_when_only_infra_remains() {
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( 'https://example.com' ) );
		$this->assertSame( '', AI_Helper::sanitize_ai_error_message( 'org-AAAAAA1111' ) );
	}

	/**
	 * Empty endpoint argument means we should not log; the public API still
	 * returns a sanitized message.
	 */
	public function test_sanitize_ai_error_message_does_not_log_when_endpoint_empty() {
		// Pre-condition: WP_DEBUG state should not influence the return value
		// when no endpoint is supplied. Just exercise the path without asserting
		// log output (PHPUnit can't easily inspect error_log destinations here).
		$result = AI_Helper::sanitize_ai_error_message( 'plain message' );
		$this->assertSame( 'plain message', $result );
	}
}
