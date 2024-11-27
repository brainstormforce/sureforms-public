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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Export {
	use Get_Instance;

	/**
	 * Unserialized post metas.
	 *
	 * @var array<string>
	 */
	public $unserialized_post_metas = [
		'_srfm_conditional_logic',
		'_srfm_email_notification',
		'_srfm_form_confirmation',
		'_srfm_compliance',
		'_srfm_forms_styling',
		'_srfm_integrations_webhooks',
		'_srfm_instant_form_settings',
		'_srfm_page_break_settings',
	];

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
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'export_form_nonce' ) ) {
			$error_message = __( 'Nonce verification failed.', 'sureforms' );

			$error_data = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}

		if ( isset( $_POST['post_id'] ) ) {
			$post_ids = explode( ',', sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
		} else {
			$post_ids = [];
		}

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

		// Unserialize the post metas that are serialized.
		// This is needed because the post metas are serialized before saving.
		foreach ( $posts as $key => $post ) {
			$post_metas = isset( $post['post_meta'] ) && is_array( $post['post_meta'] ) ? $post['post_meta'] : [];
			foreach ( $this->unserialized_post_metas as $meta_key ) {
				if ( isset( $post_metas[ $meta_key ] ) && is_array( $post_metas[ $meta_key ] ) ) {
					$post_metas[ $meta_key ] = maybe_unserialize( $post_metas[ $meta_key ][0] );
				}
			}
			$posts[ $key ]['post_meta'] = $post_metas;
		}

		wp_send_json( $posts );
	}

	/**
	 * Handle Import Form
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function handle_import_form( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

		// Get the raw POST data.
		$post_data = file_get_contents( 'php://input' );
		if ( ! $post_data ) {
			wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
		}
		$data      = json_decode( $post_data, true );
		$responses = [];
		if ( ! is_iterable( $data ) ) {
			wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
		}
		foreach ( $data as $form_data ) {

			// sanitize the data before saving.
			$post_content = wp_kses_post( $form_data['post']['post_content'] );
			$post_title   = sanitize_text_field( $form_data['post']['post_title'] );
			$post_meta    = $form_data['post_meta'];
			$post_type    = sanitize_text_field( $form_data['post']['post_type'] );

			$post_content = addslashes( $post_content );

			// Check if sureforms/form exists in post_content.
			if ( 'sureforms_form' === $post_type ) {
				$new_post = [
					'post_title'  => $post_title,
					'post_status' => 'draft',
					'post_type'   => 'sureforms_form',
				];

				$post_id = wp_insert_post( $new_post );

				// Update the post content formId to the new post id.
				$post_content = str_replace( '\"formId\":' . $form_data['post']['ID'], '\"formId\":' . $post_id, $post_content );

				// update the post content.
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $post_content,
					]
				);

				if ( ! $post_id ) {
					http_response_code( 400 );
					wp_send_json_error( __( 'Failed to import form.', 'sureforms' ) );
				}
				// Update post meta.
				foreach ( $post_meta as $meta_key => $meta_value ) {
					// Check if the meta key is one of the unserialized post metas then add it as is.
					if ( in_array( $meta_key, $this->unserialized_post_metas, true ) ) {
						add_post_meta( $post_id, $meta_key, $meta_value );
					} else {
						if ( is_array( $meta_value ) && isset( $meta_value[0] ) ) {
							add_post_meta( $post_id, $meta_key, $meta_value[0] );
						} else {
							add_post_meta( $post_id, $meta_key, $meta_value );
						}
					}
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
		$helper = Helper::get_instance();
		register_rest_route(
			'sureforms/v1',
			'/sureforms_import',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle_import_form' ],
				'permission_callback' => [ $helper, 'current_user_can' ],
			]
		);
	}
}
