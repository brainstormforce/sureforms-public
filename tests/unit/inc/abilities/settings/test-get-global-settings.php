<?php
/**
 * Class Test_Get_Global_Settings
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Settings\Get_Global_Settings;

/**
 * Tests Get_Global_Settings ability.
 */
class Test_Get_Global_Settings extends TestCase {

	/**
	 * The ability instance.
	 *
	 * @var Get_Global_Settings
	 */
	protected $ability;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->ability = new Get_Global_Settings();
	}

	/**
	 * Test that the required capability is manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );

		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
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
	}
}
