<?php
/**
 * Sureforms export.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

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
		'_srfm_conversational_form',
		'_srfm_premium_common',
		'_srfm_forms_styling_starter',
		'_srfm_user_registration_settings',
	];

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		// Modern REST API endpoints are registered in rest-api.php.
	}

	/**
	 * Get unserialized post meta keys.
	 *
	 * Retrieves the list of post meta keys that need to be unserialized during export.
	 * Allows filtering of meta keys via 'srfm_export_and_import_post_meta_keys' filter.
	 *
	 * @since 1.9.0
	 * @return array<string> Array of post meta keys to unserialize.
	 */
	public function get_unserialized_post_metas() {
		return Helper::apply_filters_as_array( 'srfm_export_and_import_post_meta_keys', $this->unserialized_post_metas );
	}

	/**
	 * Get forms with meta by post IDs.
	 * Uses:
	 *     - On websitedemos.net, for exporting the Spectra Block Patterns & Pages with SureForms form.
	 *
	 * @since 1.13.0
	 * @param array<int,string>|array<int, int> $post_ids Array of post IDs to retrieve forms for.
	 * @return array Array of forms with their post data and meta data.
	 */
	public function get_forms_with_meta( $post_ids = [] ) {
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

			foreach ( $this->get_unserialized_post_metas() as $meta_key ) {
				if ( isset( $post_metas[ $meta_key ] ) && is_array( $post_metas[ $meta_key ] ) ) {
					$post_metas[ $meta_key ] = maybe_unserialize( $post_metas[ $meta_key ][0] );
				}
			}
			$posts[ $key ]['post_meta'] = $post_metas;
		}

		return $posts;
	}

	/**
	 * Handle Export form via REST API
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_export_form_rest( $request ) {
		$nonce = sanitize_text_field( Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) ) );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$params   = $request->get_params();
		$post_ids = [];

		// Handle post_ids parameter - can be array or comma-separated string.
		if ( isset( $params['post_ids'] ) ) {
			if ( is_array( $params['post_ids'] ) ) {
				$post_ids = array_map( 'intval', $params['post_ids'] );
			} else {
				$post_ids = array_map( 'intval', explode( ',', sanitize_text_field( Helper::get_string_value( $params['post_ids'] ) ) ) );
			}
		}

		// Validate that all post IDs are valid sureforms_form posts.
		$validated_post_ids = [];
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post && 'sureforms_form' === $post->post_type ) {
				$validated_post_ids[] = $post_id;
			}
		}

		if ( empty( $validated_post_ids ) ) {
			return new \WP_Error(
				'no_valid_forms',
				__( 'No valid forms found for export.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$posts = $this->get_forms_with_meta( $validated_post_ids );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $posts,
				'count'   => count( $posts ),
			]
		);
	}

	/**
	 * Handle Import form via REST API
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_import_form_rest( $request ) {
		$nonce = sanitize_text_field( Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) ) );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$params = $request->get_params();

		// Get forms data from the request.
		$forms_data     = isset( $params['forms_data'] ) && is_array( $params['forms_data'] ) ? $params['forms_data'] : [];
		$default_status = isset( $params['default_status'] ) ? sanitize_text_field( Helper::get_string_value( $params['default_status'] ) ) : 'draft';

		if ( empty( $forms_data ) ) {
			return new \WP_Error(
				'no_forms_data',
				__( 'No forms data provided for import.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Validate forms data structure.
		foreach ( $forms_data as $form_data ) {
			if ( ! is_array( $form_data ) || ! isset( $form_data['post'] ) || ! isset( $form_data['post_meta'] ) ) {
				return new \WP_Error(
					'invalid_form_data',
					__( 'Invalid form data structure provided.', 'sureforms' ),
					[ 'status' => 400 ]
				);
			}
		}

		$result = $this->import_forms_with_meta( $forms_data, $default_status );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response(
			[
				'success'        => true,
				'message'        => __( 'Forms imported successfully.', 'sureforms' ),
				'forms_mapping'  => $result,
				'imported_count' => count( $result ),
			]
		);
	}

	/**
	 * Import Forms with Meta
	 * Uses:
	 *     - In Design Library for importing the Spectra Block Patterns and Pages with SureForms form.
	 *
	 * @param array<array<array<string>>> $data           Form data to import.
	 * @param string                      $default_status Default post status for imported forms. Default is 'draft'.
	 *
	 * @since 1.13.0
	 * @return array<int, int>|\WP_Error Returns mapping array on success, WP_Error on failure.
	 */
	public function import_forms_with_meta( $data, $default_status = 'draft' ) {
		$forms_mapping = [];
		foreach ( $data as $form_data ) {
			// sanitize the data before saving.
			$old_id       = intval( $form_data['post']['ID'] );
			$post_content = wp_kses_post( $form_data['post']['post_content'] );
			$post_title   = sanitize_text_field( $form_data['post']['post_title'] );
			$post_meta    = $form_data['post_meta'];
			$post_type    = sanitize_text_field( $form_data['post']['post_type'] );

			$post_content = addslashes( $post_content );

			// Check if sureforms/form exists in post_content.
			if ( 'sureforms_form' === $post_type ) {
				$new_post = [
					'post_title'  => $post_title,
					'post_status' => $default_status,
					'post_type'   => 'sureforms_form',
				];

				$post_id = wp_insert_post( $new_post );

				// Update the post content formId to the new post id.
				$post_content = str_replace(
					'\"formId\":' . intval( $form_data['post']['ID'] ),
					'\"formId\":' . intval( $post_id ),
					$post_content
				);

				// update the post content.
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $post_content,
					]
				);

				if ( ! $post_id ) {
					return new \WP_Error( 'import_forms_failed', __( 'Failed to import form.', 'sureforms' ) );
				}

				$forms_mapping[ $old_id ] = $post_id;

				// Update post meta.
				foreach ( $post_meta as $meta_key => $meta_value ) {
					// Check if the meta key is one of the unserialized post metas then add it as is.
					if ( in_array( $meta_key, $this->get_unserialized_post_metas(), true ) ) {
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
				return new \WP_Error( 'import_forms_invalid_post_type', __( 'Failed to import form.', 'sureforms' ) );
			}
		}

		return $forms_mapping;
	}

}
