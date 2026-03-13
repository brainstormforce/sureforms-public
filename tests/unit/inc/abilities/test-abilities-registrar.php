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
	 * Test that register_abilities creates all 12 core abilities.
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
			'sureforms/update-entry-status',
			'sureforms/delete-entry',
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
				$this->assertCount( 13, $abilities );
				return $abilities;
			}
		);

		$this->registrar->register_abilities();
		$this->assertTrue( $filter_called, 'srfm_register_abilities filter should be called.' );
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
}
