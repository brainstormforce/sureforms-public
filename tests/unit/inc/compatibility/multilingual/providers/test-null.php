<?php
/**
 * Class Test_Null_Provider
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\Providers\Null_Provider;

/**
 * Tests for the Null multilingual provider.
 */
class Test_Null_Provider extends TestCase {

	/**
	 * Provider under test.
	 *
	 * @var Null_Provider
	 */
	protected $provider;

	public function set_up(): void {
		parent::set_up();
		$this->provider = new Null_Provider();
	}

	public function test_is_active() {
		$this->assertFalse( $this->provider->is_active() );
	}

	public function test_current_language() {
		$this->assertSame( '', $this->provider->current_language() );
	}

	public function test_default_language() {
		$this->assertSame( '', $this->provider->default_language() );
	}

	public function test_register_string() {
		$before = did_action( 'wpml_register_single_string' );

		$this->provider->register_string( 'test_name', 'test_value' );
		$this->provider->register_string( 'test_name', 'test_value', 'custom-domain' );

		$after = did_action( 'wpml_register_single_string' );

		// No actions should have fired.
		$this->assertSame( $before, $after );
	}

	public function test_translate() {
		$this->assertSame( 'Hello', $this->provider->translate( 'Hello', 'greeting' ) );
		$this->assertSame( 'Hello', $this->provider->translate( 'Hello', 'greeting', 'custom-domain' ) );
		$this->assertSame( 'Hello', $this->provider->translate( 'Hello', 'greeting', 'custom-domain', 'de' ) );
		$this->assertSame( '', $this->provider->translate( '', 'empty' ) );
	}

	public function test_switch_language() {
		$before = did_action( 'wpml_switch_language' );

		$this->provider->switch_language( 'de' );

		$after = did_action( 'wpml_switch_language' );

		// No actions should have fired, no exceptions should have been thrown.
		$this->assertSame( $before, $after );
	}

	public function test_restore_language() {
		$before = did_action( 'wpml_switch_language' );

		$this->provider->restore_language();

		$after = did_action( 'wpml_switch_language' );

		// No actions should have fired, no exceptions should have been thrown.
		$this->assertSame( $before, $after );
	}
}
