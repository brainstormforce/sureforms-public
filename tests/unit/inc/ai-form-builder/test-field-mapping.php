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
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_missing_form_data', $result->get_error_code() );
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

	/**
	 * Test that form_data set to a non-array value returns the
	 * srfm_ai_mapping_invalid_form_data error code.
	 */
	public function test_invalid_form_data_returns_wp_error() {
		$request = new WP_REST_Request();
		$request->set_param( 'form_data', 'not-an-array' );

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_invalid_form_data', $result->get_error_code() );
	}

	/**
	 * Test that a missing or empty form key returns the
	 * srfm_ai_mapping_missing_form error code.
	 */
	public function test_missing_form_returns_wp_error() {
		$request = new WP_REST_Request();
		$request->set_param(
			'form_data',
			[
				// 'form' key intentionally absent.
				'meta' => [ 'something' => 'else' ],
			]
		);

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_missing_form', $result->get_error_code() );
	}

	/**
	 * Test that an empty or missing formFields array returns the
	 * srfm_ai_mapping_missing_form_fields error code.
	 */
	public function test_missing_form_fields_returns_wp_error() {
		$request = new WP_REST_Request();
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formTitle'  => 'Untitled',
					'formFields' => [],
				],
			]
		);

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_missing_form_fields', $result->get_error_code() );
	}

	/**
	 * Test that a malformed individual field returns the
	 * srfm_ai_mapping_invalid_field error code.
	 */
	public function test_invalid_field_returns_wp_error() {
		$request = new WP_REST_Request();
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formFields' => [
						// Non-array entry breaks the per-field invariant.
						'not-a-field-array',
					],
				],
			]
		);

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_invalid_field', $result->get_error_code() );
	}

	/**
	 * Test that the WP_Error returned for missing form data carries the
	 * documented HTTP status (400) so the REST layer surfaces it.
	 */
	public function test_wp_error_carries_400_status() {
		$request = new WP_REST_Request();
		$request->set_param( 'form_data', [] );

		$result = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$data = $result->get_error_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertSame( 400, $data['status'] );
	}
}
