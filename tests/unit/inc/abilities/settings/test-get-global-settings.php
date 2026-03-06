<?php
/**
 * Tests for Get_Global_Settings ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Settings\Get_Global_Settings;

/**
 * Test_Get_Global_Settings class.
 */
class Test_Get_Global_Settings extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Get_Global_Settings
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Global_Settings();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/get-global-settings', $this->ability->get_id() );
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
	 * Test input schema has categories property.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'categories', $schema['properties'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema has expected category keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'general', $schema['properties'] );
		$this->assertArrayHasKey( 'validation-messages', $schema['properties'] );
		$this->assertArrayHasKey( 'email-summary', $schema['properties'] );
		$this->assertArrayHasKey( 'security', $schema['properties'] );
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
	 * Test execute with empty input returns all 4 categories.
	 */
	public function test_execute_returns_all_categories() {
		$result = $this->ability->execute( [] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'general', $result );
		$this->assertArrayHasKey( 'validation-messages', $result );
		$this->assertArrayHasKey( 'email-summary', $result );
		$this->assertArrayHasKey( 'security', $result );
	}

	/**
	 * Test execute with single category returns only that category.
	 */
	public function test_execute_single_category() {
		$result = $this->ability->execute( [ 'categories' => [ 'general' ] ] );

		if ( $result instanceof WP_Error ) {
			$this->fail( 'Expected array, got WP_Error: ' . $result->get_error_message() );
		}

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'general', $result );
		$this->assertArrayNotHasKey( 'validation-messages', $result );
		$this->assertArrayNotHasKey( 'email-summary', $result );
		$this->assertArrayNotHasKey( 'security', $result );
	}
}
