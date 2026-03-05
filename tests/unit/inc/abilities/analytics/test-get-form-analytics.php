<?php
/**
 * Class Test_Get_Form_Analytics
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Analytics\Get_Form_Analytics;

/**
 * Tests Get_Form_Analytics ability.
 */
class Test_Get_Form_Analytics extends TestCase {

	/**
	 * The ability instance.
	 *
	 * @var Get_Form_Analytics
	 */
	protected $ability;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->ability = new Get_Form_Analytics();
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
	 * Test execute returns error when dates are missing.
	 */
	public function test_execute_missing_dates_returns_error() {
		$result = $this->ability->execute( [] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_missing_dates', $result->get_error_code() );
	}

	/**
	 * Test execute returns error for invalid date format.
	 */
	public function test_execute_invalid_date_format_returns_error() {
		$result = $this->ability->execute(
			[
				'date_from' => '2024/01/01',
				'date_to'   => '2024-01-31',
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_invalid_date_format', $result->get_error_code() );
	}

	/**
	 * Test MAX_RESULTS constant exists and has a sensible value.
	 */
	public function test_max_results_constant() {
		$this->assertEquals( 10000, Get_Form_Analytics::MAX_RESULTS );
	}

	/**
	 * Test that the input schema requires date_from and date_to.
	 */
	public function test_schema_requires_date_from_and_date_to() {
		$schema = $this->ability->get_input_schema();

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'date_from', $schema['required'] );
		$this->assertContains( 'date_to', $schema['required'] );
	}
}
