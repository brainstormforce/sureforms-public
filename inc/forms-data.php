<?php
/**
 * Sureforms get forms title and Ids.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Forms_Data {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
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
			'/forms-data',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'load_forms' ],
				'permission_callback' => [ $this, 'get_form_permissions_check' ],
			]
		);
	}

	/**
	 * Checks whether a given request has permission to read the form.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_form_permissions_check() {
		if ( Helper::current_user_can( 'edit_posts' ) ) {
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
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.0.1
	 */
	public function load_forms( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

		$args = [
			'post_type'      => 'sureforms_form',
			'post_status'    => 'publish',
			'posts_per_page' => -1, // Retrieve all posts.
		];

		$form_posts = get_posts( $args );

		$data = [];

		foreach ( $form_posts as $post ) {
			$data[] = [
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => $post->post_content,
			];
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * Get forms list for the forms listing page.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 2.0.0
	 */
	public function get_forms_list( $request ) {
		$nonce = sanitize_text_field( Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) ) );

		Helper::verify_nonce_and_capabilities( 'rest', $nonce, 'wp_rest' );

		// Get and validate request parameters.
		$page   = max( 1, Helper::get_integer_value( $request->get_param( 'page' ) ) );
		$status = sanitize_text_field( $request->get_param( 'status' ) );

		// Get per_page from option first, then request parameter, with fallback to 10.
		$saved_per_page   = Helper::get_srfm_option( 'forms_per_page', 10 );
		$request_per_page = $request->get_param( 'per_page' );
		$per_page         = $request_per_page ? min( 100, max( 1, Helper::get_integer_value( $request_per_page ) ) ) : $saved_per_page;

		// Save per_page to option if it came from request.
		if ( $request_per_page && 'trash' !== $status && 1 < $request_per_page ) {
			Helper::update_srfm_option( 'forms_per_page', $per_page );
		}

		$search    = sanitize_text_field( $request->get_param( 'search' ) );
		$orderby   = sanitize_text_field( $request->get_param( 'orderby' ) );
		$order     = sanitize_text_field( $request->get_param( 'order' ) );
		$date_from = sanitize_text_field( $request->get_param( 'after' ) );
		$date_to   = sanitize_text_field( $request->get_param( 'before' ) );

		// Build query arguments.
		$args = [
			'post_type'      => SRFM_FORMS_POST_TYPE,
			'post_status'    => 'any' === $status ? [ 'publish', 'draft' ] : $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		// Add search parameter.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Add date range filtering.
		if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
			$date_query = [];
			// Handle 'after' date.
			if ( ! empty( $date_from ) ) {
				$date_query['after'] = $date_from;
			}

			// Handle 'before' date - add 1 day to include the full end date.
			if ( ! empty( $date_to ) ) {
				$end_date = new \DateTime( $date_to );
				$end_date->add( new \DateInterval( 'P1D' ) ); // Add 1 day.
				$date_query['before'] = $end_date->format( 'Y-m-d' );
			}

			$date_query['inclusive'] = true;
			$args['date_query']      = [ $date_query ];
		}

		// Execute query.
		$query = new \WP_Query( $args );

		$forms = [];
		/**
		 * Post object from the query.
		 *
		 * @var \WP_Post $post */
		foreach ( $query->posts as $post ) {
			$forms[] = $this->prepare_form_for_listing( $post );
		}

		// Prepare response.
		$response_data = [
			'forms'        => $forms,
			'total'        => Helper::get_integer_value( $query->found_posts ),
			'total_pages'  => Helper::get_integer_value( $query->max_num_pages ),
			'current_page' => $page,
			'per_page'     => $per_page,
		];

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Prepare a single form for the listing response.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<mixed> Prepared form data for listing.
	 * @since 2.0.0
	 */
	private function prepare_form_for_listing( $post ) {
		$form_id = $post->ID;

		// Get entries count.
		$entries_count = Helper::get_integer_value( Entries::get_total_entries_by_status( 'all', $form_id ) );

		return [
			'id'            => $form_id,
			'title'         => $post->post_title,
			'status'        => $post->post_status,
			'date_created'  => mysql_to_rfc3339( $post->post_date ),
			'date_modified' => mysql_to_rfc3339( $post->post_modified ),
			'entries_count' => $entries_count,
			'shortcode'     => "[sureforms id='{$form_id}']",
			'edit_url'      => admin_url( "post.php?post={$form_id}&action=edit" ),
			'frontend_url'  => get_permalink( $form_id ),
		];
	}
}
