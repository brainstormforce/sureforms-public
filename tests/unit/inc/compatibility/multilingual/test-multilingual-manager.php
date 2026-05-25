<?php
/**
 * Class Test_Multilingual_Manager
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager;
use SRFM\Inc\Compatibility\Multilingual\Providers\Null_Provider;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;

/**
 * Stub provider used to verify the srfm_multilingual_provider filter override path.
 */
class Srfm_Stub_Multilingual_Provider implements Provider {
	public function is_active(): bool {
		return true;
	}

	public function current_language(): string {
		return 'xx';
	}

	public function default_language(): string {
		return 'xx';
	}

	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		// No-op stub.
	}

	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		return $value;
	}

	public function switch_language( string $language ): void {
		// No-op stub.
	}

	public function restore_language(): void {
		// No-op stub.
	}

	public function render_language_switcher(): string {
		return '';
	}
}

/**
 * Subclass used to obtain fresh manager instances without polluting the global singleton.
 */
class Srfm_Test_Multilingual_Manager extends Multilingual_Manager {
	// Inherits constructor; instantiated directly to bypass get_instance() caching.
}

/**
 * Tests for Multilingual_Manager.
 */
class Test_Multilingual_Manager extends TestCase {

	public function test_get_instance_returns_singleton() {
		$a = Multilingual_Manager::get_instance();
		$b = Multilingual_Manager::get_instance();

		$this->assertSame( $a, $b );
		$this->assertInstanceOf( Multilingual_Manager::class, $a );
	}

	public function test_provider_returns_null_provider_when_wpml_inactive() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( '\SitePress' ) ) {
			$this->markTestSkipped( 'WPML appears to be loaded in the test environment.' );
		}

		$manager = new Srfm_Test_Multilingual_Manager();
		$this->assertInstanceOf( Null_Provider::class, $manager->provider() );
	}

	public function test_srfm_multilingual_provider_filter_overrides_default() {
		$stub   = new Srfm_Stub_Multilingual_Provider();
		$filter = static function () use ( $stub ) {
			return $stub;
		};
		add_filter( 'srfm_multilingual_provider', $filter, 10, 1 );

		$manager = new Srfm_Test_Multilingual_Manager();

		remove_filter( 'srfm_multilingual_provider', $filter, 10 );

		$this->assertSame( $stub, $manager->provider() );
	}

	public function test_srfm_multilingual_provider_filter_ignores_invalid_value() {
		$filter = static function () {
			return 'not-a-provider';
		};
		add_filter( 'srfm_multilingual_provider', $filter, 10, 1 );

		$manager = new Srfm_Test_Multilingual_Manager();

		remove_filter( 'srfm_multilingual_provider', $filter, 10 );

		// Falls back to the default (Null_Provider when WPML is inactive).
		$this->assertInstanceOf( Provider::class, $manager->provider() );
		$this->assertNotSame( 'not-a-provider', $manager->provider() );
	}

	public function test_resolve_provider_returns_provider_instance() {
		// Indirectly exercise the protected resolve_provider() via the public provider()
		// surface — every instantiation routes through resolve_provider().
		$manager = new Srfm_Test_Multilingual_Manager();
		$this->assertInstanceOf( Provider::class, $manager->provider() );
	}
}
