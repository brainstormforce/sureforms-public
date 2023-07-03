<?php
/**
 * Sureforms Update Form Status Class file.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use WP_REST_Response;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Update Form Status Class.
 *
 * @since X.X.X
 */
class Update_Form_Status {
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
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/update-form-status',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_sureforms_form_status' ],
				'permission_callback' => '__return_true',
			)
		);
	}


	/**
	 * Handle Form status
	 *
	 * @param object $request Request object or array containing form data.
	 *
	 * @return WP_REST_Response
	 * @since X.X.X
	 */
	public function handle_sureforms_form_status( $request ) {
		// @phpstan-ignore-next-line
		$data = $request->get_json_params();
		update_post_meta( $data['post_id'], 'sureforms_form_visibility', $data['sureforms_form_status'] );
		return new WP_REST_Response( [ 'success' => $data ] );
	}
}
