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
use SRFM\Inc\Entries as Entries_Class;
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
	 * Dropdown counter for field name generation.
	 *
	 * @var int
	 * @since 2.0.0
	 */
	private static $dropdown_counter = 0;

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
	 * Sanitize entry IDs.
	 *
	 * @param mixed $value Value to sanitize.
	 * @since 2.0.0
	 * @return array<int>
	 */
	public function sanitize_entry_ids( $value ) {
		if ( is_array( $value ) ) {
			return array_filter( array_map( 'absint', $value ) );
		}
		if ( is_numeric( $value ) ) {
			return [ absint( $value ) ];
		}
		if ( is_string( $value ) ) {
			// Handle comma-separated values.
			$ids = explode( ',', $value );
			return array_filter( array_map( 'absint', $ids ) );
		}
		return [];
	}

	/**
	 * Validate read action parameter.
	 *
	 * @param string $param Action parameter value.
	 * @since 2.0.0
	 * @return bool
	 */
	public function validate_read_action( $param ) {
		return in_array( $param, [ 'read', 'unread' ], true );
	}

	/**
	 * Validate trash action parameter.
	 *
	 * @param string $param Action parameter value.
	 * @since 2.0.0
	 * @return bool
	 */
	public function validate_trash_action( $param ) {
		return in_array( $param, [ 'trash', 'restore' ], true );
	}

	/**
	 * Get entries list with filters and pagination.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function get_entries_list( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$params = $request->get_params();

		$args = [
			'form_id'   => isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0,
			'status'    => isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'all',
			'search'    => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
			'date_from' => isset( $params['date_from'] ) ? sanitize_text_field( $params['date_from'] ) : '',
			'date_to'   => isset( $params['date_to'] ) ? sanitize_text_field( $params['date_to'] ) : '',
			'orderby'   => isset( $params['orderby'] ) ? sanitize_text_field( $params['orderby'] ) : 'created_at',
			'order'     => isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'DESC',
			'per_page'  => isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 20,
			'page'      => isset( $params['page'] ) ? absint( $params['page'] ) : 1,
		];

		$result = Entries_Class::get_entries( $args );

		// Add form permalink to each entry.
		if ( isset( $result['entries'] ) && is_array( $result['entries'] ) ) {
			foreach ( $result['entries'] as &$entry ) {
				if ( isset( $entry['form_id'] ) ) {
					$entry['form_permalink'] = get_permalink( absint( $entry['form_id'] ) );
				}
			}
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Update entries read status (read/unread).
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function update_entries_read_status( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$entry_ids = $request->get_param( 'entry_ids' );
		$action    = $request->get_param( 'action' );

		if ( empty( $entry_ids ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'No entry IDs provided.', 'sureforms' ) ],
				400
			);
		}

		if ( empty( $action ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'No action provided.', 'sureforms' ) ],
				400
			);
		}

		// Validate action.
		if ( ! $this->validate_read_action( $action ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Invalid action. Must be "read" or "unread".', 'sureforms' ) ],
				400
			);
		}

		$result = Entries_Class::update_status( $entry_ids, $action );

		$status_code = $result['success'] ? 200 : 400;

		return new \WP_REST_Response( $result, $status_code );
	}

	/**
	 * Update entries trash status (trash/restore).
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function update_entries_trash_status( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$entry_ids = $request->get_param( 'entry_ids' );
		$action    = $request->get_param( 'action' );

		if ( empty( $entry_ids ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'No entry IDs provided.', 'sureforms' ) ],
				400
			);
		}

		if ( empty( $action ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'No action provided.', 'sureforms' ) ],
				400
			);
		}

		// Validate action.
		if ( ! $this->validate_trash_action( $action ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Invalid action. Must be "trash" or "restore".', 'sureforms' ) ],
				400
			);
		}

		$result = Entries_Class::update_status( $entry_ids, $action );

		$status_code = $result['success'] ? 200 : 400;

		return new \WP_REST_Response( $result, $status_code );
	}

	/**
	 * Permanently delete entries.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function delete_entries( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$entry_ids = $request->get_param( 'entry_ids' );

		if ( empty( $entry_ids ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'No entry IDs provided.', 'sureforms' ) ],
				400
			);
		}

		$result = Entries_Class::delete_entries( $entry_ids );

		$status_code = $result['success'] ? 200 : 400;

		return new \WP_REST_Response( $result, $status_code );
	}

	/**
	 * Get entry details with form data, submission info, and metadata.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function get_entry_details( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$entry_id = absint( $request->get_param( 'id' ) );

		if ( empty( $entry_id ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Entry ID is required.', 'sureforms' ) ],
				400
			);
		}

		$entry = Entries::get( $entry_id );

		if ( ! $entry ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Entry not found.', 'sureforms' ) ],
				404
			);
		}

		// Process form data.
		$form_data       = [];
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
		$entry_form_data = $entry['form_data'] ?? [];

		if ( is_array( $entry_form_data ) ) {
			foreach ( $entry_form_data as $field_name => $value ) {
				if ( ! is_string( $field_name ) || in_array( $field_name, $excluded_fields, true ) ) {
					continue;
				}
				if ( false === str_contains( $field_name, '-lbl-' ) ) {
					continue;
				}

				$label_parts      = explode( '-lbl-', $field_name );
				$label            = isset( $label_parts[1] ) ? explode( '-', $label_parts[1] )[0] : '';
				$label            = $label ? Helper::decrypt( $label ) : '';
				$field_block_name = Helper::get_block_name_from_field( $field_name );

				/**
				 * Filter: 'srfm_entry_value'
				 *
				 * This filter is used to allow 3rd party plugins or custom code to modify
				 * the entry field value in the entry details REST API response, if required.
				 * For example, you may want to decrypt, format, or mask sensitive data before output.
				 *
				 * @since 2.0.0
				 *
				 * @param mixed $value             The original value for the field.
				 * @param array $context           An array of context, including:
				 *                                 - field_name (string)
				 *                                 - label (string)
				 *                                 - field_block_name (string)
				 *
				 * @return mixed
				 */
				$value = apply_filters(
					'srfm_entry_value',
					$value,
					[
						'field_name'       => $field_name,
						'label'            => $label,
						'field_block_name' => $field_block_name,
					]
				);

				$form_data[] = [
					'field_name' => $field_name,
					'label'      => $label,
					'value'      => $value,
					'block_name' => $field_block_name,
				];
			}
		}

		// Get user info.
		$user_id   = Helper::get_integer_value( $entry['user_id'] );
		$user_info = 0 !== $user_id ? get_userdata( $user_id ) : null;

		// Get form info.
		$form_title = get_post_field( 'post_title', $entry['form_id'] );
		// Translators: %d is the form ID.
		$form_name = ! empty( $form_title ) ? $form_title : sprintf( __( 'SureForms Form #%d', 'sureforms' ), intval( $entry['form_id'] ) );

		// Parse form content to get structured field data.
		$form_content = get_post_field( 'post_content', $entry['form_id'] );
		$form_fields  = $this->parse_form_fields( $form_content, $entry['form_data'] ?? [] );

		$response_data = [
			'id'              => $entry_id,
			'form_id'         => $entry['form_id'],
			'form_name'       => $form_name,
			'form_permalink'  => get_permalink( $entry['form_id'] ),
			'status'          => $entry['status'],
			'created_at'      => $entry['created_at'],
			'form_data'       => $form_data,
			'form_content'    => $form_fields,
			'submission_info' => [
				'user_ip'      => $entry['submission_info']['user_ip'] ?? '',
				'browser_name' => $entry['submission_info']['browser_name'] ?? '',
				'device_name'  => $entry['submission_info']['device_name'] ?? '',
			],
			'user'            => $user_info ? [
				'id'           => $user_id,
				'display_name' => $user_info->display_name,
				'profile_url'  => get_author_posts_url( $user_id ),
			] : null,
			'extras'          => $entry['extras'] ?? [],
		];

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Get entry logs with pagination support.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function get_entry_logs( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$entry_id = absint( $request->get_param( 'id' ) );
		$per_page = absint( $request->get_param( 'per_page' ) );
		$per_page = $per_page ? $per_page : 3;
		$page     = absint( $request->get_param( 'page' ) );
		$page     = $page ? $page : 1;

		if ( empty( $entry_id ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Entry ID is required.', 'sureforms' ) ],
				400
			);
		}

		$entry = Entries::get( $entry_id );

		if ( ! $entry ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Entry not found.', 'sureforms' ) ],
				404
			);
		}

		$logs        = $entry['logs'] ?? [];
		$logs        = is_array( $logs ) ? $logs : [];
		$total_logs  = count( $logs );
		$total_pages = ceil( $total_logs / $per_page );
		$offset      = ( $page - 1 ) * $per_page;

		// Paginate logs.
		$paginated_logs = array_slice( $logs, $offset, $per_page );

		// Format logs with unique IDs for deletion.
		$formatted_logs = [];
		foreach ( $paginated_logs as $index => $log ) {
			if ( ! is_array( $log ) ) {
				continue;
			}
			$formatted_logs[] = [
				'id'        => $offset + $index, // Use offset-based ID for consistent deletion.
				'title'     => $log['title'] ?? '',
				'timestamp' => $log['timestamp'] ?? time(),
				'messages'  => $log['messages'] ?? [],
			];
		}

		$response_data = [
			'logs'         => $formatted_logs,
			'current_page' => $page,
			'per_page'     => $per_page,
			'total'        => $total_logs,
			'total_pages'  => $total_pages,
		];

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Export entries to CSV or ZIP.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function export_entries( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Nonce verification failed.', 'sureforms' ) ],
				403
			);
		}

		$params = $request->get_params();

		$args = [
			'entry_ids' => isset( $params['entry_ids'] ) ? $this->sanitize_entry_ids( $params['entry_ids'] ) : [],
			'form_id'   => isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0,
			'status'    => isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'all',
			'search'    => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
			'date_from' => isset( $params['date_from'] ) ? sanitize_text_field( $params['date_from'] ) : '',
			'date_to'   => isset( $params['date_to'] ) ? sanitize_text_field( $params['date_to'] ) : '',
		];

		/**
		 * Export result with success status and either error message or file details.
		 *
		 * @var array{success: false, error: string} | array{success: true, filename: string, filepath: string, type: string} $result
		 */
		$result = Entries_Class::export_entries( $args );

		if ( ! $result['success'] ) {
			return new \WP_REST_Response(
				[ 'error' => $result['error'] ],
				400
			);
		}

		// Return file information for download.
		$filepath = Helper::get_string_value( $result['filepath'] );
		return new \WP_REST_Response(
			[
				'success'      => true,
				'filename'     => $result['filename'],
				'filepath'     => $result['filepath'],
				'type'         => $result['type'],
				'download_url' => add_query_arg(
					'_wpnonce',
					wp_create_nonce( 'srfm_download_export' ),
					admin_url( 'admin-ajax.php?action=srfm_download_export&file=' . rawurlencode( basename( $filepath ) ) )
				),
			],
			200
		);
	}
	/**
	 * Manage form lifecycle operations (trash, restore, delete).
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @since 2.0.0
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function manage_form_lifecycle( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Nonce verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$params   = $request->get_params();
		$form_ids = isset( $params['form_ids'] ) && is_array( $params['form_ids'] ) ?
			array_map( 'intval', $params['form_ids'] ) :
			[ intval( $params['form_ids'] ) ];
		$action   = isset( $params['action'] ) ? sanitize_text_field( Helper::get_string_value( $params['action'] ) ) : '';

		if ( empty( $form_ids ) || empty( $action ) ) {
			return new \WP_Error(
				'missing_parameters',
				__( 'Form IDs and action are required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$results = [];
		$errors  = [];

		foreach ( $form_ids as $form_id ) {
			$post = get_post( $form_id );

			// Validate that the post exists and is a sureforms_form.
			if ( ! $post || 'sureforms_form' !== $post->post_type ) {
				$errors[] = [
					'form_id' => $form_id,
					'error'   => __( 'Form not found or invalid post type.', 'sureforms' ),
				];
				continue;
			}

			$result = false;

			switch ( $action ) {
				case 'trash':
					if ( 'trash' === $post->post_status ) {
						$errors[] = [
							'form_id' => $form_id,
							'error'   => __( 'Form is already in trash.', 'sureforms' ),
						];
					} else {
						$result = wp_trash_post( $form_id );
					}
					break;

				case 'restore':
					if ( 'trash' !== $post->post_status ) {
						$errors[] = [
							'form_id' => $form_id,
							'error'   => __( 'Form is not in trash.', 'sureforms' ),
						];
					} else {
						$result = wp_untrash_post( $form_id );
					}
					break;

				case 'delete':
					// Force delete permanently.
					$result = wp_delete_post( $form_id, true );
					break;

				default:
					$errors[] = [
						'form_id' => $form_id,
						'error'   => __( 'Invalid action specified.', 'sureforms' ),
					];
					break;
			}

			if ( $result ) {
				$results[] = [
					'form_id' => $form_id,
					'action'  => $action,
					'success' => true,
				];
			} elseif ( ! isset( $errors[ array_search( $form_id, array_column( $errors, 'form_id' ), true ) ] ) ) {
				$errors[] = [
					'form_id' => $form_id,
					/* translators: %s: action name */
					'error'   => sprintf( __( 'Failed to %s form.', 'sureforms' ), $action ),
				];
			}
		}

		$response_data = [
			'success'       => ! empty( $results ),
			'action'        => $action,
			'processed_ids' => array_column( $results, 'form_id' ),
			'success_count' => count( $results ),
			'results'       => $results,
		];

		if ( ! empty( $errors ) ) {
			$response_data['errors']      = $errors;
			$response_data['error_count'] = count( $errors );
		}

		return new \WP_REST_Response( $response_data );
	}

	/**
	 * Recursively extract form fields from blocks.
	 *
	 * @param array<mixed>                $blocks The blocks array.
	 * @param array<string, array<mixed>> $sureforms_blocks Registered SureForms block attributes.
	 * @param array<string, array<mixed>> &$form_fields Reference to form fields array.
	 * @param array<mixed>                $entry_data The entry form data.
	 * @param bool                        $is_special_block Whether the current block is a special block (like address).
	 * @param int|null                    $base_counter Base counter for unique field naming.
	 * @since 2.0.0
	 * @return void
	 */
	public function extract_form_fields( $blocks, $sureforms_blocks, &$form_fields, $entry_data = [], $is_special_block = false, $base_counter = null ) {
		if ( null !== $base_counter ) {
			self::$dropdown_counter = $base_counter;
		}
		$block_type = '';

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) || ! isset( $block['blockName'] ) || ! is_string( $block['blockName'] ) ) {
				continue;
			}

			// Check if it's a SureForms block.
			if ( strpos( $block['blockName'], 'srfm/' ) === 0 ) {
				$block_type = str_replace( 'srfm/', '', $block['blockName'] );
				// Skip inline button or fields inside nested blocks except address.
				if ( 'inline-button' === $block_type || ( $is_special_block && 'address' !== $block_type ) ) {
					continue;
				}

				if ( isset( $sureforms_blocks[ $block_type ] ) && is_array( $sureforms_blocks[ $block_type ] ) ) {
					$block_attributes   = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
					$default_attributes = $sureforms_blocks[ $block_type ];

					// Merge block instance attributes with defaults.
					$merged_attributes = [];
					foreach ( $default_attributes as $attr_name => $attr_config ) {
						if ( ! is_string( $attr_name ) ) {
							continue;
						}
						$default_value = null;
						if ( is_array( $attr_config ) && isset( $attr_config['default'] ) ) {
							$default_value = $attr_config['default'];
						}
						$merged_attributes[ $attr_name ] = $block_attributes[ $attr_name ] ?? $default_value;
					}

					// Generate field name.
					$label           = is_string( $merged_attributes['label'] ?? '' ) ? $merged_attributes['label'] : '';
					$slug            = is_string( $merged_attributes['slug'] ?? '' ) ? $merged_attributes['slug'] : '';
					$block_id        = is_string( $merged_attributes['block_id'] ?? '' ) ? $merged_attributes['block_id'] : '';
					$field_name      = '';
					$base_field_name = '';

					if ( ! empty( $label ) && ! empty( $slug ) && ! empty( $block_id ) ) {
						$input_label     = '-lbl-' . Helper::encrypt( $label );
						$base_field_name = $input_label . '-' . $slug;

						// Handle special case for dropdown with instance counter.
						if ( 'dropdown' === $block_type ) {
							self::$dropdown_counter++;
							$unique_slug = $block_type . '-' . self::$dropdown_counter;
							$field_name  = 'srfm-' . $unique_slug . '-' . $block_id . $base_field_name;
						} elseif ( 'multi-choice' === $block_type ) {
							// Multi-choice uses standard pattern.
							$field_name = 'srfm-input-' . $block_type . '-' . $block_id . $base_field_name;
						} else {
							// Standard field name for other blocks.
							$field_name = 'srfm-' . $block_type . '-' . $block_id . $base_field_name;
						}
					}

					// Allow pro plugin to modify field_name.
					$field_name = apply_filters( 'srfm_extract_form_fields_field_name', $field_name, $base_field_name, $block_type, $block_id );

					// Get the value from entry data or use default.
					$field_value = $entry_data[ $field_name ] ?? ( $merged_attributes['defaultValue'] ?? '' );

					// Special handling for address blocks - extract inner fields.
					if ( 'address' === $block_type && isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
						$inner_fields = [];
						$this->extract_form_fields( $block['innerBlocks'], $sureforms_blocks, $inner_fields, $entry_data, false );
						$field_value = $inner_fields;
					}

					// Allow plugins to handle special blocks.
					$field_value = apply_filters( 'srfm_handle_special_block', $field_value, $block_type, $block, $sureforms_blocks, $this );

					$form_fields[] = [
						'field_name' => $field_name,
						'block_name' => 'multi-choice' === $block_type ? 'srfm-multi' : Helper::get_block_name_from_field( $field_name ),
						'value'      => $field_value,
						'attributes' => $merged_attributes,
					];
				}
			}

			// Recursively process inner blocks but skip for address blocks.
			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) && 'address' !== $block_type ) {
				// Pass true if current block has inner blocks and it doesn't need to be duplicated in the main fields array.
				$inner_is_special_block = apply_filters( 'srfm_is_special_block', false, $block_type );
				$this->extract_form_fields( $block['innerBlocks'], $sureforms_blocks, $form_fields, $entry_data, $inner_is_special_block );
			}
		}
	}

	/**
	 * Get current dropdown counter value.
	 *
	 * @since 2.0.0
	 * @return int Current dropdown counter value.
	 */
	public function get_dropdown_counter() {
		return self::$dropdown_counter;
	}

	/**
	 * Parse form content and return structured field data with attributes.
	 *
	 * @param string       $form_content The form post content.
	 * @param array<mixed> $entry_data The entry form data.
	 * @since 2.0.0
	 * @return array<string, array<mixed>>
	 */
	private function parse_form_fields( $form_content, $entry_data = [] ) {
		if ( empty( $form_content ) ) {
			return [];
		}

		// Parse blocks from form content.
		$blocks = parse_blocks( $form_content );
		if ( empty( $blocks ) ) {
			return [];
		}

		// Get registered SureForms block attributes.
		$registry          = \WP_Block_Type_Registry::get_instance();
		$registered_blocks = $registry->get_all_registered();

		$sureforms_blocks = [];
		foreach ( $registered_blocks as $block_name => $block_type ) {
			if ( strpos( $block_name, 'srfm/' ) === 0 && is_array( $block_type->attributes ) ) {
				$block_key                      = str_replace( 'srfm/', '', $block_name );
				$sureforms_blocks[ $block_key ] = $block_type->attributes;
			}
		}

		$form_fields = [];
		$this->extract_form_fields( $blocks, $sureforms_blocks, $form_fields, $entry_data, false );

		return $form_fields;
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
				'generate-form'             => [
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
				'map-fields'                => [
					'methods'             => 'POST',
					'callback'            => [ Field_Mapping::get_instance(), 'generate_gutenberg_fields_from_questions' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is used to initiate auth process when user tries to authenticate on billing portal.
				'initiate-auth'             => [
					'methods'             => 'GET',
					'callback'            => [ AI_Auth::get_instance(), 'get_auth_url' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to used to decrypt the access key and save it in the database.
				'handle-access-key'         => [
					'methods'             => 'POST',
					'callback'            => [ AI_Auth::get_instance(), 'handle_access_key' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to get the form submissions for the last 30 days.
				'entries-chart-data'        => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entries_chart_data' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// This route is to get all forms data.
				'form-data'                 => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_form_data' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// Onboarding endpoints.
				'onboarding/set-status'     => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'set_onboarding_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				'onboarding/get-status'     => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_onboarding_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				],
				// Plugin status endpoint.
				'plugin-status'             => [
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
				// Entries endpoints.
				'entries/list'              => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entries_list' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'form_id'   => [
							'sanitize_callback' => 'absint',
							'default'           => 0,
						],
						'status'    => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'all',
						],
						'search'    => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
						'date_from' => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
						'date_to'   => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
						'orderby'   => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'created_at',
						],
						'order'     => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'DESC',
						],
						'per_page'  => [
							'sanitize_callback' => 'absint',
							'default'           => 20,
						],
						'page'      => [
							'sanitize_callback' => 'absint',
							'default'           => 1,
						],
					],
				],
				'entries/read-status'       => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_entries_read_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'entry_ids' => [
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_entry_ids' ],
						],
						'action'    => [
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => [ $this, 'validate_read_action' ],
						],
					],
				],
				'entries/trash'             => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_entries_trash_status' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'entry_ids' => [
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_entry_ids' ],
						],
						'action'    => [
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => [ $this, 'validate_trash_action' ],
						],
					],
				],
				'entries/delete'            => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'delete_entries' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'entry_ids' => [
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_entry_ids' ],
						],
					],
				],
				'entries/export'            => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'export_entries' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'entry_ids' => [
							'sanitize_callback' => [ $this, 'sanitize_entry_ids' ],
							'default'           => [],
						],
						'form_id'   => [
							'sanitize_callback' => 'absint',
							'default'           => 0,
						],
						'status'    => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'all',
						],
						'search'    => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
						'date_from' => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
						'date_to'   => [
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						],
					],
				],
				// Get Single Entry Form Data.
				'entry/(?P<id>\d+)/details' => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entry_details' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
				// Get Single Entry Logs.
				'entry/(?P<id>\d+)/logs'    => [
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_entry_logs' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'id'       => [
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'sanitize_callback' => 'absint',
							'default'           => 3,
						],
						'page'     => [
							'sanitize_callback' => 'absint',
							'default'           => 1,
						],
					],
				],
				// Forms listing endpoint.
				'forms'                     => [
					'methods'             => 'GET',
					'callback'            => [ Forms_Data::get_instance(), 'get_forms_list' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'page'      => [
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						],
						'per_page'  => [
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 100,
						],
						'search'    => [
							'type' => 'string',
						],
						'status'    => [
							'type'    => 'string',
							'enum'    => [ 'publish', 'draft', 'trash', 'any' ],
							'default' => 'publish',
						],
						'orderby'   => [
							'type'    => 'string',
							'default' => 'date',
							'enum'    => [ 'date', 'id', 'title', 'modified' ],
						],
						'order'     => [
							'type'    => 'string',
							'default' => 'desc',
							'enum'    => [ 'asc', 'desc' ],
						],
						'date_from' => [
							'type'              => 'string',
							'format'            => 'date',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => static function( $value ) {
								if ( empty( $value ) ) {
									return true;
								}
								return (bool) strtotime( $value );
							},
						],
						'date_to'   => [
							'type'              => 'string',
							'format'            => 'date',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => static function( $value ) {
								if ( empty( $value ) ) {
									return true;
								}
								return (bool) strtotime( $value );
							},
						],
					],
				],
				// Export forms endpoint.
				'forms/export'              => [
					'methods'             => 'POST',
					'callback'            => [ Export::get_instance(), 'handle_export_form_rest' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'post_ids' => [
							'required'          => true,
							'type'              => [ 'array', 'string' ],
							'sanitize_callback' => static function( $value ) {
								if ( is_array( $value ) ) {
									return array_map( 'intval', $value );
								}
								return sanitize_text_field( $value );
							},
							'validate_callback' => static function( $value ) {
								if ( is_array( $value ) ) {
									return ! empty( $value );
								}
								return ! empty( trim( $value ) );
							},
						],
					],
				],
				// Import forms endpoint.
				'forms/import'              => [
					'methods'             => 'POST',
					'callback'            => [ Export::get_instance(), 'handle_import_form_rest' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'forms_data'     => [
							'required'          => true,
							'type'              => 'array',
							'validate_callback' => static function( $value ) {
								return is_array( $value ) && ! empty( $value );
							},
						],
						'default_status' => [
							'required'          => false,
							'type'              => 'string',
							'default'           => 'draft',
							'enum'              => [ 'draft', 'publish', 'private' ],
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
				// Form lifecycle management endpoint (trash/restore/delete).
				'forms/manage'              => [
					'methods'             => 'POST',
					'callback'            => [ $this, 'manage_form_lifecycle' ],
					'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
					'args'                => [
						'form_ids' => [
							'required'          => true,
							'type'              => [ 'array', 'integer' ],
							'sanitize_callback' => static function( $value ) {
								if ( is_array( $value ) ) {
									return array_map( 'intval', $value );
								}
								return [ intval( $value ) ];
							},
							'validate_callback' => static function( $value ) {
								if ( is_array( $value ) ) {
									return ! empty( $value );
								}
								return $value > 0;
							},
						],
						'action'   => [
							'required'          => true,
							'type'              => 'string',
							'enum'              => [ 'trash', 'restore', 'delete' ],
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}
}
