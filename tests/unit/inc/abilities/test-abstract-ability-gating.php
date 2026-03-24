<?php
/**
 * Tests for Abstract_Ability gating logic (permission_callback).
 *
 * Verifies that the $gated property controls ability availability
 * correctly based on the stored option value.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Create_Form;
use SRFM\Inc\Abilities\Forms\List_Forms;

/**
 * Test_Abstract_Ability_Gating class.
 */
class Test_Abstract_Ability_Gating extends TestCase {

	/**
	 * Gated ability instance (Create_Form has $gated = 'srfm_abilities_api_edit').
	 *
	 * @var Create_Form
	 */
	protected $gated_ability;

	/**
	 * Non-gated ability instance (List_Forms has no $gated value).
	 *
	 * @var List_Forms
	 */
	protected $ungated_ability;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected $admin_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	protected $subscriber_id;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->gated_ability   = new Create_Form();
		$this->ungated_ability = new List_Forms();

		$this->admin_id = wp_insert_user(
			[
				'user_login' => 'testadmin_gating_' . wp_generate_password( 4, false ),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			]
		);

		$this->subscriber_id = wp_insert_user(
			[
				'user_login' => 'testsub_gating_' . wp_generate_password( 4, false ),
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			]
		);

		// Ensure gating options are clean before each test.
		delete_option( 'srfm_abilities_api' );
		delete_option( 'srfm_abilities_api_edit' );

		// Enable master toggle by default so individual gate tests can run.
		update_option( 'srfm_abilities_api', true );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		delete_option( 'srfm_abilities_api' );
		delete_option( 'srfm_abilities_api_edit' );
		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->subscriber_id );
		parent::tearDown();
	}

	/**
	 * Test permission_callback returns true when gated option does not exist (default enabled).
	 */
	public function test_permission_callback_returns_true_when_gated_option_missing() {
		wp_set_current_user( $this->admin_id );

		// Option does not exist — ability should default to enabled.
		$this->assertTrue(
			$this->gated_ability->permission_callback(),
			'Gated ability should be enabled by default when option does not exist.'
		);
	}

	/**
	 * Test permission_callback returns true when gated option is explicitly enabled ('1').
	 */
	public function test_permission_callback_returns_true_when_gated_option_enabled() {
		wp_set_current_user( $this->admin_id );
		update_option( 'srfm_abilities_api_edit', '1' );

		$this->assertTrue(
			$this->gated_ability->permission_callback(),
			'Gated ability should be enabled when option is set to "1".'
		);
	}

	/**
	 * Test permission_callback returns false when gated option is explicitly disabled ('0').
	 */
	public function test_permission_callback_returns_false_when_gated_option_disabled() {
		wp_set_current_user( $this->admin_id );
		update_option( 'srfm_abilities_api_edit', '0' );

		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Gated ability should be disabled when option is set to "0".'
		);
	}

	/**
	 * Test permission_callback returns true for non-gated abilities regardless of option state.
	 */
	public function test_permission_callback_returns_true_when_not_gated() {
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->ungated_ability->permission_callback(),
			'Non-gated ability should always be enabled for authorized users.'
		);
	}

	/**
	 * Test permission_callback returns false for non-admin user even if gating allows.
	 */
	public function test_permission_callback_returns_false_for_non_admin() {
		wp_set_current_user( $this->subscriber_id );
		update_option( 'srfm_abilities_api_edit', '1' );

		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Gated ability should be disabled for users without the required capability.'
		);
	}

	/**
	 * Test permission_callback returns false for non-admin on non-gated ability.
	 */
	public function test_permission_callback_returns_false_for_non_admin_ungated() {
		wp_set_current_user( $this->subscriber_id );

		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'Non-gated ability should still require proper capability.'
		);
	}

	/**
	 * Test permission_callback returns false when master toggle is off.
	 */
	public function test_permission_callback_returns_false_when_master_toggle_off() {
		wp_set_current_user( $this->admin_id );
		update_option( 'srfm_abilities_api', false );

		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'All abilities should be blocked when master toggle is off.'
		);

		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Gated abilities should also be blocked when master toggle is off.'
		);
	}

	/**
	 * Test permission_callback returns false when master toggle option does not exist.
	 */
	public function test_permission_callback_returns_false_when_master_toggle_missing() {
		wp_set_current_user( $this->admin_id );
		delete_option( 'srfm_abilities_api' );

		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'Abilities should be blocked by default when master toggle option does not exist.'
		);
	}

	/**
	 * Test is_enabled returns true when gated option does not exist (default enabled).
	 */
	public function test_is_enabled_returns_true_when_option_missing() {
		$this->assertTrue(
			$this->gated_ability->is_enabled(),
			'Gated ability should be enabled by default when option does not exist.'
		);
	}

	/**
	 * Test is_enabled returns true when gated option is truthy.
	 */
	public function test_is_enabled_returns_true_when_option_enabled() {
		update_option( 'srfm_abilities_api_edit', true );

		$this->assertTrue(
			$this->gated_ability->is_enabled(),
			'Gated ability should be enabled when option is truthy.'
		);
	}

	/**
	 * Test is_enabled returns false when gated option is boolean false.
	 */
	public function test_is_enabled_returns_false_when_option_is_false() {
		// WordPress's update_option() is a no-op when setting a non-existent option
		// to false (old default === new value). Seed a truthy value first so the
		// subsequent update to false actually persists an empty string in the DB.
		update_option( 'srfm_abilities_api_edit', '1' );
		update_option( 'srfm_abilities_api_edit', false );

		$this->assertFalse(
			$this->gated_ability->is_enabled(),
			'Gated ability should be disabled when option is boolean false.'
		);
	}

	/**
	 * Test is_enabled returns false when gated option is string '0'.
	 */
	public function test_is_enabled_returns_false_when_option_is_zero_string() {
		update_option( 'srfm_abilities_api_edit', '0' );

		$this->assertFalse(
			$this->gated_ability->is_enabled(),
			'Gated ability should be disabled when option is "0".'
		);
	}

	/**
	 * Test is_enabled always returns true for non-gated abilities.
	 */
	public function test_is_enabled_returns_true_when_not_gated() {
		$this->assertTrue(
			$this->ungated_ability->is_enabled(),
			'Non-gated ability should always return true from is_enabled.'
		);
	}

	/**
	 * Test permission_callback uses is_enabled for gate check.
	 */
	public function test_permission_callback_returns_false_when_gated_option_is_boolean_false() {
		wp_set_current_user( $this->admin_id );
		// Seed a truthy value first so update_option( ..., false ) actually writes to the DB.
		update_option( 'srfm_abilities_api_edit', '1' );
		update_option( 'srfm_abilities_api_edit', false );

		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Gated ability should be disabled when option is boolean false.'
		);
	}
}
