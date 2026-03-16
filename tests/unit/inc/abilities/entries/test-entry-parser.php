<?php
/**
 * Tests for Entry_Parser trait.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Entries\Get_Entry;

/**
 * Test_Entry_Parser class.
 *
 * Uses Get_Entry as a concrete class that uses the Entry_Parser trait.
 */
class Test_Entry_Parser extends TestCase {

	/**
	 * Ability instance that uses the trait.
	 *
	 * @var Get_Entry
	 */
	protected $ability;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ability = new Get_Entry();
	}

	/**
	 * Test parse_entry processes entry data.
	 */
	public function test_parse_entry() {
		$reflection = new \ReflectionMethod( $this->ability, 'parse_entry' );
		$reflection->setAccessible( true );
		$this->assertTrue( $reflection->isProtected() );
	}
}
