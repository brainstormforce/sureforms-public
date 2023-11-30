<?php
/**
 * Sureforms Load Defaults Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_Error;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Load_Form_Defaults {
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
	 * Add custom API Route load-form-defaults
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/load-form-defaults',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_sureforms_form_defaults' ],
				'permission_callback' => [ $this, 'get_form_permissions_check' ],
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read the form.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_form_permissions_check( $request ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to view the form.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Handle Form status
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 *
	 * @return WP_REST_Response
	 * @since 0.0.1
	 */
	public function handle_sureforms_form_defaults( $request ) {
		$data            = Sureforms_Helper::sanitize_recursively( 'sanitize_text_field', $request->get_json_params() );
		$current_post_id = isset( $data['post_id'] ) ? Sureforms_Helper::get_integer_value( $data['post_id'] ) : '';

		$meta_values = array(
			'_srfm_color1',
			'_srfm_textcolor1',
			'_srfm_color2',
			'_srfm_fontsize',
			'_srfm_bg',
			'_srfm_thankyou_message',
			'_srfm_email',
			'_srfm_submit_type',
			'_srfm_submit_url',
			'_srfm_sender_notification',
			'_srfm_form_recaptcha',
			'_srfm_submit_alignment',
			'_srfm_submit_width',
			'_srfm_submit_styling_inherit_from_theme',
			'_srfm_form_container_width',
			'_srfm_form_styling',
			'_srfm_thankyou_message_title',
		);

		foreach ( $meta_values as $meta_key ) {
			$meta_value = isset( $data[ $meta_key ] ) ? $data[ $meta_key ] : '';
			update_post_meta( Sureforms_Helper::get_integer_value( $current_post_id ), $meta_key, $meta_value );
		}

		return new WP_REST_Response( [ 'success' => $data ] );
	}
}
