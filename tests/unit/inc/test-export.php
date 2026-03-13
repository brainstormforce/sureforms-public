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
}
