<?php
/**
<<<<<<< Updated upstream
 * Class Test_Get_Global_Settings
=======
 * Tests for Get_Global_Settings ability.
>>>>>>> Stashed changes
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Settings\Get_Global_Settings;

/**
<<<<<<< Updated upstream
 * Tests Get_Global_Settings ability.
=======
 * Test_Get_Global_Settings class.
>>>>>>> Stashed changes
 */
class Test_Get_Global_Settings extends TestCase {

	/**
<<<<<<< Updated upstream
	 * The ability instance.
=======
	 * Ability instance.
>>>>>>> Stashed changes
	 *
	 * @var Get_Global_Settings
	 */
	protected $ability;

	/**
<<<<<<< Updated upstream
	 * Set up.
	 */
	protected function setUp(): void {
=======
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
>>>>>>> Stashed changes
		$this->ability = new Get_Global_Settings();
	}

	/**
<<<<<<< Updated upstream
	 * Test that the required capability is manage_options.
=======
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
>>>>>>> Stashed changes
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );
<<<<<<< Updated upstream

=======
>>>>>>> Stashed changes
		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
<<<<<<< Updated upstream
	 * Test that secret keys are masked in the output.
	 */
	public function test_secret_keys_masked() {
		// Store security settings with real secret values.
		update_option(
			'srfm_security_settings_options',
			[
				'srfm_v2_checkbox_site_key'    => 'site-key-123',
				'srfm_v2_checkbox_secret_key'  => 'real-secret-value',
				'srfm_v2_invisible_site_key'   => '',
				'srfm_v2_invisible_secret_key' => 'another-secret',
				'srfm_v3_site_key'             => '',
				'srfm_v3_secret_key'           => '',
				'srfm_cf_appearance_mode'      => 'auto',
				'srfm_cf_turnstile_site_key'   => '',
				'srfm_cf_turnstile_secret_key' => '',
				'srfm_hcaptcha_site_key'       => '',
				'srfm_hcaptcha_secret_key'     => '',
				'srfm_honeypot'                => false,
			]
		);

		$result = $this->ability->execute( [ 'categories' => [ 'security' ] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'security', $result );

		// Secret keys with values should be masked.
		$this->assertEquals( '********', $result['security']['srfm_v2_checkbox_secret_key'] );
		$this->assertEquals( '********', $result['security']['srfm_v2_invisible_secret_key'] );

		// Site key should NOT be masked.
		$this->assertEquals( 'site-key-123', $result['security']['srfm_v2_checkbox_site_key'] );

		delete_option( 'srfm_security_settings_options' );
	}

	/**
	 * Test that the schema categories enum is correctly defined.
	 */
	public function test_schema_categories_enum() {
		$schema     = $this->ability->get_input_schema();
		$categories = $schema['properties']['categories']['items']['enum'];

		$this->assertContains( 'general', $categories );
		$this->assertContains( 'validation-messages', $categories );
		$this->assertContains( 'email-summary', $categories );
		$this->assertContains( 'security', $categories );
		$this->assertCount( 4, $categories );
=======
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
>>>>>>> Stashed changes
	}
}
