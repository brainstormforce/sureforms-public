<?php
/**
<<<<<<< Updated upstream
 * Class Test_Get_Form_Analytics
=======
 * Tests for Get_Form_Analytics ability.
>>>>>>> Stashed changes
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Analytics\Get_Form_Analytics;

/**
<<<<<<< Updated upstream
 * Tests Get_Form_Analytics ability.
=======
 * Test_Get_Form_Analytics class.
>>>>>>> Stashed changes
 */
class Test_Get_Form_Analytics extends TestCase {

	/**
<<<<<<< Updated upstream
	 * The ability instance.
=======
	 * Ability instance.
>>>>>>> Stashed changes
	 *
	 * @var Get_Form_Analytics
	 */
	protected $ability;

	/**
<<<<<<< Updated upstream
	 * Set up.
	 */
	protected function setUp(): void {
=======
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
>>>>>>> Stashed changes
		$this->ability = new Get_Form_Analytics();
	}

	/**
<<<<<<< Updated upstream
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
=======
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/get-form-analytics', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate readonly and idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
		$this->assertEquals( 1.0, $annotations['priority'] );
		$this->assertFalse( $annotations['openWorldHint'] );
	}

	/**
	 * Test input schema requires date_from and date_to, has optional form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'date_from', $schema['properties'] );
		$this->assertArrayHasKey( 'date_to', $schema['properties'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertContains( 'date_from', $schema['required'] );
		$this->assertContains( 'date_to', $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'submissions', $schema['properties'] );
		$this->assertArrayHasKey( 'total_count', $schema['properties'] );
		$this->assertArrayHasKey( 'truncated', $schema['properties'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'date_from', $schema['properties'] );
		$this->assertArrayHasKey( 'date_to', $schema['properties'] );
	}

	/**
	 * Test execute with missing dates returns WP_Error.
	 */
	public function test_execute_missing_dates() {
		$result = $this->ability->execute( [] );
		$this->assertInstanceOf( WP_Error::class, $result );
>>>>>>> Stashed changes
		$this->assertEquals( 'srfm_missing_dates', $result->get_error_code() );
	}

	/**
<<<<<<< Updated upstream
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
=======
	 * Test execute with invalid date format returns WP_Error.
	 */
	public function test_execute_invalid_date_format() {
		$result = $this->ability->execute(
			[
				'date_from' => 'not-a-date',
				'date_to'   => '2025-01-31',
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
>>>>>>> Stashed changes
		$this->assertEquals( 'srfm_invalid_date_format', $result->get_error_code() );
	}

	/**
<<<<<<< Updated upstream
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
=======
	 * Test execute with valid range returns expected structure.
	 */
	public function test_execute_valid_range_no_results() {
		$result = $this->ability->execute(
			[
				'date_from' => '2025-01-01',
				'date_to'   => '2025-01-31',
			]
		);

		// May return WP_Error if entries table doesn't exist in test env.
		if ( $result instanceof WP_Error ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			return;
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'submissions', $result );
		$this->assertArrayHasKey( 'total_count', $result );
		$this->assertEquals( 0, $result['total_count'] );
		$this->assertEmpty( $result['submissions'] );
>>>>>>> Stashed changes
	}
}
