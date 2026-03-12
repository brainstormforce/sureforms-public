<?php
/**
 * Class Test_Create_New_Form
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Create_New_Form;

class Test_Create_New_Form extends TestCase {

	public function test_get_default_meta_keys_returns_array() {
		$result = Create_New_Form::get_default_meta_keys();
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
	}

	public function test_get_default_meta_keys_has_expected_keys() {
		$result = Create_New_Form::get_default_meta_keys();
		$this->assertArrayHasKey( '_srfm_submit_button_text', $result );
		$this->assertArrayHasKey( '_srfm_form_container_width', $result );
		$this->assertArrayHasKey( '_srfm_bg_type', $result );
		$this->assertArrayHasKey( '_srfm_bg_color', $result );
		$this->assertArrayHasKey( '_srfm_submit_alignment', $result );
		$this->assertArrayHasKey( '_srfm_form_recaptcha', $result );
	}

	public function test_get_default_meta_keys_values() {
		$result = Create_New_Form::get_default_meta_keys();
		$this->assertEquals( 650, $result['_srfm_form_container_width'] );
		$this->assertEquals( 'image', $result['_srfm_bg_type'] );
		$this->assertEquals( '#ffffff', $result['_srfm_bg_color'] );
		$this->assertEquals( 'left', $result['_srfm_submit_alignment'] );
		$this->assertEquals( 'none', $result['_srfm_form_recaptcha'] );
		$this->assertEquals( 'message', $result['_srfm_submit_type'] );
		$this->assertFalse( $result['_srfm_use_label_as_placeholder'] );
		$this->assertFalse( $result['_srfm_is_inline_button'] );
	}
}
