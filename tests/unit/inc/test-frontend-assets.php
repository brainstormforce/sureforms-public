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

	/**
	 * Test enqueue_srfm_script localizes Quill i18n strings for rich text textarea.
	 */
	public function test_enqueue_srfm_script_localizes_quill_i18n_for_richtext_textarea() {
		// Enqueue the textarea block with isRichText enabled.
		$this->frontend_assets->enqueue_srfm_script( 'srfm/textarea', [ 'isRichText' => true ] );

		// Retrieve the localized data for the textarea script.
		$localized_data = wp_scripts()->get_data( SRFM_SLUG . '-textarea', 'data' );

		$this->assertNotEmpty( $localized_data, 'Localized script data should not be empty for rich text textarea.' );
		$this->assertStringContainsString( 'srfm_quill_i18n', $localized_data, 'Localized data should contain srfm_quill_i18n object.' );

		// Verify all expected i18n keys are present in the localized data.
		$expected_keys = [
			'normal',
			'heading_1',
			'heading_2',
			'heading_3',
			'heading_4',
			'heading_5',
			'heading_6',
			'visit_url',
			'enter_link',
			'edit',
			'save',
			'remove',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertStringContainsString( '"' . $key . '"', $localized_data, "Localized data should contain the '{$key}' key." );
		}
	}

	/**
	 * Test enqueue_srfm_script does not localize Quill i18n for non-richtext textarea.
	 */
	public function test_enqueue_srfm_script_does_not_localize_quill_i18n_for_plain_textarea() {
		// Reset scripts registry to start clean.
		wp_scripts()->registered = [];
		wp_scripts()->queue      = [];

		// Enqueue the textarea block without isRichText.
		$this->frontend_assets->enqueue_srfm_script( 'srfm/textarea', [] );

		// The textarea script should not be enqueued at all for non-richtext.
		$localized_data = wp_scripts()->get_data( SRFM_SLUG . '-textarea', 'data' );

		$this->assertFalse( $localized_data, 'Localized data should not exist for non-richtext textarea.' );
	}
}
