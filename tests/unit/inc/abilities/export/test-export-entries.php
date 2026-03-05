<?php
/**
 * Class Test_Export_Entries
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Export\Export_Entries;

/**
 * Tests Export_Entries ability.
 */
class Test_Export_Entries extends TestCase {

	/**
	 * The ability instance.
	 *
	 * @var Export_Entries
	 */
	protected $ability;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->ability = new Export_Entries();
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
	 * Test that per_page is capped at 500.
	 */
	public function test_per_page_capped_at_500() {
		$schema  = $this->ability->get_input_schema();
		$per_page = $schema['properties']['per_page'];

		$this->assertEquals( 500, $per_page['maximum'] );
		$this->assertEquals( 1, $per_page['minimum'] );
		$this->assertEquals( 100, $per_page['default'] );
	}

	/**
	 * Test that invalid date returns WP_Error.
	 */
	public function test_invalid_date_returns_error() {
		$result = $this->ability->execute(
			[
				'date_from' => 'not-a-date',
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_invalid_date_format', $result->get_error_code() );
	}

	/**
	 * Test that the schema status enum is correctly defined.
	 */
	public function test_schema_status_enum() {
		$schema = $this->ability->get_input_schema();
		$status = $schema['properties']['status'];

		$this->assertContains( 'all', $status['enum'] );
		$this->assertContains( 'read', $status['enum'] );
		$this->assertContains( 'unread', $status['enum'] );
		$this->assertContains( 'trash', $status['enum'] );
		$this->assertCount( 4, $status['enum'] );
	}
}
