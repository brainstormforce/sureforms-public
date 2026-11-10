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

class Test_Front_End_Subscription extends SRFM_Unit_Test_Case {

	private $form_id;
	private $block = 'sub01';

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
	 * @param string $subscription_status Stripe subscription status to return.
	 * @param string $intent_status       Status of the confirmed first-invoice PaymentIntent.
	 */
	private function stub_stripe( $subscription_status, $intent_status ) {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $subscription_status, $intent_status ) {
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
					return $json( [ 'id' => 'sub_x', 'latest_invoice' => 'in_x', 'status' => $subscription_status ] );
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
		$subscription_value = [
			'subscriptionId' => 'sub_x',
			'customerId'     => 'cus_x',
			'setupIntent'    => 'seti_x',
			'email'          => 'buyer@example.test',
			'name'           => 'Buyer',
		];
		return Front_End::get_instance()->verify_stripe_subscription_intent_and_save( $subscription_value, $this->block, [ 'form-id' => $this->form_id ] );
	}

	public function test_incomplete_subscription_is_rejected() {
		$result = $this->run_submit( 'incomplete', 'requires_action' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result, 'an incomplete (unpaid) subscription must not be accepted' );
		$this->assertArrayNotHasKey( 'payment_id', $result, 'an incomplete subscription must not mark the block verified' );
	}

	public function test_unpaid_first_invoice_is_rejected_even_if_subscription_reports_active() {
		$result = $this->run_submit( 'active', 'requires_action' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result, 'the first invoice PaymentIntent must have succeeded' );
		$this->assertArrayNotHasKey( 'payment_id', $result );
	}

	public function test_trialing_subscription_is_rejected() {
		// SureForms forms have no trial option, so a trialing subscription was not created by this form.
		$result = $this->run_submit( 'trialing' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result, 'a trialing subscription must not mark the block verified' );
		$this->assertArrayNotHasKey( 'payment_id', $result );
	}

	public function test_active_subscription_is_accepted() {
		$result = $this->run_submit( 'active' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'payment_id', $result, 'an active subscription is accepted (backward compatible)' );
	}
}
