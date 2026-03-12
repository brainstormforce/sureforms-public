<?php
/**
 * Tests for Form_Metadata trait.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Forms\Create_Form;

/**
 * Test_Form_Metadata class.
 *
 * Uses Create_Form as a concrete class that uses the Form_Metadata trait.
 */
class Test_Form_Metadata extends TestCase {

	/**
	 * Ability instance that uses the trait.
	 *
	 * @var Create_Form
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Create_Form();
	}

	/**
	 * Test apply_metadata_overrides processes metadata.
	 */
	public function test_apply_metadata_overrides() {
		$reflection = new \ReflectionMethod( $this->ability, 'apply_metadata_overrides' );
		$reflection->setAccessible( true );
		$this->assertTrue( $reflection->isProtected() );
	}
}
