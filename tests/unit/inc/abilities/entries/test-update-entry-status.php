<?php
/**
 * Tests for Update_Entry_Status ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\Update_Entry_Status;

/**
 * Test_Update_Entry_Status class.
 */
class Test_Update_Entry_Status extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Update_Entry_Status
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Update_Entry_Status();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/update-entry-status', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate write and idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires entry_ids and status.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'entry_ids', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertContains( 'entry_ids', $schema['required'] );
		$this->assertContains( 'status', $schema['required'] );
	}

	/**
	 * Test status enum values.
	 */
	public function test_status_enum() {
		$schema = $this->ability->get_input_schema();
		$enum   = $schema['properties']['status']['enum'];
		$this->assertContains( 'read', $enum );
		$this->assertContains( 'unread', $enum );
		$this->assertContains( 'trash', $enum );
		$this->assertContains( 'restore', $enum );
	}

	/**
	 * Test execute with missing entry_ids returns WP_Error.
	 */
	public function test_execute_missing_entry_ids() {
		$result = $this->ability->execute( [ 'status' => 'read' ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with missing status returns WP_Error.
	 */
	public function test_execute_missing_status() {
		$result = $this->ability->execute( [ 'entry_ids' => [ 1 ] ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with empty entry_ids returns WP_Error.
	 */
	public function test_execute_empty_entry_ids() {
		$result = $this->ability->execute(
			[
				'entry_ids' => [],
				'status'    => 'read',
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
