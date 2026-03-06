<?php
/**
 * Tests for Get_Form_Stats ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Get_Form_Stats;

/**
 * Test_Get_Form_Stats class.
 */
class Test_Get_Form_Stats extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Get_Form_Stats
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
				'post_title'  => 'Stats Form',
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
		$this->ability = new Get_Form_Stats();
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
		$this->assertEquals( 'sureforms/get-form-stats', $this->ability->get_id() );
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
	 * Test input schema has optional form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		// form_id is optional — no 'required' key or not in required.
		if ( isset( $schema['required'] ) ) {
			$this->assertNotContains( 'form_id', $schema['required'] );
		}
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'total_entries', $schema['properties'] );
		$this->assertArrayHasKey( 'unread_count', $schema['properties'] );
		$this->assertArrayHasKey( 'read_count', $schema['properties'] );
		$this->assertArrayHasKey( 'trash_count', $schema['properties'] );
	}

	/**
	 * Test execute with non-existent form_id returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute( [ 'form_id' => 999999 ] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with valid form returns stats array.
	 */
	public function test_execute_valid_form() {
		$result = $this->ability->execute( [ 'form_id' => self::$form_id ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'total_entries', $result );
		$this->assertArrayHasKey( 'unread_count', $result );
		$this->assertArrayHasKey( 'read_count', $result );
		$this->assertArrayHasKey( 'trash_count', $result );
		$this->assertIsInt( $result['total_entries'] );
	}

	/**
	 * Test execute with zero form_id returns global stats.
	 */
	public function test_execute_global_stats() {
		$result = $this->ability->execute( [ 'form_id' => 0 ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'total_entries', $result );
	}
}
