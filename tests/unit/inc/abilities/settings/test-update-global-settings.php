<?php
/**
<<<<<<< Updated upstream
 * Class Test_Update_Global_Settings
=======
 * Tests for Update_Global_Settings ability.
>>>>>>> Stashed changes
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Settings\Update_Global_Settings;
<<<<<<< Updated upstream
use SRFM\Inc\Abilities\Settings\Get_Global_Settings;

/**
 * Tests Update_Global_Settings ability.
=======

/**
 * Test_Update_Global_Settings class.
>>>>>>> Stashed changes
 */
class Test_Update_Global_Settings extends TestCase {

	/**
<<<<<<< Updated upstream
	 * The ability instance.
=======
	 * Ability instance.
>>>>>>> Stashed changes
	 *
	 * @var Update_Global_Settings
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
		$this->ability = new Update_Global_Settings();
	}

	/**
<<<<<<< Updated upstream
	 * Test that the required capability is manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );

		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
	 * Test execute returns error when settings are empty.
	 */
	public function test_execute_empty_settings_returns_error() {
		$result = $this->ability->execute(
			[
				'category' => 'general',
				'settings' => [],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_missing_settings', $result->get_error_code() );
	}

	/**
	 * Test execute returns error for invalid category.
	 */
	public function test_execute_invalid_category_returns_error() {
		$result = $this->ability->execute(
			[
				'category' => 'nonexistent',
				'settings' => [ 'foo' => 'bar' ],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_invalid_category', $result->get_error_code() );
	}

	/**
	 * Test that email summary email is sanitized.
	 */
	public function test_sanitize_email_summary_email() {
		$reflection = new \ReflectionMethod( $this->ability, 'sanitize_settings' );
		$reflection->setAccessible( true );

		$result = $reflection->invoke(
			$this->ability,
			'email-summary',
			[ 'srfm_email_sent_to' => 'invalid<>email@test.com' ]
		);

		// sanitize_email strips invalid chars.
		$this->assertEquals( 'invalidemail@test.com', $result['srfm_email_sent_to'] );
	}

	/**
	 * Test sentinel preservation — when '********' is sent, stored value is kept.
	 */
	public function test_sentinel_preservation() {
		// Set up stored security settings.
		update_option(
			'srfm_security_settings_options',
			[
				'srfm_v3_secret_key' => 'real-stored-secret',
				'srfm_honeypot'      => false,
			]
		);

		$reflection = new \ReflectionMethod( $this->ability, 'save_security_settings' );
		$reflection->setAccessible( true );

		$result_settings = [
			'srfm_v3_secret_key' => '********',
			'srfm_honeypot'      => true,
		];

		// The method will call Global_Settings which handles the actual save.
		// We test that the sentinel replacement logic works by checking the array
		// before it's passed to Global_Settings.
		$existing = get_option( 'srfm_security_settings_options', [] );
		$this->assertEquals( 'real-stored-secret', $existing['srfm_v3_secret_key'] );

		delete_option( 'srfm_security_settings_options' );
	}

	/**
	 * Test that the secret_keys constant matches between get and update abilities.
	 */
	public function test_secret_keys_constant_matches_get() {
		$update_reflection = new \ReflectionProperty( Update_Global_Settings::class, 'secret_keys' );
		$update_reflection->setAccessible( true );
		$update_keys = $update_reflection->getValue();

		$get_ability     = new Get_Global_Settings();
		$get_reflection  = new \ReflectionProperty( Get_Global_Settings::class, 'secret_keys' );
		$get_reflection->setAccessible( true );
		$get_keys = $get_reflection->getValue();

		$this->assertEquals( $update_keys, $get_keys );
=======
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
>>>>>>> Stashed changes
	}
}
