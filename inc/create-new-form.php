<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

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
	 * @since  X.X.X
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
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_sureforms_form' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			)
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
	 * Create new form from selected templates
	 *
	 * @param \WP_REST_Request $data Form Markup Data.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 0.0.1
	 */
	public static function create_sureforms_form( $data ) {

		$form_info     = $data->get_body();
		$form_info_obj = json_decode( $form_info );

		$title   = $form_info_obj->template_name;
		$content = $form_info_obj->form_data;

		// Create a new SureForms Form with Template.
		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'sureforms_form',
			)
		);

		if ( ! empty( $post_id ) ) {
			$response = array(
				'status'  => 'success',
				'message' => 'SureForms Form created successfully',
				'id'      => $post_id,
			);
			return rest_ensure_response( $response );
		} else {
			$response = array(
				'status'  => 'error',
				'message' => 'Error creating SureForms Form',
			);
			return rest_ensure_response( $response );
		}
	}
}
