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

		$after  = is_array( $params ) && ! empty( $params['after'] ) ? sanitize_text_field( Helper::get_string_value( $params['after'] ) ) : '';
		$before = is_array( $params ) && ! empty( $params['before'] ) ? sanitize_text_field( Helper::get_string_value( $params['before'] ) ) : '';

		if ( empty( $after ) || empty( $before ) ) {
			wp_send_json_error( __( 'Invalid date.', 'sureforms' ) );
		}

		$form = is_array( $params ) && ! empty( $params['form'] ) ? sanitize_text_field( Helper::get_string_value( $params['form'] ) ) : '';

		$where = [
			[
				[
					'key'     => 'created_at',
					'value'   => $after,
					'compare' => '>=',
				],
				[
					'key'     => 'created_at',
					'value'   => $before,
					'compare' => '<=',
				],
			],
		];

		if ( ! empty( $form ) ) {
			$where[0][] = [
				'key'     => 'form_id',
				'value'   => $form,
				'compare' => '=',
			];
		}

		return Entries::get_instance()->get_results(
			$where,
			'created_at',
			[ 'ORDER BY created_at DESC' ]
		);
	}

	/**
	 * Get the data for all the forms.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 1.7.0
	 * @return array<mixed>
	 */
	public function get_form_data( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		$forms = Helper::get_instance()->get_sureforms();

		return ! empty( $forms ) ? $forms : [];
	}

	/**
	 * Set onboarding completion status.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 1.9.1
	 * @return \WP_REST_Response
	 */
	public function set_onboarding_status( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		// Set the onboarding status to yes always.
		Onboarding::get_instance()->set_onboarding_status( 'yes' );

		// Get analytics data from request.
		$analytics_data = $request->get_param( 'analyticsData' );

		// Save analytics data if provided.
		if ( $analytics_data ) {
			// Use Helper::update_srfm_option instead of update_option.
			Helper::update_srfm_option( 'onboarding_analytics', $analytics_data );
		}

		return new \WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Get onboarding completion status.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 1.9.1
	 * @return \WP_REST_Response
	 */
	public function get_onboarding_status( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$status = Onboarding::get_instance()->get_onboarding_status();

		return new \WP_REST_Response( [ 'completed' => $status ] );
	}

	/**
	 * Get plugin status for specified plugin.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 1.9.1
	 * @return \WP_REST_Response
	 */
	public function get_plugin_status( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$params      = $request->get_params();
		$plugin_slug = is_array( $params ) && isset( $params['plugin'] ) ?
			sanitize_text_field( Helper::get_string_value( $params['plugin'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Plugin slug is required.', 'sureforms' ) ],
				400
			);
		}

		$integrations = Helper::sureforms_get_integration();

		if ( ! isset( $integrations[ $plugin_slug ] ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Plugin not found.', 'sureforms' ) ],
				404
			);
		}

		$plugin_data = $integrations[ $plugin_slug ];

		// Get fresh status.
		if ( is_array( $plugin_data ) && isset( $plugin_data['path'] ) ) {
			$plugin_data['status'] = Helper::get_plugin_status( Helper::get_string_value( $plugin_data['path'] ) );
		}

		return new \WP_REST_Response( $plugin_data );
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
				'generate-form'         => [
					'methods'             => 'POST',
					'callback'            => [ AI_Form_Builder::get_instance(), 'generate_ai_form' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'use_system_message' => [
							'sanitize_callback' => [ $this, 'sanitize_boolean_field' ],
						],
					],
				],
				// This route is used to map the AI response to SureForms fields markup.
				'map-fields'            => [
					'methods'             => 'POST',
					'callback'            => [ Field_Mapping::get_instance(), 'generate_gutenberg_fields_from_questions' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is used to initiate auth process when user tries to authenticate on billing portal.
				'initiate-auth'         => [
					'methods'             => 'GET',
					'callback'            => [ AI_Auth::get_instance(), 'get_auth_url' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to used to decrypt the access key and save it in the database.
				'handle-access-key'     => [
					'methods'             => 'POST',
					'callback'            => [ AI_Auth::get_instance(), 'handle_access_key' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to get the form submissions for the last 30 days.
				'entries-chart-data'    => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entries_chart_data' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to get all forms data.
				'form-data'             => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_form_data' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// Onboarding endpoints.
				'onboarding/set-status' => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'set_onboarding_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				'onboarding/get-status' => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_onboarding_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// Plugin status endpoint.
				'plugin-status'         => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_plugin_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'plugin' => [
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}
}
