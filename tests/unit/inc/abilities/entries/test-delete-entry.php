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
	public function test_get_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires entry_ids.
	 */
	public function test_get_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'entry_ids', $schema['properties'] );
		$this->assertContains( 'entry_ids', $schema['required'] );
		$this->assertEquals( 'array', $schema['properties']['entry_ids']['type'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_get_output_schema() {
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

	/**
	 * Test that MAX_ENTRIES constant is defined.
	 */
	public function test_max_entries_constant() {
		$this->assertEquals( 50, Delete_Entry::MAX_ENTRIES );
	}

	/**
	 * Test execute with more than MAX_ENTRIES IDs returns WP_Error.
	 */
	public function test_execute_too_many_ids() {
		$ids    = range( 1, 51 );
		$result = $this->ability->execute( [ 'entry_ids' => $ids ] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'srfm_too_many_entry_ids', $result->get_error_code() );
	}

	/**
	 * Test execute with exactly MAX_ENTRIES IDs does not return limit error.
	 */
	public function test_execute_at_max_entries_limit() {
		$ids    = range( 1, 50 );
		$result = $this->ability->execute( [ 'entry_ids' => $ids ] );
		// Should not be the "too many" error — may be other errors since entries don't exist.
		if ( $result instanceof WP_Error ) {
			$this->assertNotEquals( 'srfm_too_many_entry_ids', $result->get_error_code() );
		} else {
			$this->assertIsArray( $result );
		}
	}

	/**
	 * Test that capability is set to manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );
		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}
}
