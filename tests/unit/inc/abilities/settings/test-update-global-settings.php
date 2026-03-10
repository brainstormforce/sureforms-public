<?php
/**
 * Tests for Update_Global_Settings ability.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Settings\Update_Global_Settings;

/**
 * Test_Update_Global_Settings class.
 */
class Test_Update_Global_Settings extends TestCase {

	/**
	 * Ability instance.
	 *
	 * @var Update_Global_Settings
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Update_Global_Settings();
	}

	/**
	 * Test constructor sets correct properties.
	 */
	public function test_constructor() {
		$this->assertEquals( 'sureforms/update-global-settings', $this->ability->get_id() );
	}

	/**
	 * Test annotations indicate write, destructive, and idempotent.
	 */
	public function test_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
		$this->assertEquals( 2.0, $annotations['priority'] );
		$this->assertFalse( $annotations['openWorldHint'] );
	}

	/**
	 * Test input schema requires category and settings.
	 */
	public function test_input_schema() {
		$schema = $this->ability->get_input_schema();
		$this->assertArrayHasKey( 'category', $schema['properties'] );
		$this->assertArrayHasKey( 'settings', $schema['properties'] );
		$this->assertContains( 'category', $schema['required'] );
		$this->assertContains( 'settings', $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * Test output schema has expected keys.
	 */
	public function test_output_schema() {
		$schema = $this->ability->get_output_schema();
		$this->assertArrayHasKey( 'saved', $schema['properties'] );
		$this->assertArrayHasKey( 'category', $schema['properties'] );
	}

	/**
	 * Test that gated property is set to srfm_abilities_api_edit.
	 */
	public function test_gated_property() {
		$reflection = new \ReflectionProperty( $this->ability, 'gated' );
		$reflection->setAccessible( true );
		$this->assertEquals( 'srfm_abilities_api_edit', $reflection->getValue( $this->ability ) );
	}

	/**
	 * Test execute with missing category returns WP_Error.
	 */
	public function test_execute_missing_category() {
		$result = $this->ability->execute(
			[
				'settings' => [ 'srfm_ip_log' => true ],
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with missing settings returns WP_Error.
	 */
	public function test_execute_missing_settings() {
		$result = $this->ability->execute(
			[
				'category' => 'general',
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test execute with invalid category returns WP_Error.
	 */
	public function test_execute_invalid_category() {
		$result = $this->ability->execute(
			[
				'category' => 'nonexistent',
				'settings' => [ 'key' => 'value' ],
			]
		);
		$this->assertInstanceOf( WP_Error::class, $result );
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

	/**
	 * Alias for test_output_schema — satisfies method-name coverage check.
	 */
	public function test_get_output_schema() {
		$this->test_output_schema();
	}
}
