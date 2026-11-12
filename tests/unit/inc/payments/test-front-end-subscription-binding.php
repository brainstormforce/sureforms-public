<?php
/**
 * Class Test_Front_End_Subscription_Binding
 *
 * The submitted Stripe subscriptionId must match the one the payment session
 * created (stored in the setup-intent transient). All Stripe HTTP calls are
 * stubbed so no network request is made.
 *
 * @package sureforms
 */

use SRFM\Inc\Payments\Front_End;
use SRFM\Inc\Helper;

class Test_Front_End_Subscription_Binding extends SRFM_Unit_Test_Case {

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
		// The session created subscription 'sub_server'.
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
	}

	private function stub_stripe() {
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

	private function run_submit( $submitted_subscription_id ) {
		$this->stub_stripe();
		$subscription_value = [
			'subscriptionId' => $submitted_subscription_id,
			'customerId'     => 'cus_x',
			'setupIntent'    => 'seti_x',
			'email'          => 'buyer@example.test',
			'name'           => 'Buyer',
		];
		return Front_End::get_instance()->verify_stripe_subscription_intent_and_save( $subscription_value, $this->block, [ 'form-id' => $this->form_id ] );
	}

	public function test_submitted_subscription_id_must_match_session() {
		// A subscriptionId that was not created by this payment session is rejected.
		$result = $this->run_submit( 'sub_attacker' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result, 'a substituted subscriptionId must be rejected' );
		$this->assertArrayNotHasKey( 'payment_id', $result );
	}

	public function test_matching_subscription_id_is_accepted() {
		// The subscriptionId this session created is accepted (backward compatible).
		$result = $this->run_submit( 'sub_server' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'payment_id', $result, 'the session subscriptionId is accepted' );
	}
}
