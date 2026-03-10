<?php
/**
 * Class Test_Export
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Export;

class Test_Export extends TestCase {

	protected $export;

	protected function setUp(): void {
		$this->export = new Export();
	}

	public function test_unserialized_post_metas_property() {
		$this->assertIsArray( $this->export->unserialized_post_metas );
		$this->assertContains( '_srfm_conditional_logic', $this->export->unserialized_post_metas );
		$this->assertContains( '_srfm_email_notification', $this->export->unserialized_post_metas );
		$this->assertContains( '_srfm_form_confirmation', $this->export->unserialized_post_metas );
	}

	public function test_get_unserialized_post_metas_returns_array() {
		$result = $this->export->get_unserialized_post_metas();
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( '_srfm_conditional_logic', $result );
	}

	public function test_get_forms_with_meta_empty_ids() {
		$result = $this->export->get_forms_with_meta( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_get_forms_with_meta_returns_post_data() {
		$post_id = wp_insert_post( [
			'post_title'   => 'Export Test Form',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => 'simple content',
		] );
		update_post_meta( $post_id, '_srfm_submit_button_text', 'Submit' );

		$result = $this->export->get_forms_with_meta( [ $post_id ] );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'post', $result[0] );
		$this->assertArrayHasKey( 'post_meta', $result[0] );
		$this->assertInstanceOf( WP_Post::class, $result[0]['post'] );

		wp_delete_post( $post_id, true );
	}

	public function test_import_forms_with_meta_success() {
		// Remove gutenberg hooks that interfere with test
		remove_all_actions( 'wp_insert_post_data' );

		$data = [
			[
				'post' => [
					'ID'           => 999,
					'post_title'   => 'Imported Form',
					'post_content' => 'Simple test content',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [
					'_srfm_submit_button_text' => [ 'Submit' ],
				],
			],
		];

		$result = $this->export->import_forms_with_meta( $data, 'draft' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 999, $result );

		$new_post_id = $result[999];
		$post = get_post( $new_post_id );
		$this->assertEquals( 'Imported Form', $post->post_title );
		$this->assertEquals( 'draft', $post->post_status );
		$this->assertEquals( 'sureforms_form', $post->post_type );

		wp_delete_post( $new_post_id, true );
	}

	public function test_import_forms_with_meta_invalid_post_type() {
		$data = [
			[
				'post' => [
					'ID'           => 888,
					'post_title'   => 'Bad Import',
					'post_content' => '',
					'post_type'    => 'page',
				],
				'post_meta' => [],
			],
		];

		$result = $this->export->import_forms_with_meta( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test handle_import_form_rest is callable.
	 */
	public function test_handle_import_form_rest() {
		$this->assertTrue( method_exists( $this->export, 'handle_import_form_rest' ) );
	}

	public function test_import_forms_preserves_meta() {
		$data = [
			[
				'post' => [
					'ID'           => 777,
					'post_title'   => 'Meta Preservation Test',
					'post_content' => 'Test content',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [
					'_srfm_submit_button_text' => [ 'Go' ],
					'_srfm_bg_color'           => [ '#000000' ],
				],
			],
		];

		$result = $this->export->import_forms_with_meta( $data );
		$new_id = $result[777];
		$this->assertEquals( 'Go', get_post_meta( $new_id, '_srfm_submit_button_text', true ) );
		$this->assertEquals( '#000000', get_post_meta( $new_id, '_srfm_bg_color', true ) );

		wp_delete_post( $new_id, true );
	}

	public function test_import_skips_unknown_meta_keys() {
		$data = [
			[
				'post' => [
					'ID'           => 600,
					'post_title'   => 'Unknown Key Test',
					'post_content' => 'Test content',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [
					'_evil_key'                => [ 'malicious_value' ],
					'_srfm_submit_button_text' => [ 'Submit' ],
					'random_meta'              => [ 'should_not_exist' ],
				],
			],
		];

		$result = $this->export->import_forms_with_meta( $data );
		$new_id = $result[600];

		// Allowed key should be stored.
		$this->assertEquals( 'Submit', get_post_meta( $new_id, '_srfm_submit_button_text', true ) );

		// Unknown keys should NOT be stored.
		$this->assertEmpty( get_post_meta( $new_id, '_evil_key', true ) );
		$this->assertEmpty( get_post_meta( $new_id, 'random_meta', true ) );

		wp_delete_post( $new_id, true );
	}

	public function test_import_sanitizes_scalar_meta_xss() {
		$data = [
			[
				'post' => [
					'ID'           => 601,
					'post_title'   => 'XSS Scalar Test',
					'post_content' => 'Test content',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [
					'_srfm_submit_button_text' => [ '<script>alert(1)</script>Submit' ],
					'_srfm_seo_title'          => [ '<img src=x onerror=alert(1)>My Title' ],
				],
			],
		];

		$result = $this->export->import_forms_with_meta( $data );
		$new_id = $result[601];

		$button_text = get_post_meta( $new_id, '_srfm_submit_button_text', true );
		$seo_title   = get_post_meta( $new_id, '_srfm_seo_title', true );

		// Script tags and event handlers must be stripped.
		$this->assertStringNotContainsString( '<script>', $button_text );
		$this->assertStringNotContainsString( 'onerror', $seo_title );

		// Legitimate text content should survive.
		$this->assertStringContainsString( 'Submit', $button_text );
		$this->assertStringContainsString( 'My Title', $seo_title );

		wp_delete_post( $new_id, true );
	}

	public function test_import_sanitizes_unserialized_meta_xss() {
		$data = [
			[
				'post' => [
					'ID'           => 602,
					'post_title'   => 'XSS Array Test',
					'post_content' => 'Test content',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [
					'_srfm_compliance' => [
						[
							'enabled' => true,
							'text'    => '<script>alert("xss")</script>I agree to the terms',
						],
					],
				],
			],
		];

		$result = $this->export->import_forms_with_meta( $data );
		$new_id = $result[602];

		$compliance = get_post_meta( $new_id, '_srfm_compliance', true );
		$this->assertIsArray( $compliance );

		// Flatten all string values to check none contain script tags.
		array_walk_recursive(
			$compliance,
			function ( $value ) {
				if ( is_string( $value ) ) {
					$this->assertStringNotContainsString( '<script>', $value );
				}
			}
		);

		wp_delete_post( $new_id, true );
	}

	public function test_get_allowed_import_meta_keys_includes_all_unserialized_keys() {
		// Use reflection to access private method.
		$method = new ReflectionMethod( Export::class, 'get_allowed_import_meta_keys' );
		$method->setAccessible( true );

		$allowed_keys      = $method->invoke( $this->export );
		$unserialized_keys = $this->export->get_unserialized_post_metas();

		// Every unserialized key must be in the allowed list.
		foreach ( $unserialized_keys as $key ) {
			$this->assertContains( $key, $allowed_keys, "Unserialized key {$key} missing from allowed import keys" );
		}
	}

	public function test_get_allowed_import_meta_keys_includes_known_scalar_keys() {
		$method = new ReflectionMethod( Export::class, 'get_allowed_import_meta_keys' );
		$method->setAccessible( true );

		$allowed_keys = $method->invoke( $this->export );

		// Spot-check known scalar keys.
		$expected_scalars = [
			'_srfm_submit_button_text',
			'_srfm_bg_color',
			'_srfm_form_custom_css',
			'_srfm_seo_title',
			'_srfm_use_label_as_placeholder',
		];

		foreach ( $expected_scalars as $key ) {
			$this->assertContains( $key, $allowed_keys, "Scalar key {$key} missing from allowed import keys" );
		}
	}

	public function test_get_allowed_import_meta_keys_rejects_arbitrary_keys() {
		$method = new ReflectionMethod( Export::class, 'get_allowed_import_meta_keys' );
		$method->setAccessible( true );

		$allowed_keys = $method->invoke( $this->export );

		$this->assertNotContains( '_evil_key', $allowed_keys );
		$this->assertNotContains( 'wp_capabilities', $allowed_keys );
		$this->assertNotContains( '_srfm_nonexistent_key', $allowed_keys );
	}
}
