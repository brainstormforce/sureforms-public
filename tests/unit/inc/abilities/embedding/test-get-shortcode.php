<?php
/**
 * Tests for Get_Shortcode ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Get_Shortcode;

/**
 * Test_Get_Shortcode class.
 */
class Test_Get_Shortcode extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Get_Shortcode
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
				'post_title'  => 'Shortcode Form',
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
		$this->ability = new Get_Shortcode();
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
		$this->assertEquals( 'sureforms/get-shortcode', $this->ability->get_id() );
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
	 * Test input schema requires form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertContains( 'form_id', $schema['required'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'shortcode', $schema['properties'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent form returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute( [ 'form_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with valid form returns shortcode and block markup.
	 */
	public function test_execute_valid_form() {
		$result = $this->ability->execute( [ 'form_id' => self::$form_id ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertEquals( self::$form_id, $result['form_id'] );

		// Shortcode format: [sureforms id="N"]
		$this->assertStringContainsString( '[sureforms', $result['shortcode'] );
		$this->assertStringContainsString( (string) self::$form_id, $result['shortcode'] );

		// Block markup format: <!-- wp:srfm/form {"id":N} /-->
		$this->assertStringContainsString( 'wp:srfm/form', $result['block_markup'] );
		$this->assertStringContainsString( (string) self::$form_id, $result['block_markup'] );
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
