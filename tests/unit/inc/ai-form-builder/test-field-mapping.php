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

	/**
	 * Test generate_gutenberg_fields_from_questions handles fields without helpText.
	 */
	public function test_generate_gutenberg_fields_from_questions() {
		$request = new WP_REST_Request();
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formFields' => [
						[
							'label'     => 'Name',
							'fieldType' => 'input',
							'required'  => true,
							// helpText intentionally omitted.
						],
					],
				],
			]
		);

		// Should not produce any PHP warnings for missing helpText.
		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'wp:srfm/input', $result );
	}

	/**
	 * Test generate_gutenberg_fields includes helpText when provided.
	 */
	public function test_generate_gutenberg_fields_with_help_text() {
		$request = new WP_REST_Request();
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formFields' => [
						[
							'label'     => 'Email',
							'fieldType' => 'email',
							'required'  => true,
							'helpText'  => 'Enter your email address',
						],
					],
				],
			]
		);

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'wp:srfm/email', $result );
		$this->assertStringContainsString( 'Enter your email address', $result );
	}
}
