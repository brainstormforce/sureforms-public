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

		// Only max_entries is a free-plugin restriction. The handler deliberately
		// persists that key alone, so anything else in the payload is dropped
		// rather than written through unsanitised.
		$this->assertArrayNotHasKey( 'ip_restriction', $saved );
		$this->assertArrayNotHasKey( 'country_restriction', $saved );
		$this->assertArrayNotHasKey( 'keyword_restriction', $saved );
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
		// max_entries is the only restriction the free plugin implements; the others
		// were asserted here but exist nowhere in the codebase.
		$this->assertSame( [ 'max_entries' ], array_keys( $defaults ) );
		$this->assertFalse( $defaults['max_entries']['status'] );
		$this->assertSame( 0, $defaults['max_entries']['maxEntries'] );
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

		// The Turnstile execution option is a boolean, and an omitted key means
		// off -- a save from any other tab must never switch it on.
		$this->assertFalse( $saved['srfm_cf_turnstile_on_interaction'] );

		$options['srfm_cf_turnstile_on_interaction'] = true;
		Global_Settings::srfm_save_security_settings( $options );
		$saved = get_option( 'srfm_security_settings_options' );
		$this->assertTrue( $saved['srfm_cf_turnstile_on_interaction'] );
	}

	/**
	 * The reCAPTCHA v3 threshold is stored as a float clamped to 0-1, and an absent
	 * or unusable value is stored as the 0.5 default.
	 *
	 * @dataProvider provide_v3_score_thresholds
	 *
	 * @param array<string,mixed> $options Settings passed to the save.
	 * @param float               $want    Threshold expected in the stored option.
	 */
	public function test_srfm_save_security_settings_v3_score_threshold( $options, $want ) {
		Global_Settings::srfm_save_security_settings( $options );

		$saved = get_option( 'srfm_security_settings_options' );
		delete_option( 'srfm_security_settings_options' );

		$this->assertSame( $want, $saved['srfm_v3_score_threshold'] );
	}

	/**
	 * Submitted thresholds and the value each must be stored as.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function provide_v3_score_thresholds() {
		return [
			'absent'         => [ [], 0.5 ],
			'float'          => [ [ 'srfm_v3_score_threshold' => 0.3 ], 0.3 ],
			'numeric string' => [ [ 'srfm_v3_score_threshold' => '0.7' ], 0.7 ],
			'zero'           => [ [ 'srfm_v3_score_threshold' => 0 ], 0.0 ],
			'empty string'   => [ [ 'srfm_v3_score_threshold' => '' ], 0.5 ],
			'non-numeric'    => [ [ 'srfm_v3_score_threshold' => 'abc' ], 0.5 ],
			'array'          => [ [ 'srfm_v3_score_threshold' => [ 0.2 ] ], 0.5 ],
			'above 1'        => [ [ 'srfm_v3_score_threshold' => 5 ], 1.0 ],
			'below 0'        => [ [ 'srfm_v3_score_threshold' => -0.4 ], 0.0 ],
		];
	}

	/**
	 * The threshold helper returns a float in 0-1 for numeric input and 0.5 otherwise.
	 *
	 * @dataProvider provide_raw_v3_thresholds
	 *
	 * @param mixed $value Raw threshold.
	 * @param float $want  Sanitized threshold.
	 */
	public function test_sanitize_recaptcha_v3_threshold( $value, $want ) {
		$this->assertSame( $want, Global_Settings::sanitize_recaptcha_v3_threshold( $value ) );
	}

	/**
	 * Raw thresholds and their sanitized values.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function provide_raw_v3_thresholds() {
		return [
			'null'           => [ null, 0.5 ],
			'true'           => [ true, 0.5 ],
			'false'          => [ false, 0.5 ],
			'empty string'   => [ '', 0.5 ],
			'non-numeric'    => [ 'abc', 0.5 ],
			'array'          => [ [ 0.2 ], 0.5 ],
			'int zero'       => [ 0, 0.0 ],
			'int one'        => [ 1, 1.0 ],
			'numeric string' => [ '0.7', 0.7 ],
			'leading dot'    => [ '.65', 0.65 ],
			'long decimal'   => [ '0.658787878', 0.66 ],
			'rounds down'    => [ 0.123, 0.12 ],
			'exponent'       => [ '1e-1', 0.1 ],
			'above 1'        => [ 5, 1.0 ],
			'below 0'        => [ -0.4, 0.0 ],
			'infinity'       => [ INF, 0.5 ],
			'nan'            => [ NAN, 0.5 ],
		];
	}

	/**
	 * An unusable value falls back to the default the caller passes.
	 */
	public function test_sanitize_recaptcha_v3_threshold_uses_given_default() {
		$this->assertSame( 0.3, Global_Settings::sanitize_recaptcha_v3_threshold( 'abc', 0.3 ) );
		$this->assertSame( 0.3, Global_Settings::sanitize_recaptcha_v3_threshold( NAN, 0.3 ) );
		$this->assertSame( 0.8, Global_Settings::sanitize_recaptcha_v3_threshold( '0.8', 0.3 ) );
	}


	/**
	 * The Settings UI read reports the threshold in effect when the stored option
	 * predates the setting or holds an unusable value.
	 *
	 * @dataProvider provide_stored_options_for_threshold_read
	 *
	 * @param array<string,mixed> $stored Stored security option.
	 * @param float               $want   Threshold the read must report.
	 */
	public function test_srfm_get_general_settings_reports_v3_threshold( $stored, $want ) {
		update_option( 'srfm_security_settings_options', $stored );

		$request = new \WP_REST_Request( 'GET', '/sureforms/v1/srfm-global-settings' );
		$request->set_param( 'options_to_fetch', 'srfm_security_settings_options' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = Global_Settings::srfm_get_general_settings( $request );
		delete_option( 'srfm_security_settings_options' );

		$data = $response instanceof \WP_REST_Response ? $response->get_data() : $response;

		$this->assertSame( $want, $data['srfm_security_settings_options']['srfm_v3_score_threshold'] ?? null );
	}

	/**
	 * Stored security options and the threshold the read must report.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function provide_stored_options_for_threshold_read() {
		return [
			'key absent'  => [ [ 'srfm_v3_site_key' => 'site-key' ], 0.5 ],
			'non-numeric' => [ [ 'srfm_v3_score_threshold' => 'abc' ], 0.5 ],
			'stored 0.7'  => [ [ 'srfm_v3_score_threshold' => 0.7 ], 0.7 ],
		];
	}

	/**
	 * Every path that reports the logging default must agree with the runtime.
	 *
	 * Client_Logger::is_enabled() treats an absent key as on. When the settings
	 * paths defaulted it to off instead, a fresh install showed the toggle OFF in
	 * both the Settings UI and the Abilities API while logging was actually
	 * running -- and the next save of any unrelated General setting persisted
	 * `false`, silently ending it. The fresh-install default array is what makes
	 * this subtle: it sets the key, so the later `! isset()` backfill never fires.
	 */
	public function test_srfm_enable_logs_default_is_consistent_across_paths() {
		$backup = get_option( 'srfm_general_settings_options', [] );
		delete_option( 'srfm_general_settings_options' );

		$request = new \WP_REST_Request( 'GET', '/sureforms/v1/srfm-global-settings' );
		$request->set_param( 'options_to_fetch', 'srfm_general_settings_options' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$ui = \SRFM\Inc\Global_Settings\Global_Settings::srfm_get_general_settings( $request );

		// The callback answers with a REST response, not a bare array.
		$ui_data  = $ui instanceof \WP_REST_Response ? $ui->get_data() : $ui;
		$ui_value = is_array( $ui_data ) ? ( $ui_data['srfm_general_settings_options']['srfm_enable_logs'] ?? null ) : null;

		$this->assertNotNull( $ui_value, 'The Settings UI must report the key at all.' );

		$this->assertTrue(
			\SRFM\Inc\Client_Logger::is_enabled(),
			'Runtime treats an absent key as on.'
		);
		$this->assertTrue( (bool) $ui_value, 'The Settings UI must agree with the runtime.' );

		// The step that used to persist false: save an unrelated General setting.
		\SRFM\Inc\Global_Settings\Global_Settings::srfm_save_general_settings( [ 'srfm_ip_log' => true ] );
		$saved = (array) get_option( 'srfm_general_settings_options', [] );

		$this->assertTrue(
			(bool) $saved['srfm_enable_logs'],
			'Saving an unrelated setting must not switch logging off.'
		);

		update_option( 'srfm_general_settings_options', $backup );
	}

}
