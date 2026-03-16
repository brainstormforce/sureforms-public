<?php
/**
 * Tests for Abilities_Registrar.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Abilities_Registrar;

/**
 * Test_Abilities_Registrar class.
 */
class Test_Abilities_Registrar extends TestCase {

	/**
	 * All SureForms ability IDs used for registry cleanup between tests.
	 *
	 * @var string[]
	 */
	private const ABILITY_IDS = [
		'sureforms/list-forms',
		'sureforms/create-form',
		'sureforms/get-form',
		'sureforms/get-shortcode',
		'sureforms/delete-form',
		'sureforms/duplicate-form',
		'sureforms/update-form',
		'sureforms/get-form-stats',
		'sureforms/list-entries',
		'sureforms/get-entry',
		'sureforms/bulk-get-entries',
		'sureforms/update-entry-status',
		'sureforms/delete-entry',
		'sureforms/get-global-settings',
		'sureforms/update-global-settings',
		'sureforms/get-form-analytics',
	];

	/**
	 * Registrar instance.
	 *
	 * @var Abilities_Registrar
	 */
	protected $registrar;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->registrar = Abilities_Registrar::get_instance();

		// Enable master toggle by default so registration tests can run.
		update_option( 'srfm_abilities_api', true );

		// Ensure a clean abilities registry so tests don't leak state.
		// The first wp_unregister_ability() call may lazily initialize the
		// WP_Abilities_Registry singleton, which fires wp_abilities_api_init
		// and registers all abilities via the registrar's hook — that's expected;
		// we immediately unregister them to start each test with a clean slate.
		$this->clean_abilities_registry();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		$this->clean_abilities_registry();
		delete_option( 'srfm_abilities_api' );
		delete_option( 'srfm_abilities_api_edit' );
		delete_option( 'srfm_abilities_api_delete' );
		parent::tearDown();
	}

	/**
	 * Unregister all SureForms abilities to isolate tests.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function clean_abilities_registry(): void {
		if ( ! function_exists( 'wp_unregister_ability' ) ) {
			return;
		}

		foreach ( self::ABILITY_IDS as $id ) {
			wp_unregister_ability( $id );
		}
	}

	/**
	 * Call register_abilities() within the wp_abilities_api_init action context.
	 *
	 * WP 6.9's wp_register_ability() requires doing_action( 'wp_abilities_api_init' )
	 * to return true. This helper simulates that context for test purposes.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function register_abilities_in_action_context(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		global $wp_current_filter;
		$wp_current_filter[] = 'wp_abilities_api_init';
		$this->registrar->register_abilities();
		array_pop( $wp_current_filter );
	}

	/**
	 * Test that the registrar returns a singleton instance.
	 */
	public function test_get_instance_returns_singleton() {
		$instance_a = Abilities_Registrar::get_instance();
		$instance_b = Abilities_Registrar::get_instance();
		$this->assertSame( $instance_a, $instance_b );
	}

	/**
	 * Test that register_abilities creates all 16 core abilities.
	 */
	public function test_register_abilities_creates_all_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		$this->register_abilities_in_action_context();

		foreach ( self::ABILITY_IDS as $id ) {
			$this->assertTrue( wp_has_ability( $id ), "Ability '{$id}' should be registered." );
		}
	}

	/**
	 * Test that register_abilities applies the srfm_register_abilities filter.
	 */
	public function test_register_abilities_applies_filter() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		$filter_called = false;
		add_filter(
			'srfm_register_abilities',
			function ( $abilities ) use ( &$filter_called ) {
				$filter_called = true;
				$this->assertIsArray( $abilities );
				$this->assertCount( 16, $abilities );
				return $abilities;
			}
		);

		$this->register_abilities_in_action_context();
		$this->assertTrue( $filter_called, 'srfm_register_abilities filter should be called.' );
	}

	/**
	 * Test that mcp_adapter_enabled returns false by default.
	 */
	public function test_mcp_adapter_enabled_returns_false_by_default() {
		$this->assertFalse( Abilities_Registrar::mcp_adapter_enabled() );
	}

	/**
	 * Test that register_category registers the sureforms category.
	 */
	public function test_register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		$this->registrar->register_category();
		$this->assertTrue( wp_has_ability_category( 'sureforms' ) );
	}

	/**
	 * Test that register_abilities bails when master toggle is off.
	 */
	public function test_register_abilities_bails_when_master_toggle_off() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		update_option( 'srfm_abilities_api', false );

		$filter_called = false;
		add_filter(
			'srfm_register_abilities',
			function ( $abilities ) use ( &$filter_called ) {
				$filter_called = true;
				return $abilities;
			}
		);

		$this->registrar->register_abilities();
		$this->assertFalse( $filter_called, 'register_abilities should bail early when master toggle is off.' );
	}

	/**
	 * Test that register_abilities skips disabled gated abilities.
	 */
	public function test_register_abilities_skips_disabled_gated_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		// Disable edit abilities — use '0' because update_option( ..., false )
		// is a no-op when the option does not yet exist in the database.
		update_option( 'srfm_abilities_api_edit', '0' );

		$this->register_abilities_in_action_context();

		// Edit-gated abilities should not be registered.
		$this->assertFalse(
			wp_has_ability( 'sureforms/create-form' ),
			'create-form should not be registered when edit toggle is off.'
		);
		$this->assertFalse(
			wp_has_ability( 'sureforms/update-form' ),
			'update-form should not be registered when edit toggle is off.'
		);

		// Non-gated abilities should still be registered.
		$this->assertTrue(
			wp_has_ability( 'sureforms/list-forms' ),
			'list-forms should still be registered when only edit toggle is off.'
		);
	}

	/**
	 * Test that register_abilities skips disabled delete abilities.
	 */
	public function test_register_abilities_skips_disabled_delete_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available (requires WP 6.9+).' );
		}

		// Disable delete abilities — use '0' because update_option( ..., false )
		// is a no-op when the option does not yet exist in the database.
		update_option( 'srfm_abilities_api_delete', '0' );

		$this->register_abilities_in_action_context();

		$this->assertFalse(
			wp_has_ability( 'sureforms/delete-form' ),
			'delete-form should not be registered when delete toggle is off.'
		);
		$this->assertFalse(
			wp_has_ability( 'sureforms/delete-entry' ),
			'delete-entry should not be registered when delete toggle is off.'
		);

		// Non-gated and edit-gated abilities should still be registered.
		$this->assertTrue(
			wp_has_ability( 'sureforms/list-forms' ),
			'list-forms should still be registered when only delete toggle is off.'
		);
	}

	/**
	 * Test that register_mcp_server method exists and is callable.
	 */
	public function test_register_mcp_server() {
		$this->assertTrue(
			method_exists( $this->registrar, 'register_mcp_server' ),
			'register_mcp_server method should exist on Abilities_Registrar.'
		);

		$this->assertTrue(
			is_callable( [ $this->registrar, 'register_mcp_server' ] ),
			'register_mcp_server should be callable on the registrar instance.'
		);

		// Skip actual invocation if MCP adapter class is not available.
		if ( ! class_exists( 'WP\MCP\Plugin' ) ) {
			$this->markTestSkipped( 'MCP adapter class WP\MCP\Plugin is not available in the test environment.' );
		}
	}
}
