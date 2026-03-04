<?php
/**
 * Tests for Create_Form ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Create_Form;

/**
 * Test_Create_Form class.
 */
class Test_Create_Form extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Create_Form
	 */
	protected $ability;

	/**
	 * Track created form IDs for cleanup.
	 *
	 * @var array<int>
	 */
	protected $created_forms = [];

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Create_Form();
	}

	/**
	 * Clean up created forms.
	 */
	protected function tearDown(): void {
		foreach ( $this->created_forms as $form_id ) {
			wp_delete_post( $form_id, true );
		}
		parent::tearDown();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/create-form', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate write, non-idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires formTitle and formFields.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'formTitle', $schema['properties'] );
		$this->assertArrayHasKey( 'formFields', $schema['properties'] );
		$this->assertContains( 'formTitle', $schema['required'] );
		$this->assertContains( 'formFields', $schema['required'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'edit_url', $schema['properties'] );
		$this->assertArrayHasKey( 'shortcode', $schema['properties'] );
	}

	/**
	 * Test that field_types filter is applied in schema.
	 */
	public function test_field_types_filter() {
		$filter_called = false;
		add_filter(
			'srfm_ability_create_form_field_types',
			function ( $types ) use ( &$filter_called ) {
				$filter_called = true;
				return $types;
			}
		);
		$this->ability->get_input_schema();
		$this->assertTrue( $filter_called );
	}

	/**
	 * Test execute with missing title returns WP_Error.
	 */
	public function test_execute_missing_title() {
		$result = $this->ability->execute(
			[
				'formFields' => [
					[ 'label' => 'Name', 'fieldType' => 'input' ],
				],
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with empty fields returns WP_Error.
	 */
	public function test_execute_empty_fields() {
		$result = $this->ability->execute(
			[
				'formTitle'  => 'Test Form',
				'formFields' => [],
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with valid input creates a form.
	 */
	public function test_execute_creates_form() {
		$result = $this->ability->execute(
			[
				'formTitle'  => 'PHPUnit Test Form',
				'formFields' => [
					[
						'label'     => 'Full Name',
						'fieldType' => 'input',
						'required'  => true,
					],
					[
						'label'     => 'Email',
						'fieldType' => 'email',
					],
				],
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'form_id', $result );
		$this->assertIsInt( $result['form_id'] );
		$this->assertGreaterThan( 0, $result['form_id'] );
		$this->assertEquals( 'PHPUnit Test Form', $result['title'] );
		$this->assertEquals( 'draft', $result['status'] );
		$this->assertStringContainsString( '[sureforms', $result['shortcode'] );

		$this->created_forms[] = $result['form_id'];
	}

	/**
	 * Test execute with custom status.
	 */
	public function test_execute_with_publish_status() {
		$result = $this->ability->execute(
			[
				'formTitle'  => 'Published Form',
				'formFields' => [
					[
						'label'     => 'Name',
						'fieldType' => 'input',
					],
				],
				'formStatus' => 'publish',
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertEquals( 'publish', $result['status'] );
		$this->created_forms[] = $result['form_id'];
	}
}
