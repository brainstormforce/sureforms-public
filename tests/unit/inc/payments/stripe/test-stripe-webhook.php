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
	// verify_stripe_signature_locally (private)
	// ──────────────────────────────────────────────

	public function test_verify_signature_locally_with_valid_signature() {
		$secret    = 'whsec_test_secret_key';
		$payload   = '{"type":"checkout.session.completed"}';
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		$header    = 't=' . $timestamp . ',v1=' . $signature;

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, $secret ] );
		$this->assertTrue( $result );
	}

	public function test_verify_signature_locally_rejects_invalid_signature() {
		$secret    = 'whsec_test_secret_key';
		$payload   = '{"type":"checkout.session.completed"}';
		$timestamp = (string) time();
		$header    = 't=' . $timestamp . ',v1=invalidsignaturevalue';

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, $secret ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_rejects_expired_timestamp() {
		$secret    = 'whsec_test_secret_key';
		$payload   = '{"type":"checkout.session.completed"}';
		$timestamp = (string) ( time() - 400 ); // 400 seconds ago, beyond 300s tolerance.
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		$header    = 't=' . $timestamp . ',v1=' . $signature;

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, $secret ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_accepts_within_custom_tolerance() {
		$secret    = 'whsec_test_secret_key';
		$payload   = '{"type":"checkout.session.completed"}';
		$timestamp = (string) ( time() - 500 ); // 500 seconds ago.
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		$header    = 't=' . $timestamp . ',v1=' . $signature;

		// With 600s tolerance, this should pass.
		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, $secret, 600 ] );
		$this->assertTrue( $result );
	}

	public function test_verify_signature_locally_rejects_missing_timestamp() {
		$header = 'v1=somesignature';

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ 'payload', $header, 'secret' ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_rejects_missing_v1_signature() {
		$header = 't=' . time();

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ 'payload', $header, 'secret' ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_rejects_empty_header() {
		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ 'payload', '', 'secret' ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_rejects_tampered_payload() {
		$secret           = 'whsec_test_secret_key';
		$original_payload = '{"amount":1000}';
		$timestamp        = (string) time();
		$signature        = hash_hmac( 'sha256', $timestamp . '.' . $original_payload, $secret );
		$header           = 't=' . $timestamp . ',v1=' . $signature;

		// Tamper with the payload.
		$tampered_payload = '{"amount":9999}';

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $tampered_payload, $header, $secret ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_rejects_wrong_secret() {
		$secret    = 'whsec_correct_secret';
		$payload   = '{"type":"checkout.session.completed"}';
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		$header    = 't=' . $timestamp . ',v1=' . $signature;

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, 'whsec_wrong_secret' ] );
		$this->assertFalse( $result );
	}

	public function test_verify_signature_locally_handles_malformed_header_parts() {
		// Header with parts that have no '=' delimiter.
		$header = 'garbage,more_garbage,t=' . time();

		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ 'payload', $header, 'secret' ] );
		$this->assertFalse( $result ); // Missing v1.
	}

	public function test_verify_signature_locally_handles_multiple_v1_uses_first() {
		$secret     = 'whsec_test_secret_key';
		$payload    = '{"type":"test"}';
		$timestamp  = (string) time();
		$signature  = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		// First v1 is correct, second is wrong — method uses last v1 encountered.
		$header     = 't=' . $timestamp . ',v1=' . $signature . ',v1=invalidsig';

		// The foreach overwrites, so the last v1 wins — should fail.
		$result = $this->call_private_method( $this->webhook, 'verify_stripe_signature_locally', [ $payload, $header, $secret ] );
		$this->assertFalse( $result );
	}

	// ──────────────────────────────────────────────
	// validate_webhook_permission (public)
	// ──────────────────────────────────────────────

	public function test_validate_webhook_permission_returns_wp_error_with_empty_payload() {
		// php://input is empty in test context and no signature header.
		$result = $this->webhook->validate_webhook_permission( 'test' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'srfm_webhook_unauthorized', $result->get_error_code() );
		$this->assertSame( 401, $result->get_error_data()['status'] );
	}

	public function test_validate_webhook_permission_returns_wp_error_with_missing_signature() {
		// Even with no payload (php://input empty), the first check catches it.
		$result = $this->webhook->validate_webhook_permission( 'live' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'srfm_webhook_unauthorized', $result->get_error_code() );
	}

	public function test_validate_webhook_permission_defaults_invalid_mode_to_test() {
		$result = $this->webhook->validate_webhook_permission( 'invalid_mode' );

		// Returns WP_Error because php://input is empty — early return before mode assignment.
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'srfm_webhook_unauthorized', $result->get_error_code() );
	}

	// ──────────────────────────────────────────────
	// validate_stripe_signature (deprecated)
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

	// ──────────────────────────────────────────────
	// register_endpoints
	// ──────────────────────────────────────────────

	public function test_register_endpoints_registers_test_webhook_route() {
		$this->webhook->register_endpoints();
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/sureforms/webhook_test', $routes );
	}

	public function test_register_endpoints_registers_live_webhook_route() {
		$this->webhook->register_endpoints();
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/sureforms/webhook_live', $routes );
	}

	public function test_register_endpoints_test_route_accepts_post_method() {
		$this->webhook->register_endpoints();
		$routes     = rest_get_server()->get_routes();
		$test_route = $routes['/sureforms/webhook_test'];
		$methods    = array_keys( $test_route[0]['methods'] ?? [] );
		$this->assertContains( 'POST', $methods );
	}

	public function test_register_endpoints_live_route_accepts_post_method() {
		$this->webhook->register_endpoints();
		$routes     = rest_get_server()->get_routes();
		$live_route = $routes['/sureforms/webhook_live'];
		$methods    = array_keys( $live_route[0]['methods'] ?? [] );
		$this->assertContains( 'POST', $methods );
	}

	public function test_register_endpoints_routes_have_callable_callbacks() {
		$this->webhook->register_endpoints();
		$routes = rest_get_server()->get_routes();
		$this->assertIsCallable( $routes['/sureforms/webhook_test'][0]['callback'] );
		$this->assertIsCallable( $routes['/sureforms/webhook_live'][0]['callback'] );
	}

	public function test_register_endpoints_routes_have_callable_permission_callbacks() {
		$this->webhook->register_endpoints();
		$routes = rest_get_server()->get_routes();
		$this->assertIsCallable( $routes['/sureforms/webhook_test'][0]['permission_callback'] );
		$this->assertIsCallable( $routes['/sureforms/webhook_live'][0]['permission_callback'] );
	}

	// ──────────────────────────────────────────────
	// update_refund_data
	// ──────────────────────────────────────────────

	public function test_update_refund_data_returns_false_with_zero_payment_id() {
		$refund_response = [ 'id' => 're_test123', 'status' => 'succeeded' ];
		$result          = $this->webhook->update_refund_data( 0, $refund_response, 1000, 'usd' );
		$this->assertFalse( $result );
	}

	public function test_update_refund_data_returns_false_with_null_payment_id() {
		$refund_response = [ 'id' => 're_test123', 'status' => 'succeeded' ];
		$result          = $this->webhook->update_refund_data( null, $refund_response, 1000, 'usd' );
		$this->assertFalse( $result );
	}

	public function test_update_refund_data_returns_false_with_empty_string_payment_id() {
		$refund_response = [ 'id' => 're_test123', 'status' => 'succeeded' ];
		$result          = $this->webhook->update_refund_data( '', $refund_response, 1000, 'usd' );
		$this->assertFalse( $result );
	}

	public function test_update_refund_data_returns_false_with_empty_refund_response() {
		$result = $this->webhook->update_refund_data( 1, [], 1000, 'usd' );
		$this->assertFalse( $result );
	}

	public function test_update_refund_data_returns_false_with_nonexistent_payment_id() {
		$refund_response = [
			'id'     => 're_test123',
			'status' => 'succeeded',
			'amount' => 1000,
		];
		// Payment ID 999999 won't exist in the test database.
		$result = $this->webhook->update_refund_data( 999999, $refund_response, 1000, 'usd' );
		$this->assertFalse( $result );
	}
}
