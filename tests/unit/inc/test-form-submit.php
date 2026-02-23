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
     * Test process_form_fields with form-id handling.
     * Tests the new logic for passing form-id through the filter.
     */
    public function test_process_form_fields_with_form_id() {
        // Test case 1: Form data with form-id should pass it to filter and then remove it
        $form_data = [
            'form-id' => '19',
            'text-lbl-field-name' => 'Test User'
        ];

        // Mock the filter to verify form-id is passed
        $filter_called_with_form_id = false;
        add_filter('srfm_before_prepare_submission_data', function($data) use (&$filter_called_with_form_id) {
            if (isset($data['form-id']) && $data['form-id'] === 19) {
                $filter_called_with_form_id = true;
            }
            return $data;
        }, 10, 1);

        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);

        // Verify form-id was passed to filter
        $this->assertTrue($filter_called_with_form_id, 'form-id should be passed to the filter');

        // Verify form-id is removed from final result
        $this->assertArrayNotHasKey('form-id', $result);

        // Verify other fields are present
        $this->assertArrayHasKey('text-lbl-field-name', $result);
        $this->assertEquals('Test User', $result['text-lbl-field-name']);

        // Remove the filter
        remove_all_filters('srfm_before_prepare_submission_data');
    }

    /**
     * Test process_form_fields with non-numeric form-id.
     */
    public function test_process_form_fields_with_non_numeric_form_id() {
        $form_data = [
            'form-id' => 'invalid-id',
            'text-lbl-field-name' => 'Test User'
        ];

        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);

        // Should not include form-id in result
        $this->assertArrayNotHasKey('form-id', $result);
    }

    /**
     * Test process_form_fields with missing form-id.
     */
    public function test_process_form_fields_without_form_id() {
        $form_data = [
            'text-lbl-field-name' => 'Test User'
        ];

        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);

        // Should process normally without errors
        $this->assertArrayHasKey('text-lbl-field-name', $result);
        $this->assertEquals('Test User', $result['text-lbl-field-name']);
    }

    /**
     * Test process_form_fields with zero form-id.
     */
    public function test_process_form_fields_with_zero_form_id() {
        $form_data = [
            'form-id' => '0',
            'text-lbl-field-name' => 'Test User'
        ];

        $result = $this->call_private_method($this->form_submit, 'process_form_fields', [$form_data]);

        // form-id = 0 is valid but should be removed from result
        $this->assertArrayNotHasKey('form-id', $result);
    }

    /**
     * Helper method to call private methods for testing.
     */
    private function call_private_method($object, $method_name, $parameters = []) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
