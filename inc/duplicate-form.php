<?php
/**
 * Sureforms form duplication.
 *
 * @package sureforms.
 * @since 2.3.0
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Duplicate Form Class.
 *
 * @since 2.3.0
 */
class Duplicate_Form {
	use Get_Instance;

	/**
	 * Duplicate a form with all its metadata
	 *
	 * @param int    $form_id Form ID to duplicate.
	 * @param string $title_suffix Suffix to append to title. Default ' (Copy)'.
	 * @return array<string, mixed>|\WP_Error Result with new form ID or error.
	 * @since 2.3.0
	 */
	public function duplicate_form( $form_id, $title_suffix = ' (Copy)' ) {
		// Validate form ID.
		$form_id = intval( $form_id );
		if ( $form_id <= 0 ) {
			return new \WP_Error(
				'invalid_form_id',
				__( 'Invalid form ID provided.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get source form.
		$source_form = get_post( $form_id );

		if ( ! $source_form ) {
			return new \WP_Error(
				'form_not_found',
				__( 'Source form not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		// Verify it's a sureforms_form post type.
		if ( SRFM_FORMS_POST_TYPE !== $source_form->post_type ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'The specified post is not a SureForms form.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get all post meta.
		$post_meta = get_post_meta( $form_id );

		// Create new form title with suffix.
		$new_title = $this->generate_unique_title( $source_form->post_title, $title_suffix );

		// Prepare new post data.
		$new_post_args = [
			'post_title'   => $new_title,
			'post_content' => $source_form->post_content,
			'post_status'  => 'draft', // Always create as draft for safety.
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_author'  => get_current_user_id(), // Use current user as author.
		];

		// Create the new post.
		$new_form_id_or_error = wp_insert_post( $new_post_args );

		// Check for WP_Error or invalid post ID.
		if ( ! is_int( $new_form_id_or_error ) || $new_form_id_or_error <= 0 ) {
			return new \WP_Error(
				'duplication_failed',
				__( 'Failed to create duplicate form.', 'sureforms' ),
				[ 'status' => 500 ]
			);
		}

		// At this point, we're certain $new_form_id_or_error is a valid post ID (int).
		$new_form_id = Helper::get_integer_value( $new_form_id_or_error );

		// Update formId in Gutenberg blocks.
		$updated_content = $this->update_block_form_ids( $source_form->post_content, $form_id, $new_form_id );

		// Update the post content with new formId.
		wp_update_post(
			[
				'ID'           => $new_form_id,
				'post_content' => $updated_content,
			]
		);

		// Get list of unserialized meta keys.
		$unserialized_metas = $this->get_unserialized_post_metas();

		// Copy all post meta.
		// Ensure $post_meta is an array before iterating.
		if ( is_array( $post_meta ) ) {
			foreach ( $post_meta as $meta_key => $meta_values ) {
				// Ensure meta_key is a string.
				if ( ! is_string( $meta_key ) ) {
					continue;
				}

				// Skip WordPress internal meta keys.
				if ( '_edit_lock' === $meta_key || '_edit_last' === $meta_key ) {
					continue;
				}

				// Handle unserialized metas (these are already arrays/objects).
				if ( in_array( $meta_key, $unserialized_metas, true ) ) {
					if ( is_array( $meta_values ) && isset( $meta_values[0] ) ) {
						// Ensure the value is a string before unserializing.
						$first_value = $meta_values[0];
						if ( is_string( $first_value ) ) {
							$meta_value = maybe_unserialize( $first_value );
							add_post_meta( $new_form_id, $meta_key, $meta_value );
						}
					}
				} else {
					// Handle serialized metas (get first value).
					if ( is_array( $meta_values ) && isset( $meta_values[0] ) ) {
						add_post_meta( $new_form_id, $meta_key, $meta_values[0] );
					}
				}
			}
		}

		// Allow other plugins to hook after duplication.
		do_action( 'srfm_after_form_duplicated', $new_form_id, $form_id );

		// Get edit URL for the new form.
		$edit_url = admin_url( 'admin.php?page=sureforms_form_editor&post=' . $new_form_id );

		// Return success response.
		return [
			'success'          => true,
			'original_form_id' => $form_id,
			'new_form_id'      => $new_form_id,
			'new_form_title'   => $new_title,
			'edit_url'         => $edit_url,
		];
	}

	/**
	 * Handle duplicate form REST API request
	 *
	 * Infrastructure through the permission_callback.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 * @since 2.3.0
	 */
	public function handle_duplicate_form_rest( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$form_id      = absint( $request->get_param( 'form_id' ) );
		$title_suffix = sanitize_text_field( $request->get_param( 'title_suffix' ) );

		// Duplicate the form.
		$result = $this->duplicate_form( $form_id, $title_suffix );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Generate unique title by appending suffix
	 *
	 * If a form with the same title already exists, append a number.
	 *
	 * @param string $base_title Original form title.
	 * @param string $suffix Suffix to append. Default ' (Copy)'.
	 * @return string Unique title.
	 * @since 2.3.0
	 */
	private function generate_unique_title( $base_title, $suffix = ' (Copy)' ) {
		$new_title = $base_title . $suffix;
		$counter   = 2;

		// Check if a form with this title exists.
		while ( $this->title_exists( $new_title ) ) {
			$new_title = $base_title . $suffix . ' ' . $counter;
			++$counter;
		}

		return $new_title;
	}

	/**
	 * Check if a form title already exists
	 *
	 * @param string $title Title to check.
	 * @return bool True if title exists, false otherwise.
	 * @since 2.3.0
	 */
	private function title_exists( $title ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'sureforms_form' AND post_status != 'trash' LIMIT 1",
			$title
		);

		$existing = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		return ! empty( $existing );
	}

	/**
	 * Update formId in Gutenberg blocks
	 *
	 * Replaces the old form ID with new form ID in the block markup.
	 * This function performs a direct string replacement on the post content
	 * to update formId references in Gutenberg block attributes.
	 *
	 * @param string $content Post content with blocks.
	 * @param int    $old_id Original form ID.
	 * @param int    $new_id New form ID.
	 * @return string Updated content with new form ID references.
	 * @since 2.3.0
	 */
	private function update_block_form_ids( $content, $old_id, $new_id ) {
		// Direct string replacement - no escaping needed for this operation.
		return str_replace(
			'"formId":' . intval( $old_id ),
			'"formId":' . intval( $new_id ),
			$content
		);
	}

	/**
	 * Get list of unserialized post meta keys
	 *
	 * These meta keys are already arrays/objects and don't need double unserializing.
	 *
	 * @return array<string> Array of meta keys.
	 * @since 2.3.0
	 */
	private function get_unserialized_post_metas() {
		$export_instance = Export::get_instance();
		return $export_instance->get_unserialized_post_metas();
	}
}
