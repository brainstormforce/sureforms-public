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

	/**
	 * A payment field whose payment was not verified this request must not survive
	 * as a payment-record id.
	 *
	 * A bare integer skips gateway verification (json_decode() is not an array), so
	 * without deny-by-default it passed straight through and {form-payment} resolved
	 * it against an arbitrary payment row (IDOR). See #3090.
	 */
	public function test_validate_payment_fields_drops_unverified_payment_id() {
		$field     = 'srfm-payment-blk1-lbl-0-donation';
		$form_data = [
			// form-id 0: no payment-enabled form, so the required-payment path does not short-circuit.
			'form-id' => 0,
			$field    => '1', // attacker-chosen payment row id, sent as a bare integer.
		];

		$result = $this->front_end->validate_payment_fields( $form_data );

		$this->assertSame(
			'',
			$result[ $field ],
			'An unverified payment field must be cleared, not passed through as a database id.'
		);
	}

	/**
	 * Build a published form carrying one payment block.
	 *
	 * @param array<string,mixed> $attrs Payment block attributes to merge over the defaults.
	 * @return int Form ID.
	 */
	private function make_payment_form( $attrs = [] ) {
		remove_all_actions( 'wp_insert_post_data' );

		$attrs = array_merge(
			[
				'block_id'           => 'pay12345',
				'paymentType'        => 'one-time',
				'customerEmailField' => 'email',
				'fixedAmount'        => 25,
			],
			$attrs
		);

		return wp_insert_post(
			[
				'post_title'   => 'Payment Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/payment ' . wp_json_encode( $attrs ) . ' /-->',
			]
		);
	}

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

	/**
	 * Stand in for a gateway that verified the payment.
	 *
	 * @return array<string,mixed>
	 */
	public function stub_verified_payment() {
		return [ 'payment_id' => 'pay_verified_123' ];
	}

	/**
	 * A submission that omits the payment field on a payment-enabled form must fail.
	 *
	 * Payment validation was opt-in per submitted field: every failure path was a
	 * `continue`, so stripping srfm-payment-* from the POST body produced an entry,
	 * fired notifications and paid nothing. See #2998.
	 */
	public function test_validate_payment_fields_requires_payment_when_field_omitted() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		$form_id   = $this->make_payment_form();
		$form_data = [
			'form-id'              => $form_id,
			'srfm-input-1-lbl-x-e' => 'john@example.com',
		];

		$result = $this->front_end->validate_payment_fields( $form_data );

		$this->assertArrayHasKey( 'error', $result, 'A payment-enabled form must not accept a submission with no payment.' );

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * An empty or malformed payment value is not a substitute for a verified payment.
	 */
	public function test_validate_payment_fields_requires_payment_when_value_is_empty() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		$form_id = $this->make_payment_form();

		foreach ( [ '', '{}', wp_json_encode( [ 'blockId' => 'pay12345' ] ) ] as $payment_value ) {
			$result = $this->front_end->validate_payment_fields(
				[
					'form-id'                          => $form_id,
					'srfm-payment-pay12345-lbl-x-payment' => $payment_value,
				]
			);

			$this->assertArrayHasKey( 'error', $result, 'An unverified payment value must not pass.' );
		}

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * A verified payment for the form's payment block passes through unchanged.
	 */
	public function test_validate_payment_fields_accepts_verified_payment() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		add_filter( 'srfm_verify_payment_value', [ $this, 'stub_verified_payment' ] );

		$form_id = $this->make_payment_form();
		$field   = 'srfm-payment-pay12345-lbl-x-payment';

		$result = $this->front_end->validate_payment_fields(
			[
				'form-id' => $form_id,
				$field    => wp_json_encode(
					[
						'paymentId'     => 'pi_test_123',
						'blockId'       => 'pay12345',
						'paymentType'   => 'one-time',
						'paymentMethod' => 'test-gateway',
					]
				),
			]
		);

		$this->assertArrayNotHasKey( 'error', $result, 'A verified payment must be accepted.' );
		$this->assertSame( 'pay_verified_123', $result[ $field ] );

		remove_filter( 'srfm_verify_payment_value', [ $this, 'stub_verified_payment' ] );
		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * A payment block that renders nothing cannot be required.
	 *
	 * Without the customer email mapping Payment_Markup::markup() returns '', so a
	 * legitimate submission carries no payment field and must still be accepted.
	 */
	public function test_validate_payment_fields_ignores_unrenderable_payment_block() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		$form_id = $this->make_payment_form( [ 'customerEmailField' => '' ] );

		$result = $this->front_end->validate_payment_fields( [ 'form-id' => $form_id ] );

		$this->assertArrayNotHasKey( 'error', $result );

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * Register the conditional-logic meta the way the Pro add-on does, so the exclusion
	 * is reachable in a free-only test environment.
	 *
	 * @return void
	 */
	private function register_conditional_logic_meta() {
		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_conditional_logic',
			[
				'type'   => 'array',
				'single' => true,
			]
		);
	}

	/**
	 * A payment field under conditional logic may legitimately be hidden client-side.
	 */
	public function test_validate_payment_fields_ignores_conditionally_hidden_payment_block() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		$this->register_conditional_logic_meta();

		$form_id = $this->make_payment_form();
		// The rule's trigger field must be on the form; a missing one reads as empty.
		wp_update_post(
			[
				'ID'           => $form_id,
				'post_content' => '<!-- wp:srfm/email {"block_id":"email"} /-->' . get_post_field( 'post_content', $form_id ),
			]
		);
		update_post_meta(
			$form_id,
			'_srfm_conditional_logic',
			[
				[
					'pay12345' => [
						'action' => 'show',
						'logic'  => [ [ [ 'field' => 'email', 'operator' => '==', 'value' => 'yes' ] ] ],
					],
				],
			]
		);

		// The `show` condition does not hold for these values, so the field is hidden.
		$result = $this->front_end->validate_payment_fields( [ 'form-id' => $form_id ] );

		$this->assertArrayNotHasKey( 'error', $result );

		// A `hide` rule leaves the field visible by default; it is exempt only when the
		// submitted values match the conditions.
		update_post_meta(
			$form_id,
			'_srfm_conditional_logic',
			[
				[
					'pay12345' => [
						'action' => 'hide',
						'logic'  => [ [ [ 'field' => 'email', 'operator' => '==', 'value' => 'yes' ] ] ],
					],
				],
			]
		);
		$this->assertArrayNotHasKey(
			'error',
			$this->front_end->validate_payment_fields(
				[
					'form-id'                      => $form_id,
					'srfm-email-email-lbl-x-email' => 'yes',
				]
			)
		);
		$this->assertArrayHasKey( 'error', $this->front_end->validate_payment_fields( [ 'form-id' => $form_id ] ) );

		unregister_post_meta( SRFM_FORMS_POST_TYPE, '_srfm_conditional_logic' );
		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * A conditional-logic rule with no conditions can never hide the field, so it must
	 * not buy the payment block an exemption from the requirement.
	 */
	public function test_validate_payment_fields_requires_payment_for_empty_conditional_rule() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		$this->register_conditional_logic_meta();

		$form_id = $this->make_payment_form();

		foreach ( [ [], [ [] ], [ [ [] ] ] ] as $empty_logic ) {
			update_post_meta(
				$form_id,
				'_srfm_conditional_logic',
				[
					[
						'pay12345' => [
							'action' => 'show',
							'logic'  => $empty_logic,
						],
					],
				]
			);

			$this->assertArrayHasKey(
				'error',
				$this->front_end->validate_payment_fields( [ 'form-id' => $form_id ] ),
				'A rule with no conditions must not exempt the payment block.'
			);
		}

		unregister_post_meta( SRFM_FORMS_POST_TYPE, '_srfm_conditional_logic' );
		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * Without the add-on that evaluates conditional logic, nothing hides the field, so a
	 * stale rule (e.g. written by a form importer) must not exempt it either.
	 */
	public function test_validate_payment_fields_requires_payment_when_conditional_logic_unregistered() {
		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		$form_id = $this->make_payment_form();
		update_post_meta(
			$form_id,
			'_srfm_conditional_logic',
			[
				[
					'pay12345' => [
						'action' => 'show',
						'logic'  => [ [ [ 'field' => 'email', 'operator' => '===', 'value' => 'yes' ] ] ],
					],
				],
			]
		);

		$this->assertArrayHasKey(
			'error',
			$this->front_end->validate_payment_fields( [ 'form-id' => $form_id ] ),
			'A conditional-logic rule must not exempt a payment block when nothing evaluates it.'
		);

		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * A form with no payment block is unaffected.
	 */
	public function test_validate_payment_fields_form_without_payment_block_unchanged() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Plain Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/input {"block_id":"plain567"} /-->',
			]
		);

		$form_data = [
			'form-id'                => $form_id,
			'srfm-input-1-lbl-x-name' => 'John',
		];

		$this->assertSame( $form_data, $this->front_end->validate_payment_fields( $form_data ) );

		wp_delete_post( $form_id, true );
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

	// --- create_payment_intent token verification ---

	/**
	 * Test create_payment_intent rejects request without a valid HMAC token.
	 */
	public function test_create_payment_intent_rejects_without_token() {
		// Simulate AJAX POST without token.
		$_POST = [
			'amount'   => 1000,
			'currency' => 'usd',
			'form_id'  => 1,
			'block_id' => 'block-1',
		];

		// Expect wp_send_json_error to terminate via wp_die.
		try {
			ob_start();
			$this->front_end->create_payment_intent();
			ob_end_clean();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$_POST = []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$_POST = []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->fail( 'Expected WPDieException for missing token in create_payment_intent.' );
	}

	/**
	 * Test create_subscription_intent rejects request without a valid HMAC token.
	 */
	public function test_create_subscription_intent_rejects_without_token() {
		// Simulate AJAX POST without token.
		$_POST = [
			'amount'    => 1000,
			'currency'  => 'usd',
			'form_id'   => 1,
			'block_id'  => 'block-1',
			'interval'  => 'month',
			'plan_name' => 'Test Plan',
		];

		try {
			ob_start();
			$this->front_end->create_subscription_intent();
			ob_end_clean();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$_POST = []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$_POST = []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->fail( 'Expected WPDieException for missing token in create_subscription_intent.' );
	}

	// --- verify_stripe_subscription_intent_and_save ---

	public function test_verify_stripe_subscription_intent_and_save_empty_subscription_id() {
		$result = $this->front_end->verify_stripe_subscription_intent_and_save(
			[ 'subscriptionId' => '', 'customerId' => 'cus_1', 'setupIntent' => 'seti_1' ],
			'block-1',
			[ 'form-id' => 1 ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	public function test_verify_stripe_subscription_intent_and_save_missing_customer_id() {
		// With a valid-looking subscriptionId but no stored payment intent metadata,
		// verification fails before the customer_id branch — still returns an error array.
		$result = $this->front_end->verify_stripe_subscription_intent_and_save(
			[ 'subscriptionId' => 'sub_nonexistent', 'customerId' => '', 'setupIntent' => 'seti_nonexistent' ],
			'block-1',
			[ 'form-id' => 1 ]
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	public function test_verify_stripe_subscription_intent_and_save_invalid_subscription_id_type() {
		// Non-string subscriptionId is rejected.
		$result = $this->front_end->verify_stripe_subscription_intent_and_save(
			[ 'subscriptionId' => 12345, 'customerId' => 'cus_1', 'setupIntent' => 'seti_1' ],
			'block-1',
			[ 'form-id' => 1 ]
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
