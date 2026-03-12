<?php
/**
 * Class Test_Front_End_Payments
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Front_End;

class Test_Front_End_Payments extends TestCase {

	protected $front_end;

	protected function setUp(): void {
		parent::setUp();
		$this->front_end = Front_End::get_instance();
	}

	// --- show_options_values ---

	public function test_show_options_values_returns_true_when_value_is_true() {
		$result = $this->front_end->show_options_values( false, true );
		$this->assertTrue( $result );
	}

	public function test_show_options_values_returns_default_when_value_is_false() {
		$result = $this->front_end->show_options_values( true, false );
		$this->assertTrue( $result );
	}

	public function test_show_options_values_returns_false_default_when_value_is_false() {
		$result = $this->front_end->show_options_values( false, false );
		$this->assertFalse( $result );
	}

	// --- skip_payment_fields_from_all_data ---

	public function test_skip_payment_fields_from_all_data_skips_payment_block() {
		$result = $this->front_end->skip_payment_fields_from_all_data( true, [ 'block_name' => 'srfm-payment' ] );
		$this->assertFalse( $result );
	}

	public function test_skip_payment_fields_from_all_data_keeps_non_payment_block() {
		$result = $this->front_end->skip_payment_fields_from_all_data( true, [ 'block_name' => 'srfm-text' ] );
		$this->assertTrue( $result );
	}

	public function test_skip_payment_fields_from_all_data_missing_block_name() {
		$result = $this->front_end->skip_payment_fields_from_all_data( true, [] );
		$this->assertTrue( $result );
	}

	// --- skip_payment_fields_from_submission_data ---

	public function test_skip_payment_fields_from_submission_data_skips_payment_key() {
		$result = $this->front_end->skip_payment_fields_from_submission_data( false, [ 'key' => 'srfm-payment-abc-lbl-test' ] );
		$this->assertTrue( $result );
	}

	public function test_skip_payment_fields_from_submission_data_keeps_non_payment_key() {
		$result = $this->front_end->skip_payment_fields_from_submission_data( false, [ 'key' => 'srfm-text-abc-lbl-test' ] );
		$this->assertFalse( $result );
	}

	public function test_skip_payment_fields_from_submission_data_invalid_args() {
		$result = $this->front_end->skip_payment_fields_from_submission_data( false, 'not_array' );
		$this->assertFalse( $result );
	}

	public function test_skip_payment_fields_from_submission_data_missing_key() {
		$result = $this->front_end->skip_payment_fields_from_submission_data( false, [ 'slug' => 'something' ] );
		$this->assertFalse( $result );
	}

	// --- skip_payment_fields_from_sample_data ---

	public function test_skip_payment_fields_from_sample_data_skips_payment_block() {
		$result = $this->front_end->skip_payment_fields_from_sample_data( false, [ 'block_name' => 'srfm/payment' ] );
		$this->assertTrue( $result );
	}

	public function test_skip_payment_fields_from_sample_data_keeps_non_payment_block() {
		$result = $this->front_end->skip_payment_fields_from_sample_data( false, [ 'block_name' => 'srfm/text' ] );
		$this->assertFalse( $result );
	}

	public function test_skip_payment_fields_from_sample_data_invalid_args() {
		$result = $this->front_end->skip_payment_fields_from_sample_data( false, 'not_array' );
		$this->assertFalse( $result );
	}

	public function test_skip_payment_fields_from_sample_data_missing_block_name() {
		$result = $this->front_end->skip_payment_fields_from_sample_data( false, [] );
		$this->assertFalse( $result );
	}

	// --- prepare_cancel_at ---

	public function test_prepare_cancel_at_with_ongoing_returns_null() {
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 'ongoing',
			'subscriptionInterval'      => 'month',
		] );
		$this->assertNull( $result );
	}

	public function test_prepare_cancel_at_with_zero_cycles_returns_null() {
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 0,
			'subscriptionInterval'      => 'month',
		] );
		$this->assertNull( $result );
	}

	public function test_prepare_cancel_at_with_empty_cycles_returns_null() {
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => '',
			'subscriptionInterval'      => 'month',
		] );
		$this->assertNull( $result );
	}

	public function test_prepare_cancel_at_with_negative_cycles_returns_null() {
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => -5,
			'subscriptionInterval'      => 'month',
		] );
		$this->assertNull( $result );
	}

	public function test_prepare_cancel_at_with_invalid_interval_returns_null() {
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 3,
			'subscriptionInterval'      => 'century',
		] );
		$this->assertNull( $result );
	}

	public function test_prepare_cancel_at_monthly_3_cycles() {
		$before = time();
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 3,
			'subscriptionInterval'      => 'month',
		] );
		$expected = strtotime( '+3 months', $before );
		$this->assertNotNull( $result );
		$this->assertIsInt( $result );
		// Allow 2 seconds tolerance for test execution time.
		$this->assertLessThanOrEqual( 2, abs( $result - $expected ) );
	}

	public function test_prepare_cancel_at_daily_7_cycles() {
		$before = time();
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 7,
			'subscriptionInterval'      => 'day',
		] );
		$expected = strtotime( '+7 days', $before );
		$this->assertNotNull( $result );
		$this->assertLessThanOrEqual( 2, abs( $result - $expected ) );
	}

	public function test_prepare_cancel_at_weekly_4_cycles() {
		$before = time();
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 4,
			'subscriptionInterval'      => 'week',
		] );
		$expected = strtotime( '+4 weeks', $before );
		$this->assertNotNull( $result );
		$this->assertLessThanOrEqual( 2, abs( $result - $expected ) );
	}

	public function test_prepare_cancel_at_yearly_2_cycles() {
		$before = time();
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 2,
			'subscriptionInterval'      => 'year',
		] );
		$expected = strtotime( '+2 years', $before );
		$this->assertNotNull( $result );
		$this->assertLessThanOrEqual( 2, abs( $result - $expected ) );
	}

	public function test_prepare_cancel_at_quarter_2_cycles() {
		$before = time();
		$result = $this->front_end->prepare_cancel_at( [
			'subscriptionBillingCycles' => 2,
			'subscriptionInterval'      => 'quarter',
		] );
		$expected = strtotime( '+6 months', $before );
		$this->assertNotNull( $result );
		$this->assertLessThanOrEqual( 2, abs( $result - $expected ) );
	}

	// --- validate_payment_fields ---

	public function test_validate_payment_fields_empty_data_returns_same() {
		$result = $this->front_end->validate_payment_fields( [] );
		$this->assertEquals( [], $result );
	}

	public function test_validate_payment_fields_non_array_returns_same() {
		$result = $this->front_end->validate_payment_fields( 'invalid' );
		$this->assertEquals( 'invalid', $result );
	}

	public function test_validate_payment_fields_no_payment_fields_returns_unchanged() {
		$form_data = [
			'text-lbl-field-name'  => 'John',
			'email-lbl-field-mail' => 'john@example.com',
		];
		$result = $this->front_end->validate_payment_fields( $form_data );
		$this->assertEquals( $form_data, $result );
	}

	// --- add_payment_entry_for_linking ---

	public function test_add_payment_entry_for_linking_accepts_valid_entry() {
		$entry = [
			'payment_id' => 'pi_test_123',
			'block_id'   => 'block_abc',
			'form_id'    => 42,
		];
		// Should not throw.
		$this->front_end->add_payment_entry_for_linking( $entry );
		$this->assertTrue( true );
	}

	public function test_add_payment_entry_for_linking_ignores_empty() {
		// Empty array should be silently ignored.
		$this->front_end->add_payment_entry_for_linking( [] );
		$this->assertTrue( true );
	}

	// --- extract_customer_data (private) ---

	public function test_extract_customer_data_with_full_data() {
		$result = $this->call_private_method( $this->front_end, 'extract_customer_data', [
			[
				'name'       => 'John Doe',
				'email'      => 'john@example.com',
				'customerId' => 'cus_123',
			],
		] );
		$this->assertEquals( 'John Doe', $result['name'] );
		$this->assertEquals( 'john@example.com', $result['email'] );
		$this->assertEquals( 'cus_123', $result['customer_id'] );
	}

	public function test_extract_customer_data_with_empty_data() {
		$result = $this->call_private_method( $this->front_end, 'extract_customer_data', [ [] ] );
		$this->assertEquals( '', $result['name'] );
		$this->assertEquals( '', $result['email'] );
		$this->assertEquals( '', $result['customer_id'] );
	}

	// --- get_user_ip (private) ---

	public function test_get_user_ip_fallback() {
		// Without any SERVER vars set, should fallback to 127.0.0.1.
		$original_remote = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
		unset( $_SERVER['REMOTE_ADDR'] );
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		unset( $_SERVER['HTTP_X_REAL_IP'] );
		unset( $_SERVER['HTTP_CLIENT_IP'] );

		$result = $this->call_private_method( $this->front_end, 'get_user_ip', [] );
		$this->assertEquals( '127.0.0.1', $result );

		// Restore.
		if ( null !== $original_remote ) {
			$_SERVER['REMOTE_ADDR'] = $original_remote;
		}
	}

	// --- verify_stripe_payment ---

	public function test_verify_stripe_payment_subscription_type_empty_subscription_id() {
		$result = $this->front_end->verify_stripe_payment(
			[ 'subscriptionId' => '', 'blockId' => 'b1', 'paymentType' => 'stripe-subscription' ],
			'',
			'b1',
			[ 'form-id' => 1 ],
			'stripe-subscription'
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
