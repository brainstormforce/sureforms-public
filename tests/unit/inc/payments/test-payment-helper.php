<?php
/**
 * Class Test_Payment_Helper
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Payment_Helper;

class Test_Payment_Helper extends TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	// --- get_all_currencies_data ---

	public function test_get_all_currencies_data_returns_array() {
		$currencies = Payment_Helper::get_all_currencies_data();
		$this->assertIsArray( $currencies );
		$this->assertNotEmpty( $currencies );
	}

	public function test_get_all_currencies_data_contains_usd() {
		$currencies = Payment_Helper::get_all_currencies_data();
		$this->assertArrayHasKey( 'USD', $currencies );
		$this->assertEquals( '$', $currencies['USD']['symbol'] );
		$this->assertEquals( 2, $currencies['USD']['decimal_places'] );
	}

	public function test_get_all_currencies_data_contains_jpy_zero_decimal() {
		$currencies = Payment_Helper::get_all_currencies_data();
		$this->assertArrayHasKey( 'JPY', $currencies );
		$this->assertEquals( 0, $currencies['JPY']['decimal_places'] );
	}

	public function test_get_all_currencies_data_contains_krw_zero_decimal() {
		$currencies = Payment_Helper::get_all_currencies_data();
		$this->assertArrayHasKey( 'KRW', $currencies );
		$this->assertEquals( 0, $currencies['KRW']['decimal_places'] );
	}

	public function test_get_all_currencies_data_each_entry_has_required_keys() {
		$currencies = Payment_Helper::get_all_currencies_data();
		foreach ( $currencies as $code => $data ) {
			$this->assertArrayHasKey( 'name', $data, "Currency {$code} missing 'name'" );
			$this->assertArrayHasKey( 'symbol', $data, "Currency {$code} missing 'symbol'" );
			$this->assertArrayHasKey( 'decimal_places', $data, "Currency {$code} missing 'decimal_places'" );
		}
	}

	// --- get_currency_symbol ---

	public function test_get_currency_symbol_usd() {
		$this->assertEquals( '$', Payment_Helper::get_currency_symbol( 'USD' ) );
	}

	public function test_get_currency_symbol_eur() {
		$this->assertEquals( '€', Payment_Helper::get_currency_symbol( 'EUR' ) );
	}

	public function test_get_currency_symbol_lowercase_input() {
		$this->assertEquals( '£', Payment_Helper::get_currency_symbol( 'gbp' ) );
	}

	public function test_get_currency_symbol_empty_string() {
		$this->assertEquals( '', Payment_Helper::get_currency_symbol( '' ) );
	}

	public function test_get_currency_symbol_invalid_currency() {
		$this->assertEquals( '', Payment_Helper::get_currency_symbol( 'INVALID' ) );
	}

	public function test_get_currency_symbol_inr() {
		$this->assertEquals( '₹', Payment_Helper::get_currency_symbol( 'INR' ) );
	}

	// --- get_zero_decimal_currencies ---

	public function test_get_zero_decimal_currencies_returns_array() {
		$zero_decimal = Payment_Helper::get_zero_decimal_currencies();
		$this->assertIsArray( $zero_decimal );
		$this->assertNotEmpty( $zero_decimal );
	}

	public function test_get_zero_decimal_currencies_contains_jpy() {
		$zero_decimal = Payment_Helper::get_zero_decimal_currencies();
		$this->assertContains( 'JPY', $zero_decimal );
	}

	public function test_get_zero_decimal_currencies_contains_krw() {
		$zero_decimal = Payment_Helper::get_zero_decimal_currencies();
		$this->assertContains( 'KRW', $zero_decimal );
	}

	public function test_get_zero_decimal_currencies_does_not_contain_usd() {
		$zero_decimal = Payment_Helper::get_zero_decimal_currencies();
		$this->assertNotContains( 'USD', $zero_decimal );
	}

	// --- is_zero_decimal_currency ---

	public function test_is_zero_decimal_currency_jpy() {
		$this->assertTrue( Payment_Helper::is_zero_decimal_currency( 'JPY' ) );
	}

	public function test_is_zero_decimal_currency_usd() {
		$this->assertFalse( Payment_Helper::is_zero_decimal_currency( 'USD' ) );
	}

	public function test_is_zero_decimal_currency_lowercase() {
		$this->assertTrue( Payment_Helper::is_zero_decimal_currency( 'jpy' ) );
	}

	public function test_is_zero_decimal_currency_empty_string() {
		$this->assertFalse( Payment_Helper::is_zero_decimal_currency( '' ) );
	}

	public function test_is_zero_decimal_currency_invalid() {
		$this->assertFalse( Payment_Helper::is_zero_decimal_currency( 'ZZZZZ' ) );
	}

	// --- get_currency_names ---

	public function test_get_currency_names_returns_array() {
		$names = Payment_Helper::get_currency_names();
		$this->assertIsArray( $names );
		$this->assertNotEmpty( $names );
	}

	public function test_get_currency_names_keys_match_currencies() {
		$names      = Payment_Helper::get_currency_names();
		$currencies = Payment_Helper::get_all_currencies_data();
		$this->assertEquals( array_keys( $currencies ), array_keys( $names ) );
	}

	// --- get_payment_strings ---

	public function test_get_payment_strings_returns_array() {
		$strings = Payment_Helper::get_payment_strings();
		$this->assertIsArray( $strings );
		$this->assertNotEmpty( $strings );
	}

	public function test_get_payment_strings_has_unknown_error() {
		$strings = Payment_Helper::get_payment_strings();
		$this->assertArrayHasKey( 'unknown_error', $strings );
	}

	public function test_get_payment_strings_has_payment_failed() {
		$strings = Payment_Helper::get_payment_strings();
		$this->assertArrayHasKey( 'payment_failed', $strings );
	}

	public function test_get_payment_strings_has_stripe_decline_codes() {
		$strings = Payment_Helper::get_payment_strings();
		$this->assertArrayHasKey( 'insufficient_funds', $strings );
		$this->assertArrayHasKey( 'expired_card', $strings );
		$this->assertArrayHasKey( 'incorrect_cvc', $strings );
	}

	// --- get_error_message_by_key ---

	public function test_get_error_message_by_key_valid_key() {
		$message = Payment_Helper::get_error_message_by_key( 'payment_failed' );
		$this->assertNotEmpty( $message );
		$this->assertIsString( $message );
	}

	public function test_get_error_message_by_key_invalid_key_returns_unknown() {
		$message = Payment_Helper::get_error_message_by_key( 'nonexistent_key_12345' );
		$this->assertEquals( 'Unknown error', $message );
	}

	public function test_get_error_message_by_key_insufficient_funds() {
		$strings = Payment_Helper::get_payment_strings();
		$message = Payment_Helper::get_error_message_by_key( 'insufficient_funds' );
		$this->assertEquals( $strings['insufficient_funds'], $message );
	}

	// --- get_default_payment_settings (private, via get_all_payment_settings fallback) ---

	public function test_get_all_payment_settings_returns_array_with_defaults() {
		delete_option( 'srfm_options' );
		$settings = Payment_Helper::get_all_payment_settings();
		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'currency', $settings );
		$this->assertArrayHasKey( 'payment_mode', $settings );
		$this->assertEquals( 'USD', $settings['currency'] );
		$this->assertEquals( 'test', $settings['payment_mode'] );
	}

	// --- update_payment_settings ---

	public function test_update_payment_settings_with_valid_array() {
		$result = Payment_Helper::update_payment_settings( [
			'currency'     => 'EUR',
			'payment_mode' => 'live',
		] );
		$this->assertTrue( $result );
	}

	public function test_update_payment_settings_with_non_array_returns_false() {
		$result = Payment_Helper::update_payment_settings( 'invalid' );
		$this->assertFalse( $result );
	}

	// --- get_gateway_settings ---

	public function test_get_gateway_settings_empty_gateway_returns_empty() {
		$this->assertEquals( [], Payment_Helper::get_gateway_settings( '' ) );
	}

	// --- update_gateway_settings ---

	public function test_update_gateway_settings_empty_gateway_returns_false() {
		$this->assertFalse( Payment_Helper::update_gateway_settings( '', [ 'key' => 'val' ] ) );
	}

	public function test_update_gateway_settings_non_array_settings_returns_false() {
		$this->assertFalse( Payment_Helper::update_gateway_settings( 'stripe', 'invalid' ) );
	}

	// --- get_global_setting ---

	public function test_get_global_setting_empty_key_returns_default() {
		$this->assertEquals( 'fallback', Payment_Helper::get_global_setting( '', 'fallback' ) );
	}

	// --- get_currency ---

	public function test_get_currency_returns_string() {
		$currency = Payment_Helper::get_currency();
		$this->assertIsString( $currency );
		$this->assertNotEmpty( $currency );
	}

	// --- get_payment_mode ---

	public function test_get_payment_mode_returns_string() {
		$mode = Payment_Helper::get_payment_mode();
		$this->assertIsString( $mode );
		$this->assertNotEmpty( $mode );
	}

	// --- store_payment_intent_metadata / delete_payment_intent_metadata ---

	public function test_store_payment_intent_metadata_success() {
		$result = Payment_Helper::store_payment_intent_metadata( 'block_1', 'pi_123', [ 'amount' => 100 ] );
		$this->assertTrue( $result );
	}

	public function test_store_payment_intent_metadata_empty_block_id() {
		$result = Payment_Helper::store_payment_intent_metadata( '', 'pi_123', [ 'amount' => 100 ] );
		$this->assertFalse( $result );
	}

	public function test_store_payment_intent_metadata_empty_payment_intent_id() {
		$result = Payment_Helper::store_payment_intent_metadata( 'block_1', '', [ 'amount' => 100 ] );
		$this->assertFalse( $result );
	}

	public function test_store_payment_intent_metadata_adds_timestamp() {
		Payment_Helper::store_payment_intent_metadata( 'block_ts', 'pi_ts_123', [ 'amount' => 50 ] );
		$transient_key = 'srfm_pi_block_ts_pi_ts_123';
		$data          = get_transient( $transient_key );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'created_at', $data );
		$this->assertEquals( 50, $data['amount'] );
	}

	public function test_delete_payment_intent_metadata_success() {
		Payment_Helper::store_payment_intent_metadata( 'block_del', 'pi_del_123', [ 'amount' => 200 ] );
		$result = Payment_Helper::delete_payment_intent_metadata( 'block_del', 'pi_del_123' );
		$this->assertTrue( $result );
		$this->assertFalse( get_transient( 'srfm_pi_block_del_pi_del_123' ) );
	}

	public function test_delete_payment_intent_metadata_empty_params() {
		$this->assertFalse( Payment_Helper::delete_payment_intent_metadata( '', 'pi_123' ) );
		$this->assertFalse( Payment_Helper::delete_payment_intent_metadata( 'block_1', '' ) );
	}

	// --- get_currency_sign_position ---

	public function test_get_currency_sign_position_returns_string() {
		$position = Payment_Helper::get_currency_sign_position();
		$this->assertIsString( $position );
	}

	// --- normalize_amount_by_format (private) ---

	public function test_normalize_amount_us_style() {
		$result = $this->call_private_method( null, 'normalize_amount_by_format', [ '1,234.56', 'us-style' ] );
		$this->assertEquals( 1234.56, $result );
	}

	public function test_normalize_amount_eu_style() {
		$result = $this->call_private_method( null, 'normalize_amount_by_format', [ '1.234,56', 'eu-style' ] );
		$this->assertEquals( 1234.56, $result );
	}

	public function test_normalize_amount_numeric_float_passthrough() {
		$result = $this->call_private_method( null, 'normalize_amount_by_format', [ 99.99, 'us-style' ] );
		$this->assertEquals( 99.99, $result );
	}

	public function test_normalize_amount_string_number_us_style() {
		$result = $this->call_private_method( null, 'normalize_amount_by_format', [ '500', 'us-style' ] );
		$this->assertEquals( 500.0, $result );
	}

	// --- get_block_config_by_name_and_slug (private) ---

	public function test_get_block_config_by_name_and_slug_found() {
		$block_config = [
			'block_a' => [
				'slug'       => 'my-slug',
				'block_name' => 'srfm/dropdown',
			],
		];
		$result = $this->call_private_method( null, 'get_block_config_by_name_and_slug', [ $block_config, 'srfm/dropdown', 'my-slug' ] );
		$this->assertIsArray( $result );
		$this->assertEquals( 'my-slug', $result['slug'] );
	}

	public function test_get_block_config_by_name_and_slug_not_found() {
		$block_config = [
			'block_a' => [
				'slug'       => 'other-slug',
				'block_name' => 'srfm/dropdown',
			],
		];
		$result = $this->call_private_method( null, 'get_block_config_by_name_and_slug', [ $block_config, 'srfm/dropdown', 'nonexistent' ] );
		$this->assertNull( $result );
	}

	// --- get_amount_by_the_config_options (private) ---

	public function test_get_amount_by_config_options_single_select() {
		$block_config = [
			'block_name' => 'srfm/dropdown',
			'options'    => [
				[ 'label' => 'Small', 'value' => '10.00' ],
				[ 'label' => 'Medium', 'value' => '20.00' ],
				[ 'label' => 'Large', 'value' => '30.00' ],
			],
		];
		$result = $this->call_private_method( null, 'get_amount_by_the_config_options', [ 'Medium', $block_config ] );
		$this->assertEquals( 20.00, $result );
	}

	public function test_get_amount_by_config_options_multi_select() {
		$block_config = [
			'block_name'   => 'srfm/dropdown',
			'multi_select' => true,
			'options'      => [
				[ 'label' => 'Small', 'value' => '10.00' ],
				[ 'label' => 'Medium', 'value' => '20.00' ],
				[ 'label' => 'Large', 'value' => '30.00' ],
			],
		];
		$result = $this->call_private_method( null, 'get_amount_by_the_config_options', [ 'Small | Large', $block_config ] );
		$this->assertEquals( 40.00, $result );
	}

	public function test_get_amount_by_config_options_no_match() {
		$block_config = [
			'block_name' => 'srfm/dropdown',
			'options'    => [
				[ 'label' => 'Small', 'value' => '10.00' ],
			],
		];
		$result = $this->call_private_method( null, 'get_amount_by_the_config_options', [ 'XXL', $block_config ] );
		$this->assertNull( $result );
	}

	public function test_get_amount_by_config_options_empty_value() {
		$block_config = [
			'block_name' => 'srfm/dropdown',
			'options'    => [],
		];
		$result = $this->call_private_method( null, 'get_amount_by_the_config_options', [ '', $block_config ] );
		$this->assertNull( $result );
	}

	// --- get_form_submitted_value_by_slug_and_block_name (private) ---

	public function test_get_form_submitted_value_by_slug_and_block_name_dropdown() {
		$form_data = [
			'srfm-dropdown-abc123-lbl-someid-my-dropdown' => 'Option A',
		];
		$result = $this->call_private_method( null, 'get_form_submitted_value_by_slug_and_block_name', [
			'my-dropdown',
			'srfm/dropdown',
			$form_data,
		] );
		$this->assertEquals( 'Option A', $result );
	}

	public function test_get_form_submitted_value_by_slug_and_block_name_not_found() {
		$form_data = [
			'srfm-dropdown-abc123-lbl-someid-other-slug' => 'Option B',
		];
		$result = $this->call_private_method( null, 'get_form_submitted_value_by_slug_and_block_name', [
			'nonexistent',
			'srfm/dropdown',
			$form_data,
		] );
		$this->assertNull( $result );
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( Payment_Helper::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
