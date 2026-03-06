<?php
/**
 * Tests for Get_Form ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Get_Form;

/**
 * Test_Get_Form class.
 */
class Test_Get_Form extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Get_Form
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
				'post_title'   => 'Test Get Form',
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/input {"slug":"srfm-input-1","label":"Name","required":true} /-->',
			]
		);
	}

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Form();
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
		$this->assertEquals( 'sureforms/get-form', $this->ability->get_id() );
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
	 * Test input schema requires form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertContains( 'form_id', $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema structure.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'fields', $schema['properties'] );
		$this->assertArrayHasKey( 'settings', $schema['properties'] );
		$this->assertArrayHasKey( 'shortcode', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent form returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute( [ 'form_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'srfm_form_not_found', $result->get_error_code() );
	}

	/**
	 * Test execute with zero form_id returns WP_Error.
	 */
	public function test_execute_zero_form_id() {
		$result = $this->ability->execute( [ 'form_id' => 0 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with valid form returns expected keys.
	 */
	public function test_execute_valid_form() {
		$result = $this->ability->execute( [ 'form_id' => self::$form_id ] );
		$this->assertIsArray( $result );
		$this->assertEquals( self::$form_id, $result['form_id'] );
		$this->assertEquals( 'Test Get Form', $result['title'] );
		$this->assertEquals( 'publish', $result['status'] );
		$this->assertIsArray( $result['fields'] );
		$this->assertIsArray( $result['settings'] );
		$this->assertStringContainsString( (string) self::$form_id, $result['shortcode'] );
	}

	/**
	 * Test execute with non-form post type returns WP_Error.
	 */
	public function test_execute_wrong_post_type() {
		$page_id = wp_insert_post(
			[
				'post_title' => 'Not a form',
				'post_type'  => 'page',
			]
		);
		$result = $this->ability->execute( [ 'form_id' => $page_id ] );
		$this->assertInstanceOf( WP_Error::class, $result );
		wp_delete_post( $page_id, true );
	}
}
