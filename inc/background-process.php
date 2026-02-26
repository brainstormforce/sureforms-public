<?php
/**
 * Sureforms Background Process.
 *
 * @package sureforms.
 * @since 0.0.3
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Background Process Class.
 *
 * @since 0.0.3
 */
class Background_Process {
	use Get_Instance;

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sureforms/v1';
	/**
	 * Submission Id.
	 *
	 * @var int
	 */
	protected $submission_id = 0;
	/**
	 * Form Id.
	 *
	 * @var int
	 */
	protected $form_id = 0;
	/**
	 * Submission Data.
	 *
	 * @var array<mixed>
	 */
	protected $submission_data = [];

	/**
	 * Constructor
	 *
	 * @since  0.0.3
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route for 'After Submission' background process.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			$this->namespace,
			'/after-submission/(?P<submission_id>[0-9]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_after_submission' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'submission_id' => [
						'required'          => true,
						'validate_callback' => static function( $param ) {
							return is_integer( Helper::get_integer_value( $param ) ) && 0 < $param;
						},
					],
				],
			]
		);
	}

	/**
	 * Handle 'After Submission' background process
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @since 0.0.3
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function handle_after_submission( $request ) {
		$this->submission_id = Helper::get_integer_value( $request->get_param( 'submission_id' ) );

		$extras                                = $this->get_extras_data( $this->submission_id );
		$is_after_submission_process_triggered = $extras['is_after_submission_process_triggered'] ?? false;

		if ( $is_after_submission_process_triggered ) {
			return new \WP_Error(
				'process_already_triggered',
				__( 'After submission process has already been triggered for this submission.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$nonce = Helper::get_string_value( $request->get_param( 'after_submit_nonce' ) );
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'srfm_after_submission_' . Helper::get_string_value( $this->submission_id ) ) ) {
			return new \WP_Error(
				'rest_nonce_failed',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		if ( ! ( 0 < $this->submission_id ) ) {
			return new \WP_Error(
				'incomplete_request',
				__( 'Submission id missing.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		// Get the entries data for further processing, related to webhooks.
		$entry_data            = Entries::get( $this->submission_id );
		$this->form_id         = Helper::get_integer_value( $entry_data['form_id'] );
		$this->submission_data = Helper::get_array_value( $entry_data['form_data'] );

		if ( ! $this->trigger_after_submission_process() ) {
			return new \WP_Error(
				'process_failed',
				__( 'Something went wrong. We have logged the error for further investigation', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		return new \WP_REST_Response( [] );
	}

	/**
	 * Handle 'After Submission' background process
	 *
	 * @since 0.0.3
	 * @return bool
	 */
	public function trigger_after_submission_process() {
		if ( ! is_integer( $this->form_id ) || ! is_array( $this->submission_data ) ) {
			return false;
		}
		$form_data                  = $this->submission_data;
		$form_data['form_id']       = $this->form_id;
		$form_data['submission_id'] = $this->submission_id; // Refers to the entry ID.
		/**
		 * Hook for enabling background processes.
		 *
		 * @param array $form_data form data related to submission.
		 */
		do_action( 'srfm_after_submission_process', $form_data );

		// Update the extras column to track that the after submission process was triggered.
		if ( 0 < $this->submission_id ) {
			$extras = $this->get_extras_data( $this->submission_id );

			// Merge new data.
			$updated_extras = array_merge(
				$extras,
				[ 'is_after_submission_process_triggered' => true ]
			);

			// Update entry.
			Entries::update(
				$this->submission_id,
				[ 'extras' => $updated_extras ]
			);
		}

		return true;
	}

	/**
	 * Get extras data from entry.
	 *
	 * @param int $submission_id Submission ID.
	 * @since 1.13.2
	 * @return array<string|int,mixed> Extras array.
	 */
	protected function get_extras_data( $submission_id ) {
		$entry_data = Entries::get( $submission_id );
		return Helper::get_array_value( $entry_data['extras'] ) ?? [];
	}
}
