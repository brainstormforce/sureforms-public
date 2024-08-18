<?php
/**
 * Rest API Manager Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Rest API handler class.
 *
 * @since 0.0.7
 */
class Rest_Api {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.7
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register endpoints
	 *
	 * @since 0.0.7
	 * @return void
	 */
	public function register_endpoints() {

		$prefix       = 'sureforms';
		$version_slug = 'v1';

		$endpoints = $this->get_endpoints();

		foreach ( $endpoints as $endpoint => $args ) {
			register_rest_route(
				$prefix . '/' . $version_slug,
				$endpoint,
				$args
			);
		}
	}

	/**
	 * Check if user can edit posts
	 *
	 * @since 0.0.7
	 * @return bool
	 */
	public function can_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get endpoints
	 *
	 * @since 0.0.7
	 * @return array<array<mixed>>
	 */
	private function get_endpoints() {
		return [
			'generate-block-slugs' => [
				'methods'             => 'POST',
				'callback'            => [ $this, 'generate_block_slugs_by_content' ],
				'permission_callback' => [ $this, 'can_edit_posts' ],
			],
		];
	}

	/**
	 * Generate the block slugs as per the request by parsing the post content.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @since 0.0.7
	 * @return void
	 */
	public function generate_block_slugs_by_content( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		$slugs   = [];
		$updated = false;
		$params  = $request->get_params();

		if ( empty( $params['formID'] ) ) {
			wp_send_json_error( __( 'Invalid request. Form ID missing.', 'sureforms' ) );
		}

		$form    = get_post( absint( $params['formID'] ) );
		$content = ! empty( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';

		if ( ! is_null( $form ) ) {
			Gutenberg_Hooks::process_blocks( parse_blocks( $form->post_content ), $slugs, $updated );
		}

		Gutenberg_Hooks::process_blocks( parse_blocks( $content ), $slugs, $updated, '', true );

		wp_send_json_success( $slugs );
	}
}
