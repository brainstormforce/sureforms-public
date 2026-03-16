<?php
/**
 * Class Test_Global_Settings
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Global_Settings\Global_Settings;

class Test_Global_Settings extends TestCase {

	protected $global_settings;

	protected function setUp(): void {
		$this->global_settings = new Global_Settings();
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/srfm-global-settings' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The srfm-global-settings endpoint should be registered' );
	}

	public function test_srfm_save_general_settings() {
		$setting_options = [
			'srfm_ip_log'             => true,
			'srfm_form_analytics'     => true,
			'srfm_bsf_analytics'      => false,
			'srfm_admin_notification' => true,
		];

		$reflection = new ReflectionClass( Global_Settings::class );
		$method = $reflection->getMethod( 'srfm_save_general_settings' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $setting_options );
		$this->assertTrue( $result );
	}

	public function test_srfm_save_general_settings_dynamic_opt() {
		$setting_options = [
			'srfm_email_block_required_text' => '<script>alert(1)</script>',
			'srfm_url_block_required_text'   => 'Please enter a valid URL',
			'srfm_valid_email'               => "<img src=x onerror=alert('xss')>",
		];

		Global_Settings::srfm_save_general_settings_dynamic_opt( $setting_options );

		$saved = get_option( 'srfm_default_dynamic_block_option' );

		$this->assertIsArray( $saved );
		// sanitize_text_field strips <script> tags AND their inner content entirely.
		$this->assertSame( '', $saved['srfm_email_block_required_text'] );
		// Verifies plain text is preserved.
		$this->assertSame( 'Please enter a valid URL', $saved['srfm_url_block_required_text'] );
		// Verifies img-based XSS is stripped — no HTML tags remain.
		$this->assertStringNotContainsString( '<', $saved['srfm_valid_email'] );
		$this->assertStringNotContainsString( 'onerror', $saved['srfm_valid_email'] );
	}

	public function test_srfm_save_email_summary_settings() {
		// Valid email is preserved.
		Global_Settings::srfm_save_email_summary_settings(
			[
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => 'admin@example.com',
				'srfm_schedule_report' => 'Monday',
			]
		);
		$saved = get_option( 'srfm_email_summary_settings_options' );
		$this->assertSame( 'admin@example.com', $saved['srfm_email_sent_to'] );

		// Header injection newlines are stripped by sanitize_email().
		Global_Settings::srfm_save_email_summary_settings(
			[
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => "admin@example.com\r\nBcc: evil@hacker.com",
				'srfm_schedule_report' => 'Monday',
			]
		);
		$saved = get_option( 'srfm_email_summary_settings_options' );
		$this->assertStringNotContainsString( "\r", $saved['srfm_email_sent_to'] );
		$this->assertStringNotContainsString( "\n", $saved['srfm_email_sent_to'] );
	}

	public function test_srfm_get_general_settings() {
		$this->assertTrue( method_exists( Global_Settings::class, 'srfm_get_general_settings' ) );
	}
}
