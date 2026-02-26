<?php
/**
 * Class Test_Stripe_Helper
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;

class Test_Stripe_Helper extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// Reset the static webhook verification cache between tests.
		$reflection = new \ReflectionClass( Stripe_Helper::class );
		$cache_prop = $reflection->getProperty( 'webhook_verification_cache' );
		$cache_prop->setAccessible( true );
		$cache_prop->setValue( null, [] );
	}

	/**
	 * Helper method to call private/protected static methods.
	 */
	private function call_private_method( $class, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( $class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( null, $parameters );
	}

	// ──────────────────────────────────────────────
	// clean_amount (private)
	// ──────────────────────────────────────────────

	public function test_clean_amount_with_integer() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ 100 ] );
		$this->assertSame( 100.0, $result );
	}

	public function test_clean_amount_with_float() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ 49.99 ] );
		$this->assertSame( 49.99, $result );
	}

	public function test_clean_amount_with_string_number() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ '25.50' ] );
		$this->assertSame( 25.50, $result );
	}

	public function test_clean_amount_removes_commas() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ '1,234.56' ] );
		$this->assertSame( 1234.56, $result );
	}

	public function test_clean_amount_removes_spaces() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ '1 234' ] );
		$this->assertSame( 1234.0, $result );
	}

	public function test_clean_amount_with_non_numeric_string() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ 'abc' ] );
		$this->assertSame( 0.0, $result );
	}

	public function test_clean_amount_with_empty_string() {
		$result = $this->call_private_method( Stripe_Helper::class, 'clean_amount', [ '' ] );
		$this->assertSame( 0.0, $result );
	}

	// ──────────────────────────────────────────────
	// amount_to_stripe_format
	// ──────────────────────────────────────────────

	public function test_amount_to_stripe_format_usd() {
		$result = Stripe_Helper::amount_to_stripe_format( 10.00, 'USD' );
		$this->assertSame( 1000, $result );
	}

	public function test_amount_to_stripe_format_usd_cents() {
		$result = Stripe_Helper::amount_to_stripe_format( 19.99, 'USD' );
		$this->assertSame( 1999, $result );
	}

	public function test_amount_to_stripe_format_eur() {
		$result = Stripe_Helper::amount_to_stripe_format( 50.00, 'EUR' );
		$this->assertSame( 5000, $result );
	}

	public function test_amount_to_stripe_format_zero_decimal_jpy() {
		$result = Stripe_Helper::amount_to_stripe_format( 1000, 'JPY' );
		$this->assertSame( 1000, $result );
	}

	public function test_amount_to_stripe_format_zero_decimal_krw() {
		$result = Stripe_Helper::amount_to_stripe_format( 5000, 'KRW' );
		$this->assertSame( 5000, $result );
	}

	public function test_amount_to_stripe_format_string_with_commas() {
		$result = Stripe_Helper::amount_to_stripe_format( '1,234.56', 'USD' );
		$this->assertSame( 123456, $result );
	}

	public function test_amount_to_stripe_format_zero_amount() {
		$result = Stripe_Helper::amount_to_stripe_format( 0, 'USD' );
		$this->assertSame( 0, $result );
	}

	public function test_amount_to_stripe_format_rounds_fractional_cents() {
		// 10.005 * 100 = 1000.5, should round to 1001
		$result = Stripe_Helper::amount_to_stripe_format( 10.005, 'USD' );
		$this->assertSame( 1001, $result );
	}

	// ──────────────────────────────────────────────
	// amount_from_stripe_format
	// ──────────────────────────────────────────────

	public function test_amount_from_stripe_format_usd() {
		$result = Stripe_Helper::amount_from_stripe_format( 1999, 'USD' );
		$this->assertSame( 19.99, $result );
	}

	public function test_amount_from_stripe_format_zero_decimal_jpy() {
		$result = Stripe_Helper::amount_from_stripe_format( 500, 'JPY' );
		$this->assertSame( 500.0, $result );
	}

	public function test_amount_from_stripe_format_zero_amount() {
		$result = Stripe_Helper::amount_from_stripe_format( 0, 'USD' );
		$this->assertSame( 0.0, $result );
	}

	public function test_amount_from_stripe_format_with_string_amount() {
		$result = Stripe_Helper::amount_from_stripe_format( '2500', 'USD' );
		$this->assertSame( 25.0, $result );
	}

	// ──────────────────────────────────────────────
	// is_zero_decimal_currency (delegates to Payment_Helper)
	// ──────────────────────────────────────────────

	public function test_is_zero_decimal_currency_jpy() {
		$this->assertTrue( Stripe_Helper::is_zero_decimal_currency( 'JPY' ) );
	}

	public function test_is_zero_decimal_currency_krw() {
		$this->assertTrue( Stripe_Helper::is_zero_decimal_currency( 'KRW' ) );
	}

	public function test_is_zero_decimal_currency_usd() {
		$this->assertFalse( Stripe_Helper::is_zero_decimal_currency( 'USD' ) );
	}

	public function test_is_zero_decimal_currency_eur() {
		$this->assertFalse( Stripe_Helper::is_zero_decimal_currency( 'EUR' ) );
	}

	public function test_is_zero_decimal_currency_lowercase() {
		// Payment_Helper uppercases, so lowercase should work.
		$this->assertTrue( Stripe_Helper::is_zero_decimal_currency( 'jpy' ) );
	}

	public function test_is_zero_decimal_currency_empty_string() {
		$this->assertFalse( Stripe_Helper::is_zero_decimal_currency( '' ) );
	}

	// ──────────────────────────────────────────────
	// flatten_stripe_data (private)
	// ──────────────────────────────────────────────

	public function test_flatten_stripe_data_flat_array() {
		$data   = [ 'amount' => 1000, 'currency' => 'usd' ];
		$result = $this->call_private_method( Stripe_Helper::class, 'flatten_stripe_data', [ $data ] );
		$this->assertEquals( [ 'amount' => 1000, 'currency' => 'usd' ], $result );
	}

	public function test_flatten_stripe_data_nested_array() {
		$data   = [
			'metadata' => [
				'source'     => 'SureForms',
				'payment_id' => 123,
			],
		];
		$result = $this->call_private_method( Stripe_Helper::class, 'flatten_stripe_data', [ $data ] );
		$this->assertEquals(
			[
				'metadata[source]'     => 'SureForms',
				'metadata[payment_id]' => 123,
			],
			$result
		);
	}

	public function test_flatten_stripe_data_deeply_nested() {
		$data   = [
			'level1' => [
				'level2' => [
					'level3' => 'value',
				],
			],
		];
		$result = $this->call_private_method( Stripe_Helper::class, 'flatten_stripe_data', [ $data ] );
		$this->assertEquals( [ 'level1[level2][level3]' => 'value' ], $result );
	}

	public function test_flatten_stripe_data_empty_array() {
		$result = $this->call_private_method( Stripe_Helper::class, 'flatten_stripe_data', [ [] ] );
		$this->assertEquals( [], $result );
	}

	// ──────────────────────────────────────────────
	// generate_unique_payment_id
	// ──────────────────────────────────────────────

	public function test_generate_unique_payment_id_returns_14_chars() {
		$result = Stripe_Helper::generate_unique_payment_id( 1 );
		$this->assertSame( 14, strlen( $result ) );
	}

	public function test_generate_unique_payment_id_is_uppercase() {
		$result = Stripe_Helper::generate_unique_payment_id( 42 );
		$this->assertSame( strtoupper( $result ), $result );
	}

	public function test_generate_unique_payment_id_large_id() {
		$result = Stripe_Helper::generate_unique_payment_id( 999999999 );
		$this->assertSame( 14, strlen( $result ) );
	}

	public function test_generate_unique_payment_id_uniqueness() {
		$id1 = Stripe_Helper::generate_unique_payment_id( 1 );
		$id2 = Stripe_Helper::generate_unique_payment_id( 1 );
		// Even with the same auto-increment ID, random part should differ.
		$this->assertNotEquals( $id1, $id2 );
	}

	public function test_generate_unique_payment_id_zero() {
		$result = Stripe_Helper::generate_unique_payment_id( 0 );
		$this->assertSame( 14, strlen( $result ) );
	}

	// ──────────────────────────────────────────────
	// get_default_stripe_settings
	// ──────────────────────────────────────────────

	public function test_get_default_stripe_settings_returns_array() {
		$defaults = Stripe_Helper::get_default_stripe_settings();
		$this->assertIsArray( $defaults );
	}

	public function test_get_default_stripe_settings_has_required_keys() {
		$defaults      = Stripe_Helper::get_default_stripe_settings();
		$required_keys = [
			'stripe_connected',
			'stripe_account_id',
			'stripe_account_email',
			'stripe_live_publishable_key',
			'stripe_live_secret_key',
			'stripe_test_publishable_key',
			'stripe_test_secret_key',
			'payment_mode',
			'webhook_test_secret',
			'webhook_test_url',
			'webhook_test_id',
			'webhook_live_secret',
			'webhook_live_url',
			'webhook_live_id',
			'account_name',
		];
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $defaults, "Missing key: $key" );
		}
	}

	public function test_get_default_stripe_settings_stripe_not_connected() {
		$defaults = Stripe_Helper::get_default_stripe_settings();
		$this->assertFalse( $defaults['stripe_connected'] );
	}

	public function test_get_default_stripe_settings_default_mode_is_test() {
		$defaults = Stripe_Helper::get_default_stripe_settings();
		$this->assertSame( 'test', $defaults['payment_mode'] );
	}

	// ──────────────────────────────────────────────
	// is_stripe_connected (depends on DB options)
	// ──────────────────────────────────────────────

	public function test_is_stripe_connected_returns_false_by_default() {
		// With default/empty settings, should return false.
		$this->assertFalse( Stripe_Helper::is_stripe_connected() );
	}

	// ──────────────────────────────────────────────
	// get_stripe_secret_key
	// ──────────────────────────────────────────────

	public function test_get_stripe_secret_key_returns_empty_by_default() {
		$result = Stripe_Helper::get_stripe_secret_key( 'test' );
		$this->assertSame( '', $result );
	}

	public function test_get_stripe_secret_key_live_returns_empty_by_default() {
		$result = Stripe_Helper::get_stripe_secret_key( 'live' );
		$this->assertSame( '', $result );
	}

	// ──────────────────────────────────────────────
	// get_stripe_publishable_key
	// ──────────────────────────────────────────────

	public function test_get_stripe_publishable_key_returns_empty_by_default() {
		$result = Stripe_Helper::get_stripe_publishable_key( 'test' );
		$this->assertSame( '', $result );
	}

	public function test_get_stripe_publishable_key_live_returns_empty_by_default() {
		$result = Stripe_Helper::get_stripe_publishable_key( 'live' );
		$this->assertSame( '', $result );
	}

	// ──────────────────────────────────────────────
	// get_stripe_setting / update_stripe_setting
	// ──────────────────────────────────────────────

	public function test_get_stripe_setting_returns_default_for_missing_key() {
		$result = Stripe_Helper::get_stripe_setting( 'nonexistent_key', 'fallback' );
		$this->assertSame( 'fallback', $result );
	}

	public function test_get_stripe_setting_with_empty_key() {
		$result = Stripe_Helper::get_stripe_setting( '', 'default_val' );
		$this->assertSame( 'default_val', $result );
	}

	public function test_update_stripe_setting_with_empty_key_returns_false() {
		$result = Stripe_Helper::update_stripe_setting( '', 'value' );
		$this->assertFalse( $result );
	}

	// ──────────────────────────────────────────────
	// update_all_stripe_settings
	// ──────────────────────────────────────────────

	public function test_update_all_stripe_settings_with_non_array_returns_false() {
		$this->assertFalse( Stripe_Helper::update_all_stripe_settings( 'not_array' ) );
	}

	// ──────────────────────────────────────────────
	// get_webhook_url
	// ──────────────────────────────────────────────

	public function test_get_webhook_url_test_mode() {
		$url = Stripe_Helper::get_webhook_url( 'test' );
		$this->assertStringContainsString( 'sureforms/webhook_test', $url );
	}

	public function test_get_webhook_url_live_mode() {
		$url = Stripe_Helper::get_webhook_url( 'live' );
		$this->assertStringContainsString( 'sureforms/webhook_live', $url );
	}

	// ──────────────────────────────────────────────
	// middle_ware_base_url
	// ──────────────────────────────────────────────

	public function test_middle_ware_base_url_contains_payments_stripe() {
		$url = Stripe_Helper::middle_ware_base_url();
		$this->assertStringEndsWith( 'payments/stripe/', $url );
	}

	// ──────────────────────────────────────────────
	// get_stripe_settings_url
	// ──────────────────────────────────────────────

	public function test_get_stripe_settings_url_contains_expected_params() {
		$url = Stripe_Helper::get_stripe_settings_url();
		$this->assertStringContainsString( 'page=sureforms_form_settings', $url );
		$this->assertStringContainsString( 'tab=payments-settings', $url );
		$this->assertStringContainsString( 'gateway=stripe', $url );
	}

	// ──────────────────────────────────────────────
	// is_webhook_configured
	// ──────────────────────────────────────────────

	public function test_is_webhook_configured_returns_false_by_default() {
		$this->assertFalse( Stripe_Helper::is_webhook_configured() );
	}

	public function test_is_webhook_configured_with_invalid_mode_falls_back() {
		$this->assertFalse( Stripe_Helper::is_webhook_configured( 'invalid_mode' ) );
	}

	// ──────────────────────────────────────────────
	// stripe_api_request - not connected scenario
	// ──────────────────────────────────────────────

	public function test_stripe_api_request_returns_error_when_not_connected() {
		$result = Stripe_Helper::stripe_api_request( 'charges', 'POST', [] );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'stripe_not_connected', $result['error']['code'] );
		$this->assertSame( 'auth', $result['error']['type'] );
	}

	// ──────────────────────────────────────────────
	// get_stripe_account_id
	// ──────────────────────────────────────────────

	public function test_get_stripe_account_id_returns_empty_by_default() {
		$result = Stripe_Helper::get_stripe_account_id();
		$this->assertSame( '', $result );
	}

	// ──────────────────────────────────────────────
	// intersect_payment - validation edge cases
	// ──────────────────────────────────────────────

	public function test_intersect_payment_returns_early_with_empty_charge_id() {
		// Should return void without error (invalid charge ID).
		Stripe_Helper::intersect_payment( '', 'sk_test_123', 'acct_123' );
		$this->assertTrue( true ); // No exception thrown.
	}

	public function test_intersect_payment_returns_early_with_invalid_charge_id_format() {
		Stripe_Helper::intersect_payment( 'invalid_id', 'sk_test_123', 'acct_123' );
		$this->assertTrue( true );
	}

	public function test_intersect_payment_returns_early_with_empty_secret_key() {
		Stripe_Helper::intersect_payment( 'ch_abc123', '', 'acct_123' );
		$this->assertTrue( true );
	}

	// ──────────────────────────────────────────────
	// get_licensing_instance (private) - when Pro not available
	// ──────────────────────────────────────────────

	public function test_get_licensing_instance_returns_null_without_pro() {
		$result = $this->call_private_method( Stripe_Helper::class, 'get_licensing_instance', [] );
		$this->assertNull( $result );
	}

	public function test_is_pro_license_active_returns_empty_without_pro() {
		$result = Stripe_Helper::is_pro_license_active();
		$this->assertSame( '', $result );
	}

	public function test_get_license_key_returns_empty_without_pro() {
		$result = Stripe_Helper::get_license_key();
		$this->assertSame( '', $result );
	}
}
