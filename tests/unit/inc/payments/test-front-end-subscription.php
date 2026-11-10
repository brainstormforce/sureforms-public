<?php
/**
 * Class Test_Front_End_Subscription
 *
 * Integration coverage for the Stripe subscription submit path, with all Stripe
 * HTTP calls stubbed so no network request is made.
 *
 * @package sureforms
 */

use SRFM\Inc\Payments\Front_End;
use SRFM\Inc\Helper;
use SRFM\Inc\Database\Tables\Payments;

class Test_Front_End_Subscription extends SRFM_Unit_Test_Case {

	private $form_id;
	private $block = 'sub01';

	/**
	 * Number of subscription GET calls the stub has answered.
	 *
	 * @var int
	 */
	public $subscription_gets = 0;

	/**
	 * Payment rows for the test form before the call under test.
	 *
	 * @var int
	 */
	private $rows_before = 0;

	public function set_up() {
		parent::set_up();

		// Make Stripe appear connected in test mode with a dummy secret.
		Helper::update_srfm_option(
			'payment_settings',
			[
				'payment_mode' => 'test',
				'currency'     => 'USD',
				'stripe'       => [
					'stripe_connected'            => true,
					'stripe_test_secret_key'      => 'sk_test_dummy',
					'stripe_test_publishable_key' => 'pk_test_dummy',
				],
			]
		);

		$this->form_id = self::factory()->post->create( [ 'post_type' => 'sureforms_form' ] );
		update_post_meta(
			$this->form_id,
			'_srfm_block_config',
			[
				$this->block => [
					'payment_type'         => 'subscription',
					'amount_type'          => 'fixed',
					'fixed_amount'         => 10.00,
					'subscription_interval' => 'month',
				],
			]
		);
		set_transient(
			'srfm_pi_' . sanitize_key( $this->block ) . '_' . sanitize_key( 'seti_x' ),
			[
				'amount'          => 10.00,
				'active_type'     => 'subscription',
				'subscription_id' => 'sub_x',
				'form_id'         => $this->form_id,
			],
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Stub every Stripe HTTP call with the given subscription and confirmed
	 * first-invoice PaymentIntent statuses.
	 *
	 * @param string|string[] $subscription_status Status returned by subscription reads. A list is
	 *                                             handed out one per GET, the last value repeating.
	 * @param string          $intent_status       Status of the confirmed first-invoice PaymentIntent.
	 */
	private function stub_stripe( $subscription_status, $intent_status ) {
		$this->subscription_gets = 0;
		$statuses                = (array) $subscription_status;
		$test                    = $this;
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $statuses, $intent_status, $test ) {
				$json = static function ( $data ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode( $data ),
					];
				};
				if ( false !== strpos( $url, '/setup_intents/' ) ) {
					return $json( [ 'id' => 'seti_x', 'payment_method' => 'pm_x', 'customer' => 'cus_x', 'status' => 'succeeded' ] );
				}
				if ( false !== strpos( $url, '/subscriptions/' ) ) {
					$status = $statuses[ count( $statuses ) - 1 ];
					if ( isset( $args['method'] ) && 'GET' === $args['method'] ) {
						$status = $statuses[ min( $test->subscription_gets, count( $statuses ) - 1 ) ];
						++$test->subscription_gets;
					}
					return $json( [ 'id' => 'sub_x', 'latest_invoice' => 'in_x', 'status' => $status ] );
				}
				if ( false !== strpos( $url, '/invoices/' ) ) {
					return $json( [ 'id' => 'in_x', 'payment_intent' => 'pi_x', 'auto_advance' => true, 'status' => 'open' ] );
				}
				if ( false !== strpos( $url, '/payment_intents/' ) ) {
					return $json( [ 'id' => 'pi_x', 'status' => $intent_status, 'amount' => 1000, 'currency' => 'usd' ] );
				}
				return new WP_Error( 'blocked', 'blocked outbound ' . $url );
			},
			10,
			3
		);
	}

	private function run_submit( $subscription_status, $intent_status = 'succeeded' ) {
		$this->stub_stripe( $subscription_status, $intent_status );
		// The payments table is not rolled back between tests, so record what is already there.
		$this->rows_before = count( Payments::get_all_payment_ids_for_form( $this->form_id ) );
		$subscription_value = [
			'subscriptionId' => 'sub_x',
			'customerId'     => 'cus_x',
			'setupIntent'    => 'seti_x',
			'email'          => 'buyer@example.test',
			'name'           => 'Buyer',
		];
		return Front_End::get_instance()->verify_stripe_subscription_intent_and_save( $subscription_value, $this->block, [ 'form-id' => $this->form_id ] );
	}

	/**
	 * Assert the payment-status guard rejected the submission: its own message, no
	 * payment_id, and no payment row written. Checking the message keeps the test
	 * from passing on an unrelated earlier error.
	 *
	 * @param mixed  $result Return value of verify_stripe_subscription_intent_and_save().
	 * @param string $why    Failure message.
	 */
	private function assert_rejected_unpaid( $result, $why ) {
		$this->assertIsArray( $result );
		$this->assertSame( 'Subscription payment was not completed.', $result['error'] ?? null, $why );
		$this->assertArrayNotHasKey( 'payment_id', $result, $why );
		$this->assertSame( $this->rows_before, count( Payments::get_all_payment_ids_for_form( $this->form_id ) ), 'no payment row may be stored for an unpaid subscription' );
	}

	public function test_incomplete_subscription_is_rejected() {
		$this->assert_rejected_unpaid( $this->run_submit( 'incomplete', 'requires_action' ), 'an incomplete (unpaid) subscription must not be accepted' );
	}

	public function test_unpaid_first_invoice_is_rejected_even_if_subscription_reports_active() {
		$this->assert_rejected_unpaid( $this->run_submit( 'active', 'requires_action' ), 'the first invoice PaymentIntent must have succeeded' );
	}

	public function test_trialing_subscription_is_rejected() {
		// SureForms forms have no trial option, so a trialing subscription was not created by this form.
		$this->assert_rejected_unpaid( $this->run_submit( 'trialing' ), 'a trialing subscription must not mark the block verified' );
	}

	public function test_subscription_still_incomplete_after_reread_is_rejected() {
		$this->assert_rejected_unpaid( $this->run_submit( 'incomplete' ), 'a subscription that stays incomplete must not be accepted' );
		$this->assertSame( 2, $this->subscription_gets, 'an incomplete subscription is read exactly once more' );
	}

	public function test_subscription_activated_on_reread_is_accepted() {
		$result = $this->run_submit( [ 'incomplete', 'active' ] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'payment_id', $result, 'a paid subscription that activates on the re-read is accepted' );
		$this->assertSame( 'active', Payments::get( $result['payment_id'] )['status'] ?? null );
	}

	public function test_active_subscription_is_accepted() {
		$result = $this->run_submit( 'active' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'payment_id', $result, 'an active subscription is accepted (backward compatible)' );

		$row = Payments::get( $result['payment_id'] );
		$this->assertSame( 'active', $row['status'] ?? null );
		$this->assertSame( 'active', $row['subscription_status'] ?? null );
		$this->assertSame( 'sub_x', $row['subscription_id'] ?? null );
		$this->assertSame( 1, $this->subscription_gets, 'an active subscription is read once, with no extra call' );
	}
}
