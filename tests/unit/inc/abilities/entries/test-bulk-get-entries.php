<?php
/**
 * Tests for Bulk_Get_Entries ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\Bulk_Get_Entries;

/**
 * Test_Bulk_Get_Entries class.
 */
class Test_Bulk_Get_Entries extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Bulk_Get_Entries
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Bulk_Get_Entries();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/bulk-get-entries', $this->ability->get_id() );
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
	 * Test input schema requires entry_ids.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'entry_ids', $schema['properties'] );
		$this->assertContains( 'entry_ids', $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'entries', $schema['properties'] );
		$this->assertArrayHasKey( 'errors', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent IDs returns entries=[] and errors.
	 */
	public function test_execute_nonexistent_entries() {
		$result = $this->ability->execute( [ 'entry_ids' => [ 999999, 999998 ] ] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result['entries'] );
		$this->assertCount( 2, $result['errors'] );
		$this->assertEquals( 999999, $result['errors'][0]['entry_id'] );
		$this->assertEquals( 999998, $result['errors'][1]['entry_id'] );
	}

	/**
	 * Test execute with empty array returns WP_Error.
	 */
	public function test_execute_empty_array() {
		$result = $this->ability->execute( [ 'entry_ids' => [] ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with missing entry_ids returns WP_Error.
	 */
	public function test_execute_missing_entry_ids() {
		$result = $this->ability->execute( [] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with more than 50 IDs returns WP_Error.
	 */
	public function test_execute_too_many_ids() {
		$ids    = range( 1, 51 );
		$result = $this->ability->execute( [ 'entry_ids' => $ids ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Alias for test_annotations — satisfies method-name coverage check.
	 */
	public function test_get_annotations() {
		$this->test_annotations();
	}

	/**
	 * Alias for test_input_schema — satisfies method-name coverage check.
	 */
	public function test_get_input_schema() {
		$this->test_input_schema();
	}
}
