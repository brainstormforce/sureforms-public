<?php
/**
 * Tests for Abstract_Ability core methods.
 *
 * Covers is_enabled(), permission_callback(), meets_capability_policy(),
 * get_annotations(), and register() on concrete ability implementations.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Create_Form;
use SRFM\Inc\Abilities\Forms\List_Forms;

/**
 * Test_Abstract_Ability class.
 */
class Test_Abstract_Ability extends TestCase {

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
				'user_login' => 'testadmin_abstract_' . wp_generate_password( 4, false ),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			]
		);

		$this->subscriber_id = wp_insert_user(
			[
				'user_login' => 'testsub_abstract_' . wp_generate_password( 4, false ),
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			]
		);

		// Clean option state before each test.
		delete_option( 'srfm_abilities_api' );
		delete_option( 'srfm_abilities_api_edit' );

		// Enable master toggle by default.
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
	 * Test is_enabled() returns correct values based on gated option state.
	 */
	public function test_is_enabled() {
		// Non-gated ability is always enabled.
		$this->assertTrue(
			$this->ungated_ability->is_enabled(),
			'Non-gated ability should always be enabled.'
		);

		// Gated ability enabled by default when option does not exist.
		delete_option( 'srfm_abilities_api_edit' );
		$this->assertTrue(
			$this->gated_ability->is_enabled(),
			'Gated ability should be enabled when option does not exist (defaults to true).'
		);

		// Gated ability enabled when option is truthy.
		update_option( 'srfm_abilities_api_edit', true );
		$this->assertTrue(
			$this->gated_ability->is_enabled(),
			'Gated ability should be enabled when option is true.'
		);

		// Gated ability disabled when option is false.
		update_option( 'srfm_abilities_api_edit', false );
		$this->assertFalse(
			$this->gated_ability->is_enabled(),
			'Gated ability should be disabled when option is false.'
		);

		// Gated ability disabled when option is string '0'.
		update_option( 'srfm_abilities_api_edit', '0' );
		$this->assertFalse(
			$this->gated_ability->is_enabled(),
			'Gated ability should be disabled when option is "0".'
		);
	}

	/**
	 * Test permission_callback() with master toggle on/off and different user roles.
	 */
	public function test_permission_callback() {
		// Admin with master toggle on — should pass.
		wp_set_current_user( $this->admin_id );
		$this->assertTrue(
			$this->ungated_ability->permission_callback(),
			'Admin should have permission when master toggle is on.'
		);

		// Master toggle off — should fail for everyone.
		update_option( 'srfm_abilities_api', false );
		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'Permission should be denied when master toggle is off.'
		);
		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Gated ability permission should be denied when master toggle is off.'
		);

		// Re-enable master toggle, test subscriber.
		update_option( 'srfm_abilities_api', true );
		wp_set_current_user( $this->subscriber_id );
		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'Subscriber should not have permission (lacks manage_options).'
		);

		// Admin with gated ability disabled.
		wp_set_current_user( $this->admin_id );
		update_option( 'srfm_abilities_api_edit', false );
		$this->assertFalse(
			$this->gated_ability->permission_callback(),
			'Admin should be denied when gated ability option is false.'
		);

		// Master toggle missing (deleted) — defaults to false.
		delete_option( 'srfm_abilities_api' );
		wp_set_current_user( $this->admin_id );
		$this->assertFalse(
			$this->ungated_ability->permission_callback(),
			'Permission should be denied when master toggle option does not exist.'
		);
	}

	/**
	 * Test meets_capability_policy() enforces manage_options minimum.
	 */
	public function test_meets_capability_policy() {
		// Both concrete abilities use manage_options, so they should pass.
		$this->assertTrue(
			$this->ungated_ability->meets_capability_policy(),
			'List_Forms with manage_options should meet capability policy.'
		);
		$this->assertTrue(
			$this->gated_ability->meets_capability_policy(),
			'Create_Form with manage_options should meet capability policy.'
		);

		// Use reflection to test with a downgraded capability.
		$reflection = new ReflectionProperty( $this->ungated_ability, 'capability' );
		$reflection->setAccessible( true );
		$original_capability = $reflection->getValue( $this->ungated_ability );

		$reflection->setValue( $this->ungated_ability, 'edit_posts' );
		$this->assertFalse(
			$this->ungated_ability->meets_capability_policy(),
			'Ability with edit_posts capability should not meet the manage_options policy.'
		);

		// Restore original capability.
		$reflection->setValue( $this->ungated_ability, $original_capability );
	}

	/**
	 * Test get_annotations() returns array with expected MCP annotation keys.
	 */
	public function test_get_annotations() {
		$expected_keys = [ 'readonly', 'destructive', 'idempotent', 'priority', 'openWorldHint' ];

		// Test non-gated readonly ability (List_Forms).
		$annotations = $this->ungated_ability->get_annotations();
		$this->assertIsArray( $annotations, 'Annotations should be an array.' );

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $annotations, "Annotations should contain '{$key}' key." );
		}

		// List_Forms is readonly and idempotent.
		$this->assertTrue( $annotations['readonly'], 'List_Forms should be readonly.' );
		$this->assertFalse( $annotations['destructive'], 'List_Forms should not be destructive.' );
		$this->assertTrue( $annotations['idempotent'], 'List_Forms should be idempotent.' );

		// Test gated write ability (Create_Form).
		$annotations = $this->gated_ability->get_annotations();
		$this->assertIsArray( $annotations, 'Create_Form annotations should be an array.' );

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $annotations, "Create_Form annotations should contain '{$key}' key." );
		}

		$this->assertFalse( $annotations['readonly'], 'Create_Form should not be readonly.' );
		$this->assertFalse( $annotations['destructive'], 'Create_Form should not be destructive.' );
		$this->assertFalse( $annotations['idempotent'], 'Create_Form should not be idempotent.' );
	}

	/**
	 * Test register() registers ability with WordPress Abilities API.
	 */
	public function test_register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		// Register the non-gated ability and verify it exists.
		$this->ungated_ability->register();
		$this->assertTrue(
			wp_has_ability( 'sureforms/list-forms' ),
			'list-forms should be registered after calling register().'
		);

		// Register the gated ability and verify it exists.
		$this->gated_ability->register();
		$this->assertTrue(
			wp_has_ability( 'sureforms/create-form' ),
			'create-form should be registered after calling register().'
		);
	}
}
