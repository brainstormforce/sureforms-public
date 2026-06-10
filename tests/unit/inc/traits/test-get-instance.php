<?php
/**
 * Class Test_Get_Instance
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Fixture class that uses the Get_Instance trait so the singleton behaviour can
 * be exercised without depending on any production singleton.
 */
class Srfm_Get_Instance_Fixture {
	use \SRFM\Inc\Traits\Get_Instance;
}

/**
 * Tests for the Get_Instance trait.
 */
class Test_Get_Instance extends TestCase {

	public function test_reset_instance() {
		// First resolution caches an instance.
		$first = Srfm_Get_Instance_Fixture::get_instance();
		$this->assertInstanceOf( Srfm_Get_Instance_Fixture::class, $first );

		// Repeated calls return the same cached instance.
		$this->assertSame( $first, Srfm_Get_Instance_Fixture::get_instance() );

		// After reset, the next resolution builds a fresh instance.
		Srfm_Get_Instance_Fixture::reset_instance();
		$second = Srfm_Get_Instance_Fixture::get_instance();

		$this->assertInstanceOf( Srfm_Get_Instance_Fixture::class, $second );
		$this->assertNotSame( $first, $second );
	}
}
