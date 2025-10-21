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
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'add_forms_data_endpoint' ] );
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
	 * Add Forms Data Endpoints to the list of REST API Endpoints.
	 *
	 * @param array<mixed> $endpoints List of existing REST API Endpoints.
	 * @return array<mixed> Modified list of REST API Endpoints.
	 * @since x.x.x
	 */
	public function add_forms_data_endpoint( $endpoints ) {
		$form_endpoints = [
			'forms' => [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_forms_list' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'page'     => [
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					],
					'per_page' => [
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 100,
					],
					'search'   => [
						'type' => 'string',
					],
					'status'   => [
						'type'    => 'string',
						'enum'    => [ 'publish', 'draft', 'trash', 'any' ],
						'default' => 'publish',
					],
					'orderby'  => [
						'type'    => 'string',
						'default' => 'date',
						'enum'    => [ 'date', 'id', 'title', 'modified' ],
					],
					'order'    => [
						'type'    => 'string',
						'default' => 'desc',
						'enum'    => [ 'asc', 'desc' ],
					],
				],
			],
		];

		return array_merge( $endpoints, $form_endpoints );
	}

	/**
	 * Get forms list for the forms listing page.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since x.x.x
	 */
	public function get_forms_list( $request ) {
		$nonce = sanitize_text_field( Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) ) );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		// Get and validate request parameters.
		$page     = max( 1, Helper::get_integer_value( $request->get_param( 'page' ) ) );
		$per_page = min( 100, max( 1, Helper::get_integer_value( $request->get_param( 'per_page' ) ) ) );
		$search   = sanitize_text_field( $request->get_param( 'search' ) );
		$status   = sanitize_text_field( $request->get_param( 'status' ) );
		$orderby  = sanitize_text_field( $request->get_param( 'orderby' ) );
		$order    = sanitize_text_field( $request->get_param( 'order' ) );

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
	 * @since x.x.x
	 */
	private function prepare_form_for_listing( $post ) {
		$form_id = $post->ID;

		// Get author information.
		$author      = get_userdata( Helper::get_integer_value( $post->post_author ) );
		$author_data = $author ? [
			'id'   => (int) $author->ID,
			'name' => $author->display_name,
		] : null;

		// Get entries count.
		$entries_count = 0;
		if ( class_exists( 'SRFM\Inc\Database\Tables\Entries' ) ) {
			try {
				$entries_count = Helper::get_integer_value( Entries::get_total_entries_by_status( 'all', $form_id ) );
			} catch ( \Exception $e ) {
				// Silently fail and return 0.
				$entries_count = 0;
			}
		}

		return [
			'id'            => $form_id,
			'title'         => get_the_title( $post ),
			'status'        => $post->post_status,
			'date_created'  => mysql_to_rfc3339( $post->post_date ),
			'date_modified' => mysql_to_rfc3339( $post->post_modified ),
			'author'        => $author_data,
			'entries_count' => $entries_count,
			'shortcode'     => "[sureforms id='{$form_id}']",
			'edit_url'      => admin_url( "post.php?post={$form_id}&action=edit" ),
		];
	}
}
