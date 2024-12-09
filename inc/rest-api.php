<?php
/**
 * Rest API Manager Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc;

use SRFM\Inc\AI_Form_Builder\AI_Auth;
use SRFM\Inc\AI_Form_Builder\AI_Form_Builder;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Rest API handler class.
 *
 * @since 0.0.7
 */
class Rest_Api {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.7
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register endpoints
	 *
	 * @since 0.0.7
	 * @return void
	 */
	public function register_endpoints() {

		$prefix       = 'sureforms';
		$version_slug = 'v1';

		$endpoints = $this->get_endpoints();

		foreach ( $endpoints as $endpoint => $args ) {
			register_rest_route(
				$prefix . '/' . $version_slug,
				$endpoint,
				$args
			);
		}
	}

	/**
	 * Check if user can edit posts
	 *
	 * @since 0.0.7
	 * @return bool
	 */
	public function can_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks whether the value is boolean or not.
	 *
	 * @param mixed $value value to be checked.
	 * @since 0.0.8
	 * @return bool
	 */
	public function sanitize_boolean_field( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Generate the block slugs as per the request by parsing the post content.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @since 0.0.7
	 * @return void
	 */
	public function generate_block_slugs_by_content( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		$slugs   = [];
		$updated = false;
		$params  = $request->get_params();

		if ( empty( $params['formID'] ) ) {
			wp_send_json_error( __( 'Invalid request. Form ID missing.', 'sureforms' ) );
		}

		$form    = get_post( absint( $params['formID'] ) );
		$content = ! empty( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';

		if ( ! is_null( $form ) ) {
			Helper::process_blocks( parse_blocks( $form->post_content ), $slugs, $updated );
		}

		Helper::process_blocks( parse_blocks( $content ), $slugs, $updated, '', true );

		wp_send_json_success( $slugs );
	}

	/**
	 * Get the data for generating entries chart.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 1.0.0
	 * @return array<mixed>
	 */
	public function get_entries_chart_data( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		$params = $request->get_params();

		if ( empty( $params ) ) {
			wp_send_json_error( __( 'Request could not be processed.', 'sureforms' ) );
		}

		$after = is_array( $params ) && ! empty( $params['after'] ) ? sanitize_text_field( Helper::get_string_value( $params['after'] ) ) : '';

		if ( empty( $after ) ) {
			wp_send_json_error( __( 'Invalid date.', 'sureforms' ) );
		}

		$where = [
			[
				[
					'key'     => 'created_at',
					'value'   => $after,
					'compare' => '>=',
				],

			],
		];

		return Entries::get_instance()->get_results(
			$where,
			'created_at',
			[ 'ORDER BY created_at DESC' ]
		);
	}

	/**
	 * Get endpoints
	 *
	 * @since 0.0.7
	 * @return array<array<mixed>>
	 */
	private function get_endpoints() {
		/*
		 * @internal This filter is used to add custom endpoints.
		 * @since 1.2.0
		 * @param array<array<mixed>> $endpoints Endpoints.
		 */
		return apply_filters(
			'srfm_rest_api_endpoints',
			[
				'generate-block-slugs' => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'generate_block_slugs_by_content' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
				],
				'generate-form'        => [
					'methods'             => 'POST',
					'callback'            => [ AI_Form_Builder::get_instance(), 'generate_ai_form' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
					'args'                => [
						'use_system_message' => [
							'sanitize_callback' => [ $this, 'sanitize_boolean_field' ],
						],
					],
				],
				// This route is used to map the AI response to SureForms fields markup.
				'map-fields'           => [
					'methods'             => 'POST',
					'callback'            => [ Field_Mapping::get_instance(), 'generate_gutenberg_fields_from_questions' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
				],
				// This route is used to initiate auth process when user tries to authenticate on billing portal.
				'initiate-auth'        => [
					'methods'             => 'GET',
					'callback'            => [ AI_Auth::get_instance(), 'get_auth_url' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
				],
				// This route is to used to decrypt the access key and save it in the database.
				'handle-access-key'    => [
					'methods'             => 'POST',
					'callback'            => [ AI_Auth::get_instance(), 'handle_access_key' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
				],
				// This route is to get the form submissions for the last 30 days.
				'entries-chart-data'   => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entries_chart_data' ],
					'permission_callback' => [ $this, 'can_edit_posts' ],
				],
			]
		);
	}
}
