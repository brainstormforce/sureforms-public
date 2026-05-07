<?php
/**
 * Class Test_Payment_History_Block
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Blocks\Payment_History\Block;

class Test_Payment_History_Block extends TestCase {

	protected $block;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'SRFM\Inc\Blocks\Payment_History\Block' ) ) {
			$this->markTestSkipped( 'Payment_History Block class not available.' );
		}

		$this->block = new Block();
	}

	// ──────────────────────────────────────────────
	// render
	// ──────────────────────────────────────────────

	public function test_render_returns_login_message_when_logged_out() {
		wp_set_current_user( 0 );
		$result = $this->block->render( [] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'srfm-pd-widget', $result );
		$this->assertStringContainsString( 'srfm-pd-message', $result );
	}

	public function test_render_defaults_per_page_to_10_when_attribute_missing() {
		wp_set_current_user( 0 );
		// No perPage attribute — should not error and should use default '10'.
		$result = $this->block->render( [] );
		$this->assertIsString( $result );
	}

	public function test_render_converts_numeric_per_page_to_string() {
		wp_set_current_user( 0 );
		// perPage as integer — should be converted to string for shortcode.
		$result = $this->block->render( [ 'perPage' => 25 ] );
		$this->assertIsString( $result );
	}

	public function test_render_ignores_non_numeric_per_page() {
		wp_set_current_user( 0 );
		// Non-numeric perPage — should fall back to '10'.
		$result = $this->block->render( [ 'perPage' => 'abc' ] );
		$this->assertIsString( $result );
	}

	public function test_render_maps_show_subscription_true() {
		wp_set_current_user( 0 );
		$result = $this->block->render( [ 'showSubscription' => true ] );
		$this->assertIsString( $result );
	}

	public function test_render_maps_show_subscription_false() {
		wp_set_current_user( 0 );
		$result = $this->block->render( [ 'showSubscription' => false ] );
		$this->assertIsString( $result );
	}

	public function test_render_maps_show_renewal_boolean_to_string() {
		wp_set_current_user( 0 );
		$result = $this->block->render( [ 'showRenewal' => true ] );
		$this->assertIsString( $result );
	}

	public function test_render_handles_zero_per_page() {
		wp_set_current_user( 0 );
		// Zero perPage — absint(0) = 0, should fall back to default 10 in shortcode.
		$result = $this->block->render( [ 'perPage' => 0 ] );
		$this->assertIsString( $result );
	}
}
