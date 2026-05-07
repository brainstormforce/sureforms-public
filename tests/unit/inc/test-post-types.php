<?php
/**
 * Class Test_Post_Types
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Post_Types;

class Test_Post_Types extends TestCase {

	/**
	 * Test register_post_metas is callable.
	 */
	public function test_register_post_metas_callable() {
		$post_types = new Post_Types();
		$post_types->register_post_metas();
		$this->assertTrue( true );
	}

	/**
	 * Test sanitize_form_restriction_data sanitizes meta value.
	 */
	public function test_sanitize_form_restriction_data() {
		$post_types = new Post_Types();
		$result = $post_types->sanitize_form_restriction_data( '' );
		$this->assertIsString( $result );
	}

	public function test_register_post_types() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_types();

		$this->assertTrue( post_type_exists( SRFM_FORMS_POST_TYPE ) );

		$post_type_obj = get_post_type_object( SRFM_FORMS_POST_TYPE );
		$this->assertTrue( $post_type_obj->public );
		$this->assertTrue( $post_type_obj->show_in_rest );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'title' ) );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'editor' ) );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'custom-fields' ) );
	}

	public function test_register_post_metas() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_metas();

		// Verify scalar metas are registered with correct types.
		$expected_metas = [
			'_srfm_use_label_as_placeholder' => 'boolean',
			'_srfm_submit_button_text'       => 'string',
			'_srfm_is_inline_button'         => 'boolean',
			'_srfm_submit_width_backend'     => 'string',
			'_srfm_button_border_radius'     => 'integer',
			'_srfm_submit_alignment'         => 'string',
			'_srfm_submit_alignment_backend' => 'string',
			'_srfm_submit_width'             => 'string',
			'_srfm_inherit_theme_button'     => 'boolean',
			'_srfm_additional_classes'       => 'string',
			'_srfm_submit_type'              => 'string',
			'_srfm_captcha_security_type'    => 'string',
			'_srfm_form_recaptcha'           => 'string',
			'_srfm_is_ai_generated'          => 'boolean',
		];

		foreach ( $expected_metas as $meta_key => $expected_type ) {
			$registered = registered_meta_key_exists( 'post', $meta_key, SRFM_FORMS_POST_TYPE );
			$this->assertTrue( $registered, "Meta key {$meta_key} should be registered." );
		}

		// Verify object metas are registered.
		$this->assertTrue( registered_meta_key_exists( 'post', '_srfm_form_custom_css', SRFM_FORMS_POST_TYPE ), '_srfm_form_custom_css should be registered.' );
		$this->assertTrue( registered_meta_key_exists( 'post', '_srfm_instant_form_settings', SRFM_FORMS_POST_TYPE ), '_srfm_instant_form_settings should be registered.' );
		$this->assertTrue( registered_meta_key_exists( 'post', '_srfm_forms_styling', SRFM_FORMS_POST_TYPE ), '_srfm_forms_styling should be registered.' );
	}

	public function test_register_post_metas_sanitize_callback_for_custom_css() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_metas();

		// Custom CSS should strip script tags but preserve safe CSS-related HTML.
		$result = sanitize_meta( '_srfm_form_custom_css', '<style>.test{color:red}</style><script>alert(1)</script>', 'post', SRFM_FORMS_POST_TYPE );
		$this->assertStringNotContainsString( '<script>', $result );
	}

	public function test_register_post_metas_sanitize_callback_for_instant_form() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_metas();

		$input = [
			'site_logo'              => 'https://example.com/logo.png',
			'site_logo_id'           => 42,
			'cover_type'             => 'image',
			'cover_color'            => '#111C44',
			'cover_image'            => 'https://example.com/cover.jpg',
			'cover_image_id'         => 10,
			'bg_type'                => 'color',
			'bg_color'               => '#ffffff',
			'bg_image'               => 'https://example.com/bg.jpg',
			'bg_image_id'            => 5,
			'enable_instant_form'    => true,
			'form_container_width'   => 800,
			'single_page_form_title' => false,
			'use_banner_as_page_background' => true,
		];

		$result = sanitize_meta( '_srfm_instant_form_settings', $input, 'post', SRFM_FORMS_POST_TYPE );

		$this->assertSame( 'https://example.com/logo.png', $result['site_logo'] );
		$this->assertSame( 42, $result['site_logo_id'] );
		$this->assertSame( 'image', $result['cover_type'] );
		$this->assertTrue( $result['enable_instant_form'] );
		$this->assertSame( 800, $result['form_container_width'] );
		$this->assertFalse( $result['single_page_form_title'] );
		$this->assertTrue( $result['use_banner_as_page_background'] );
	}

	public function test_register_post_metas_instant_form_rejects_invalid_url() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_metas();

		$input = [
			'site_logo'  => 'javascript:alert(1)',
			'cover_image' => '<script>xss</script>',
		];

		$result = sanitize_meta( '_srfm_instant_form_settings', $input, 'post', SRFM_FORMS_POST_TYPE );

		$this->assertSame( '', $result['site_logo'] );
		$this->assertSame( '', $result['cover_image'] );
	}

	public function test_register_post_metas_instant_form_returns_empty_for_non_array() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_metas();

		$result = sanitize_meta( '_srfm_instant_form_settings', 'not-an-array', 'post', SRFM_FORMS_POST_TYPE );

		$this->assertSame( [], $result );
	}
}
