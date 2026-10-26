<?php
/**
 * Class Test_Payment_Helper
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Payment_Helper;

class Test_Payment_Helper extends SRFM_Unit_Test_Case {

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
		$before = \SRFM\Inc\Helper::get_srfm_option( 'payment_settings', [] );
		$result = Payment_Helper::update_payment_settings( [
			'currency'     => 'EUR',
			'payment_mode' => 'live',
		] );
		$this->assertTrue( $result );
		$this->assertSame( 'EUR', Payment_Helper::get_currency() );

		// No DB rollback here: put the store currency and mode back for later tests.
		\SRFM\Inc\Helper::update_srfm_option( 'payment_settings', $before );
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

	public function test_get_form_submitted_value_by_slug_and_block_name_rejects_repeated_slug() {
		$form_data = [
			'srfm-dropdown-planfake-lbl-UGxhbg-plan' => 'Basic',
			'srfm-dropdown-planreal-lbl-UGxhbg-plan' => 'Premium',
		];
		$result    = $this->call_private_method( null, 'get_form_submitted_value_by_slug_and_block_name', [
			'plan',
			'srfm/dropdown',
			$form_data,
		] );
		$this->assertNull( $result, 'an amount source submitted under two keys has no single value to price against' );
	}

	public function test_get_form_submitted_value_by_slug_and_block_name_matches_slug_exactly() {
		// A different field whose slug merely ends in the source slug is not the source.
		$form_data = [
			'srfm-dropdown-other001-lbl-T3RoZXI-my-plan' => 'Other',
			'srfm-dropdown-planreal-lbl-UGxhbg-plan'     => 'Premium',
		];
		$result    = $this->call_private_method( null, 'get_form_submitted_value_by_slug_and_block_name', [
			'plan',
			'srfm/dropdown',
			$form_data,
		] );
		$this->assertSame( 'Premium', $result );
	}

	public function test_validate_amount_against_config_rejects_repeated_amount_source() {
		$content = '<!-- wp:srfm/dropdown {"block_id":"planreal","slug":"plan","options":[{"label":"Basic","icon":"","value":"1"},{"label":"Premium","icon":"","value":"100"}]} /-->'
			. '<!-- wp:srfm/payment {"block_id":"pay02","slug":"srfm-payment","paymentType":"one-time","amountType":"variable","minimumAmount":1,"variableAmountField":"plan"} /-->';
		$form_id = self::factory()->post->create( [ 'post_type' => 'sureforms_form', 'post_content' => $content ] );
		\SRFM\Inc\Field_Validation::add_block_config( parse_blocks( $content ), $form_id );

		$real = 'srfm-dropdown-planreal-lbl-UGxhbg-plan';
		$fake = 'srfm-dropdown-planfake-lbl-UGxhbg-plan';

		// Baseline: the genuine selection alone is priced from the server-side option config.
		$this->assertTrue( Payment_Helper::validate_amount_against_config( 'pay02', $form_id, [ $real => 'Premium' ], 100.0 )['valid'] );
		$this->assertFalse( Payment_Helper::validate_amount_against_config( 'pay02', $form_id, [ $real => 'Premium' ], 1.0 )['valid'] );

		// Two submitted keys for the amount source: no amount is accepted, whatever the order.
		foreach ( [ [ $fake => 'Basic', $real => 'Premium' ], [ $real => 'Premium', $fake => 'Basic' ] ] as $form_data ) {
			foreach ( [ 1.0, 100.0 ] as $amount ) {
				$result = Payment_Helper::validate_amount_against_config( 'pay02', $form_id, $form_data, $amount );
				$this->assertFalse( $result['valid'], "amount $amount must not validate against a repeated amount source" );
			}
		}

		// Through the submit pipeline order (allowlist first), the repeated source is gone entirely.
		$stripped = \SRFM\Inc\Field_Validation::strip_unknown_field_keys( [ $fake => 'Basic', $real => 'Premium' ], $form_id );
		$this->assertArrayNotHasKey( $real, $stripped );
		$this->assertArrayNotHasKey( $fake, $stripped );
		$this->assertFalse( Payment_Helper::validate_amount_against_config( 'pay02', $form_id, $stripped, 1.0 )['valid'] );
	}

	/**
	 * Keys that read as the amount source in one parser and as something else in another
	 * (a second `-lbl-`, `_` read as a space, a block_id after a later `-lbl-`) must not
	 * survive the allowlist next to the real key, so the priced value is the stored one.
	 */
	public function test_strip_unknown_field_keys_rejects_keys_that_parse_as_the_amount_source() {
		$content = '<!-- wp:srfm/dropdown {"block_id":"planreal","slug":"my_plan","options":[{"label":"Basic","icon":"","value":"1"},{"label":"Premium","icon":"","value":"100"}]} /-->'
			. '<!-- wp:srfm/input {"block_id":"opt01","slug":"notes"} /-->'
			. '<!-- wp:srfm/payment {"block_id":"pay02","slug":"srfm-payment","paymentType":"one-time","amountType":"variable","minimumAmount":1,"variableAmountField":"my_plan"} /-->';
		$form_id = self::factory()->post->create( [ 'post_type' => 'sureforms_form', 'post_content' => $content ] );
		\SRFM\Inc\Field_Validation::add_block_config( parse_blocks( $content ), $form_id );

		$real = 'srfm-dropdown-planreal-lbl-UGxhbg-my_plan';
		$sets = [
			'second -lbl- under an unknown block_id'  => [ 'srfm-dropdown-zz-lbl-UGxhbg-my_plan' => 'Basic', 'srfm-dropdown-planreal-lbl-UGxhbg-my_plan-lbl-x' => 'Premium' ],
			'second -lbl- under an optional field'    => [ $real => 'Basic', 'srfm-input-opt01-lbl-UGxhbg-my_plan-lbl-x' => 'Premium' ],
			'underscore read as a space'              => [ $real => 'Basic', 'srfm-input-opt01-lbl-UGxhbg-my plan' => 'Premium' ],
			'block_id after a second -lbl-'           => [ $real => 'Basic', 'srfm-input-opt01-lbl-UGxhbg-planreal-lbl-x' => 'Premium' ],
		];

		foreach ( $sets as $name => $form_data ) {
			$stripped = \SRFM\Inc\Field_Validation::strip_unknown_field_keys( $form_data, $form_id );
			foreach ( array_keys( $form_data ) as $key ) {
				$this->assertArrayNotHasKey( $key, $stripped, "$name: $key is dropped" );
			}
			$this->assertFalse( Payment_Helper::validate_amount_against_config( 'pay02', $form_id, $stripped, 1.0 )['valid'], "$name: \$1 is rejected" );
		}

		// The genuine selection alone still prices normally.
		$stripped = \SRFM\Inc\Field_Validation::strip_unknown_field_keys( [ $real => 'Premium' ], $form_id );
		$this->assertTrue( Payment_Helper::validate_amount_against_config( 'pay02', $form_id, $stripped, 100.0 )['valid'] );
	}

	// --- validate_payment_amount ---

	public function test_validate_payment_amount_returns_invalid_for_nonexistent_form() {
		$result = Payment_Helper::validate_payment_amount( 1000, 'usd', 999999999, 'block-1' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertFalse( $result['valid'] );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertNotEmpty( $result['message'] );
	}

	public function test_validate_payment_amount_accepts_active_type_parameter() {
		// Signature accepts $active_type for "both" mode resolution; missing form
		// still produces an invalid result, not a fatal error.
		$result = Payment_Helper::validate_payment_amount( 1000, 'usd', 0, 'block-1', 'subscription' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
	}

	// --- verify_payment_intent ---

	public function test_verify_payment_intent_rejects_empty_block_id() {
		$result = Payment_Helper::verify_payment_intent( '', 'pi_test', [ 'form-id' => 1 ] );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
		$this->assertNotEmpty( $result['message'] );
	}

	public function test_verify_payment_intent_rejects_empty_payment_intent_id() {
		$result = Payment_Helper::verify_payment_intent( 'block-1', '', [ 'form-id' => 1 ] );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
	}

	public function test_verify_payment_intent_rejects_missing_form_id() {
		$result = Payment_Helper::verify_payment_intent( 'block-1', 'pi_test', [] );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
	}

	public function test_verify_payment_intent_fails_without_stored_metadata() {
		// No transient stored for this block+intent, so verification fails.
		$result = Payment_Helper::verify_payment_intent( 'block-nonexistent', 'pi_nonexistent', [ 'form-id' => 1 ], 'one-time' );
		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
	}

	public function test_get_submitted_value_by_slug() {
		$form_data = [
			'srfm-input-abc-lbl-RnVsbCBOYW1l-full-name' => 'John',
			'srfm-number-xyz-lbl-UHJpY2U-price'         => '50',
		];

		// Resolves by slug suffix regardless of block type.
		$this->assertSame( '50', Payment_Helper::get_submitted_value_by_slug( 'price', $form_data ) );
		$this->assertSame( 'John', Payment_Helper::get_submitted_value_by_slug( 'full-name', $form_data ) );

		// Missing slug, empty slug, and non-array form data return null.
		$this->assertNull( Payment_Helper::get_submitted_value_by_slug( 'missing', $form_data ) );
		$this->assertNull( Payment_Helper::get_submitted_value_by_slug( '', $form_data ) );
		$this->assertNull( Payment_Helper::get_submitted_value_by_slug( 'price', 'not-an-array' ) );

		// The slug must match exactly: `base-price` is a different field from `price`.
		$this->assertSame( '50', Payment_Helper::get_submitted_value_by_slug( 'price', [ 'srfm-number-aaa-lbl-QmFzZQ-base-price' => '9' ] + $form_data ) );

		// A slug submitted under two keys has no single value.
		$this->assertNull( Payment_Helper::get_submitted_value_by_slug( 'price', [ 'srfm-number-fake-lbl-UHJpY2U-price' => '1' ] + $form_data ) );
	}

	public function test_validate_amount_against_config() {
		// With no resolvable form configuration, validation must fail safe with a structured
		// result (it must never silently accept the amount).
		$result = Payment_Helper::validate_amount_against_config( 'block123', 0, [ 'form-id' => 0 ], 10.0, 'one-time' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertFalse( $result['valid'] );
	}

	public function test_validate_amount_against_config_unrecoverable_source_floor() {
		// A legacy variable-amount form whose amount-source field reference is empty and cannot be
		// recovered (empty post content => refresh_block_config() returns null). The stored config
		// has amount_type 'variable' with no source but a positive minimum amount.
		$form_id = self::factory()->post->create( [ 'post_content' => '' ] );

		update_post_meta(
			$form_id,
			'_srfm_block_config',
			[
				'block123' => [
					'blockName'                        => 'srfm/stripe',
					'block_id'                         => 'block123',
					'amount_type'                      => 'variable',
					'minimum_amount'                   => 50,
					'variable_amount_field'            => '',
					'variable_amount_field_block_name' => '',
				],
			]
		);

		// With a positive minimum floor, a charge at/above the floor is accepted (legacy form keeps
		// working without a re-save) and a charge below the floor is rejected.
		$above = Payment_Helper::validate_amount_against_config( 'block123', $form_id, [ 'form-id' => $form_id ], 100.0 );
		$this->assertTrue( $above['valid'] );

		$below = Payment_Helper::validate_amount_against_config( 'block123', $form_id, [ 'form-id' => $form_id ], 10.0 );
		$this->assertFalse( $below['valid'] );

		// With no positive floor, there is nothing safe to validate against, so it must fail safe
		// and reject (preventing the unauthenticated underpayment bypass from reopening).
		update_post_meta(
			$form_id,
			'_srfm_block_config',
			[
				'block123' => [
					'blockName'                        => 'srfm/stripe',
					'block_id'                         => 'block123',
					'amount_type'                      => 'variable',
					'minimum_amount'                   => 0,
					'variable_amount_field'            => '',
					'variable_amount_field_block_name' => '',
				],
			]
		);

		$no_floor = Payment_Helper::validate_amount_against_config( 'block123', $form_id, [ 'form-id' => $form_id ], 100.0 );
		$this->assertFalse( $no_floor['valid'] );
	}

	/**
	 * A calculation-enabled number amount source whose formula the server can't
	 * recompute (e.g. free-only, no Pro recompute handler) must FAIL SAFE — never
	 * fall back to the client-submitted amount. With minimum_amount = 0 this isolates
	 * the bypass: without the fail-safe, the "name your price" branch would accept
	 * the attacker's matching amount.
	 */
	public function test_validate_amount_against_config_calc_number_fails_safe() {
		$form_id = self::factory()->post->create( [ 'post_content' => '' ] );

		update_post_meta(
			$form_id,
			'_srfm_block_config',
			[
				'pay123' => [
					'block_id'                         => 'pay123',
					'amount_type'                      => 'variable',
					'minimum_amount'                   => 0,
					'variable_amount_field'            => 'price',
					'variable_amount_field_block_name' => 'srfm/number',
				],
				'num456' => [
					'block_id'           => 'num456',
					'block_name'         => 'srfm/number',
					'slug'               => 'price',
					'enableCalculation'  => true,
					'calculationFormula' => '{form:price} * 2',
				],
			]
		);

		// Attacker submits a low value and charges that same low amount. Without the
		// fail-safe (calc-enabled + server amount null) this would be accepted.
		$form_data = [ 'srfm-number-num456-lbl-UHJpY2U-price' => '10' ];
		$result    = Payment_Helper::validate_amount_against_config( 'pay123', $form_id, $form_data, 10.0 );

		$this->assertFalse( $result['valid'] );
	}

	// --- resolve_payment_user ---

	public function test_resolve_payment_user_non_array_returns_zero() {
		$this->assertSame( 0, Payment_Helper::resolve_payment_user( 'not-an-array' ) );
	}

	public function test_resolve_payment_user_empty_payment_returns_zero() {
		$this->assertSame( 0, Payment_Helper::resolve_payment_user( [] ) );
	}

	public function test_resolve_payment_user_guest_unknown_email_returns_zero() {
		// Guest checkout (no entry user_id) with an email matching no WP user resolves to 0.
		$payment = [
			'entry_id'       => 0,
			'customer_email' => 'no-such-user-' . uniqid() . '@example.com',
		];
		$this->assertSame( 0, Payment_Helper::resolve_payment_user( $payment ) );
	}

	public function test_resolve_payment_user_matches_user_by_customer_email() {
		// Falls back to a WP user matching customer_email when there is no entry user_id.
		$user_id = self::factory()->user->create( [ 'user_email' => 'buyer-resolve@example.com' ] );
		$payment = [
			'entry_id'       => 0,
			'customer_email' => 'buyer-resolve@example.com',
		];
		$this->assertSame( $user_id, Payment_Helper::resolve_payment_user( $payment ) );
	}

	// --- build_payment_context ---

	public function test_build_payment_context_non_array_returns_defaults() {
		$context = Payment_Helper::build_payment_context( 'not-an-array' );
		$this->assertIsArray( $context );
		foreach ( [ 'form_id', 'entry_id', 'user_id', 'customer_email', 'type', 'gateway', 'mode' ] as $key ) {
			$this->assertArrayHasKey( $key, $context, "Context missing '{$key}'" );
		}
		$this->assertSame( 0, $context['form_id'] );
		$this->assertSame( 0, $context['entry_id'] );
		$this->assertSame( 0, $context['user_id'] );
		$this->assertSame( '', $context['customer_email'] );
		$this->assertSame( '', $context['type'] );
		$this->assertSame( '', $context['gateway'] );
		$this->assertSame( '', $context['mode'] );
	}

	public function test_build_payment_context_resolves_and_sanitizes_fields() {
		$user_id = self::factory()->user->create( [ 'user_email' => 'buyer-context@example.com' ] );
		$payment = [
			'form_id'        => '42',
			'entry_id'       => 0,
			'customer_email' => 'buyer-context@example.com',
			'type'           => 'subscription',
			'gateway'        => 'stripe',
			'mode'           => 'test',
		];

		$context = Payment_Helper::build_payment_context( $payment );

		// Numeric strings are cast to int, strings sanitized, and user_id resolved via email.
		$this->assertSame( 42, $context['form_id'] );
		$this->assertSame( 0, $context['entry_id'] );
		$this->assertSame( $user_id, $context['user_id'] );
		$this->assertSame( 'buyer-context@example.com', $context['customer_email'] );
		$this->assertSame( 'subscription', $context['type'] );
		$this->assertSame( 'stripe', $context['gateway'] );
		$this->assertSame( 'test', $context['mode'] );
	}

	/**
	 * An unresolved hidden-field amount source (a hidden field whose default is a
	 * non-numeric smart tag, so resolve_server_side_variable_amount() returns null and
	 * there is no Pro recompute handler) must FAIL SAFE when there is no positive
	 * minimum-amount floor. Without the guard the charge would fall through to a zero
	 * floor and accept any amount — the unauthenticated underpayment bypass flagged by
	 * the WordPress.org scanner. With a positive minimum, the documented dynamic-prefill
	 * behavior is preserved: charges at/above the floor are accepted, below are rejected.
	 */
	public function test_validate_amount_against_config_unresolved_hidden_fails_safe() {
		$form_id = self::factory()->post->create( [ 'post_content' => '' ] );

		$config = static function ( $minimum_amount ) {
			return [
				'pay123' => [
					'block_id'                         => 'pay123',
					'amount_type'                      => 'variable',
					'minimum_amount'                   => $minimum_amount,
					'variable_amount_field'            => 'amount',
					'variable_amount_field_block_name' => 'srfm/hidden',
				],
				'hid456' => [
					'block_id'     => 'hid456',
					'block_name'   => 'srfm/hidden',
					'slug'         => 'amount',
					// Non-numeric smart-tag default => resolve_server_side_variable_amount() returns null.
					'defaultValue' => '{get_input:amount}',
				],
			];
		};

		// Submitted hidden value is present (so we pass the "value required" check) but must
		// never be trusted as the price.
		$form_data = [ 'srfm-hidden-hid456-lbl-QW1vdW50-amount' => '10' ];

		// minimum_amount = 0: nothing safe to validate against => reject.
		update_post_meta( $form_id, '_srfm_block_config', $config( 0 ) );
		$no_floor = Payment_Helper::validate_amount_against_config( 'pay123', $form_id, $form_data, 10.0 );
		$this->assertFalse( $no_floor['valid'] );

		// minimum_amount = 50: dynamic-prefill preserved — at/above floor accepted, below rejected.
		update_post_meta( $form_id, '_srfm_block_config', $config( 50 ) );

		$above = Payment_Helper::validate_amount_against_config( 'pay123', $form_id, $form_data, 100.0 );
		$this->assertTrue( $above['valid'] );

		$below = Payment_Helper::validate_amount_against_config( 'pay123', $form_id, $form_data, 10.0 );
		$this->assertFalse( $below['valid'] );
	}

	// --- get_registered_payment_methods / is_payment_field_active / get_required_payment_block_ids ---

	/**
	 * Force a usable payment method so tests do not depend on a connected Stripe account.
	 *
	 * @param array<string,mixed> $methods Registered methods.
	 * @return array<string,mixed>
	 */
	public function force_enabled_payment_method( $methods ) {
		$methods['stripe']['enabled'] = true;

		return $methods;
	}

	public function test_get_registered_payment_methods_respects_enabled_flag() {
		// Nothing connected => no usable method.
		$this->assertSame( [], Payment_Helper::get_registered_payment_methods( [ 'paymentMethods' => [ 'stripe' ] ] ) );

		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		$this->assertArrayHasKey( 'stripe', Payment_Helper::get_registered_payment_methods( [ 'paymentMethods' => [ 'stripe' ] ] ) );

		// A method the block does not enable is never returned.
		$this->assertSame( [], Payment_Helper::get_registered_payment_methods( [ 'paymentMethods' => [ 'unknown-gateway' ] ] ) );

		// Missing attribute defaults to stripe, preserving pre-2.4.0 blocks.
		$this->assertArrayHasKey( 'stripe', Payment_Helper::get_registered_payment_methods( [] ) );

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
	}

	public function test_is_payment_field_active_mirrors_render_requirements() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		// One-time with the email mapping => renders.
		$this->assertTrue( Payment_Helper::is_payment_field_active( [ 'customerEmailField' => 'email' ] ) );

		// No email mapping => markup() returns '' => not active.
		$this->assertFalse( Payment_Helper::is_payment_field_active( [ 'customerEmailField' => '' ] ) );
		$this->assertFalse( Payment_Helper::is_payment_field_active( [] ) );

		// Subscription also needs the name mapping.
		$this->assertFalse(
			Payment_Helper::is_payment_field_active(
				[
					'paymentType'        => 'subscription',
					'customerEmailField' => 'email',
				]
			)
		);
		$this->assertTrue(
			Payment_Helper::is_payment_field_active(
				[
					'paymentType'        => 'subscription',
					'customerEmailField' => 'email',
					'customerNameField'  => 'name',
				]
			)
		);

		// Legacy subscriptionPlan mappings still count.
		$this->assertTrue(
			Payment_Helper::is_payment_field_active(
				[
					'paymentType'       => 'both',
					'subscriptionPlan'  => [
						'customer_email' => 'email',
						'customer_name'  => 'name',
					],
				]
			)
		);

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		// With no usable payment method nothing is active.
		$this->assertFalse( Payment_Helper::is_payment_field_active( [ 'customerEmailField' => 'email' ] ) );
		$this->assertFalse( Payment_Helper::is_payment_field_active( 'not-an-array' ) );
	}

	public function test_get_required_payment_block_ids_derives_set_from_form() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		$attrs = static function ( $block_id, $email_field = 'email' ) {
			return wp_json_encode(
				[
					'block_id'           => $block_id,
					'paymentType'        => 'one-time',
					'customerEmailField' => $email_field,
				]
			);
		};

		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Required Payment Blocks Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/payment ' . $attrs( 'active01' ) . ' /-->'
					. '<!-- wp:group --><!-- wp:srfm/payment ' . $attrs( 'nested02' ) . ' /--><!-- /wp:group -->'
					. '<!-- wp:srfm/payment ' . $attrs( 'broken03', '' ) . ' /-->'
					. '<!-- wp:srfm/input {"block_id":"plain04"} /-->',
			]
		);

		$required = Payment_Helper::get_required_payment_block_ids( $form_id );

		$this->assertContains( 'active01', $required );
		$this->assertContains( 'nested02', $required, 'Payment blocks inside containers must be found.' );
		$this->assertNotContains( 'broken03', $required, 'A block that renders nothing must not be required.' );
		$this->assertNotContains( 'plain04', $required );

		// An actionable conditional-logic rule can hide the field client-side, so that
		// block is not required — but only while something is there to evaluate it, and
		// only for a rule that carries at least one condition.
		$actionable = [
			[
				'active01' => [
					'action' => 'show',
					'logic'  => [ [ [ 'field' => 'email', 'operator' => '===', 'value' => 'yes' ] ] ],
				],
			],
		];

		// Unregistered meta (no evaluator present) => the rule buys nothing.
		update_post_meta( $form_id, '_srfm_conditional_logic', $actionable );
		$this->assertContains( 'active01', Payment_Helper::get_required_payment_block_ids( $form_id ) );

		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_conditional_logic',
			[
				'type'   => 'array',
				'single' => true,
			]
		);

		$this->assertNotContains( 'active01', Payment_Helper::get_required_payment_block_ids( $form_id ) );
		$this->assertContains( 'nested02', Payment_Helper::get_required_payment_block_ids( $form_id ) );

		// A rule with no conditions can never match, so the block stays required.
		update_post_meta( $form_id, '_srfm_conditional_logic', [ [ 'active01' => [ 'action' => 'show' ] ] ] );
		$this->assertContains( 'active01', Payment_Helper::get_required_payment_block_ids( $form_id ) );

		unregister_post_meta( SRFM_FORMS_POST_TYPE, '_srfm_conditional_logic' );
		delete_post_meta( $form_id, '_srfm_conditional_logic' );

		// Missing form / empty content => nothing required.
		$this->assertSame( [], Payment_Helper::get_required_payment_block_ids( 999999 ) );
		$this->assertSame( [], Payment_Helper::get_required_payment_block_ids( 0 ) );

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	public function test_get_required_payment_block_ids_expands_reusable_patterns() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		remove_all_actions( 'wp_insert_post_data' );

		$attrs = wp_json_encode(
			[
				'block_id'           => 'inpattern',
				'paymentType'        => 'one-time',
				'customerEmailField' => 'email',
			]
		);

		$pattern_id = wp_insert_post(
			[
				'post_title'   => 'Reusable payment',
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/payment ' . $attrs . ' /-->',
			]
		);
		$form_id    = wp_insert_post(
			[
				'post_title'   => 'Pattern Payment Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:block {"ref":' . $pattern_id . '} /-->',
			]
		);

		$this->assertContains(
			'inpattern',
			Payment_Helper::get_required_payment_block_ids( $form_id ),
			'A payment block that renders from inside a synced pattern must still be required.'
		);

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
		wp_delete_post( $pattern_id, true );
	}

	public function test_get_payment_strings_has_payment_required_message() {
		$strings = Payment_Helper::get_payment_strings();
		$this->assertArrayHasKey( 'payment_required', $strings );
		$this->assertNotEmpty( $strings['payment_required'] );
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( Payment_Helper::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
