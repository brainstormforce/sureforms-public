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

	/**
	 * Test dynamic_messages_source returns plain English source strings with
	 * the same keys as dynamic_messages, so the multilingual integration has
	 * a stable identifier to register with WPML String Translation.
	 */
	public function test_dynamic_messages_source() {
		$source     = Translatable::dynamic_messages_source();
		$translated = Translatable::dynamic_messages();

		$this->assertIsArray( $source );
		$this->assertNotEmpty( $source );

		// Keys must mirror dynamic_messages so the two arrays can be zipped.
		$this->assertSame( array_keys( $translated ), array_keys( $source ) );

		// All values are non-empty strings (raw English source, no __() wrapping).
		foreach ( $source as $key => $value ) {
			$this->assertIsString( $value, "Source for {$key} should be a string." );
			$this->assertNotEmpty( $value );
		}

		// Spot-check a couple of source values.
		$this->assertSame( 'Enter a valid email address.', $source['srfm_valid_email'] );
		$this->assertSame( 'Enter a valid URL.', $source['srfm_valid_url'] );
	}
}
