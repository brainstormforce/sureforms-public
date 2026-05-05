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

	/**
	 * create_form should return a 500 WP_REST_Response when wp_insert_post fails.
	 *
	 * Forces wp_insert_post to fail via the `wp_insert_post_empty_content`
	 * filter and verifies the response carries a user-visible message rather
	 * than silently returning a WP_Error object or an incorrect post ID.
	 */
	public function test_create_form_returns_error_response_on_insert_failure() {
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_test_' . uniqid() . '@example.com',
				'role'       => 'administrator',
			]
		);
		$this->assertNotInstanceOf( 'WP_Error', $user_id );
		wp_set_current_user( $user_id );

		$force_error = static function () {
			return true;
		};
		add_filter( 'wp_insert_post_empty_content', $force_error );

		$request = new WP_REST_Request( 'POST' );
		$request->set_header( 'content-type', 'text/html' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_body(
			wp_json_encode(
				[
					'template_name'  => 'AI Test Form',
					'form_data'      => '<!-- wp:paragraph --><p>hi</p><!-- /wp:paragraph -->',
					'template_metas' => [],
				]
			)
		);

		$response = Create_New_Form::create_form( $request );

		remove_filter( 'wp_insert_post_empty_content', $force_error );
		wp_delete_user( $user_id );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertSame( 500, $response->get_status() );
	}
}
