<?php
/**
 * Tests for Get_Entry ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\Get_Entry;

/**
 * Test_Get_Entry class.
 */
class Test_Get_Entry extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Get_Entry
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Entry();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/get-entry', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate readonly and idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires entry_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'entry_id', $schema['properties'] );
		$this->assertContains( 'entry_id', $schema['required'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'form_data', $schema['properties'] );
		$this->assertArrayHasKey( 'submission_info', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent entry returns WP_Error.
	 */
	public function test_execute_nonexistent_entry() {
		$result = $this->ability->execute( [ 'entry_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with zero entry_id returns WP_Error.
	 */
	public function test_execute_zero_entry_id() {
		$result = $this->ability->execute( [ 'entry_id' => 0 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with missing entry_id returns WP_Error.
	 */
	public function test_execute_missing_entry_id() {
		$result = $this->ability->execute( [] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
