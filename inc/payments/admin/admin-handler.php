<?php
/**
 * Admin Payment Operations Handler Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc\Payments\Admin;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Helper;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Payment Operations handler class.
 *
 * @since 2.0.0
 */
class Admin_Handler {
	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_srfm_fetch_payments_transactions', [ $this, 'fetch_payments' ] );
		add_action( 'wp_ajax_srfm_fetch_single_payment', [ $this, 'fetch_single_payment' ] );
		add_action( 'wp_ajax_srfm_fetch_subscription', [ $this, 'fetch_subscription' ] );
		add_action( 'wp_ajax_srfm_fetch_forms_list', [ $this, 'fetch_forms_list' ] );
		add_action( 'wp_ajax_srfm_add_payment_note', [ $this, 'ajax_add_note' ] );
		add_action( 'wp_ajax_srfm_delete_payment_note', [ $this, 'ajax_delete_note' ] );
		add_action( 'wp_ajax_srfm_delete_payment_log', [ $this, 'ajax_delete_log' ] );
		add_action( 'wp_ajax_srfm_bulk_delete_payments', [ $this, 'ajax_bulk_delete_payments' ] );
	}

	/**
	 * Enqueue Admin Scripts for Payment Operations.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();

		/**
		 * List of the handles in which we need to add translation compatibility.
		 */
		$script_translations_handlers = [];

		// Check if we're on payment related pages.
		if ( isset( $current_screen->id ) &&
			( strpos( $current_screen->id, 'sureforms_payments' ) !== false || strpos( $current_screen->id, 'sureforms_payments_react' ) !== false ) ) {
			// Enqueue payment specific scripts.
			wp_enqueue_script( SRFM_SLUG . '-payments', SRFM_URL . 'assets/build/payments.js', [], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-payments', SRFM_URL . 'assets/build/payments.css', [], SRFM_VER );

			// Localize script with payment admin data.
			wp_localize_script(
				SRFM_SLUG . '-payments',
				SRFM_SLUG . '_payment_admin',
				[
					'ajax_url'                 => admin_url( 'admin-ajax.php' ),
					'srfm_payment_admin_nonce' => wp_create_nonce( 'srfm_payment_admin_nonce' ),
					'zeroDecimalCurrencies'    => Payment_Helper::get_zero_decimal_currencies(),
					'currenciesData'           => Payment_Helper::get_all_currencies_data(),
				]
			);

			$script_translations_handlers[] = SRFM_SLUG . '-payments';
		}

		// Register script translations if needed.
		if ( ! empty( $script_translations_handlers ) ) {
			foreach ( $script_translations_handlers as $script_handle ) {
				Helper::register_script_translations( $script_handle );
			}
		}
	}

	/**
	 * AJAX handler for fetching payments data.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function fetch_payments() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		try {
			// Sanitize input parameters.
			$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
			$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$form_id      = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
			$payment_mode = isset( $_POST['payment_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_mode'] ) ) : '';
			$date_from    = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
			$date_to      = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
			$page         = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$per_page     = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;

			// Validate pagination parameters.
			$page     = max( 1, $page );
			$per_page = max( 1, min( 100, $per_page ) ); // Limit to 100 records per page.
			$offset   = ( $page - 1 ) * $per_page;

			// Validate date format if provided.
			if ( ! empty( $date_from ) && ! $this->validate_date( $date_from ) ) {
				wp_send_json_error( [ 'message' => __( 'Invalid date format for date_from.', 'sureforms' ) ] );
			}

			if ( ! empty( $date_to ) && ! $this->validate_date( $date_to ) ) {
				wp_send_json_error( [ 'message' => __( 'Invalid date format for date_to.', 'sureforms' ) ] );
			}

			// Get total count for pagination.
			$total_count = $this->get_payments_count( $search, $status, $date_from, $date_to, $form_id, $payment_mode );

			if ( 0 === $total_count && empty( $search ) && empty( $status ) && empty( $date_from ) && empty( $date_to ) && empty( $form_id ) && empty( $payment_mode ) ) {
				wp_send_json_success(
					[
						'payments'              => [],
						'total'                 => 0,
						'transactions_is_empty' => 'with_no_filter',
					]
				);
			}

			if ( 0 === $total_count && ( ! empty( $search ) || ! empty( $status ) || ! empty( $date_from ) || ! empty( $date_to ) || ! empty( $form_id ) || ! empty( $payment_mode ) ) ) {
				wp_send_json_success(
					[
						'payments'              => [],
						'total'                 => 0,
						'transactions_is_empty' => 'with_filter',
					]
				);
			}

			// Get payments data from database.
			$payments = $this->get_payments_data( $search, $status, $date_from, $date_to, $per_page, $offset, $form_id, $payment_mode );

			wp_send_json_success(
				[
					'payments'              => $payments,
					'total'                 => $total_count,
					'page'                  => $page,
					'per_page'              => $per_page,
					'total_pages'           => ceil( $total_count / $per_page ),
					'transactions_is_empty' => false,
				]
			);

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching payments.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for fetching single payment data.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function fetch_single_payment() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Validate payment ID.
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		if ( empty( $payment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Payment ID is required.', 'sureforms' ) ] );
		}

		try {
			// Get single payment from database.
			$payment = Payments::get( $payment_id );

			if ( ! $payment ) {
				wp_send_json_error( [ 'message' => __( 'Payment not found.', 'sureforms' ) ] );
			}

			// Transform payment data for frontend.
			$payment_data = $this->transform_payment_for_frontend( $payment );

			wp_send_json_success( $payment_data );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching payment details.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for fetching subscription data with billing history.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function fetch_subscription() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Validate subscription ID - could be main subscription record ID or subscription_id.
		$subscription_id = isset( $_POST['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) ) : '';
		if ( empty( $subscription_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Subscription ID is required.', 'sureforms' ) ] );
		}

		try {
			// Check if the ID is a payment record ID first.
			if ( is_numeric( $subscription_id ) ) {
				$payment_record = Payments::get( absint( $subscription_id ) );
				if ( $payment_record && 'subscription' === ( $payment_record['type'] ?? '' ) ) {
					// This is a main subscription record ID, get the Stripe subscription ID.
					$stripe_subscription_id = $payment_record['subscription_id'] ?? '';
					if ( empty( $stripe_subscription_id ) ) {
						wp_send_json_error( [ 'message' => __( 'Stripe subscription ID not found in payment record.', 'sureforms' ) ] );
					}
					$main_subscription = $payment_record;
				} else {
					wp_send_json_error( [ 'message' => __( 'Invalid subscription record.', 'sureforms' ) ] );
				}
			} else {
				// This should be a Stripe subscription ID, get the main subscription record.
				$main_subscription = Payments::get_main_subscription_record( $subscription_id );
				if ( ! $main_subscription ) {
					wp_send_json_error( [ 'message' => __( 'Subscription not found.', 'sureforms' ) ] );
				}
				$stripe_subscription_id = $subscription_id;
			}

			// Get all related billing transactions for this subscription.
			$billing_payments = Payments::get_subscription_related_payments( $stripe_subscription_id );

			// Transform main subscription data for frontend.
			$subscription_data = $this->transform_payment_for_frontend( $main_subscription );

			// Transform billing payments for frontend.
			$billing_data = [];
			foreach ( $billing_payments as $payment ) {
				$billing_data[] = $this->transform_payment_for_frontend( $payment );
			}

			// Add subscription-specific fields.
			$subscription_data['stripe_subscription_id'] = $stripe_subscription_id;
			$subscription_data['interval']               = $this->get_subscription_interval( $main_subscription );
			$subscription_data['next_payment_date']      = $this->get_next_payment_date( $main_subscription );
			$subscription_data['amount_per_cycle']       = $subscription_data['total_amount']; // Use total_amount as cycle amount.

			// Combine data.
			$response_data = [
				'subscription' => $subscription_data,
				'payments'     => $billing_data,
			];

			wp_send_json_success( $response_data );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching subscription details.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for fetching forms list.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function fetch_forms_list() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		try {
			// Get all published forms.
			$all_forms = \SRFM\Inc\Helper::get_sureforms();

			// Get form IDs that have payments.
			$forms_with_payments = \SRFM\Inc\Database\Tables\Payments::get_all_forms_with_payments();

			// Filter forms to only include those with payments.
			$forms_list = [];
			foreach ( $all_forms as $form_id => $form_title ) {
				// Only include forms that have payment records.
				if ( in_array( (int) $form_id, $forms_with_payments, true ) ) {
					$forms_list[] = [
						'id'    => $form_id,
						/* translators: %d: Form ID */
						'title' => ! empty( $form_title ) ? $form_title : sprintf( __( 'Form - #%d', 'sureforms' ), $form_id ),
					];
				}
			}

			wp_send_json_success( [ 'forms' => $forms_list ] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching forms list.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for adding a note to payment.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_add_note() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Validate and sanitize inputs.
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		$note_text  = isset( $_POST['note_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_text'] ) ) : '';

		if ( empty( $payment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Payment ID is required.', 'sureforms' ) ] );
		}

		if ( empty( trim( $note_text ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Note text cannot be empty.', 'sureforms' ) ] );
		}

		try {
			// Add the note.
			$updated_notes = $this->add_payment_note( $payment_id, $note_text );

			if ( false === $updated_notes ) {
				wp_send_json_error( [ 'message' => __( 'Failed to add note.', 'sureforms' ) ] );
			}

			wp_send_json_success( [ 'notes' => $this->get_formatted_notes( $updated_notes ) ] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while adding the note.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for deleting a note from payment.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_delete_note() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Validate and sanitize inputs.
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		$note_index = isset( $_POST['note_index'] ) ? absint( $_POST['note_index'] ) : -1;

		if ( empty( $payment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Payment ID is required.', 'sureforms' ) ] );
		}

		if ( $note_index < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid note index.', 'sureforms' ) ] );
		}

		try {
			// Delete the note.
			$updated_notes = $this->delete_payment_note( $payment_id, $note_index );

			if ( false === $updated_notes ) {
				wp_send_json_error( [ 'message' => __( 'Failed to delete note.', 'sureforms' ) ] );
			}

			wp_send_json_success( [ 'notes' => $this->get_formatted_notes( $updated_notes ) ] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while deleting the note.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for deleting a log entry from payment.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_delete_log() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Validate and sanitize inputs.
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		$log_index  = isset( $_POST['log_index'] ) ? absint( $_POST['log_index'] ) : -1;

		if ( empty( $payment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Payment ID is required.', 'sureforms' ) ] );
		}

		if ( $log_index < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid log index.', 'sureforms' ) ] );
		}

		try {
			// Delete the log entry.
			$updated_logs = $this->delete_payment_log( $payment_id, $log_index );

			if ( false === $updated_logs ) {
				wp_send_json_error( [ 'message' => __( 'Failed to delete log entry.', 'sureforms' ) ] );
			}

			wp_send_json_success( [ 'logs' => $updated_logs ] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while deleting the log entry.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for bulk deleting payments.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_bulk_delete_payments() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
		}

		// Get and validate payment IDs.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below after JSON decode.
		$payment_ids_raw = isset( $_POST['payment_ids'] ) ? wp_unslash( $_POST['payment_ids'] ) : [];

		// Handle JSON string or array format.
		if ( is_string( $payment_ids_raw ) ) {
			// Decode JSON string.
			$payment_ids = json_decode( $payment_ids_raw, true );

			// Check if JSON decode was successful.
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_send_json_error( [ 'message' => __( 'Invalid JSON format for payment IDs.', 'sureforms' ) ] );
			}
		} else {
			$payment_ids = $payment_ids_raw;
		}

		// Ensure it's an array.
		if ( ! is_array( $payment_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid payment IDs format.', 'sureforms' ) ] );
		}

		// Sanitize: Convert to integers and remove invalid values.
		// This handles both string numbers ("169") and actual integers.
		$payment_ids = array_map( 'absint', $payment_ids );
		$payment_ids = array_filter(
			$payment_ids,
			static function ( $id ) {
				return $id > 0;
			}
		);

		// Re-index array to ensure sequential keys.
		$payment_ids = array_values( $payment_ids );

		// Check if array is empty after sanitization.
		if ( empty( $payment_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No valid payment IDs provided.', 'sureforms' ) ] );
		}

		// Limit bulk operations to prevent timeout (max 100 at once).
		if ( count( $payment_ids ) > 100 ) {
			wp_send_json_error(
				[
					'message' => __( 'Cannot delete more than 100 payments at once. Please select fewer payments.', 'sureforms' ),
				]
			);
		}

		try {
			$deleted_count = 0;
			$failed_ids    = [];

			// Delete each payment with proper error handling.
			foreach ( $payment_ids as $payment_id ) {
				// Verify payment exists before attempting delete.
				$payment = Payments::get( $payment_id );

				if ( ! $payment ) {
					$failed_ids[] = $payment_id;
					continue;
				}

				// Attempt deletion.
				$result = Payments::delete( $payment_id );

				if ( $result ) {
					$deleted_count++;
				} else {
					$failed_ids[] = $payment_id;
				}
			}

			// Prepare response message.
			if ( count( $payment_ids ) === $deleted_count ) {
				// All deleted successfully.
				wp_send_json_success(
					[
						'message'       => sprintf(
							/* translators: %d: number of payments deleted */
							_n(
								'%d payment deleted successfully.',
								'%d payments deleted successfully.',
								$deleted_count,
								'sureforms'
							),
							$deleted_count
						),
						'deleted_count' => $deleted_count,
					]
				);
			} elseif ( $deleted_count > 0 ) {
				// Partial success.
				wp_send_json_success(
					[
						'message'       => sprintf(
							/* translators: 1: number deleted, 2: number failed */
							__( '%1$d payment(s) deleted successfully. %2$d failed.', 'sureforms' ),
							$deleted_count,
							count( $failed_ids )
						),
						'deleted_count' => $deleted_count,
						'failed_count'  => count( $failed_ids ),
						'partial'       => true,
					]
				);
			} else {
				// All failed.
				wp_send_json_error(
					[
						'message'      => __( 'Failed to delete payments. Please try again.', 'sureforms' ),
						'failed_count' => count( $failed_ids ),
					]
				);
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				[
					'message' => __( 'An error occurred while deleting payments.', 'sureforms' ),
				]
			);
		}
	}

	/**
	 * Get payments data based on filters.
	 *
	 * @param string $search    Search term.
	 * @param string $status    Payment status filter.
	 * @param string $date_from Start date filter.
	 * @param string $date_to   End date filter.
	 * @param int    $limit     Number of records to return.
	 * @param int    $offset    Number of records to skip.
	 * @param int    $form_id   Form ID filter.
	 * @param string $payment_mode Payment mode filter (test/live).
	 * @since 2.0.0
	 * @return array Filtered payments data.
	 */
	private function get_payments_data( $search = '', $status = '', $date_from = '', $date_to = '', $limit = 20, $offset = 0, $form_id = 0, $payment_mode = '' ) {
		// Build WHERE conditions for database query.
		$where_conditions = [];

		// Add search filter - search in form names, customer data, etc.
		if ( ! empty( $search ) ) {
			global $wpdb;
			$search_term        = '%' . $wpdb->esc_like( $search ) . '%';
			$where_conditions[] = [
				[
					'key'     => 'id',
					'compare' => 'IN',
					'value'   => $this->get_payment_ids_by_search( $search_term ),
				],
			];
		}

		// Add status filter - map frontend status to database status.
		if ( ! empty( $status ) ) {
			$db_status = $this->map_frontend_status_to_db( $status );
			if ( $db_status ) {
				$where_conditions[] = [
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => $db_status,
					],
				];
			}
		}

		// Add form_id filter.
		if ( ! empty( $form_id ) ) {
			$where_conditions[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		// Add payment mode filter (test/live).
		if ( ! empty( $payment_mode ) && in_array( $payment_mode, [ 'test', 'live' ], true ) ) {
			$where_conditions[] = [
				[
					'key'     => 'mode',
					'compare' => '=',
					'value'   => $payment_mode,
				],
			];
		}

		// Add date range filter.
		if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
			if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => $date_from . ' 00:00:00',
					],
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => $date_to . ' 23:59:59',
					],
				];
			} elseif ( ! empty( $date_from ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => $date_from . ' 00:00:00',
					],
				];
			} elseif ( ! empty( $date_to ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => $date_to . ' 23:59:59',
					],
				];
			}
		}

		// Get payments from database using the main payments method.
		// Sorted by created_at in descending order (newest first).
		$args = [
			'where'   => $where_conditions,
			'limit'   => $limit,
			'offset'  => $offset,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		];

		$db_payments = Payments::get_all_main_payments( $args, true );

		// Transform database records to frontend format.
		$formatted_payments = [];
		foreach ( $db_payments as $payment ) {
			$formatted_payments[] = $this->transform_payment_for_frontend( $payment );
		}

		return $formatted_payments;
	}

	/**
	 * Get payment IDs that match search criteria.
	 *
	 * @param string $search_term Search term with wildcards.
	 * @since 2.0.0
	 * @return array Array of payment IDs.
	 */
	private function get_payment_ids_by_search( $search_term ) {
		global $wpdb;

		// Get payments table name.
		$payments_table = Payments::get_instance()->get_tablename();

		if ( empty( $payments_table ) || ! is_string( $payments_table ) ) {
			return [ 0 ];
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for search, table name is validated and cannot be parameterized with prepare().
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT id FROM {$payments_table}
			WHERE id LIKE %s
			OR customer_name LIKE %s
			OR customer_email LIKE %s
			OR transaction_id LIKE %s
			OR srfm_txn_id LIKE %s",
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// If no results found, return array with 0 to prevent empty IN clause.
		return ! empty( $results ) ? array_map( 'absint', $results ) : [ 0 ];
	}

	/**
	 * Map frontend status to database status.
	 *
	 * @param string $frontend_status Status from frontend.
	 * @since 2.0.0
	 * @return string|false Database status or false if invalid.
	 */
	private function map_frontend_status_to_db( $frontend_status ) {
		$status_mapping = [
			'succeeded'          => 'succeeded',
			'partially_refunded' => 'partially_refunded',
			'pending'            => 'pending',
			'failed'             => 'failed',
			'refunded'           => 'refunded',
			'cancelled'          => 'canceled',
		];

		return $status_mapping[ $frontend_status ] ?? false;
	}

	/**
	 * Map database status to frontend status.
	 *
	 * @param string $db_status Status from database.
	 * @since 2.0.0
	 * @return string Frontend status.
	 */
	private function map_db_status_to_frontend( $db_status ) {
		$status_mapping = [
			'succeeded'               => 'paid',
			'pending'                 => 'pending',
			'failed'                  => 'failed',
			'refunded'                => 'refunded',
			'partially_refunded'      => 'refunded',
			'canceled'                => 'canceled',
			'requires_action'         => 'pending',
			'requires_payment_method' => 'pending',
			'processing'              => 'pending',
		];

		return $status_mapping[ $db_status ] ?? $db_status;
	}

	/**
	 * Transform database payment record to frontend format.
	 *
	 * @param array<mixed> $payment Database payment record.
	 * @since 2.0.0
	 * @return array Transformed payment data.
	 */
	private function transform_payment_for_frontend( $payment ) {
		static $form_titles = []; // Cache for form titles.

		// Get form title with caching using WordPress built-in function.
		$form_id = isset( $payment['form_id'] ) && ! empty( $payment['form_id'] ) && is_numeric( $payment['form_id'] ) ? intval( $payment['form_id'] ) : 0;
		if ( is_numeric( $form_id ) && ! isset( $form_titles[ $form_id ] ) ) {
			$form_title              = get_the_title( intval( $form_id ) );
			$form_titles[ $form_id ] = ! empty( $form_title ) ? html_entity_decode( $form_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : __( 'Unknown Form', 'sureforms' );
		}
		$form_title     = isset( $form_titles[ $form_id ] ) && ! empty( $form_titles[ $form_id ] ) ? $form_titles[ $form_id ] : __( 'Unknown Form', 'sureforms' );
		$form_permalink = isset( $form_titles[ $form_id ] ) && ! empty( $form_titles[ $form_id ] ) ? get_permalink( intval( $form_id ) ) : '';
		$form_url       = ! empty( $form_permalink ) && is_string( $form_permalink ) ? html_entity_decode( $form_permalink ) : '';

		// Get customer name - for now use customer_id, in real implementation.
		// You would get customer data from entries or payment_data.
		$customer_name  = ! empty( $payment['customer_name'] ) ? $payment['customer_name'] : __( 'N/A', 'sureforms' );
		$customer_email = ! empty( $payment['customer_email'] ) ? $payment['customer_email'] : __( 'N/A', 'sureforms' );
		$notes          = Payments::get_extra_value( $payment['id'], 'notes', [] );
		$notes          = ! empty( $notes ) && is_array( $notes ) ? $notes : [];

		// Determine payment type display label.
		if ( 'subscription' === $payment['type'] ) {
			$payment_type = __( 'Subscription', 'sureforms' );
		} elseif ( 'renewal' === $payment['type'] ) {
			$payment_type = __( 'Renewal', 'sureforms' );
		} else {
			$payment_type = __( 'One Time', 'sureforms' );
		}

		$payment_front_end_data = [
			// All original payment_data fields.
			'id'                     => $payment['id'],
			'form_id'                => $payment['form_id'],
			'block_id'               => $payment['block_id'] ?? '',
			'status'                 => $payment['status'],
			'total_amount'           => $payment['total_amount'],
			'refunded_amount'        => $payment['refunded_amount'] ?? '0.00000000',
			'currency'               => $payment['currency'],
			'entry_id'               => $payment['entry_id'] ?? '',
			'gateway'                => $payment['gateway'],
			'type'                   => $payment['type'],
			'mode'                   => $payment['mode'] ?? '',
			'transaction_id'         => $payment['transaction_id'] ?? '',
			'customer_id'            => $payment['customer_id'] ?? '',
			'subscription_id'        => $payment['subscription_id'] ?? '',
			'subscription_status'    => $payment['subscription_status'] ?? '',
			'parent_subscription_id' => $payment['parent_subscription_id'] ?? '0',
			'payment_data'           => $payment['payment_data'] ?? '{}',
			'extra'                  => $payment['extra'] ?? '[]',
			'log'                    => $payment['log'] ?? '[]',
			'created_at'             => $payment['created_at'],
			'updated_at'             => $payment['updated_at'],
			'srfm_txn_id'            => $payment['srfm_txn_id'],

			// Additional frontend fields.
			'form_title'             => $form_title,
			'form_url'               => $form_url,
			'form'                   => $form_title, // Keep for backward compatibility.
			'customer_name'          => $customer_name,
			'customer_email'         => $customer_email,
			'amount'                 => floatval( $payment['total_amount'] ),
			'frontend_status'        => $this->map_db_status_to_frontend( $payment['status'] ),
			'datetime'               => $payment['created_at'], // Keep for backward compatibility.
			'payment_type'           => $payment_type,
			'notes'                  => $this->get_formatted_notes( $notes ),
			'logs'                   => $this->get_formatted_logs( $payment['log'] ),
		];

		return apply_filters( 'srfm_payment_admin_data', $payment_front_end_data, $payment );
	}

	/**
	 * Get total count of payments with filters.
	 *
	 * @param string $search    Search term.
	 * @param string $status    Payment status filter.
	 * @param string $date_from Start date filter.
	 * @param string $date_to   End date filter.
	 * @param int    $form_id   Form ID filter.
	 * @param string $payment_mode Payment mode filter (test/live).
	 * @since 2.0.0
	 * @return int Total count.
	 */
	private function get_payments_count( $search = '', $status = '', $date_from = '', $date_to = '', $form_id = 0, $payment_mode = '' ) {
		// Build WHERE conditions similar to get_payments_data.
		$where_conditions = [];

		if ( ! empty( $search ) ) {
			global $wpdb;
			$search_term        = '%' . $wpdb->esc_like( $search ) . '%';
			$where_conditions[] = [
				[
					'key'     => 'id',
					'compare' => 'IN',
					'value'   => $this->get_payment_ids_by_search( $search_term ),
				],
			];
		}

		if ( ! empty( $status ) ) {
			$db_status = $this->map_frontend_status_to_db( $status );
			if ( $db_status ) {
				$where_conditions[] = [
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => $db_status,
					],
				];
			}
		}

		// Add form_id filter.
		if ( ! empty( $form_id ) ) {
			$where_conditions[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		// Add payment mode filter (test/live).
		if ( ! empty( $payment_mode ) && in_array( $payment_mode, [ 'test', 'live' ], true ) ) {
			$where_conditions[] = [
				[
					'key'     => 'mode',
					'compare' => '=',
					'value'   => $payment_mode,
				],
			];
		}

		if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
			if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => $date_from . ' 00:00:00',
					],
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => $date_to . ' 23:59:59',
					],
				];
			} elseif ( ! empty( $date_from ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => $date_from . ' 00:00:00',
					],
				];
			} elseif ( ! empty( $date_to ) ) {
				$where_conditions[] = [
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => $date_to . ' 23:59:59',
					],
				];
			}
		}

		return Payments::get_total_main_payments_by_status( 'all', 0, $where_conditions );
	}

	/**
	 * Get subscription billing interval from payment data.
	 *
	 * @param array<mixed> $subscription_record Main subscription payment record.
	 * @since 2.0.0
	 * @return string Billing interval.
	 */
	private function get_subscription_interval( $subscription_record ) {
		// Try to get interval from payment_data.
		if ( ! empty( $subscription_record['payment_data'] ) ) {
			$payment_data = \SRFM\Inc\Helper::get_array_value( $subscription_record['payment_data'] );

			// Check various possible locations for interval data.
			$interval_paths = [
				'subscription.items.data.0.price.recurring.interval',
				'subscription.plan.interval',
				'price.recurring.interval',
				'plan.interval',
				'interval',
			];

			foreach ( $interval_paths as $path ) {
				$interval = $this->get_nested_array_value( $payment_data, $path );
				if ( ! empty( $interval ) && is_string( $interval ) ) {
					return ucfirst( $interval ); // month -> Month, year -> Year.
				}
			}
		}

		return __( 'Unknown', 'sureforms' );
	}

	/**
	 * Get next payment date from subscription data.
	 *
	 * @param array<mixed> $subscription_record Main subscription payment record.
	 * @since 2.0.0
	 * @return string|null Next payment date or null.
	 */
	private function get_next_payment_date( $subscription_record ) {
		// Try to get next payment date from payment_data.
		if ( ! empty( $subscription_record['payment_data'] ) ) {
			$payment_data = \SRFM\Inc\Helper::get_array_value( $subscription_record['payment_data'] );

			// Check various possible locations for next payment date.
			$date_paths = [
				'subscription.current_period_end',
				'current_period_end',
				'next_payment_attempt',
			];

			foreach ( $date_paths as $path ) {
				$timestamp = $this->get_nested_array_value( $payment_data, $path );
				if ( ! empty( $timestamp ) && is_numeric( $timestamp ) ) {
					$timestamp_int = intval( $timestamp );
					return gmdate( 'Y-m-d H:i:s', $timestamp_int );
				}
			}
		}

		return null;
	}

	/**
	 * Get nested value from array using dot notation.
	 *
	 * @param array<mixed> $array Array to search.
	 * @param string       $path Dot-separated path.
	 * @since 2.0.0
	 * @return mixed Value or null if not found.
	 */
	private function get_nested_array_value( $array, $path ) {
		$keys    = explode( '.', $path );
		$current = $array;

		foreach ( $keys as $key ) {
			if ( ! is_array( $current ) || ! isset( $current[ $key ] ) ) {
				return null;
			}
			$current = $current[ $key ];
		}

		return $current;
	}

	/**
	 * Validate date format (YYYY-MM-DD).
	 *
	 * @param string $date Date string to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_date( $date ) {
		$parsed_date = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $parsed_date && $parsed_date->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Add a note to payment.
	 *
	 * @param int    $payment_id Payment ID.
	 * @param string $note_text  Note text to add.
	 * @since 2.0.0
	 * @return array|false Updated notes array or false on failure.
	 */
	private function add_payment_note( $payment_id, $note_text ) {
		if ( empty( $payment_id ) || empty( trim( $note_text ) ) ) {
			return false;
		}

		// Verify payment exists.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		// Get current notes from extra data.
		$notes = Payments::get_extra_value( $payment_id, 'notes', [] );

		// Ensure notes is an array.
		if ( ! is_array( $notes ) ) {
			$notes = [];
		}

		// Create new note with metadata.
		$new_note = [
			'text'       => trim( $note_text ),
			'created_at' => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
		];

		// Add new note to the beginning of the array (most recent first).
		array_unshift( $notes, $new_note );

		// Update extra data with new notes array.
		$result = Payments::update_extra_key( $payment_id, 'notes', $notes );

		if ( false === $result ) {
			return false;
		}

		return $notes;
	}

	/**
	 * Delete a note from payment by index.
	 *
	 * @param int $payment_id Payment ID.
	 * @param int $note_index Index of note to delete.
	 * @since 2.0.0
	 * @return array|false Updated notes array or false on failure.
	 */
	private function delete_payment_note( $payment_id, $note_index ) {
		if ( empty( $payment_id ) || $note_index < 0 ) {
			return false;
		}

		// Verify payment exists.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		// Get current notes.
		$notes = Payments::get_extra_value( $payment_id, 'notes', [] );

		// Ensure notes is an array.
		if ( ! is_array( $notes ) ) {
			return false;
		}

		// Check if note index exists.
		if ( ! isset( $notes[ $note_index ] ) ) {
			return false;
		}

		// Remove note at specified index.
		array_splice( $notes, $note_index, 1 );

		// Re-index array to prevent gaps.
		$notes = array_values( $notes );

		// Update extra data with modified notes array.
		$result = Payments::update_extra_key( $payment_id, 'notes', $notes );

		if ( false === $result ) {
			return false;
		}

		return $notes;
	}

	/**
	 * Delete a log entry from payment by index.
	 *
	 * @param int $payment_id Payment ID.
	 * @param int $log_index  Index of log entry to delete.
	 * @since 2.0.0
	 * @return array|false Updated formatted logs array or false on failure.
	 */
	private function delete_payment_log( $payment_id, $log_index ) {
		if ( empty( $payment_id ) || $log_index < 0 ) {
			return false;
		}

		// Verify payment exists.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		// Get current logs from log column.
		$logs_data = $payment['log'] ?? '[]';
		$logs      = json_decode( $logs_data, true ) ?? [];

		// Ensure logs is an array.
		if ( ! is_array( $logs ) ) {
			return false;
		}

		// Check if log index exists.
		if ( ! isset( $logs[ $log_index ] ) ) {
			return false;
		}

		// Remove log at specified index.
		array_splice( $logs, $log_index, 1 );

		// Re-index array to prevent gaps.
		$logs = array_values( $logs );

		// Update log column with modified logs array.
		$result = Payments::update(
			$payment_id,
			[
				'log' => wp_json_encode( $logs ),
			]
		);

		if ( false === $result ) {
			return false;
		}

		$encoded_logs = wp_json_encode( $logs );
		$encoded_logs = is_string( $encoded_logs ) ? $encoded_logs : '';

		// Return formatted logs for frontend.
		return $this->get_formatted_logs( $encoded_logs );
	}

	/**
	 * Get formatted logs from log data.
	 *
	 * @param string $log_data JSON encoded log data.
	 * @since 2.0.0
	 * @return array Formatted logs array.
	 */
	private function get_formatted_logs( $log_data ) {
		if ( empty( $log_data ) ) {
			return [];
		}

		// If already an array, use it directly.
		if ( is_array( $log_data ) ) {
			$logs = $log_data;
		} else {
			return [];
		}

		if ( ! is_array( $logs ) ) {
			return [];
		}

		$formatted_logs = [];

		foreach ( $logs as $log ) {
			// Handle both object and array formats.
			if ( is_object( $log ) ) {
				$formatted_logs[] = [
					'title'      => $log->title ?? '',
					'created_at' => $log->created_at ?? 0,
					'messages'   => $log->messages ?? [],
				];
			} elseif ( is_array( $log ) ) {
				$formatted_logs[] = [
					'title'      => $log['title'] ?? '',
					'created_at' => $log['created_at'] ?? 0,
					'messages'   => $log['messages'] ?? [],
				];
			}
		}

		return $formatted_logs;
	}

	/**
	 * Get formatted notes from notes data.
	 *
	 * @param array<mixed> $notes_data Notes array from extra data.
	 * @since 2.0.0
	 * @return array Formatted notes array.
	 */
	private function get_formatted_notes( $notes_data ) {
		if ( empty( $notes_data ) ) {
			return [];
		}

		// If not an array, return empty.
		if ( ! is_array( $notes_data ) ) {
			return [];
		}

		$formatted_notes = [];

		foreach ( $notes_data as $note ) {
			// Handle both object and array formats.
			if ( is_object( $note ) ) {
				$formatted_notes[] = [
					'text'                 => $note->text ?? '',
					'created_at'           => $note->created_at ?? '',
					'created_by'           => $note->created_by ?? 0,
					'created_by_user_name' => isset( $note->created_by ) && is_numeric( $note->created_by ) ? $this->get_user_detail_by_id( $note->created_by ) : __( 'Guest User', 'sureforms' ),
				];
			} elseif ( is_array( $note ) ) {
				$formatted_notes[] = [
					'text'                 => $note['text'] ?? '',
					'created_at'           => $note['created_at'] ?? '',
					'created_by'           => $note['created_by'] ?? 0,
					'created_by_user_name' => isset( $note['created_by'] ) && is_numeric( $note['created_by'] ) ? $this->get_user_detail_by_id( $note['created_by'] ) : __( 'Guest User', 'sureforms' ),
				];
			}
		}

		return $formatted_notes;
	}

	/**
	 * Get user detail by ID.
	 *
	 * @param mixed $user_id User ID.
	 * @since 2.0.0
	 * @return string User name or 'Guest User' if user not found.
	 */
	private function get_user_detail_by_id( $user_id ) {
		if ( empty( $user_id ) || ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return __( 'Guest User', 'sureforms' );
		}
		$user_id = is_numeric( $user_id ) ? intval( $user_id ) : 0;
		if ( $user_id <= 0 ) {
			return __( 'Guest User', 'sureforms' );
		}
		$user      = get_userdata( $user_id );
		$user_name = $user ? $user->user_login : '';
		return is_string( $user_name ) ? $user_name : __( 'Guest User', 'sureforms' );
	}
}
