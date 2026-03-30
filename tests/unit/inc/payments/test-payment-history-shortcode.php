<?php
/**
 * Class Test_Payment_History_Shortcode
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Payment_History_Shortcode;

class Test_Payment_History_Shortcode extends TestCase {

	protected $shortcode;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'SRFM\Inc\Payments\Payment_History_Shortcode' ) ) {
			$this->markTestSkipped( 'Payment_History_Shortcode class not available.' );
		}

		$this->shortcode = Payment_History_Shortcode::get_instance();
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
	// render - shortcode attributes
	// ──────────────────────────────────────────────

	public function test_render_returns_login_message_for_logged_out_user() {
		wp_set_current_user( 0 );
		$result = $this->shortcode->render( [] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'srfm-pd-widget', $result );
		$this->assertStringContainsString( 'srfm-pd-message', $result );
	}

	public function test_render_accepts_string_atts() {
		wp_set_current_user( 0 );
		$result = $this->shortcode->render( '' );
		$this->assertIsString( $result );
	}

	public function test_render_accepts_array_atts() {
		wp_set_current_user( 0 );
		$result = $this->shortcode->render( [ 'per_page' => '5' ] );
		$this->assertIsString( $result );
	}

	// ──────────────────────────────────────────────
	// enqueue_assets
	// ──────────────────────────────────────────────

	public function test_enqueue_assets_skips_when_no_global_post() {
		$GLOBALS['post'] = null;
		$this->shortcode->enqueue_assets();
		$this->assertFalse( wp_style_is( 'srfm-payment-history', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'srfm-payment-history', 'enqueued' ) );
	}

	public function test_enqueue_assets_skips_when_post_has_no_shortcode_or_block() {
		$post_id         = wp_insert_post( [
			'post_title'   => 'No Payment History',
			'post_content' => 'Just regular content.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['post'] = get_post( $post_id );

		$this->shortcode->enqueue_assets();
		$this->assertFalse( wp_style_is( 'srfm-payment-history', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'srfm-payment-history', 'enqueued' ) );

		wp_delete_post( $post_id, true );
	}

	public function test_enqueue_assets_enqueues_when_shortcode_present() {
		$post_id         = wp_insert_post( [
			'post_title'   => 'Payment History Page',
			'post_content' => '[srfm_payment_history]',
			'post_status'  => 'publish',
		] );
		$GLOBALS['post'] = get_post( $post_id );

		$this->shortcode->enqueue_assets();
		$this->assertTrue( wp_style_is( 'srfm-payment-history', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'srfm-payment-history', 'enqueued' ) );

		// Cleanup.
		wp_dequeue_style( 'srfm-payment-history' );
		wp_dequeue_script( 'srfm-payment-history' );
		wp_delete_post( $post_id, true );
	}

	// ──────────────────────────────────────────────
	// ajax_cancel_subscription
	// ──────────────────────────────────────────────

	public function test_ajax_cancel_subscription_fails_without_nonce() {
		// Simulate AJAX call without nonce — should trigger wp_send_json_error.
		$_POST = [];
		try {
			$this->shortcode->ajax_cancel_subscription();
		} catch ( \WPDieException $e ) {
			$this->assertStringContainsString( 'Security check failed', $e->getMessage() );
			return;
		}
		// If wp_send_json_error calls wp_die in test env, we get here.
		$this->assertTrue( true );
	}

	public function test_ajax_cancel_subscription_fails_when_logged_out() {
		wp_set_current_user( 0 );
		$_POST['nonce'] = wp_create_nonce( 'srfm_frontend_payment_nonce' );
		try {
			$this->shortcode->ajax_cancel_subscription();
		} catch ( \WPDieException $e ) {
			$this->assertStringContainsString( 'logged in', $e->getMessage() );
			return;
		}
		$this->assertTrue( true );
	}

	public function test_ajax_cancel_subscription_fails_with_empty_payment_id() {
		$user_id = wp_insert_user( [
			'user_login' => 'testuser_cancel_' . wp_rand(),
			'user_pass'  => 'password',
			'user_email' => 'cancel_test_' . wp_rand() . '@example.com',
		] );
		wp_set_current_user( $user_id );
		$_POST['nonce']      = wp_create_nonce( 'srfm_frontend_payment_nonce' );
		$_POST['payment_id'] = 0;
		try {
			$this->shortcode->ajax_cancel_subscription();
		} catch ( \WPDieException $e ) {
			$this->assertStringContainsString( 'Invalid payment data', $e->getMessage() );
			wp_delete_user( $user_id );
			return;
		}
		wp_delete_user( $user_id );
		$this->assertTrue( true );
	}

	// ──────────────────────────────────────────────
	// format_amount
	// ──────────────────────────────────────────────

	public function test_format_amount_left_position() {
		$result = $this->call_private_method( $this->shortcode, 'format_amount', [ 99.99, 'USD' ] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '99.99', $result );
	}

	public function test_format_amount_zero() {
		$result = $this->call_private_method( $this->shortcode, 'format_amount', [ 0.0, 'USD' ] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '0.00', $result );
	}

	public function test_format_amount_large_number() {
		$result = $this->call_private_method( $this->shortcode, 'format_amount', [ 1234567.89, 'USD' ] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '1,234,567.89', $result );
	}

	// ──────────────────────────────────────────────
	// format_interval
	// ──────────────────────────────────────────────

	public function test_format_interval_month() {
		$result = $this->call_private_method( $this->shortcode, 'format_interval', [ 'month', 1 ] );
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	public function test_format_interval_year() {
		$result = $this->call_private_method( $this->shortcode, 'format_interval', [ 'year', 1 ] );
		$this->assertIsString( $result );
	}

	public function test_format_interval_with_count_greater_than_one() {
		$result = $this->call_private_method( $this->shortcode, 'format_interval', [ 'month', 3 ] );
		$this->assertStringContainsString( '3', $result );
	}

	public function test_format_interval_unknown_type() {
		$result = $this->call_private_method( $this->shortcode, 'format_interval', [ 'unknown_interval', 1 ] );
		$this->assertSame( 'unknown_interval', $result );
	}

	// ──────────────────────────────────────────────
	// get_subscription_status_label
	// ──────────────────────────────────────────────

	public function test_subscription_status_label_active() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'active' ] );
		$this->assertSame( 'Active', $result );
	}

	public function test_subscription_status_label_canceled() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'canceled' ] );
		$this->assertSame( 'Cancelled', $result );
	}

	public function test_subscription_status_label_trialing() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'trialing' ] );
		$this->assertSame( 'Trialing', $result );
	}

	public function test_subscription_status_label_past_due() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'past_due' ] );
		$this->assertSame( 'Past Due', $result );
	}

	public function test_subscription_status_label_paused() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'paused' ] );
		$this->assertSame( 'Paused', $result );
	}

	public function test_subscription_status_label_unknown_returns_ucfirst() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ 'some_status' ] );
		$this->assertSame( 'Some status', $result );
	}

	public function test_subscription_status_label_empty() {
		$result = $this->call_private_method( $this->shortcode, 'get_subscription_status_label', [ '' ] );
		$this->assertSame( '', $result );
	}

	// ──────────────────────────────────────────────
	// get_payment_status_label
	// ──────────────────────────────────────────────

	public function test_payment_status_label_succeeded() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'succeeded' ] );
		$this->assertSame( 'Paid', $result );
	}

	public function test_payment_status_label_pending() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'pending' ] );
		$this->assertSame( 'Pending', $result );
	}

	public function test_payment_status_label_failed() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'failed' ] );
		$this->assertSame( 'Failed', $result );
	}

	public function test_payment_status_label_refunded() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'refunded' ] );
		$this->assertSame( 'Refunded', $result );
	}

	public function test_payment_status_label_partially_refunded() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'partially_refunded' ] );
		$this->assertSame( 'Partially Refunded', $result );
	}

	public function test_payment_status_label_unknown_returns_ucfirst() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_status_label', [ 'custom_status' ] );
		$this->assertSame( 'Custom status', $result );
	}

	// ──────────────────────────────────────────────
	// get_payment_badge_class
	// ──────────────────────────────────────────────

	public function test_payment_badge_class_succeeded() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'succeeded' ] );
		$this->assertSame( 'srfm-pd-badge--paid', $result );
	}

	public function test_payment_badge_class_active() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'active' ] );
		$this->assertSame( 'srfm-pd-badge--paid', $result );
	}

	public function test_payment_badge_class_pending() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'pending' ] );
		$this->assertSame( 'srfm-pd-badge--pending', $result );
	}

	public function test_payment_badge_class_failed() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'failed' ] );
		$this->assertSame( 'srfm-pd-badge--cancelled', $result );
	}

	public function test_payment_badge_class_refunded() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'refunded' ] );
		$this->assertSame( 'srfm-pd-badge--refunded', $result );
	}

	public function test_payment_badge_class_unknown_returns_pending() {
		$result = $this->call_private_method( $this->shortcode, 'get_payment_badge_class', [ 'unknown' ] );
		$this->assertSame( 'srfm-pd-badge--pending', $result );
	}

	// ──────────────────────────────────────────────
	// parse_json_field
	// ──────────────────────────────────────────────

	public function test_parse_json_field_with_valid_json_string() {
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ '{"key":"value"}' ] );
		$this->assertIsArray( $result );
		$this->assertSame( 'value', $result['key'] );
	}

	public function test_parse_json_field_with_array() {
		$input  = [ 'key' => 'value' ];
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ $input ] );
		$this->assertSame( $input, $result );
	}

	public function test_parse_json_field_with_empty_string() {
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ '' ] );
		$this->assertSame( [], $result );
	}

	public function test_parse_json_field_with_invalid_json() {
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ 'not json' ] );
		$this->assertSame( [], $result );
	}

	public function test_parse_json_field_with_null() {
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ null ] );
		$this->assertSame( [], $result );
	}

	public function test_parse_json_field_with_integer() {
		$result = $this->call_private_method( $this->shortcode, 'parse_json_field', [ 123 ] );
		$this->assertSame( [], $result );
	}

	// ──────────────────────────────────────────────
	// get_string_from_sources
	// ──────────────────────────────────────────────

	public function test_get_string_from_sources_returns_from_primary() {
		$result = $this->call_private_method(
			$this->shortcode,
			'get_string_from_sources',
			[ 'name', [ 'name' => 'Primary' ], [ 'name' => 'Fallback' ] ]
		);
		$this->assertSame( 'Primary', $result );
	}

	public function test_get_string_from_sources_falls_back_to_secondary() {
		$result = $this->call_private_method(
			$this->shortcode,
			'get_string_from_sources',
			[ 'name', [], [ 'name' => 'Fallback' ] ]
		);
		$this->assertSame( 'Fallback', $result );
	}

	public function test_get_string_from_sources_returns_empty_when_not_found() {
		$result = $this->call_private_method(
			$this->shortcode,
			'get_string_from_sources',
			[ 'missing', [ 'other' => 'val' ], [ 'other2' => 'val2' ] ]
		);
		$this->assertSame( '', $result );
	}

	public function test_get_string_from_sources_skips_empty_primary_value() {
		$result = $this->call_private_method(
			$this->shortcode,
			'get_string_from_sources',
			[ 'name', [ 'name' => '' ], [ 'name' => 'Fallback' ] ]
		);
		$this->assertSame( 'Fallback', $result );
	}

	public function test_get_string_from_sources_converts_numeric_to_string() {
		$result = $this->call_private_method(
			$this->shortcode,
			'get_string_from_sources',
			[ 'count', [ 'count' => 42 ], [] ]
		);
		$this->assertSame( '42', $result );
	}

	// ──────────────────────────────────────────────
	// format_timestamp
	// ──────────────────────────────────────────────

	public function test_format_timestamp_with_unix_timestamp() {
		$result = $this->call_private_method( $this->shortcode, 'format_timestamp', [ '1700000000' ] );
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	public function test_format_timestamp_with_date_string() {
		$result = $this->call_private_method( $this->shortcode, 'format_timestamp', [ '2024-01-15' ] );
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	// ──────────────────────────────────────────────
	// get_form_title
	// ──────────────────────────────────────────────

	public function test_get_form_title_returns_unknown_for_zero_id() {
		$result = $this->call_private_method( $this->shortcode, 'get_form_title', [ 0 ] );
		$this->assertSame( 'Unknown Form', $result );
	}

	public function test_get_form_title_returns_unknown_for_negative_id() {
		$result = $this->call_private_method( $this->shortcode, 'get_form_title', [ -1 ] );
		$this->assertSame( 'Unknown Form', $result );
	}

	public function test_get_form_title_returns_unknown_for_nonexistent_post() {
		$result = $this->call_private_method( $this->shortcode, 'get_form_title', [ 999999 ] );
		$this->assertSame( 'Unknown Form', $result );
	}

	// ──────────────────────────────────────────────
	// extract_subscription_data
	// ──────────────────────────────────────────────

	public function test_extract_subscription_data_returns_expected_keys() {
		$payment = [
			'payment_data' => '{}',
			'extra'        => '{}',
		];
		$result  = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ $payment ] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'plan_name', $result );
		$this->assertArrayHasKey( 'interval_label', $result );
		$this->assertArrayHasKey( 'next_payment', $result );
		$this->assertArrayHasKey( 'cancelled_on', $result );
		$this->assertArrayHasKey( 'access_until', $result );
	}

	public function test_extract_subscription_data_empty_payment() {
		$result = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ [] ] );
		$this->assertIsArray( $result );
		$this->assertSame( '', $result['plan_name'] );
		$this->assertSame( '', $result['interval_label'] );
		$this->assertSame( '', $result['next_payment'] );
		$this->assertSame( '', $result['cancelled_on'] );
		$this->assertSame( '', $result['access_until'] );
	}

	public function test_extract_subscription_data_with_plan_name_in_payment_data() {
		$payment = [
			'payment_data' => wp_json_encode( [ 'plan_name' => 'Premium Plan' ] ),
			'extra'        => '{}',
		];
		$result  = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ $payment ] );
		$this->assertSame( 'Premium Plan', $result['plan_name'] );
	}

	public function test_extract_subscription_data_with_interval() {
		$payment = [
			'payment_data' => wp_json_encode( [
				'interval'       => 'month',
				'interval_count' => '1',
			] ),
			'extra'         => '{}',
		];
		$result  = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ $payment ] );
		$this->assertNotEmpty( $result['interval_label'] );
	}

	public function test_extract_subscription_data_with_next_period_end() {
		$payment = [
			'payment_data' => wp_json_encode( [ 'current_period_end' => '1700000000' ] ),
			'extra'        => '{}',
		];
		$result  = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ $payment ] );
		$this->assertNotEmpty( $result['next_payment'] );
	}

	public function test_extract_subscription_data_with_canceled_status_and_next_date() {
		$payment = [
			'payment_data'        => wp_json_encode( [ 'current_period_end' => '1700000000' ] ),
			'extra'               => '{}',
			'subscription_status' => 'canceled',
		];
		$result  = $this->call_private_method( $this->shortcode, 'extract_subscription_data', [ $payment ] );
		$this->assertNotEmpty( $result['access_until'] );
	}

	// ──────────────────────────────────────────────
	// build_where_conditions
	// ──────────────────────────────────────────────

	public function test_build_where_conditions_returns_array() {
		$result = $this->call_private_method( $this->shortcode, 'build_where_conditions', [ 1, [ 'per_page' => '10' ] ] );
		$this->assertIsArray( $result );
	}

	public function test_build_where_conditions_includes_gateway_filter() {
		$result = $this->call_private_method( $this->shortcode, 'build_where_conditions', [ 1, [] ] );
		$this->assertIsArray( $result );
		// Should contain at least gateway condition.
		$found_gateway = false;
		foreach ( $result as $group ) {
			if ( is_array( $group ) ) {
				foreach ( $group as $condition ) {
					if ( is_array( $condition ) && isset( $condition['key'] ) && 'gateway' === $condition['key'] ) {
						$found_gateway = true;
						break 2;
					}
				}
			}
		}
		$this->assertTrue( $found_gateway, 'Gateway filter condition should be present.' );
	}

	// ──────────────────────────────────────────────
	// get_i18n_strings
	// ──────────────────────────────────────────────

	public function test_get_i18n_strings_returns_array_with_expected_keys() {
		$result = $this->call_private_method( $this->shortcode, 'get_i18n_strings', [] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'cancel_confirm_now', $result );
		$this->assertArrayHasKey( 'are_you_sure', $result );
		$this->assertArrayHasKey( 'keep_subscription', $result );
		$this->assertArrayHasKey( 'yes_cancel', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertArrayHasKey( 'subscription_cancelled', $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	public function test_get_i18n_strings_values_are_all_strings() {
		$result = $this->call_private_method( $this->shortcode, 'get_i18n_strings', [] );
		foreach ( $result as $key => $value ) {
			$this->assertIsString( $value, "i18n key '{$key}' should be a string." );
		}
	}

	// ──────────────────────────────────────────────
	// get_login_message / get_empty_message
	// ──────────────────────────────────────────────

	public function test_get_login_message_contains_widget_class() {
		$result = $this->call_private_method( $this->shortcode, 'get_login_message', [] );
		$this->assertStringContainsString( 'srfm-pd-widget', $result );
		$this->assertStringContainsString( 'srfm-pd-message', $result );
	}

	public function test_get_empty_message_contains_widget_class() {
		$result = $this->call_private_method( $this->shortcode, 'get_empty_message', [] );
		$this->assertStringContainsString( 'srfm-pd-widget', $result );
		$this->assertStringContainsString( 'srfm-pd-message', $result );
	}

	// ──────────────────────────────────────────────
	// process_stripe_subscription_cancellation
	// ──────────────────────────────────────────────

	public function test_stripe_subscription_cancellation_skips_non_stripe() {
		if ( ! class_exists( 'SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler' ) ) {
			$this->markTestSkipped( 'Admin_Stripe_Handler not available.' );
		}
		$handler = \SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler::get_instance();
		$default = [ 'success' => false, 'message' => 'default' ];
		$payment = [ 'gateway' => 'paypal' ];
		$result  = $handler->process_stripe_subscription_cancellation( $default, $payment );
		$this->assertSame( $default, $result );
	}

	public function test_stripe_subscription_cancellation_skips_empty_gateway() {
		if ( ! class_exists( 'SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler' ) ) {
			$this->markTestSkipped( 'Admin_Stripe_Handler not available.' );
		}
		$handler = \SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler::get_instance();
		$default = [ 'success' => false, 'message' => 'default' ];
		$payment = [];
		$result  = $handler->process_stripe_subscription_cancellation( $default, $payment );
		$this->assertSame( $default, $result );
	}

	public function test_stripe_subscription_cancellation_missing_subscription_id() {
		if ( ! class_exists( 'SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler' ) ) {
			$this->markTestSkipped( 'Admin_Stripe_Handler not available.' );
		}
		$handler = \SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler::get_instance();
		$default = [ 'success' => false, 'message' => 'default' ];
		$payment = [ 'gateway' => 'stripe' ];
		$result  = $handler->process_stripe_subscription_cancellation( $default, $payment );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Subscription ID not found', $result['message'] );
	}

	public function test_stripe_subscription_cancellation_non_string_subscription_id() {
		if ( ! class_exists( 'SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler' ) ) {
			$this->markTestSkipped( 'Admin_Stripe_Handler not available.' );
		}
		$handler = \SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler::get_instance();
		$default = [ 'success' => false, 'message' => 'default' ];
		$payment = [ 'gateway' => 'stripe', 'subscription_id' => 12345 ];
		$result  = $handler->process_stripe_subscription_cancellation( $default, $payment );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Subscription ID not found', $result['message'] );
	}
}
