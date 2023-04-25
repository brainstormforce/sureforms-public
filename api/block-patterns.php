<?php
/**
 * Register API for Block Patterns.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\API;

use SureForms\Inc\Traits\Get_Instance;
use WP_Error;
use WP_Post_Type;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Block_Patterns_Registry;

/**
 * Core class used to access block patterns via the REST API.
 *
 * @see WP_REST_Controller
 * @since X.X.X
 */
class Block_Patterns extends WP_REST_Controller {

	use Get_Instance;

	/**
	 * Class Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->namespace = 'sureforms/v1';
		$this->rest_base = 'form-patterns';

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since X.X.X
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read block patterns.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since X.X.X
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
			__( 'Sorry, you are not allowed to view the registered form patterns.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Retrieves all block patterns.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since X.X.X
	 */
	public function get_items( $request ) {
		$response = [];
		$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
		$filtered = array_filter(
			$patterns,
			static function( $pattern ) {
				return in_array( 'sureforms_form', $pattern['categories'] ?? [], true );
			}
		);
		foreach ( $filtered as $pattern ) {
			$prepared_pattern = $this->prepare_item_for_response( $pattern, $request );
			if ( ! is_wp_error( $prepared_pattern ) ) {
				$response[] = $this->prepare_response_for_collection( $prepared_pattern );
			}
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a raw block pattern before it gets output in a REST API response.
	 *
	 * @param array<mixed>    $item    Raw pattern as registered, before any changes.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since X.X.X
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$keys   = array(
			'name'          => 'name',
			'title'         => 'title',
			'description'   => 'description',
			'viewportWidth' => 'viewport_width',
			'blockTypes'    => 'block_types',
			'categories'    => 'categories',
			'keywords'      => 'keywords',
			'content'       => 'content',
			'inserter'      => 'inserter',
		);
		$data   = array();
		foreach ( $keys as $item_key => $rest_key ) {
			if ( isset( $item[ $item_key ] ) && rest_is_field_included( $rest_key, $fields ) ) {
				$data[ $rest_key ] = $item[ $item_key ];
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );
		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the block pattern schema, conforming to JSON Schema.
	 *
	 * @return array<mixed>  Item schema data.
	 * @since X.X.X
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'block-pattern',
			'type'       => 'object',
			'properties' => array(
				'name'           => array(
					'description' => __( 'The pattern name.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'title'          => array(
					'description' => __( 'The pattern title, in human readable format.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'description'    => array(
					'description' => __( 'The pattern detailed description.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'viewport_width' => array(
					'description' => __( 'The pattern viewport width for inserter preview.', 'sureforms' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'block_types'    => array(
					'description' => __( 'Block types that the pattern is intended to be used with.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'categories'     => array(
					'description' => __( 'The pattern category slugs.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'keywords'       => array(
					'description' => __( 'The pattern keywords.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'content'        => array(
					'description' => __( 'The pattern content.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'inserter'       => array(
					'description' => __( 'Determines whether the pattern is visible in inserter.', 'sureforms' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
