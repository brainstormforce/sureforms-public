<?php
/**
 * Tests for Delete_Entry ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\Delete_Entry;

/**
 * Test_Delete_Entry class.
 */
class Test_Delete_Entry extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Delete_Entry
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Delete_Entry();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/delete-entry', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate destructive.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires entry_ids.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'entry_ids', $schema['properties'] );
		$this->assertContains( 'entry_ids', $schema['required'] );
		$this->assertEquals( 'array', $schema['properties']['entry_ids']['type'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'deleted', $schema['properties'] );
		$this->assertArrayHasKey( 'errors', $schema['properties'] );
	}

	/**
	 * Test execute with missing entry_ids returns WP_Error.
	 */
	public function test_execute_missing_entry_ids() {
		$result = $this->ability->execute( [] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with empty entry_ids returns WP_Error.
	 */
	public function test_execute_empty_entry_ids() {
		$result = $this->ability->execute( [ 'entry_ids' => [] ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
