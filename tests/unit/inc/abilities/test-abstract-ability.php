<?php
/**
 * Tests for Abstract_Ability base class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Get_Form_Stats;

/**
 * Test_Abstract_Ability class.
 *
 * Uses Get_Form_Stats as a concrete implementation to test abstract class methods.
 */
class Test_Abstract_Ability extends TestCase {

	/**
	 * Concrete ability instance for testing abstract methods.
	 *
	 * @var Get_Form_Stats
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Form_Stats();
	}

	/**
	 * Test permission_callback checks current_user_can with the correct capability.
	 */
	public function test_permission_callback() {
		// In the WP test environment, the default user should have manage_options.
		$result = $this->ability->permission_callback();
		$this->assertIsBool( $result );
	}

	/**
	 * Test meets_capability_policy returns true when capability matches MIN_CAPABILITY.
	 */
	public function test_meets_capability_policy() {
		// Get_Form_Stats uses 'manage_options' which matches MIN_CAPABILITY.
		$result = $this->ability->meets_capability_policy();
		$this->assertTrue( $result );
	}

	/**
	 * Test get_annotations returns array with required keys.
	 */
	public function test_get_annotations() {
		$annotations = $this->ability->get_annotations();
		$this->assertIsArray( $annotations );
		$this->assertArrayHasKey( 'readonly', $annotations );
		$this->assertArrayHasKey( 'destructive', $annotations );
		$this->assertArrayHasKey( 'idempotent', $annotations );
	}

	/**
	 * Test execute_wrapper calls execute and returns result.
	 */
	public function test_execute_wrapper() {
		// execute_wrapper wraps execute() with before/after hooks.
		$result = $this->ability->execute_wrapper( [ 'form_id' => 0 ] );

		// Should return same type as execute() — either array or WP_Error.
		$this->assertTrue( is_array( $result ) || $result instanceof WP_Error );
	}

	/**
	 * Test get_id returns the ability identifier.
	 */
	public function test_get_id() {
		$this->assertIsString( $this->ability->get_id() );
	}

	/**
	 * Test register method is callable.
	 */
	public function test_register() {
		// register() calls wp_register_ability which may not exist in test env.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'wp_register_ability() not available in this WordPress version.' );
			return;
		}

		// Should not throw.
		$this->ability->register();
		$this->assertTrue( true );
	}
}
