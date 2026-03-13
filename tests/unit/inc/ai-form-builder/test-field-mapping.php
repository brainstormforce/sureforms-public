<?php
/**
 * Class Test_Field_Mapping
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;

class Test_Field_Mapping extends TestCase {

	public function test_generate_gutenberg_fields_empty_request() {
		$request = new WP_REST_Request();
		$request->set_param( 'form_data', [] );

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertEquals( '', $result );
	}
}
