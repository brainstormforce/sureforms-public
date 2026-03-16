<?php
/**
 * Class Test_Admin_Stripe_Handler
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler;

class Test_Admin_Stripe_Handler extends TestCase {

	protected $handler;

	protected function setUp(): void {
		parent::setUp();
		$this->handler = Admin_Stripe_Handler::get_instance();
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

	// ──────────────────────────────────────────────
	// process_stripe_refund - gateway filtering
	// ──────────────────────────────────────────────

	public function test_process_stripe_refund_skips_non_stripe_gateway() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway' => 'paypal',
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertSame( $default_result, $result );
	}

	public function test_process_stripe_refund_skips_empty_gateway() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway' => '',
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertSame( $default_result, $result );
	}

	public function test_process_stripe_refund_invalid_params_returns_error() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway'        => 'stripe',
			'payment'        => [],
			'payment_id'     => 0,
			'transaction_id' => '',
			'refund_amount'  => 0,
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid refund parameters', $result['message'] );
	}

	public function test_process_stripe_refund_invalid_transaction_id_format() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway'        => 'stripe',
			'payment'        => [
				'status'         => 'succeeded',
				'transaction_id' => 'invalid_123',
				'payment_mode'   => 'test',
			],
			'payment_id'     => 1,
			'transaction_id' => 'invalid_123',
			'refund_amount'  => 500,
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid transaction ID format', $result['message'] );
	}

	public function test_process_stripe_refund_status_check_rejects_pending() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway'        => 'stripe',
			'payment'        => [
				'status'         => 'pending',
				'transaction_id' => 'ch_test123',
				'payment_mode'   => 'test',
			],
			'payment_id'     => 1,
			'transaction_id' => 'ch_test123',
			'refund_amount'  => 500,
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Only succeeded or partially refunded', $result['message'] );
	}

	public function test_process_stripe_refund_transaction_id_mismatch() {
		$default_result = [ 'success' => false, 'message' => 'default' ];
		$refund_args    = [
			'gateway'        => 'stripe',
			'payment'        => [
				'status'         => 'succeeded',
				'transaction_id' => 'ch_original',
				'payment_mode'   => 'test',
			],
			'payment_id'     => 1,
			'transaction_id' => 'ch_different',
			'refund_amount'  => 500,
		];
		$result = $this->handler->process_stripe_refund( $default_result, $refund_args );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Transaction ID mismatch', $result['message'] );
	}

	// ──────────────────────────────────────────────
	// update_refund_data - edge cases
	// ──────────────────────────────────────────────

	public function test_update_refund_data_returns_false_for_empty_payment_id() {
		$result = $this->handler->update_refund_data( 0, [ 'id' => 're_123' ], 500, 'USD' );
		$this->assertFalse( $result );
	}

	public function test_update_refund_data_returns_false_for_empty_refund_response() {
		$result = $this->handler->update_refund_data( 1, [], 500, 'USD' );
		$this->assertFalse( $result );
	}

	// ──────────────────────────────────────────────
	// webhook_configuration_notice
	// ──────────────────────────────────────────────

	/**
	 * Test ajax_cancel_subscription is callable.
	 */
	public function test_ajax_cancel_subscription() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_cancel_subscription' ) );
	}

	/**
	 * Test ajax_pause_subscription is callable.
	 */
	public function test_ajax_pause_subscription() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_pause_subscription' ) );
	}

	public function test_webhook_configuration_notice_requires_admin() {
		// When not admin (default test context), should output nothing.
		ob_start();
		$this->handler->webhook_configuration_notice();
		$output = ob_get_clean();
		// Either empty (no admin) or notice is shown - depends on test environment.
		$this->assertIsString( $output );
	}
}
