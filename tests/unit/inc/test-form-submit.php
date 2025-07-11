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
}
