<?php
/**
 * Class Test_Export_Forms
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Export\Export_Forms;

/**
 * Tests Export_Forms ability.
 */
class Test_Export_Forms extends TestCase {

	/**
	 * The ability instance.
	 *
	 * @var Export_Forms
	 */
	protected $ability;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->ability = new Export_Forms();
	}

	/**
	 * Test that the required capability is manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );

		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
	 * Test execute returns error when form_ids is empty.
	 */
	public function test_execute_empty_form_ids_returns_error() {
		$result = $this->ability->execute( [ 'form_ids' => [] ] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_missing_form_ids', $result->get_error_code() );
	}
}
