<?php
/**
 * Tests for Duplicate_Form ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Duplicate_Form;

/**
 * Test_Duplicate_Form_Ability class.
 */
class Test_Duplicate_Form_Ability extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Duplicate_Form
	 */
	protected $ability;

	/**
	 * Test form ID.
	 *
	 * @var int
	 */
	protected static $form_id;

	/**
	 * Set up class fixtures.
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		self::$form_id = wp_insert_post(
			[
				'post_title'  => 'Duplicate Me',
				'post_type'   => 'sureforms_form',
				'post_status' => 'publish',
			]
		);
	}

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Duplicate_Form();
	}

	/**
	 * Clean up class fixtures.
	 */
	public static function tear_down_after_class(): void {
		if ( self::$form_id ) {
			wp_delete_post( self::$form_id, true );
		}
		parent::tear_down_after_class();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/duplicate-form', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate non-idempotent write.
	 */
	public function test_get_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires form_id.
	 */
	public function test_get_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'title_suffix', $schema['properties'] );
		$this->assertContains( 'form_id', $schema['required'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_get_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'original_form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'new_form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'new_form_title', $schema['properties'] );
		$this->assertArrayHasKey( 'shortcode', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent form returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute( [ 'form_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute duplicates a valid form.
	 */
	public function test_execute_duplicates_form() {
		$result = $this->ability->execute( [ 'form_id' => self::$form_id ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertEquals( self::$form_id, $result['original_form_id'] );
		$this->assertGreaterThan( 0, $result['new_form_id'] );
		$this->assertNotEquals( self::$form_id, $result['new_form_id'] );
		$this->assertStringContainsString( 'sureforms', $result['shortcode'] );

		wp_delete_post( $result['new_form_id'], true );
	}
}
