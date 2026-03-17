<?php
/**
 * Tests for AI_Helper class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\AI_Helper;

/**
 * Test_AI_Helper class.
 */
class Test_AI_Helper extends TestCase {

	/**
	 * Test get_error_message returns string for WP_Error.
	 */
	public function test_get_error_message() {
		$error  = new \WP_Error( 'test_error', 'Test error message' );
		$result = AI_Helper::get_error_message( $error );
		$this->assertIsString( $result );
	}
}
