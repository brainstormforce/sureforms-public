<?php
/**
 * Class Test_Provider
 *
 * Contract tests for the multilingual Provider interface. Every concrete
 * implementation must honour these signatures, so we verify them via
 * Reflection rather than instantiating an interface (which is impossible).
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;

/**
 * Tests for the Provider interface contract.
 */
class Test_Provider extends TestCase {

	/**
	 * Assert that a method exists on the Provider interface with the expected
	 * parameter and return shapes.
	 *
	 * @param string                $method        Method name to inspect.
	 * @param int                   $param_count   Expected parameter count.
	 * @param string                $return_type   Expected return type as a string.
	 * @param bool                  $return_nullable Whether the return type is nullable.
	 * @return void
	 */
	private function assert_signature( $method, $param_count, $return_type, $return_nullable = false ) {
		$reflection = new \ReflectionClass( Provider::class );

		$this->assertTrue( $reflection->hasMethod( $method ), "Provider interface is missing required method: {$method}" );

		$method_ref = $reflection->getMethod( $method );

		$this->assertSame( $param_count, $method_ref->getNumberOfParameters(), "Provider::{$method}() parameter count mismatch." );

		$return = $method_ref->getReturnType();
		$this->assertNotNull( $return, "Provider::{$method}() must declare a return type." );

		$this->assertSame( $return_type, (string) $return, "Provider::{$method}() return type mismatch." );
		$this->assertSame( $return_nullable, $return->allowsNull(), "Provider::{$method}() return-type nullability mismatch." );
	}

	public function test_is_active() {
		$this->assert_signature( 'is_active', 0, 'bool' );
	}

	public function test_current_language() {
		$this->assert_signature( 'current_language', 0, 'string' );
	}

	public function test_default_language() {
		$this->assert_signature( 'default_language', 0, 'string' );
	}

	public function test_register_string() {
		$this->assert_signature( 'register_string', 3, 'void' );
	}

	public function test_translate() {
		$this->assert_signature( 'translate', 4, 'string' );
	}

	public function test_switch_language() {
		$this->assert_signature( 'switch_language', 1, 'void' );
	}

	public function test_restore_language() {
		$this->assert_signature( 'restore_language', 0, 'void' );
	}

	public function test_render_language_switcher() {
		$this->assert_signature( 'render_language_switcher', 0, 'string' );
	}

	public function test_supports_packages() {
		$this->assert_signature( 'supports_packages', 0, 'bool' );
	}

	public function test_start_package() {
		$this->assert_signature( 'start_package', 1, 'void' );
	}

	public function test_finish_package() {
		$this->assert_signature( 'finish_package', 1, 'void' );
	}

	public function test_register_package_string() {
		$this->assert_signature( 'register_package_string', 5, 'void' );
	}

	public function test_translate_package_string() {
		$this->assert_signature( 'translate_package_string', 3, 'string' );
	}
}
