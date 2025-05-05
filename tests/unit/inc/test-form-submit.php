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
        $result = $this->form_submit->recaptcha_error_message('g-recaptcha', $api_response);

        $this->assertStringContainsString('Captcha validation failed. No error code provided.', $result, 'Failed asserting when no error code provided.');

        // Test case 2: Error code provided and captcha type is g-recaptcha
        $api_response = [
            'error-codes' => ['missing-input-secret'],
        ];
        $result = $this->form_submit->recaptcha_error_message('g-recaptcha', $api_response);

        $this->assertStringContainsString('Google reCAPTCHA', $result);
        $this->assertStringContainsString('The secret parameter is missing.', $result);
        $this->assertStringContainsString('missing-input-secret', $result);

        // Test case 3: Error code provided and captcha type is hcaptcha
        $api_response = [
            'error-codes' => ['invalid-input-secret'],
        ];
        $result = $this->form_submit->recaptcha_error_message('hcaptcha', $api_response);

        $this->assertStringContainsString('hCaptcha', $result);
        $this->assertStringContainsString('Your secret key is invalid or malformed.', $result);
        $this->assertStringContainsString('invalid-input-secret', $result);

        // Test case 4: Error code provided and captcha type is cf-turnstile
        $api_response = [
            'error-codes' => ['timeout-or-duplicate'],
        ];
        $result = $this->form_submit->recaptcha_error_message('cf-turnstile', $api_response);

        $this->assertStringContainsString('Cloudflare Turnstile', $result);
        $this->assertStringContainsString('The response parameter (token) has already been validated before.', $result);
        $this->assertStringContainsString('timeout-or-duplicate', $result);

        // Test case 5: Error code provided and captcha type is unknown
        $api_response = [
            'error-codes' => ['missing-input-response'],
        ];
        $result = $this->form_submit->recaptcha_error_message('unknown-captcha', $api_response);

        $this->assertStringContainsString('Unknown Captcha', $result);
        $this->assertStringContainsString('Invalid captcha type.', $result);

	}

}
