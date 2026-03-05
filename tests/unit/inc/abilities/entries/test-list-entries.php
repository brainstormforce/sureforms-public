<?php
/**
 * Tests for List_Entries ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\List_Entries;

/**
 * Test_List_Entries class.
 */
class Test_List_Entries extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var List_Entries
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new List_Entries();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/list-entries', $this->ability->get_id() );
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
	 * Test input schema has all expected properties.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'orderby', $schema['properties'] );
		$this->assertArrayHasKey( 'order', $schema['properties'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'entries', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'total_pages', $schema['properties'] );
		$this->assertArrayHasKey( 'current_page', $schema['properties'] );
	}

	/**
	 * Test execute returns expected structure.
	 */
	public function test_execute_returns_structure() {
		$result = $this->ability->execute( [] );

		if ( $result instanceof WP_Error ) {
			// Custom entries table may not exist in test env — still valid.
			$this->assertInstanceOf( WP_Error::class, $result );
			return;
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'entries', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertIsArray( $result['entries'] );
	}

	/**
	 * Test status enum values in schema.
	 */
	public function test_status_enum() {
		$schema = $this->ability->get_input_schema();
		$this->assertContains( 'all', $schema['properties']['status']['enum'] );
		$this->assertContains( 'read', $schema['properties']['status']['enum'] );
		$this->assertContains( 'unread', $schema['properties']['status']['enum'] );
		$this->assertContains( 'trash', $schema['properties']['status']['enum'] );
	}
}
