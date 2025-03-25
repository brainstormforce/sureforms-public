<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_Post_Type;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create New Form.
 *
 * @since 0.0.1
 */
class Create_New_Form {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route create-new-form.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/create-new-form',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_form' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		);
	}

	/**
	 * Checks whether a given request has permission to create new forms.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_items_permissions_check() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}
		foreach ( get_post_types( [ 'show_in_rest' => true ], 'objects' ) as $post_type ) {
			/**
			 * The post type.
			 *
			 * @var WP_Post_Type $post_type
			 */
			if ( current_user_can( $post_type->cap->edit_posts ) ) {
				return true;
			}
		}

		return new \WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to create forms.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Get default post metas for form when creating using template.
	 *
	 * @return array<string, array<int, int|string>> Default meta keys.
	 * @since 0.0.2
	 */
	public static function get_default_meta_keys() {
		return [
			'_srfm_submit_button_text'       => [ 'Submit' ],
			'_srfm_use_label_as_placeholder' => [ '' ],
			'_srfm_single_page_form_title'   => [ 1 ],
			'_srfm_instant_form'             => [ '' ],
			'_srfm_form_container_width'     => [ 650 ],
			'_srfm_bg_type'                  => [ 'image' ],
			'_srfm_bg_image'                 => [ '' ],
			'_srfm_cover_image'              => [ '' ],
			'_srfm_bg_color'                 => [ '#ffffff' ],
			'_srfm_submit_width_backend'     => [ 'max-content' ],
			'_srfm_submit_alignment'         => [ 'left' ],
			'_srfm_submit_alignment_backend' => [ '100%' ],
			'_srfm_submit_width'             => [ '' ],
			'_srfm_inherit_theme_button'     => [ '' ],
			'_srfm_additional_classes'       => [ '' ],
			'_srfm_submit_type'              => [ 'message' ],
			'_srfm_form_recaptcha'           => [ 'none' ],
		];
	}

	/**
	 * Create new form post from selected template.
	 *
	 * @param \WP_REST_Request $data Form Markup Data.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 0.0.1
	 */
	public static function create_form( $data ) {
		$nonce = Helper::get_string_value( $data->get_header( 'X-WP-Nonce' ) );
		$nonce = sanitize_text_field( $nonce );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Nonce verification failed.', 'sureforms' ),
				]
			);
		}

		if ( ! is_user_logged_in() ) {
			// Return error if user is not logged in.
			wp_send_json_error(
				[
					'message' => esc_html__( 'You must be logged in to make this request.', 'sureforms' ),
				]
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			// Return error if user does not have permissions to manage options.
			wp_send_json_error(
				[
					'message' => esc_html__( 'You do not have permissions to manage options.', 'sureforms' ),
				]
			);
		}

		$form_info     = $data->get_body();
		$form_info_obj = json_decode( $form_info );

		// Check if JSON decoding was successful and $form_info_obj is an object.
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_object( $form_info_obj ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid JSON format.', 'sureforms' ),
				]
			);
		}

		$required_properties = [ 'template_name', 'form_data' ];

		// Check if required properties exist in the $form_info_obj.
		foreach ( $required_properties as $property ) {
			if ( ! property_exists( $form_info_obj, $property ) ) {
				wp_send_json_error(
					[
						'message' => __( 'Missing required properties in form info.', 'sureforms' ),
					]
				);
			}
		}

		$title   = $form_info_obj->template_name ?? '';
		$content = $form_info_obj->form_data ?? '';

		// Create post metas for the creating form.
		$post_metas = apply_filters(
			'srfm_modify_ai_post_metas',
			[],
			$form_info_obj,
		);

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'meta_input'   => $post_metas,
				'post_status'  => 'draft',
				'post_type'    => 'sureforms_form',
			]
		);

		if ( ! empty( $post_id ) ) {

			/**
			 * Update _srfm_is_ai_generated meta to true.
			 * If the request is coming here then the form is AI generated.
			 */
			update_post_meta( $post_id, '_srfm_is_ai_generated', true );

			return new WP_REST_Response(
				[
					'message' => __( 'SureForms Form created successfully.', 'sureforms' ),
					'id'      => $post_id,
				]
			);
		}
			wp_send_json_error(
				[
					'message' => __( 'Error creating SureForms Form, ', 'sureforms' ),
				]
			);
	}

}
