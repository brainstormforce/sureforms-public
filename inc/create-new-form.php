<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Post_Type;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

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
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_items_permissions_check( $request ) {
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
			'_srfm_submit_button_text'              => [ 'Submit' ],
			'_srfm_show_labels'                     => [ 1 ],
			'_srfm_show_asterisk'                   => [ 1 ],
			'_srfm_single_page_form_title'          => [ 1 ],
			'_srfm_instant_form'                    => [ '' ],
			'_srfm_form_container_width'            => [ 650 ],
			'_srfm_color1'                          => [ '#D54407' ],
			'_srfm_bg_type'                         => [ 'image' ],
			'_srfm_bg_image'                        => [ '' ],
			'_srfm_cover_image'                     => [ '' ],
			'_srfm_bg_color'                        => [ '#ffffff' ],
			'_srfm_fontsize'                        => [ 20 ],
			'_srfm_label_color'                     => [ '#111827' ],
			'_srfm_help_color'                      => [ '#4B5563' ],
			'_srfm_input_text_color'                => [ '#4B5563' ],
			'_srfm_input_placeholder_color'         => [ '#94A3B8' ],
			'_srfm_input_bg_color'                  => [ '#ffffff' ],
			'_srfm_input_border_color'              => [ '#D0D5DD' ],
			'_srfm_input_shadow_color'              => [ '#D0D5DD' ],
			'_srfm_input_border_width'              => [ 1 ],
			'_srfm_input_border_radius'             => [ 4 ],
			'_srfm_field_error_color'               => [ '#DC2626' ],
			'_srfm_field_error_surface_color'       => [ '#EF4444' ],
			'_srfm_field_error_shadow_color'        => [ '#FEE4E2' ],
			'_srfm_field_error_bg_color'            => [ '#FEF2F2' ],
			'_srfm_button_text_color'               => [ '#ffffff' ],
			'_srfm_btn_bg_type'                     => [ 'filled' ],
			'_srfm_button_bg_color'                 => [ '#D54407' ],
			'_srfm_button_border_color'             => [ '#ffffff' ],
			'_srfm_button_border_width'             => [ 0 ],
			'_srfm_submit_width_backend'            => [ 'max-content' ],
			'_srfm_button_border_radius'            => [ 4 ],
			'_srfm_submit_alignment'                => [ 'left' ],
			'_srfm_submit_alignment_backend'        => [ '100%' ],
			'_srfm_submit_width'                    => [ '' ],
			'_srfm_inherit_theme_button'            => [ '' ],
			'_srfm_additional_classes'              => [ '' ],
			'_srfm_submit_type'                     => [ 'message' ],
			'_srfm_thankyou_message_title'          => [ 'Thank you' ],
			'_srfm_thankyou_message'                => [ 'Form submitted successfully!' ],
			'_srfm_submit_url'                      => [ '' ],
			'_srfm_form_recaptcha'                  => [ 'none' ],
			'_srfm_is_page_break'                   => [ '' ],
			'_srfm_first_page_label'                => [ 'Page break' ],
			'_srfm_page_break_progress_indicator'   => [ 'connector' ],
			'_srfm_page_break_toggle_label'         => [ '' ],
			'_srfm_previous_button_text'            => [ 'Previous' ],
			'_srfm_next_button_text'                => [ 'Next' ],
			'_srfm_page_break_button_bg_color'      => [ '#D54407' ],
			'_srfm_page_break_button_text_color'    => [ '#ffffff' ],
			'_srfm_page_break_button_border_color'  => [ '#ffffff' ],
			'_srfm_page_break_button_border_width'  => [ 0 ],
			'_srfm_page_break_button_border_radius' => [ 6 ],
			'_srfm_page_break_inherit_theme_button' => [ '' ],
			'_srfm_page_break_button_bg_type'       => [ 'filled' ],
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

		$title          = isset( $form_info_obj->template_name ) ? $form_info_obj->template_name : '';
		$content        = isset( $form_info_obj->form_data ) ? $form_info_obj->form_data : '';
		$template_metas = isset( $form_info_obj->template_metas ) ? (array) $form_info_obj->template_metas : [];

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'sureforms_form',
			]
		);

		if ( ! empty( $post_id ) ) {
			if ( ! empty( $template_metas ) ) {
				$default_post_metas = self::get_default_meta_keys();
				$post_metas         = array_merge( $default_post_metas, $template_metas );

				foreach ( $post_metas as $meta_key => $meta_value ) {
					add_post_meta( $post_id, $meta_key, $meta_value[0] );
				}
			}

			return new WP_REST_Response(
				[
					'message' => __( 'SureForms Form created successfully.', 'sureforms' ),
					'id'      => $post_id,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Error creating SureForms Form, ', 'sureforms' ),
				]
			);
		}
	}


}
