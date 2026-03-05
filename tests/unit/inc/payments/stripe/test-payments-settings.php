<?php
/**
 * Class Test_Payments_Settings
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Stripe\Payments_Settings;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;

class Test_Payments_Settings extends TestCase {

	protected $payments_settings;

	protected function setUp(): void {
		parent::setUp();
		$this->payments_settings = Payments_Settings::get_instance();
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

	// ──────────────────────────────────────────────
	// permission_check
	// ──────────────────────────────────────────────

	public function test_permission_check_returns_bool() {
		$result = $this->payments_settings->permission_check();
		$this->assertIsBool( $result );
	}

	public function test_permission_check_requires_manage_options() {
		// In test context without a logged-in admin, should return false.
		wp_set_current_user( 0 );
		$this->assertFalse( $this->payments_settings->permission_check() );
	}

	public function test_permission_check_passes_for_admin() {
		$admin_id = wp_insert_user( [ 'user_login' => 'test_admin_' . wp_rand(), 'user_pass' => 'test', 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );
		$this->assertTrue( $this->payments_settings->permission_check() );
		wp_set_current_user( 0 );
	}

	// ──────────────────────────────────────────────
	// filter_entry_value_for_payment
	// ──────────────────────────────────────────────

	public function test_filter_entry_value_for_payment_returns_original_for_non_payment_block() {
		$args = [
			'field_block_name' => 'srfm-text',
		];
		$result = $this->payments_settings->filter_entry_value_for_payment( 'some value', $args );
		$this->assertSame( 'some value', $result );
	}

	public function test_filter_entry_value_for_payment_returns_original_for_missing_block_name() {
		$result = $this->payments_settings->filter_entry_value_for_payment( 'value', [] );
		$this->assertSame( 'value', $result );
	}

	public function test_filter_entry_value_for_payment_returns_original_for_invalid_payment_id() {
		$args = [
			'field_block_name' => 'srfm-payment',
		];
		$result = $this->payments_settings->filter_entry_value_for_payment( 'not-numeric', $args );
		$this->assertSame( 'not-numeric', $result );
	}

	public function test_filter_entry_value_for_payment_returns_original_for_zero_payment_id() {
		$args = [
			'field_block_name' => 'srfm-payment',
		];
		$result = $this->payments_settings->filter_entry_value_for_payment( 0, $args );
		$this->assertSame( 0, $result );
	}

	public function test_filter_entry_value_for_payment_returns_link_for_valid_payment_id() {
		$args = [
			'field_block_name' => 'srfm-payment',
		];
		$result = $this->payments_settings->filter_entry_value_for_payment( 123, $args );
		$this->assertStringContainsString( '<a', $result );
		$this->assertStringContainsString( 'sureforms_payments', $result );
		$this->assertStringContainsString( '#/payment/123', $result );
		$this->assertStringContainsString( 'View Payment', $result );
	}

	public function test_filter_entry_value_for_payment_escapes_url() {
		$args = [
			'field_block_name' => 'srfm-payment',
		];
		$result = $this->payments_settings->filter_entry_value_for_payment( 999, $args );
		$this->assertStringContainsString( '#/payment/999', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
	}

	// ──────────────────────────────────────────────
	// add_payments_settings
	// ──────────────────────────────────────────────

	public function test_add_payments_settings_adds_payment_settings_key() {
		$settings = [];
		$result   = $this->payments_settings->add_payments_settings( $settings );
		$this->assertArrayHasKey( 'payment_settings', $result );
	}

	public function test_add_payments_settings_preserves_existing_settings() {
		$settings = [ 'existing_key' => 'existing_value' ];
		$result   = $this->payments_settings->add_payments_settings( $settings );
		$this->assertArrayHasKey( 'existing_key', $result );
		$this->assertSame( 'existing_value', $result['existing_key'] );
		$this->assertArrayHasKey( 'payment_settings', $result );
	}

	public function test_add_payments_settings_returns_array() {
		$result = $this->payments_settings->add_payments_settings( [] );
		$this->assertIsArray( $result );
		$this->assertIsArray( $result['payment_settings'] );
	}

	// ──────────────────────────────────────────────
	// disconnect_stripe
	// ──────────────────────────────────────────────

	public function test_disconnect_stripe_returns_rest_response() {
		$result = $this->payments_settings->disconnect_stripe();
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertStringContainsString( 'disconnected', $data['message'] );
	}

	public function test_disconnect_stripe_clears_all_keys() {
		// First set some settings.
		Stripe_Helper::update_all_stripe_settings( [
			'stripe_connected'            => true,
			'stripe_account_id'           => 'acct_test',
			'stripe_account_email'        => 'test@example.com',
			'stripe_live_publishable_key' => 'pk_live_test',
			'stripe_live_secret_key'      => 'sk_live_test',
			'stripe_test_publishable_key' => 'pk_test_test',
			'stripe_test_secret_key'      => 'sk_test_test',
			'webhook_test_secret'         => 'whsec_test',
			'webhook_test_url'            => 'https://example.com/webhook_test',
			'webhook_test_id'             => 'we_test',
			'webhook_live_secret'         => 'whsec_live',
			'webhook_live_url'            => 'https://example.com/webhook_live',
			'webhook_live_id'             => 'we_live',
			'account_name'                => 'Test Account',
		] );

		$this->payments_settings->disconnect_stripe();

		$settings = Stripe_Helper::get_all_stripe_settings();
		$this->assertFalse( $settings['stripe_connected'] );
		$this->assertSame( '', $settings['stripe_account_id'] );
		$this->assertSame( '', $settings['stripe_account_email'] );
		$this->assertSame( '', $settings['stripe_live_publishable_key'] );
		$this->assertSame( '', $settings['stripe_live_secret_key'] );
		$this->assertSame( '', $settings['stripe_test_publishable_key'] );
		$this->assertSame( '', $settings['stripe_test_secret_key'] );
		$this->assertSame( '', $settings['account_name'] );
	}

	// ──────────────────────────────────────────────
	// handle_webhook_creation_request - mode validation
	// ──────────────────────────────────────────────

	public function test_handle_webhook_creation_request_with_invalid_mode_defaults() {
		$request = new \WP_REST_Request( 'POST' );
		$request->set_param( 'mode', 'invalid_mode' );
		$result = $this->payments_settings->handle_webhook_creation_request( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	// ──────────────────────────────────────────────
	// create_webhook_for_mode
	// ──────────────────────────────────────────────

	public function test_create_webhook_for_mode_fails_when_not_connected() {
		$result = $this->payments_settings->create_webhook_for_mode( 'test' );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not connected', $result['message'] );
	}

	public function test_create_webhook_for_mode_fails_with_invalid_mode() {
		// Set stripe as connected.
		Stripe_Helper::update_all_stripe_settings( array_merge(
			Stripe_Helper::get_default_stripe_settings(),
			[ 'stripe_connected' => true ]
		) );
		$result = $this->payments_settings->create_webhook_for_mode( 'invalid' );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid payment mode', $result['message'] );
	}

	public function test_create_webhook_for_mode_fails_without_secret_key() {
		Stripe_Helper::update_all_stripe_settings( array_merge(
			Stripe_Helper::get_default_stripe_settings(),
			[
				'stripe_connected'       => true,
				'stripe_test_secret_key' => '',
			]
		) );
		$result = $this->payments_settings->create_webhook_for_mode( 'test' );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'secret key is missing', $result['message'] );
	}

	// ──────────────────────────────────────────────
	// setup_stripe_webhooks
	// ──────────────────────────────────────────────

	public function test_setup_stripe_webhooks_fails_when_not_connected() {
		Stripe_Helper::update_all_stripe_settings( Stripe_Helper::get_default_stripe_settings() );
		$result = $this->payments_settings->setup_stripe_webhooks();
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not connected', $result['message'] );
	}

	// ──────────────────────────────────────────────
	// delete_stripe_webhooks
	// ──────────────────────────────────────────────

	public function test_delete_stripe_webhooks_fails_when_not_connected() {
		Stripe_Helper::update_all_stripe_settings( Stripe_Helper::get_default_stripe_settings() );
		$result = $this->payments_settings->delete_stripe_webhooks();
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not connected', $result['message'] );
	}
}
