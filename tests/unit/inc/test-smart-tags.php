<?php
/**
 * Class Test_Smart_Tags
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Smart_Tags;

class Test_Smart_Tags extends TestCase {

	protected $smart_tags;

	protected function setUp(): void {
		$this->smart_tags = new Smart_Tags();
	}

	public function test_smart_tag_list_returns_array() {
		$result = Smart_Tags::smart_tag_list();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( '{site_url}', $result );
		$this->assertArrayHasKey( '{admin_email}', $result );
		$this->assertArrayHasKey( '{site_title}', $result );
		$this->assertArrayHasKey( '{form_title}', $result );
		$this->assertArrayHasKey( '{ip}', $result );
		$this->assertArrayHasKey( '{date_mdy}', $result );
		$this->assertArrayHasKey( '{date_dmy}', $result );
		$this->assertArrayHasKey( '{user_email}', $result );
	}

	public function test_email_smart_tag_list_returns_expected() {
		$result = Smart_Tags::email_smart_tag_list();
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( '{admin_email}', $result );
		$this->assertArrayHasKey( '{user_email}', $result );
	}

	public function test_smart_tags_callback_site_url() {
		$result = Smart_Tags::smart_tags_callback( '{site_url}' );
		$this->assertEquals( site_url(), $result );
	}

	public function test_smart_tags_callback_admin_email() {
		$result = Smart_Tags::smart_tags_callback( '{admin_email}' );
		$this->assertEquals( get_option( 'admin_email' ), $result );
	}

	public function test_smart_tags_callback_site_title() {
		$result = Smart_Tags::smart_tags_callback( '{site_title}' );
		$this->assertEquals( get_option( 'blogname' ), $result );
	}

	public function test_smart_tags_callback_date_mdy() {
		$result = Smart_Tags::smart_tags_callback( '{date_mdy}' );
		$this->assertIsString( $result );
		$this->assertMatchesRegularExpression( '/^\d{2}\/\d{2}\/\d{4}$/', $result );
	}

	public function test_smart_tags_callback_date_dmy() {
		$result = Smart_Tags::smart_tags_callback( '{date_dmy}' );
		$this->assertIsString( $result );
		$this->assertMatchesRegularExpression( '/^\d{2}\/\d{2}\/\d{4}$/', $result );
	}

	public function test_process_smart_tags_no_tags() {
		$content = 'Hello World, no tags here';
		$result = $this->smart_tags->process_smart_tags( $content );
		$this->assertEquals( $content, $result );
	}

	public function test_process_smart_tags_empty_content() {
		$result = $this->smart_tags->process_smart_tags( '' );
		$this->assertEmpty( $result );
	}

	public function test_get_the_user_ip_remote_addr() {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		unset( $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_FORWARDED'], $_SERVER['HTTP_FORWARDED_FOR'], $_SERVER['HTTP_FORWARDED'] );
		$result = Smart_Tags::get_the_user_ip();
		$this->assertEquals( '127.0.0.1', $result );
	}

	public function test_check_form_by_id_invalid() {
		$result = $this->smart_tags->check_form_by_id( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test parse_form_input returns value when no submission data.
	 *
	 * @since x.x.x
	 */
	public function test_parse_form_input_no_data() {
		$result = Smart_Tags::parse_form_input( '{form:name}' );
		$this->assertEquals( '{form:name}', $result );
	}

	/**
	 * Test parse_form_input returns value when no form tag match.
	 *
	 * @since x.x.x
	 */
	public function test_parse_form_input_no_tag() {
		$result = Smart_Tags::parse_form_input( 'plain text', [ 'key' => 'val' ] );
		$this->assertEquals( 'plain text', $result );
	}

	/**
	 * Test parse_form_input resolves a text field smart tag.
	 *
	 * @since x.x.x
	 */
	public function test_parse_form_input_text_field() {
		$submission_data = [
			'srfm-input-abc123-lbl-input-name' => 'John Doe',
		];
		$result = Smart_Tags::parse_form_input( '{form:name}', $submission_data );
		$this->assertStringContainsString( 'John Doe', $result );
	}

	/**
	 * Test parse_form_input decodes rawurlencode'd upload URLs.
	 *
	 * @since x.x.x
	 */
	public function test_parse_form_input_upload_decodes_urls() {
		$submission_data = [
			'srfm-upload-abc123-lbl-upload-resume' => [
				rawurlencode( 'https://example.com/uploads/my file.pdf' ),
				rawurlencode( 'https://example.com/uploads/doc (2).pdf' ),
			],
		];
		// With raw format type, should return comma-separated decoded URLs.
		$form_data = [ 'upload_format_type' => 'raw' ];
		$result    = Smart_Tags::parse_form_input( '{form:resume}', $submission_data, $form_data );
		$this->assertStringContainsString( 'https://example.com/uploads/my file.pdf', $result );
		$this->assertStringContainsString( 'https://example.com/uploads/doc (2).pdf', $result );
		$this->assertStringNotContainsString( '%20', $result );
	}

	/**
	 * Test parse_form_input with single upload file returns anchor tag with decoded URL.
	 *
	 * @since x.x.x
	 */
	public function test_parse_form_input_single_upload_returns_link() {
		$submission_data = [
			'srfm-upload-abc123-lbl-upload-resume' => [
				rawurlencode( 'https://example.com/uploads/my file.pdf' ),
			],
		];
		$result = Smart_Tags::parse_form_input( '{form:resume}', $submission_data );
		$this->assertStringContainsString( '<a ', $result );
		$this->assertStringContainsString( 'my file.pdf', $result );
	}

	public function test_parse_payment_smart_tag_no_submission_data() {
		$result = Smart_Tags::parse_payment_smart_tag( '{form-payment:slug:amount}', null );
		$this->assertEquals( '', $result );
	}

	public function test_parse_payment_smart_tag_invalid_property() {
		$result = Smart_Tags::parse_payment_smart_tag( '{form-payment:slug:invalid}', [ 'test' => 'data' ] );
		$this->assertEquals( '', $result );
	}
}
