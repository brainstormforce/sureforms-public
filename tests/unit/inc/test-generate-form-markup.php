<?php
/**
 * Class Test_Generate_Form_Markup
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Generate_Form_Markup;

class Test_Generate_Form_Markup extends TestCase {

	protected $generate_form_markup;

	protected function setUp(): void {
		$this->generate_form_markup = new Generate_Form_Markup();
	}

	public function test_get_form_markup_empty_id() {
		$result = Generate_Form_Markup::get_form_markup( 0 );
		$this->assertIsString( $result );
	}

	public function test_get_form_markup_invalid_id() {
		$result = Generate_Form_Markup::get_form_markup( 999999 );
		$this->assertIsString( $result );
	}

	public function test_get_form_markup_with_valid_form() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'Test Markup Form',
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_status'  => 'publish',
			'post_content' => 'simple content',
		] );

		$result = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertIsString( $result );

		wp_delete_post( $form_id, true );
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/generate-form-markup' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The generate-form-markup endpoint should be registered' );
	}

	/**
	 * Test get_confirmation_markup returns string.
	 */
	public function test_get_confirmation_markup() {
		$result = Generate_Form_Markup::get_confirmation_markup();
		$this->assertIsString( $result );
	}

	public function test_get_form_markup_returns_string() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'String Test Form',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		$result = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertIsString( $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test form markup contains the HMAC submit token attribute.
	 */
	public function test_form_markup_contains_submit_token() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'Token Markup Test',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		$markup = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringContainsString( 'data-submit-token=', $markup, 'Form markup should contain data-submit-token attribute.' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test form markup does not contain old nonce attributes.
	 */
	public function test_form_markup_does_not_contain_old_nonce_attributes() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'No Nonce Markup Test',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		$markup = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringNotContainsString( 'data-nonce=', $markup, 'Form markup should not contain old data-nonce attribute.' );
		$this->assertStringNotContainsString( 'data-update-nonce=', $markup, 'Form markup should not contain old data-update-nonce attribute.' );

		wp_delete_post( $form_id, true );
	}
}
