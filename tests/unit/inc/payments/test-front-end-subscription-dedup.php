<?php
/**
 * Class Test_Front_End_Subscription_Dedup
 *
 * A subscription whose reference is already claimed (a concurrent or duplicate
 * submit of the same activated subscription) must not create a second paid entry.
 * All Stripe HTTP calls are stubbed so no network request is made.
 *
 * @package sureforms
 */

use SRFM\Inc\Payments\Front_End;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Helper;

class Test_Front_End_Subscription_Dedup extends SRFM_Unit_Test_Case {

	private $form_id;
	private $block = 'sub01';

	public function set_up() {
		parent::set_up();

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
					'payment_type'          => 'subscription',
					'amount_type'           => 'fixed',
					'fixed_amount'          => 10.00,
					'subscription_interval' => 'month',
				],
			]
		);
		set_transient(
			'srfm_pi_' . sanitize_key( $this->block ) . '_' . sanitize_key( 'seti_x' ),
			[
				'amount'          => 10.00,
				'active_type'     => 'subscription',
				'subscription_id' => 'sub_server',
				'form_id'         => $this->form_id,
			],
			HOUR_IN_SECONDS
		);

		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) {
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
					return $json( [ 'id' => 'sub_server', 'latest_invoice' => 'in_x', 'status' => 'active' ] );
				}
				if ( false !== strpos( $url, '/invoices/' ) ) {
					return $json( [ 'id' => 'in_x', 'payment_intent' => 'pi_x', 'auto_advance' => true, 'status' => 'paid' ] );
				}
				if ( false !== strpos( $url, '/payment_intents/' ) ) {
					return $json( [ 'id' => 'pi_x', 'status' => 'succeeded', 'amount' => 1000, 'currency' => 'usd' ] );
				}
				return new WP_Error( 'blocked', 'blocked outbound ' . $url );
			},
			10,
			3
		);
	}

	private function run_submit() {
		$subscription_value = [
			'subscriptionId' => 'sub_server',
			'customerId'     => 'cus_x',
			'setupIntent'    => 'seti_x',
			'email'          => 'buyer@example.test',
			'name'           => 'Buyer',
		];
		return Front_End::get_instance()->verify_stripe_subscription_intent_and_save( $subscription_value, $this->block, [ 'form-id' => $this->form_id ] );
	}

	public function test_first_submit_is_accepted() {
		$result = $this->run_submit();
		$this->assertArrayHasKey( 'payment_id', $result, 'the first submit creates the paid entry' );
	}

	public function test_duplicate_submit_is_rejected() {
		// Simulate the first (concurrent/prior) submit having already claimed the
		// subscription; the second submit must not create another paid entry.
		Payment_Helper::claim_payment_reference( 'sub_server' );

		$result = $this->run_submit();
		$this->assertArrayHasKey( 'error', $result, 'a duplicate subscription submit is rejected' );
		$this->assertArrayNotHasKey( 'payment_id', $result );
	}
}
