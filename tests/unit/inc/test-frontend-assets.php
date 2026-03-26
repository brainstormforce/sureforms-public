<?php
/**
 * Class Test_Frontend_Assets
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Frontend_Assets;

/**
 * Tests for Frontend_Assets.
 */
class Test_Frontend_Assets extends TestCase {

	/**
	 * Frontend_Assets instance.
	 *
	 * @var Frontend_Assets
	 */
	protected $frontend_assets;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->frontend_assets = Frontend_Assets::get_instance();
	}

	/**
	 * Test enqueue_srfm_script does not localize payment_nonce for Stripe.
	 *
	 * After the HMAC token migration, the srfm_ajax localized data should
	 * contain ajax_url but NOT payment_nonce.
	 */
	public function test_enqueue_srfm_script_no_payment_nonce_for_stripe() {
		// Enqueue the payment block scripts.
		$this->frontend_assets->enqueue_srfm_script( 'srfm/payment', [] );

		// Get the localized data for the Stripe payment script.
		$scripts        = wp_scripts();
		$stripe_handle  = 'srfm-stripe-payment';
		$localized_data = $scripts->get_data( $stripe_handle, 'data' );

		if ( $localized_data ) {
			$this->assertStringContainsString( 'ajax_url', $localized_data, 'srfm_ajax should contain ajax_url.' );
			$this->assertStringNotContainsString( 'payment_nonce', $localized_data, 'srfm_ajax should NOT contain payment_nonce after HMAC migration.' );
		} else {
			// Script may not have been registered if SRFM_URL/SRFM_VER aren't defined in test env.
			$this->markTestSkipped( 'Stripe payment script not registered in test environment.' );
		}
	}

	/**
	 * Test enqueue_srfm_script is a callable method.
	 */
	public function test_enqueue_srfm_script_is_callable() {
		$this->assertTrue(
			method_exists( $this->frontend_assets, 'enqueue_srfm_script' ),
			'enqueue_srfm_script method should exist on Frontend_Assets.'
		);
	}
}
