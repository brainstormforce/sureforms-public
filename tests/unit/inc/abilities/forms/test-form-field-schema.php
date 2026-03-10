<?php
/**
 * Tests for Form_Field_Schema trait.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Update_Form;

/**
 * Test_Form_Field_Schema class.
 *
 * Uses Update_Form as a concrete class that uses the Form_Field_Schema trait.
 */
class Test_Form_Field_Schema extends TestCase {

	/**
	 * Ability instance that uses the trait.
	 *
	 * @var Update_Form
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Update_Form();
	}

	/**
	 * Test get_form_field_schema returns array with expected field types.
	 */
	public function test_get_form_field_schema() {
		// Access the protected method via reflection.
		$reflection = new \ReflectionMethod( $this->ability, 'get_form_field_schema' );
		$reflection->setAccessible( true );
		$schema = $reflection->invoke( $this->ability );

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'label', $schema );
		$this->assertArrayHasKey( 'fieldType', $schema );

		// Verify fieldType has enum with expected block types.
		$field_type_enum = $schema['fieldType']['enum'];
		$this->assertContains( 'input', $field_type_enum );
		$this->assertContains( 'email', $field_type_enum );
		$this->assertContains( 'textarea', $field_type_enum );
		$this->assertContains( 'number', $field_type_enum );
		$this->assertContains( 'dropdown', $field_type_enum );
	}
}
