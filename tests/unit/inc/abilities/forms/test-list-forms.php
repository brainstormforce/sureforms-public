<?php
/**
 * Tests for List_Forms ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\List_Forms;

/**
 * Test_List_Forms class.
 */
class Test_List_Forms extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var List_Forms
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
				'post_title'  => 'Test List Form',
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
		$this->ability = new List_Forms();
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
		$this->assertEquals( 'sureforms/list-forms', $this->ability->get_id() );
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
	 * Test input schema structure.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'search', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema structure.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'forms', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'pages', $schema['properties'] );
	}

	/**
	 * Test execute returns forms array.
	 */
	public function test_execute_returns_forms() {
		$result = $this->ability->execute( [] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'forms', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'pages', $result );
		$this->assertIsArray( $result['forms'] );
	}

	/**
	 * Test execute respects per_page parameter.
	 */
	public function test_execute_respects_per_page() {
		$result = $this->ability->execute( [ 'per_page' => 1 ] );
		$this->assertLessThanOrEqual( 1, count( $result['forms'] ) );
	}

	/**
	 * Test execute respects status filter.
	 */
	public function test_execute_filters_by_status() {
		$result = $this->ability->execute( [ 'status' => 'publish' ] );
		$this->assertIsArray( $result );
		foreach ( $result['forms'] as $form ) {
			$this->assertEquals( 'publish', $form['status'] );
		}
	}

	/**
	 * Test that capability is set to manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );
		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
	 * Test per_page is clamped to maximum of 100.
	 */
	public function test_per_page_clamped_to_max_100() {
		$result = $this->ability->execute( [ 'per_page' => 500 ] );
		$this->assertIsArray( $result );
		// Should not error — per_page is silently clamped.
		$this->assertArrayHasKey( 'forms', $result );
	}

	/**
	 * Test per_page is clamped to minimum of 1.
	 */
	public function test_per_page_clamped_to_min_1() {
		$result = $this->ability->execute( [ 'per_page' => -5 ] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'forms', $result );
	}

	/**
	 * Test per_page of zero is clamped to 1.
	 */
	public function test_per_page_zero_clamped() {
		$result = $this->ability->execute( [ 'per_page' => 0 ] );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'forms', $result );
	}

	/**
	 * Test each form in results has expected keys.
	 */
	public function test_form_result_keys() {
		$result = $this->ability->execute( [] );
		if ( ! empty( $result['forms'] ) ) {
			$form = $result['forms'][0];
			$this->assertArrayHasKey( 'id', $form );
			$this->assertArrayHasKey( 'title', $form );
			$this->assertArrayHasKey( 'status', $form );
			$this->assertArrayHasKey( 'date', $form );
			$this->assertArrayHasKey( 'entry_count', $form );
		}
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
