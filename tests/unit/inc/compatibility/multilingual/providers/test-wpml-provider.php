<?php
/**
 * Class Test_Wpml_Provider
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\Providers\WPML_Provider;

/**
 * Test double that forces is_active() to return true so we can exercise the
 * WPML branch without booting a real WPML install in the test environment.
 */
class Srfm_Active_Wpml_Provider extends WPML_Provider {
	/**
	 * Always reports active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
	}
}

/**
 * Tests for the WPML multilingual provider.
 */
class Test_Wpml_Provider extends TestCase {

	/**
	 * Provider under test (default inactive state).
	 *
	 * @var WPML_Provider
	 */
	protected $provider;

	public function set_up(): void {
		parent::set_up();
		$this->provider = new WPML_Provider();
	}

	public function test_is_active_returns_false_when_wpml_constants_undefined() {
		// The default test environment does not load WPML; constant should be missing.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( '\SitePress' ) ) {
			$this->markTestSkipped( 'WPML appears to be loaded in the test environment.' );
		}

		$this->assertFalse( $this->provider->is_active() );
	}

	public function test_current_language_returns_empty_string_when_inactive() {
		$this->assertSame( '', $this->provider->current_language() );
	}

	public function test_default_language_returns_empty_string_when_inactive() {
		$this->assertSame( '', $this->provider->default_language() );
	}

	public function test_register_string_does_nothing_when_inactive() {
		$before = did_action( 'wpml_register_single_string' );

		$this->provider->register_string( 'test_name', 'test_value' );

		$after = did_action( 'wpml_register_single_string' );

		$this->assertSame( $before, $after );
	}

	public function test_translate_returns_original_when_inactive() {
		$this->assertSame( 'Hello', $this->provider->translate( 'Hello', 'x' ) );
	}

	public function test_switch_language_does_nothing_when_inactive() {
		$before = did_action( 'wpml_switch_language' );

		$this->provider->switch_language( 'de' );

		$after = did_action( 'wpml_switch_language' );

		$this->assertSame( $before, $after );
	}

	public function test_restore_language_does_nothing_when_inactive() {
		$before = did_action( 'wpml_switch_language' );

		$this->provider->restore_language();

		$after = did_action( 'wpml_switch_language' );

		$this->assertSame( $before, $after );
	}

	public function test_register_string_fires_wpml_action_when_active() {
		$captured = [];
		$spy      = static function ( $domain, $name, $value ) use ( &$captured ) {
			$captured[] = [ $domain, $name, $value ];
		};
		add_action( 'wpml_register_single_string', $spy, 10, 3 );

		$provider = new Srfm_Active_Wpml_Provider();
		$provider->register_string( 'test_name', 'test_value' );

		remove_action( 'wpml_register_single_string', $spy, 10 );

		$this->assertCount( 1, $captured );
		$this->assertSame( [ 'sureforms', 'test_name', 'test_value' ], $captured[0] );
	}

	public function test_translate_applies_wpml_filter_when_active() {
		$filter = static function ( $value ) {
			return is_string( $value ) ? strtoupper( $value ) : $value;
		};
		add_filter( 'wpml_translate_single_string', $filter, 10, 1 );

		$provider = new Srfm_Active_Wpml_Provider();
		$result   = $provider->translate( 'hello', 'x' );

		remove_filter( 'wpml_translate_single_string', $filter, 10 );

		$this->assertSame( 'HELLO', $result );
	}

	public function test_switch_and_restore_language_maintain_stack() {
		$captured = [];
		$spy      = static function ( $language ) use ( &$captured ) {
			$captured[] = $language;
		};
		add_action( 'wpml_switch_language', $spy, 10, 1 );

		$provider = new Srfm_Active_Wpml_Provider();

		$provider->switch_language( 'de' );
		$provider->switch_language( 'fr' );
		$provider->restore_language();
		$provider->restore_language();

		remove_action( 'wpml_switch_language', $spy, 10 );

		// Two switches followed by two restores -> four total action firings.
		$this->assertCount( 4, $captured );

		// First two should be the languages we switched to, in order.
		$this->assertSame( 'de', $captured[0] );
		$this->assertSame( 'fr', $captured[1] );

		// Restores should pop in LIFO order: first restore returns to whatever was
		// current when 'fr' was pushed, second restore returns to whatever was current
		// when 'de' was pushed. With no real WPML those are empty strings.
		$this->assertSame( '', $captured[2] );
		$this->assertSame( '', $captured[3] );
	}

	public function test_render_language_switcher_returns_empty_when_inactive() {
		$result = $this->provider->render_language_switcher();
		$this->assertSame( '', $result );
	}

	public function test_render_language_switcher_builds_list_from_wpml_active_languages() {
		$active = new Srfm_Active_Wpml_Provider();

		$listener = static function () {
			return [
				'en' => [
					'language_code' => 'en',
					'native_name'   => 'English',
					'url'           => 'https://example.test/',
					'active'        => 1,
				],
				'hi' => [
					'language_code' => 'hi',
					'native_name'   => 'हिन्दी',
					'url'           => 'https://example.test/?lang=hi',
					'active'        => 0,
				],
			];
		};
		add_filter( 'wpml_active_languages', $listener );

		$html = $active->render_language_switcher();

		remove_filter( 'wpml_active_languages', $listener );

		$this->assertStringContainsString( 'srfm-lang-switcher-list', $html );
		$this->assertStringContainsString( 'srfm-lang-item-current', $html );
		$this->assertStringContainsString( 'href="https://example.test/?lang=hi"', $html );
		$this->assertStringContainsString( 'English', $html );
		$this->assertStringContainsString( 'हिन्दी', $html );
	}

	public function test_render_language_switcher_returns_empty_with_fewer_than_two_languages() {
		$active = new Srfm_Active_Wpml_Provider();

		$listener = static function () {
			return [
				'en' => [
					'language_code' => 'en',
					'native_name'   => 'English',
					'url'           => 'https://example.test/',
					'active'        => 1,
				],
			];
		};
		add_filter( 'wpml_active_languages', $listener );

		$html = $active->render_language_switcher();

		remove_filter( 'wpml_active_languages', $listener );

		$this->assertSame( '', $html );
	}
}
