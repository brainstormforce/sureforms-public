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
	 * Test sanitize_form_fields sanitizes field data.
	 */
	public function test_sanitize_form_fields() {
		$reflection = new \ReflectionMethod( $this->ability, 'sanitize_form_fields' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke( $this->ability, [] );
		$this->assertIsArray( $result );
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

	/**
	 * Test the schema exposes a string `className` property.
	 */
	public function test_schema_exposes_class_name_property() {
		$reflection = new \ReflectionMethod( $this->ability, 'get_form_field_schema' );
		$reflection->setAccessible( true );
		$schema = $reflection->invoke( $this->ability );

		$this->assertArrayHasKey( 'className', $schema );
		$this->assertSame( 'string', $schema['className']['type'] );
	}

	/**
	 * Test sanitize_form_fields preserves a valid single className.
	 */
	public function test_sanitize_form_fields_preserves_single_class() {
		$reflection = new \ReflectionMethod( $this->ability, 'sanitize_form_fields' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke(
			$this->ability,
			[
				[
					'label'     => 'Name',
					'className' => 'vk-0',
				],
			]
		);
		$this->assertSame( 'vk-0', $result[0]['className'] );
	}

	/**
	 * Test sanitize_form_fields keeps multiple space-separated classes.
	 */
	public function test_sanitize_form_fields_preserves_multiple_classes() {
		$reflection = new \ReflectionMethod( $this->ability, 'sanitize_form_fields' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke(
			$this->ability,
			[
				[
					'label'     => 'Name',
					'className' => '  vk-0   highlight ',
				],
			]
		);
		$this->assertSame( 'vk-0 highlight', $result[0]['className'] );
	}

	/**
	 * Test sanitize_form_fields strips invalid CSS-class characters per token.
	 */
	public function test_sanitize_form_fields_strips_invalid_class_chars() {
		$reflection = new \ReflectionMethod( $this->ability, 'sanitize_form_fields' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke(
			$this->ability,
			[
				[
					'label'     => 'Name',
					'className' => 'vk-0! <bad> ok_2',
				],
			]
		);
		// sanitize_html_class drops disallowed chars: "vk-0!" -> "vk-0", "<bad>" -> "bad", "ok_2" kept.
		$this->assertSame( 'vk-0 bad ok_2', $result[0]['className'] );
	}
}
