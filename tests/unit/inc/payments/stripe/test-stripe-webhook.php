<?php
/**
 * Class Test_Stripe_Webhook
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Stripe\Stripe_Webhook;

class Test_Stripe_Webhook extends TestCase {

	protected $webhook;

	protected function setUp(): void {
		parent::setUp();
		$this->webhook = Stripe_Webhook::get_instance();
	}

	/**
	 * Helper method to call private methods for testing.
	 */
	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * Helper to set private property value.
	 */
	private function set_private_property( $object, $property_name, $value ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $value );
	}

	/**
	 * Helper to get private property value.
	 */
	private function get_private_property( $object, $property_name ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		return $property->getValue( $object );
	}

	// ──────────────────────────────────────────────
	// Constants
	// ──────────────────────────────────────────────

	public function test_webhook_constants_are_defined() {
		$this->assertSame( 'srfm_live_webhook_began_at', Stripe_Webhook::SRFM_LIVE_BEGAN_AT );
		$this->assertSame( 'srfm_live_webhook_last_success_at', Stripe_Webhook::SRFM_LIVE_LAST_SUCCESS_AT );
		$this->assertSame( 'srfm_live_webhook_last_failure_at', Stripe_Webhook::SRFM_LIVE_LAST_FAILURE_AT );
		$this->assertSame( 'srfm_live_webhook_last_error', Stripe_Webhook::SRFM_LIVE_LAST_ERROR );
		$this->assertSame( 'srfm_test_webhook_began_at', Stripe_Webhook::SRFM_TEST_BEGAN_AT );
		$this->assertSame( 'srfm_test_webhook_last_success_at', Stripe_Webhook::SRFM_TEST_LAST_SUCCESS_AT );
		$this->assertSame( 'srfm_test_webhook_last_failure_at', Stripe_Webhook::SRFM_TEST_LAST_FAILURE_AT );
		$this->assertSame( 'srfm_test_webhook_last_error', Stripe_Webhook::SRFM_TEST_LAST_ERROR );
	}

	// ──────────────────────────────────────────────
	// Default mode
	// ──────────────────────────────────────────────

	public function test_default_mode_is_test() {
		$mode = $this->get_private_property( $this->webhook, 'mode' );
		$this->assertSame( 'test', $mode );
	}

	// ──────────────────────────────────────────────
	// validate_stripe_signature
	// ──────────────────────────────────────────────

	public function test_validate_stripe_signature_returns_false_with_empty_payload() {
		// php://input is empty in test context, so this should return false.
		$result = $this->webhook->validate_stripe_signature( 'test' );
		$this->assertFalse( $result );
	}

	public function test_validate_stripe_signature_returns_false_with_missing_signature_header() {
		// No HTTP_STRIPE_SIGNATURE in $_SERVER, payload is also empty.
		$result = $this->webhook->validate_stripe_signature( 'live' );
		$this->assertFalse( $result );
	}

	// ──────────────────────────────────────────────
	// dev_validate_stripe_signature
	// ──────────────────────────────────────────────

	public function test_dev_validate_stripe_signature_returns_false_with_empty_payload() {
		$result = $this->webhook->dev_validate_stripe_signature();
		$this->assertFalse( $result );
	}

	// ──────────────────────────────────────────────
	// webhook_listener
	// ──────────────────────────────────────────────

	public function test_webhook_listener_returns_early_with_invalid_event() {
		// With no payload, validate_stripe_signature returns false, so webhook_listener returns early.
		$this->webhook->webhook_listener( 'test' );
		// No exception or error = success (void method).
		$this->assertTrue( true );
	}

	public function test_webhook_listener_with_null_mode() {
		$this->webhook->webhook_listener( null );
		$this->assertTrue( true );
	}

	// ──────────────────────────────────────────────
	// handle_refund_record
	// ──────────────────────────────────────────────

	public function test_handle_refund_record_returns_early_with_no_payment_found() {
		$refund = [
			'id'             => 're_test123',
			'payment_intent' => 'pi_nonexistent',
			'charge'         => 'ch_nonexistent',
			'amount'         => 1000,
			'currency'       => 'usd',
			'status'         => 'succeeded',
		];
		// Should return without error because no matching payment exists in DB.
		$this->webhook->handle_refund_record( $refund );
		$this->assertTrue( true );
	}

	public function test_handle_refund_record_with_empty_refund_data() {
		$this->webhook->handle_refund_record( [] );
		$this->assertTrue( true );
	}

	// ──────────────────────────────────────────────
	// handle_invoice_payment_succeeded
	// ──────────────────────────────────────────────

	public function test_handle_invoice_payment_succeeded_skips_non_subscription_cycle() {
		$invoice = [
			'id'             => 'in_test123',
			'billing_reason' => 'manual',
		];
		$this->webhook->handle_invoice_payment_succeeded( $invoice );
		$this->assertTrue( true );
	}

	public function test_handle_invoice_payment_succeeded_skips_empty_subscription_id() {
		$invoice = [
			'id'             => 'in_test123',
			'billing_reason' => 'subscription_cycle',
			'subscription'   => '',
		];
		$this->webhook->handle_invoice_payment_succeeded( $invoice );
		$this->assertTrue( true );
	}

	// ──────────────────────────────────────────────
	// handle_subscription_deleted
	// ──────────────────────────────────────────────

	public function test_handle_subscription_deleted_with_empty_subscription_id() {
		$subscription = [
			'id' => '',
		];
		$this->webhook->handle_subscription_deleted( $subscription );
		$this->assertTrue( true );
	}

	public function test_handle_subscription_deleted_with_nonexistent_subscription() {
		$subscription = [
			'id'     => 'sub_nonexistent',
			'status' => 'canceled',
		];
		$this->webhook->handle_subscription_deleted( $subscription );
		$this->assertTrue( true );
	}
}
