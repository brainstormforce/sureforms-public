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

	/**
	 * Test saving MCP settings stores individual options and grouped option.
	 */
	public function test_srfm_save_mcp_settings() {
		$setting_options = [
			'srfm_abilities_api'        => true,
			'srfm_abilities_api_edit'   => true,
			'srfm_abilities_api_delete' => false,
			'srfm_mcp_server'           => true,
		];

		$reflection = new ReflectionClass( Global_Settings::class );
		$method     = $reflection->getMethod( 'srfm_save_mcp_settings' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $setting_options );

		// Verify individual options are saved correctly.
		$this->assertTrue( (bool) get_option( 'srfm_abilities_api' ) );
		$this->assertTrue( (bool) get_option( 'srfm_abilities_api_edit' ) );
		$this->assertFalse( (bool) get_option( 'srfm_abilities_api_delete' ) );
		$this->assertTrue( (bool) get_option( 'srfm_mcp_server' ) );

		// Verify grouped option.
		$grouped = get_option( 'srfm_mcp_settings_options' );
		$this->assertIsArray( $grouped );
		$this->assertTrue( $grouped['srfm_abilities_api'] );
		$this->assertTrue( $grouped['srfm_abilities_api_edit'] );
		$this->assertFalse( $grouped['srfm_abilities_api_delete'] );
		$this->assertTrue( $grouped['srfm_mcp_server'] );
	}

	/**
	 * Test saving global settings via the public srfm_save_global_settings method.
	 */
	public function test_srfm_save_global_settings() {
		$setting_options = [
			'srfm_ip_log'             => true,
			'srfm_form_analytics'     => false,
			'srfm_bsf_analytics'      => true,
			'srfm_admin_notification' => true,
		];

		$reflection = new ReflectionClass( Global_Settings::class );
		$method     = $reflection->getMethod( 'srfm_save_general_settings' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $setting_options );
		$this->assertTrue( $result );

		// Verify the general settings option was saved.
		$saved = get_option( 'srfm_general_settings_options' );
		$this->assertIsArray( $saved );
		$this->assertTrue( $saved['srfm_ip_log'] );
		$this->assertFalse( $saved['srfm_form_analytics'] );
		$this->assertTrue( $saved['srfm_admin_notification'] );
	}

	/**
	 * Test saving payments settings returns true for non-stripe gateway.
	 */
	public function test_srfm_save_payments_settings() {
		// Test with minimal settings and no specific gateway (defaults to stripe, but
		// without stripe-specific data, it still calls update_gateway_settings).
		$setting_options = [
			'gateway'      => 'paypal',
			'currency'     => 'USD',
			'payment_mode' => 'test',
		];

		$reflection = new ReflectionClass( Global_Settings::class );
		$method     = $reflection->getMethod( 'srfm_save_payments_settings' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $setting_options );

		// For non-stripe gateway, the method returns true.
		$this->assertTrue( $result );
	}

	/**
	 * Test that the srfm_get_general_settings method exists and is callable.
	 */
	public function test_srfm_get_general_settings() {
		$this->assertTrue(
			method_exists( Global_Settings::class, 'srfm_get_general_settings' ),
			'The srfm_get_general_settings method should exist on the Global_Settings class.'
		);

		$reflection = new ReflectionClass( Global_Settings::class );
		$method     = $reflection->getMethod( 'srfm_get_general_settings' );
		$this->assertTrue( $method->isPublic(), 'srfm_get_general_settings should be public.' );
		$this->assertTrue( $method->isStatic(), 'srfm_get_general_settings should be static.' );
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

}
