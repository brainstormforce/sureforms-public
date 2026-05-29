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

	/**
	 * Test srfm_save_form_restriction_settings saves and sanitizes correctly.
	 */
	public function test_srfm_save_form_restriction_settings() {
		$options = [
			'max_entries'         => [
				'status'     => true,
				'maxEntries' => '50',
				'message'    => 'Form closed.',
			],
			'ip_restriction'      => [
				'status' => false,
				'mode'   => 'allow',
				'ips'    => '192.168.1.1',
			],
			'country_restriction' => [
				'status' => false,
			],
			'keyword_restriction' => [
				'status' => false,
			],
		];

		$result = Global_Settings::srfm_save_form_restriction_settings( $options );
		$this->assertTrue( $result );

		$saved = get_option( 'srfm_form_restriction_settings_options' );
		$this->assertTrue( $saved['max_entries']['status'] );
		$this->assertSame( 50, $saved['max_entries']['maxEntries'] );
		$this->assertSame( 'allow', $saved['ip_restriction']['mode'] );
	}

	/**
	 * Test srfm_save_compliance_settings saves correctly.
	 */
	public function test_srfm_save_compliance_settings() {
		$options = [
			'gdpr'                 => true,
			'do_not_store_entries' => false,
			'auto_delete_entries'  => true,
			'auto_delete_days'     => '60',
		];

		$result = Global_Settings::srfm_save_compliance_settings( $options );
		$this->assertTrue( $result );

		$saved = get_option( 'srfm_compliance_settings_options' );
		$this->assertTrue( $saved['gdpr'] );
		$this->assertFalse( $saved['do_not_store_entries'] );
		$this->assertSame( 60, $saved['auto_delete_days'] );
	}

	/**
	 * Test get_default_compliance_settings returns expected structure.
	 */
	public function test_get_default_compliance_settings() {
		$defaults = Global_Settings::get_default_compliance_settings();
		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'gdpr', $defaults );
		$this->assertArrayHasKey( 'do_not_store_entries', $defaults );
		$this->assertArrayHasKey( 'auto_delete_entries', $defaults );
		$this->assertArrayHasKey( 'auto_delete_days', $defaults );
		$this->assertFalse( $defaults['gdpr'] );
		$this->assertSame( 30, $defaults['auto_delete_days'] );
	}

	/**
	 * Test srfm_save_form_confirmation_settings saves and validates types.
	 */
	public function test_srfm_save_form_confirmation_settings() {
		$options = [
			'confirmation_type' => 'same page',
			'message'           => '<p>Thank you!</p>',
			'submission_action' => 'hide form',
			'page_url'          => 'https://example.com/thanks',
			'custom_url'        => '',
		];

		$result = Global_Settings::srfm_save_form_confirmation_settings( $options );
		$this->assertTrue( $result );

		$saved = get_option( 'srfm_form_confirmation_settings_options' );
		$this->assertSame( 'same page', $saved['confirmation_type'] );
		$this->assertStringContainsString( 'Thank you!', $saved['message'] );

		// Invalid confirmation type falls back to default.
		Global_Settings::srfm_save_form_confirmation_settings( [ 'confirmation_type' => 'invalid' ] );
		$saved = get_option( 'srfm_form_confirmation_settings_options' );
		$this->assertSame( 'same page', $saved['confirmation_type'] );
	}

	/**
	 * Test get_default_confirmation_message returns non-empty HTML.
	 */
	public function test_get_default_confirmation_message() {
		$message = Global_Settings::get_default_confirmation_message();
		$this->assertIsString( $message );
		$this->assertNotEmpty( $message );
		$this->assertStringContainsString( 'Thank you', $message );
	}

	/**
	 * Test get_default_form_confirmation_settings returns expected structure.
	 */
	public function test_get_default_form_confirmation_settings() {
		$defaults = Global_Settings::get_default_form_confirmation_settings();
		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'confirmation_type', $defaults );
		$this->assertArrayHasKey( 'message', $defaults );
		$this->assertArrayHasKey( 'submission_action', $defaults );
		$this->assertSame( 'same page', $defaults['confirmation_type'] );
		$this->assertSame( 'hide form', $defaults['submission_action'] );
	}

	/**
	 * Test srfm_save_email_notification_settings saves and sanitizes.
	 */
	public function test_srfm_save_email_notification_settings() {
		$options = [
			'email_to'       => 'admin@example.com',
			'subject'        => 'New Submission',
			'email_body'     => '<p>Hello</p>',
			'from_name'      => '{site_title}',
			'from_email'     => '{admin_email}',
			'email_cc'       => '',
			'email_bcc'      => '',
			'email_reply_to' => '',
		];

		$result = Global_Settings::srfm_save_email_notification_settings( $options );
		$this->assertTrue( $result );

		$saved = get_option( 'srfm_email_notification_settings_options' );
		$this->assertSame( 'admin@example.com', $saved['email_to'] );
		$this->assertSame( 'New Submission', $saved['subject'] );
	}

	/**
	 * Test get_default_email_notification_settings returns expected structure.
	 */
	public function test_get_default_email_notification_settings() {
		$defaults = Global_Settings::get_default_email_notification_settings();
		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'email_to', $defaults );
		$this->assertArrayHasKey( 'subject', $defaults );
		$this->assertArrayHasKey( 'email_body', $defaults );
		$this->assertSame( '{admin_email}', $defaults['email_to'] );
		$this->assertSame( '{all_data}', $defaults['email_body'] );
	}

	/**
	 * Test get_default_form_restriction_settings returns expected structure.
	 */
	public function test_get_default_form_restriction_settings() {
		$defaults = Global_Settings::get_default_form_restriction_settings();
		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'max_entries', $defaults );
		$this->assertArrayHasKey( 'ip_restriction', $defaults );
		$this->assertArrayHasKey( 'country_restriction', $defaults );
		$this->assertArrayHasKey( 'keyword_restriction', $defaults );
		$this->assertFalse( $defaults['max_entries']['status'] );
	}

	/**
	 * Test srfm_save_global_settings dispatches to correct handler.
	 */
	public function test_srfm_save_global_settings() {
		$request = new WP_REST_Request( 'POST' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'srfm_tab', 'compliance-settings' );
		$request->set_param( 'gdpr', true );

		$response = Global_Settings::srfm_save_global_settings( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$saved = get_option( 'srfm_compliance_settings_options' );
		$this->assertTrue( $saved['gdpr'] );
	}

	/**
	 * Test srfm_save_security_settings saves captcha keys.
	 */
	public function test_srfm_save_security_settings() {
		$options = [
			'srfm_v2_checkbox_site_key'   => 'test-site-key',
			'srfm_v2_checkbox_secret_key' => 'test-secret-key',
			'srfm_honeypot'               => true,
		];

		$result = Global_Settings::srfm_save_security_settings( $options );
		$this->assertTrue( $result );

		$saved = get_option( 'srfm_security_settings_options' );
		$this->assertSame( 'test-site-key', $saved['srfm_v2_checkbox_site_key'] );
		$this->assertTrue( $saved['srfm_honeypot'] );
	}

}
