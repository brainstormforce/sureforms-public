<?php
/**
 * Sureforms get forms title and Ids.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_Error;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Load Defaults Class.
 *
 * @since X.X.X
 */
class Forms_Data {
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
	 * @since X.X.X
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/forms-data',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'load_sureforms_forms' ],
				'permission_callback' => [ $this, 'get_form_permissions_check' ],
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read the form.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since X.X.X
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
	 * @return WP_REST_Response
	 * @since X.X.X
	 */
	public function load_sureforms_forms() {
		$args = array(
			'post_type'      => 'sureforms_form',
			'post_status'    => 'publish',
			'posts_per_page' => -1, // Retrieve all posts.
		);

		$form_posts = get_posts( $args );

		$data = array();

		foreach ( $form_posts as $post ) {
			$data[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => $post->post_content,
			);
		}

		return new WP_REST_Response( $data );
	}
}
