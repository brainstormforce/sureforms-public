<?php
/**
 * Class Test_Form_Submit
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Form_Submit;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Form_Submit extends TestCase {

    protected $form_submit;

	protected function setUp(): void {
		$this->form_submit = new Form_Submit();
	}

	/**
	 * Test recaptcha_error_message method.
     * This function tests various scenarios for the recaptcha_error_message method.
     * It includes cases where:
     * 1. No error code provided.
     * 2. Error code provided and captcha type is g-recaptcha.
     * 3. Error code provided and captcha type is hcaptcha.
     * 4. Error code provided and captcha type is cf-turnstile.
     * 5. Error code provided and captcha type is unknown.
     *
	 */
    public function test_recaptcha_error_message() {

        // Test case 1: No error code provided
        $api_response = [];
        $expected = [
            'detail_message' => 'Captcha validation failed. No error code provided.',
            'message'        => 'Captcha validation failed.',
        ];
        $result = $this->form_submit->recaptcha_error_message('g-recaptcha', $api_response);
        $this->assertEquals($expected, $result, 'Failed asserting when no error code provided.');

        // Test case 2: g-recaptcha
        $api_response = [ 'error-codes' => [ 'missing-input-secret' ] ];
        $expected = [
            'log_message' => 'Google reCAPTCHA: The secret parameter is missing. <br> Error Code: missing-input-secret',
            'message'     => 'Google reCAPTCHA verification failed. Please contact your site administrator.',
        ];
        $result = $this->form_submit->recaptcha_error_message('g-recaptcha', $api_response);
        $this->assertEquals($expected, $result, 'Failed asserting for g-recaptcha error.');

        // Test case 3: hcaptcha
        $api_response = [ 'error-codes' => [ 'invalid-input-secret' ] ];
        $expected = [
            'log_message' => 'hCaptcha: Your secret key is invalid or malformed. <br> Error Code: invalid-input-secret',
            'message'     => 'hCaptcha verification failed. Please contact your site administrator.',
        ];
        $result = $this->form_submit->recaptcha_error_message('hcaptcha', $api_response);
        $this->assertEquals($expected, $result, 'Failed asserting for hcaptcha error.');

        // Test case 4: cf-turnstile
        $api_response = [ 'error-codes' => [ 'timeout-or-duplicate' ] ];
        $expected = [
            'log_message' => 'Cloudflare Turnstile: The response parameter (token) has already been validated before. This means that the token was issued five minutes ago and is no longer valid, or it was already redeemed. <br> Error Code: timeout-or-duplicate',
            'message'     => 'Cloudflare Turnstile verification failed. Please contact your site administrator.',
        ];
        $result = $this->form_submit->recaptcha_error_message('cf-turnstile', $api_response);
        $this->assertEquals($expected, $result, 'Failed asserting for cf-turnstile error.');

        // Test case 5: Unknown captcha type
        $api_response = [ 'error-codes' => [ 'missing-input-response' ] ];
        $expected = [
            'log_message' => 'Unknown Captcha: Invalid captcha type. <br> Error Code: missing-input-response',
            'message'     => 'Unknown Captcha verification failed. Please contact your site administrator.',
        ];
        $result = $this->form_submit->recaptcha_error_message('unknown-captcha', $api_response);
        $this->assertEquals($expected, $result, 'Failed asserting for unknown captcha type.');
    }

    /**
     * Test process_form_fields method.
     */
    public function test_process_form_fields() {

        // Test case 1: Valid sureforms fields with -lbl-
        $form_data = [
            'text-lbl-field-name' => 'John Doe',
            'email-lbl-field-email' => 'john@example.com'
        ];
        $expected = [
            'text-lbl-field-name' => 'John Doe',
            'email-lbl-field-email' => 'john@example.com'
        ];
        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);
        $this->assertEquals($expected, $result);

        // Test case 2: Form data without -lbl- fields (should be filtered out)
        $form_data = [
            'form-id' => '123',
            'nonce' => 'test_nonce',
            'regular_field' => 'value'
        ];
        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);
        $this->assertEquals([], $result);

        // Test case 3: Array values (like file uploads)
        $form_data = [
            'upload-lbl-field-files' => ['file1.jpg', 'file2.pdf']
        ];
        $expected = [
            'upload-lbl-field-files' => ['file1.jpg', 'file2.pdf']
        ];
        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);
        $this->assertEquals($expected, $result);

        // Test case 4: HTML special characters sanitization
        $form_data = [
            'text-lbl-field-content' => '<script>alert("xss")</script>'
        ];
        $expected = [
            'text-lbl-field-content' => '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
        ];
        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);
        $this->assertEquals($expected, $result);
    }

	/**
	 * Test validate_turnstile_token with empty secret key.
	 */
	public function test_validate_turnstile_token_empty_secret_key() {
		$result = Form_Submit::validate_turnstile_token( '', 'some-response', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'secret key is invalid', $result['error'] );
	}

	/**
	 * Test validate_turnstile_token with non-string secret key.
	 */
	public function test_validate_turnstile_token_non_string_secret() {
		$result = Form_Submit::validate_turnstile_token( 123, 'some-response', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test validate_turnstile_token with empty response.
	 */
	public function test_validate_turnstile_token_empty_response() {
		$result = Form_Submit::validate_turnstile_token( 'valid-secret', '', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'response is missing', $result['error'] );
	}

	/**
	 * Test validate_hcaptcha_token with empty secret key.
	 */
	public function test_validate_hcaptcha_token_empty_secret_key() {
		$result = Form_Submit::validate_hcaptcha_token( '', 'some-response', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'secret key is invalid', $result['error'] );
	}

	/**
	 * Test validate_hcaptcha_token with non-string secret key.
	 */
	public function test_validate_hcaptcha_token_non_string_secret() {
		$result = Form_Submit::validate_hcaptcha_token( 456, 'some-response', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test validate_hcaptcha_token with empty response.
	 */
	public function test_validate_hcaptcha_token_empty_response() {
		$result = Form_Submit::validate_hcaptcha_token( 'valid-secret', '', '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'response is missing', $result['error'] );
	}

	/**
	 * Test validate_hcaptcha_token with false response.
	 */
	public function test_validate_hcaptcha_token_false_response() {
		$result = Form_Submit::validate_hcaptcha_token( 'valid-secret', false, '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test validate_turnstile_token with false response.
	 */
	public function test_validate_turnstile_token_false_response() {
		$result = Form_Submit::validate_turnstile_token( 'valid-secret', false, '127.0.0.1' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test recaptcha_error_message with multiple error codes returns first.
	 */
	public function test_recaptcha_error_message_multiple_error_codes() {
		$api_response = [ 'error-codes' => [ 'bad-request', 'timeout-or-duplicate' ] ];
		$result = $this->form_submit->recaptcha_error_message( 'g-recaptcha', $api_response );
		$this->assertArrayHasKey( 'log_message', $result );
		$this->assertStringContainsString( 'bad-request', $result['log_message'] );
	}

	/**
	 * Test recaptcha_error_message with empty error-codes array.
	 */
	public function test_recaptcha_error_message_empty_error_codes_array() {
		$api_response = [ 'error-codes' => [] ];
		$result = $this->form_submit->recaptcha_error_message( 'g-recaptcha', $api_response );
		$this->assertArrayHasKey( 'detail_message', $result );
		$this->assertStringContainsString( 'No error code provided', $result['detail_message'] );
	}

	/**
	 * Test recaptcha_error_message with non-array error-codes.
	 */
	public function test_recaptcha_error_message_non_array_error_codes() {
		$api_response = [ 'error-codes' => 'string-value' ];
		$result = $this->form_submit->recaptcha_error_message( 'g-recaptcha', $api_response );
		$this->assertArrayHasKey( 'detail_message', $result );
		$this->assertStringContainsString( 'No error code provided', $result['detail_message'] );
	}

	/**
	 * Test process_form_fields with empty array.
	 */
	public function test_process_form_fields_empty_array() {
		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ [] ] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test process_form_fields with mixed valid and invalid keys.
	 */
	public function test_process_form_fields_mixed_keys() {
		$form_data = [
			'text-lbl-field-name' => 'John',
			'form-id'             => '123',
			'email-lbl-field-email' => 'test@test.com',
			'nonce'               => 'abc',
		];
		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test prepare_submission_data with basic fields.
	 */
	public function test_prepare_submission_data_basic() {
		$submission_data = [
			'srfm-text-abc123-lbl-first-name' => 'John Doe',
		];
		$result = $this->form_submit->prepare_submission_data( $submission_data );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertEquals( 'John Doe', $result['name'] );
	}

	/**
	 * Test prepare_submission_data with upload field arrays.
	 */
	public function test_prepare_submission_data_upload_field() {
		$submission_data = [
			'srfm-upload-abc123-lbl-upload-resume' => [ 'https://example.com/file1.pdf', 'https://example.com/file2.pdf' ],
		];
		$result = $this->form_submit->prepare_submission_data( $submission_data );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'resume', $result );
		$this->assertStringContainsString( ',', $result['resume'] );
	}

	/**
	 * Test prepare_submission_data with empty submission data.
	 */
	public function test_prepare_submission_data_empty() {
		$result = $this->form_submit->prepare_submission_data( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test process_form_fields with form-id handling.
	 * Tests the new logic for passing form-id through the filter.
	 */
	public function test_process_form_fields_with_form_id() {
		// Test case 1: Form data with form-id should pass it to filter and then remove it
		$form_data = [
			'form-id'             => '19',
			'text-lbl-field-name' => 'Test User',
		];

		// Mock the filter to verify form_id is passed via context array.
		$received_context = null;
		$callback         = function( $data, $context ) use ( &$received_context ) {
			$received_context = $context;
			return $data;
		};
		add_filter( 'srfm_before_prepare_submission_data', $callback, 10, 2 );

		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );

		// Verify form_id was passed via the context array.
		$this->assertIsArray( $received_context, 'Context array should be passed to the filter' );
		$this->assertArrayHasKey( 'form_id', $received_context, 'Context should contain form_id' );
		$this->assertEquals( 19, $received_context['form_id'] );

		// Verify form-id is not present in submission data (it should never be injected).
		$this->assertArrayNotHasKey( 'form-id', $result );

		// Verify other fields are present.
		$this->assertArrayHasKey( 'text-lbl-field-name', $result );
		$this->assertEquals( 'Test User', $result['text-lbl-field-name'] );

		// Remove only this specific callback, not all filters on the hook.
		remove_filter( 'srfm_before_prepare_submission_data', $callback, 10 );
	}

	/**
	 * Test that a filter callback can modify submission data without affecting the context array.
	 */
	public function test_process_form_fields_filter_modifies_submission_data() {
		$form_data = [
			'form-id'             => '19',
			'text-lbl-field-name' => 'Test User',
		];

		// Simulate a filter that adds a custom key to submission data.
		$callback = function( $data ) {
			$data['custom-key'] = 'custom-value';
			return $data;
		};
		add_filter( 'srfm_before_prepare_submission_data', $callback, 10, 1 );

		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );

		// Custom key added by the filter should be present.
		$this->assertArrayHasKey( 'custom-key', $result );
		$this->assertEquals( 'custom-value', $result['custom-key'] );

		// Other fields should be unaffected.
		$this->assertArrayHasKey( 'text-lbl-field-name', $result );
		$this->assertEquals( 'Test User', $result['text-lbl-field-name'] );

		remove_filter( 'srfm_before_prepare_submission_data', $callback, 10 );
	}

	/**
	 * Test process_form_fields with non-numeric form-id.
	 */
	public function test_process_form_fields_with_non_numeric_form_id() {
		$form_data = [
			'form-id'             => 'invalid-id',
			'text-lbl-field-name' => 'Test User',
		];

		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );

		// Should not include form-id in result
		$this->assertArrayNotHasKey( 'form-id', $result );
	}

	/**
	 * Test process_form_fields with missing form-id.
	 */
	public function test_process_form_fields_without_form_id() {
		$form_data = [
			'text-lbl-field-name' => 'Test User',
		];

		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );

		// Should process normally without errors
		$this->assertArrayHasKey( 'text-lbl-field-name', $result );
		$this->assertEquals( 'Test User', $result['text-lbl-field-name'] );
	}

	/**
	 * Test process_form_fields with zero form-id.
	 */
	public function test_process_form_fields_with_zero_form_id() {
		$form_data = [
			'form-id'             => '0',
			'text-lbl-field-name' => 'Test User',
		];

		$result = $this->call_private_method( $this->form_submit, 'process_form_fields', [ $form_data ] );

		// form-id = 0 is valid but should be removed from result
		$this->assertArrayNotHasKey( 'form-id', $result );
	}

	/**
	 * Helper method to call private methods for testing.
	 *
	 * @param object $object      The object containing the private method.
	 * @param string $method_name The name of the private method.
	 * @param array  $parameters  The parameters to pass to the method.
	 * @return mixed
	 */
	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
