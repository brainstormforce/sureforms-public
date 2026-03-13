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
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Shortcode();
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
	 * Test execute returns result.
	 */
	public function test_execute() {
		$result = $this->ability->execute( [ 'form_id' => 0 ] );
		$this->assertTrue( is_array( $result ) || $result instanceof \WP_Error );
	}
}
