<?php
/**
 * Sureforms export.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use WP_REST_Server;
/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class SRFM_Export {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'wp_ajax_export_form', [ $this, 'handle_export_form' ] );
		add_action( 'wp_ajax_nopriv_export_form', [ $this, 'handle_export_form' ] );
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Handle Export form
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function handle_export_form() {
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'export_form_nonce' ) ) {
			$error_message = 'Nonce verification failed.';

			$error_data = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}
		$post_ids = explode( ',', $_POST['post_id'] );

		$posts = [];

		foreach ( $post_ids as $post_id ) {
			$post_id   = intval( $post_id );
			$post      = get_post( $post_id );
			$post_meta = get_post_meta( $post_id );
			$posts[]   = [
				'post'      => $post,
				'post_meta' => $post_meta,
			];
		}
		wp_send_json( $posts );
	}

	/**
	 * Handle Import Form
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function handle_import_form() {
		// Get the raw POST data.
		$post_data = file_get_contents( 'php://input' );
		if ( ! $post_data ) {
			wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
		}
		$data      = json_decode( $post_data, true );
		$responses = [];
		if( ! is_iterable($data)){
			wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
		}
		foreach ( $data as $form_data ) {
			$post_content = $form_data['post']['post_content'];
			$post_title   = $form_data['post']['post_title'];
			$post_meta    = $form_data['post_meta'];
			$post_type    = $form_data['post']['post_type'];
			// Check if sureforms/form exists in post_content.
			if ( 'sureforms_form' === $post_type ) {
				$new_post = [
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_status'  => 'draft',
					'post_type'    => 'sureforms_form',
				];

				$post_id = wp_insert_post( $new_post );
				if ( ! $post_id ) {
					http_response_code( 400 );
					wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
				}
				// Update post meta.
				foreach ( $post_meta as $meta_key => $meta_value ) {
					add_post_meta( $post_id, $meta_key, $meta_value[0] );
				}
			} else {
				http_response_code( 400 );
				wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
			}
		}

		// Return the responses.
		wp_send_json_success( $responses );
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/sureforms_import',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle_import_form' ],
				'permission_callback' => '__return_true',
			]
		);
	}
}
