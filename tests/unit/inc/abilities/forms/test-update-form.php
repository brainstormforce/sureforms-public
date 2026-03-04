<?php
/**
 * Tests for Update_Form ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Update_Form;

/**
 * Test_Update_Form class.
 */
class Test_Update_Form extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Update_Form
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
				'post_title'  => 'Original Title',
				'post_type'   => 'sureforms_form',
				'post_status' => 'draft',
			]
		);
	}

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Update_Form();
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
		$this->assertEquals( 'sureforms/update-form', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate write and idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires form_id.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertContains( 'form_id', $schema['required'] );
	}

	/**
	 * Test execute with non-existent form returns WP_Error.
	 */
	public function test_execute_nonexistent_form() {
		$result = $this->ability->execute(
			[
				'form_id' => 999999,
				'title'   => 'New Title',
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute updates title.
	 */
	public function test_execute_updates_title() {
		$result = $this->ability->execute(
			[
				'form_id' => self::$form_id,
				'title'   => 'Updated Title',
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertEquals( 'Updated Title', $result['title'] );
		$this->assertContains( 'title', $result['updated_fields'] );
	}

	/**
	 * Test execute updates status.
	 */
	public function test_execute_updates_status() {
		$result = $this->ability->execute(
			[
				'form_id' => self::$form_id,
				'status'  => 'publish',
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertEquals( 'publish', $result['status'] );
		$this->assertContains( 'status', $result['updated_fields'] );
	}

	/**
	 * Test execute with no changes.
	 */
	public function test_execute_no_changes() {
		// Re-fetch current title to pass it unchanged.
		$post   = get_post( self::$form_id );
		$result = $this->ability->execute(
			[
				'form_id' => self::$form_id,
				'title'   => $post->post_title,
			]
		);

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertNotContains( 'title', $result['updated_fields'] );
	}
}
