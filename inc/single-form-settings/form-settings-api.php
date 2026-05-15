<?php
/**
 * SureForms single form settings - REST endpoint for saving scoped meta.
 *
 * @package sureforms
 * @since 2.9.0
 */

namespace SRFM\Inc\Single_Form_Settings;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves a scoped subset of `sureforms_form` post meta in one round-trip.
 * Used by the form-settings dialog's per-tab Save button so a tab can
 * persist only its own meta keys without dirtying meta the user didn't
 * touch.
 *
 * @since 2.9.0
 */
class Form_Settings_Api {
	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_endpoint' ] );
	}

	/**
	 * Register the form-settings endpoint via the existing route filter.
	 *
	 * @param array<string,array<string,mixed>> $endpoints Existing endpoints.
	 * @since 2.9.0
	 * @return array<string,array<string,mixed>>
	 */
	public function register_endpoint( $endpoints ) {
		$endpoints['form-settings'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_form_settings' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'post_id'   => [
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $value ) {
						return is_numeric( $value ) && (int) $value > 0;
					},
				],
				'meta_data' => [
					'required'          => true,
					'type'              => 'object',
					'validate_callback' => static function ( $value ) {
						return is_array( $value ) && ! empty( $value );
					},
				],
			],
		];

		return $endpoints;
	}

	/**
	 * Permission check for the endpoint. Verifies the REST nonce and
	 * the user's capability to edit the target form post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since 2.9.0
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new WP_Error(
				'srfm_invalid_nonce',
				__( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$post_id = absint( $request->get_param( 'post_id' ) );
		// Belt-and-braces: `edit_post` on a `sureforms_form` resolves to
		// `manage_options` today via the CPT's capability map, but a
		// future change there shouldn't be able to silently widen this
		// endpoint to lower roles. Require both explicitly.
		if (
			! $post_id ||
			! current_user_can( 'manage_options' ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return new WP_Error(
				'srfm_cannot_edit_post',
				__( 'You do not have permission to edit this form.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Save the supplied meta keys against the target form post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since 2.9.0
	 * @return WP_Error|WP_REST_Response
	 */
	public function save_form_settings( $request ) {
		$post_id   = absint( $request->get_param( 'post_id' ) );
		$meta_data = (array) $request->get_param( 'meta_data' );

		$post = get_post( $post_id );
		// Guard against ID=0 / non-existent post / wrong post type. The
		// permission check already validates `edit_post` on the supplied
		// ID, but a brand-new draft can resolve to a post with ID 0 if
		// the caller raced ahead of autosave.
		if ( ! $post || ! $post->ID || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'srfm_invalid_form_id',
				__( 'Invalid form id.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		// Allowlist incoming keys so the endpoint can't be used to write
		// arbitrary meta on the form post even though the caller has
		// `edit_post`. Pro extends this list via the
		// `srfm_form_settings_allowed_meta_keys` filter.
		$default_keys = [
			'_srfm_email_notification',
			'_srfm_form_confirmation',
			'_srfm_captcha_security_type',
			'_srfm_form_recaptcha',
			'_srfm_form_custom_css',
			'_srfm_form_restriction',
			'_srfm_compliance',
		];
		// Restrict the merged result to keys that match our prefix so a
		// third-party filter callback can't enable writes to core meta
		// like `_edit_lock` or `_thumbnail_id` through this endpoint.
		$allowed_keys = array_values(
			array_filter(
				(array) apply_filters( 'srfm_form_settings_allowed_meta_keys', $default_keys ),
				static function ( $key ) {
					return is_string( $key ) && 0 === strpos( $key, '_srfm_' );
				}
			)
		);

		$saved = [];
		foreach ( $meta_data as $meta_key => $value ) {
			$meta_key = sanitize_key( $meta_key );
			if ( '' === $meta_key || ! \in_array( $meta_key, $allowed_keys, true ) ) {
				continue;
			}

			update_post_meta( $post_id, $meta_key, wp_slash( $value ) );

			// Read back the persisted value so the client can re-baseline
			// against whatever sanitize_callbacks returned.
			$saved[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Form settings saved.', 'sureforms' ),
				'meta'    => $saved,
			],
			200
		);
	}
}
