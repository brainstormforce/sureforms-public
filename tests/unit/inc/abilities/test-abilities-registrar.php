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
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		delete_option( 'srfm_abilities_api' );
		delete_option( 'srfm_abilities_api_edit' );
		delete_option( 'srfm_abilities_api_delete' );
		parent::tearDown();
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

		$this->registrar->register_abilities();

		$expected_ids = [
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

		foreach ( $expected_ids as $id ) {
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

		$this->registrar->register_abilities();
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

		// Disable edit abilities.
		update_option( 'srfm_abilities_api_edit', false );

		$this->registrar->register_abilities();

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

		// Disable delete abilities.
		update_option( 'srfm_abilities_api_delete', false );

		$this->registrar->register_abilities();

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
