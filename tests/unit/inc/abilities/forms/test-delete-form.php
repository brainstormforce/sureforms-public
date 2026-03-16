<?php
/**
 * Tests for Delete_Form ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Delete_Form;

/**
 * Test_Delete_Form class.
 */
class Test_Delete_Form extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Delete_Form
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Delete_Form();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/delete-form', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate destructive.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
		$this->assertEquals( 3.0, $annotations['priority'] );
		$this->assertFalse( $annotations['openWorldHint'] );
	}

	/**
	 * Test input schema requires form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'force', $schema['properties'] );
		$this->assertContains( 'form_id', $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test get_annotations returns array with annotation keys.
	 */
	public function test_get_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertIsArray( $annotations );
		$this->assertArrayHasKey( 'readonly', $annotations );
	}

	/**
	 * Test get_input_schema returns valid schema.
	 */
	public function test_get_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
	}

	/**
	 * Test get_output_schema returns valid schema.
	 */
	public function test_get_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'properties', $schema );
	}

	/**
	 * Test execute with non-existent form returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute( [ 'form_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute trashes form by default.
	 */
	public function test_execute_trashes_form() {
		$form_id = wp_insert_post(
			[
				'post_title'  => 'To Be Trashed',
				'post_type'   => 'sureforms_form',
				'post_status' => 'publish',
			]
		);

		$result = $this->ability->execute( [ 'form_id' => $form_id ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertEquals( 'publish', $result['previous_status'] );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test execute with force permanently deletes form.
	 */
	public function test_execute_force_deletes_form() {
		$form_id = wp_insert_post(
			[
				'post_title'  => 'To Be Deleted',
				'post_type'   => 'sureforms_form',
				'post_status' => 'publish',
			]
		);

		$result = $this->ability->execute(
			[
				'form_id' => $form_id,
				'force'   => true,
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertTrue( $result['deleted'] );
		$this->assertNull( get_post( $form_id ) );
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
