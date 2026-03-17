<?php
/**
 * Tests for Translatable class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Translatable;

/**
 * Test_Translatable class.
 */
class Test_Translatable extends TestCase {

	/**
	 * Test dynamic_messages returns array of messages.
	 */
	public function test_dynamic_messages() {
		$result = Translatable::dynamic_messages();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_default_form_restriction_message returns string.
	 */
	public function test_get_default_form_restriction_message() {
		$result = Translatable::get_default_form_restriction_message();
		$this->assertIsString( $result );
	}
}
