<?php
/**
 * Register API for Block Patterns.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\API;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Block_Patterns_Registry;
use WP_Error;
use WP_Post_Type;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Core class used to access block patterns via the REST API.
 *
 * @see WP_REST_Controller
 * @since 0.0.1
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
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Checks whether a given request has permission to read block patterns.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_items_permissions_check( $request ) {
		unset( $request );
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
	 * @since 0.0.1
	 */
	public function get_items( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

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
	 * @since 0.0.1
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$keys   = [
			'name'             => 'name',
			'title'            => 'title',
			'info'             => 'info',
			'description'      => 'description',
			'viewportWidth'    => 'viewport_width',
			'blockTypes'       => 'block_types',
			'categories'       => 'categories',
			'templateCategory' => 'templateCategory',
			'postMetas'        => 'postMetas',
			'slug'             => 'slug',
			'id'               => 'id',
			'isPro'            => 'isPro',
			'keywords'         => 'keywords',
			'content'          => 'content',
			'inserter'         => 'inserter',
		];
		$data   = [];
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
	 * @since 0.0.1
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'block-pattern',
			'type'       => 'object',
			'properties' => [
				'name'             => [
					'description' => __( 'The pattern name.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'title'            => [
					'description' => __( 'The pattern title, in human readable format.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'info'             => [
					'description' => __( 'The pattern basic description', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'description'      => [
					'description' => __( 'The pattern detailed description.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'viewport_width'   => [
					'description' => __( 'The pattern viewport width for inserter preview.', 'sureforms' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'block_types'      => [
					'description' => __( 'Block types that the pattern is intended to be used with.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'categories'       => [
					'description' => __( 'The pattern category slugs.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'templateCategory' => [
					'description' => __( 'The pattern template category.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'postMetas'        => [
					'description' => __( 'The pattern post metas.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'slug'             => [
					'description' => __( 'The pattern slug.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'id'               => [
					'description' => __( 'The pattern template id.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'isPro'            => [
					'description' => __( 'Wether pattern is pro or not.', 'sureforms' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'keywords'         => [
					'description' => __( 'The pattern keywords.', 'sureforms' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'content'          => [
					'description' => __( 'The pattern content.', 'sureforms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
				'inserter'         => [
					'description' => __( 'Determines whether the pattern is visible in inserter.', 'sureforms' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => [ 'view', 'edit', 'embed' ],
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}
}
