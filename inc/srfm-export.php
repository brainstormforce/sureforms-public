<?php
/**
 * Sureforms export.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
use WP_REST_Server;
/**
 * Load Defaults Class.
 *
 * @since X.X.X
 */
class SRFM_Export {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_action( 'wp_ajax_export_form', [ $this, 'handle_export_form' ] );
		add_action( 'wp_ajax_import_form', [ $this, 'handle_import_form' ] );
		add_action( 'wp_ajax_nopriv_export_form', [ $this, 'handle_export_form' ] );
		add_action( 'wp_ajax_nopriv_import_form', [ $this, 'handle_import_form' ] );
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function handle_export_form() {
		$postIds = explode( ',', $_POST['post_id'] );

		$posts = array();

		foreach ( $postIds as $postId ) {
			$post    = get_post( $postId );
			$posts[] = $post;
		}

		$jsonData = json_encode( $posts );

		echo $jsonData;
		die();
	}

	public function handle_import_form() {
		// Get the raw POST data
		$postData = file_get_contents( 'php://input' );
		$data = json_decode($postData, true);
		$responses = array();

		foreach ( $data as $formData ) {
			$post_content = $formData['post_content'];
			$post_title   = $formData['post_title'];
			$new_post     = array(
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_status'  => 'publish',
				'post_type'    => 'sureforms_form',
			);

			$post_id = wp_insert_post( $new_post );

			if ( $post_id ) {
				$responses[] = 'form inserted successfully with the post ID of ' . $post_id;
			} else {
				$responses[] = 'Error, form not inserted';
			}
		}

		// Return the responses
		wp_send_json_success( $responses );
		die();
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/sureforms_import',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle_import_form' ],
				'permission_callback' => '__return_true',
			)
		);
	}
}
