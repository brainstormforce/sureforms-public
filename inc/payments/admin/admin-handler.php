<?php
/**
 * Admin Payment Operations Handler Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc\Payments\Admin;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Database\Tables\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Payment Operations handler class.
 *
 * @since 1.0.0
 */
class Admin_Handler {
	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_srfm_fetch_payments_transactions', [ $this, 'fetch_payments' ] );
		add_action( 'wp_ajax_srfm_fetch_single_payment', [ $this, 'fetch_single_payment' ] );
		add_action( 'wp_ajax_srfm_fetch_subscription', [ $this, 'fetch_subscription' ] );
	}

	/**
	 * Enqueue Admin Scripts for Payment Operations.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();

		/**
		 * List of the handles in which we need to add translation compatibility.
		 */
		$script_translations_handlers = [];

		// Check if we're on payment related pages
		if ( isset( $current_screen->id ) &&
			( strpos( $current_screen->id, 'sureforms_payments' ) !== false ||
			  strpos( $current_screen->id, 'sureforms_payments_react' ) !== false ) ) {
			// Enqueue payment specific scripts
			wp_enqueue_script( SRFM_SLUG . '-payments', SRFM_URL . 'assets/build/payments.js', [], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-payments', SRFM_URL . 'assets/build/payments.css', [], SRFM_VER );

			// Localize script with payment admin data
			wp_localize_script(
				SRFM_SLUG . '-payments',
				SRFM_SLUG . '_payment_admin',
				[
					'ajax_url'                 => admin_url( 'admin-ajax.php' ),
					'srfm_payment_admin_nonce' => wp_create_nonce( 'srfm_payment_admin_nonce' ),
					'payment_test'             => 'payment test',
				]
			);

			$script_translations_handlers[] = SRFM_SLUG . '-payments';
		}

		// Register script translations if needed
		if ( ! empty( $script_translations_handlers ) ) {
			foreach ( $script_translations_handlers as $script_handle ) {
				if ( function_exists( '\SRFM\Inc\Helper::register_script_translations' ) ) {
					\SRFM\Inc\Helper::register_script_translations( $script_handle );
				}
			}
		}
	}

	/**
	 * AJAX handler for fetching payments data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function fetch_payments() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
			return;
		}

		try {
			// Sanitize input parameters
			$search    = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
			$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
			$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
			$page      = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$per_page  = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;

			// Validate pagination parameters
			$page     = max( 1, $page );
			$per_page = max( 1, min( 100, $per_page ) ); // Limit to 100 records per page
			$offset   = ( $page - 1 ) * $per_page;

			// Validate date format if provided
			if ( ! empty( $date_from ) && ! $this->validate_date( $date_from ) ) {
				wp_send_json_error( [ 'message' => __( 'Invalid date format for date_from.', 'sureforms' ) ] );
				return;
			}

			if ( ! empty( $date_to ) && ! $this->validate_date( $date_to ) ) {
				wp_send_json_error( [ 'message' => __( 'Invalid date format for date_to.', 'sureforms' ) ] );
				return;
			}

			// Get payments data from database
			$payments = $this->get_payments_data( $search, $status, $date_from, $date_to, $per_page, $offset );

			// Get total count for pagination
			$total_count = $this->get_payments_count( $search, $status, $date_from, $date_to );

			wp_send_json_success(
				[
					'payments'    => $payments,
					'total'       => $total_count,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_count / $per_page ),
				]
			);

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching payments.', 'sureforms' ) ] );
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
	 * @return array Filtered payments data.
	 * @since 1.0.0
	 */
	private function get_payments_data( $search = '', $status = '', $date_from = '', $date_to = '', $limit = 20, $offset = 0 ) {
		// Build WHERE conditions for database query
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

		// Add status filter - map frontend status to database status
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

		// Add date range filter
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

		// Get payments from database using the main payments method
		$args = [
			'where'   => $where_conditions,
			'limit'   => $limit,
			'offset'  => $offset,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		];

		$db_payments = Payments::get_all_main_payments( $args, true );

		// Transform database records to frontend format
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
	 * @return array Array of payment IDs.
	 * @since 1.0.0
	 */
	private function get_payment_ids_by_search( $search_term ) {
		global $wpdb;

		// Get payments table name
		$payments_table = Payments::get_instance()->get_tablename();

		// Search in multiple fields - for now, search in transaction_id and gateway
		// In a real implementation, you might want to join with forms/entries tables
		// to search in form names and customer data
		$query = $wpdb->prepare(
			"SELECT DISTINCT id FROM {$payments_table} 
			WHERE transaction_id LIKE %s 
			OR customer_id LIKE %s 
			OR gateway LIKE %s
			OR id LIKE %s",
			$search_term,
			$search_term,
			$search_term,
			$search_term
		);

		$results = $wpdb->get_col( $query );

		// If no results found, return array with 0 to prevent empty IN clause
		return ! empty( $results ) ? array_map( 'absint', $results ) : [ 0 ];
	}

	/**
	 * Map frontend status to database status.
	 *
	 * @param string $frontend_status Status from frontend.
	 * @return string|false Database status or false if invalid.
	 * @since 1.0.0
	 */
	private function map_frontend_status_to_db( $frontend_status ) {
		$status_mapping = [
			'paid'      => 'succeeded',
			'pending'   => 'pending',
			'failed'    => 'failed',
			'refunded'  => 'refunded',
			'cancelled' => 'canceled',
		];

		return $status_mapping[ $frontend_status ] ?? false;
	}

	/**
	 * Map database status to frontend status.
	 *
	 * @param string $db_status Status from database.
	 * @return string Frontend status.
	 * @since 1.0.0
	 */
	private function map_db_status_to_frontend( $db_status ) {
		$status_mapping = [
			'succeeded'               => 'paid',
			'pending'                 => 'pending',
			'failed'                  => 'failed',
			'refunded'                => 'refunded',
			'partially_refunded'      => 'refunded',
			'canceled'                => 'cancelled',
			'requires_action'         => 'pending',
			'requires_payment_method' => 'pending',
			'processing'              => 'pending',
		];

		return $status_mapping[ $db_status ] ?? $db_status;
	}

	/**
	 * Transform database payment record to frontend format.
	 *
	 * @param array $payment Database payment record.
	 * @return array Transformed payment data.
	 * @since 1.0.0
	 */
	private function transform_payment_for_frontend( $payment ) {
		static $form_titles = []; // Cache for form titles

		// Get form title with caching using WordPress built-in function
		$form_id = $payment['form_id'];
		if ( ! isset( $form_titles[ $form_id ] ) ) {
			$form_titles[ $form_id ] = get_the_title( $form_id ) ?: __( 'Unknown Form', 'sureforms' );
		}
		$form_title = $form_titles[ $form_id ];

		// Get customer name - for now use customer_id, in real implementation
		// you would get customer data from entries or payment_data
		$customer_name = ! empty( $payment['customer_id'] ) ? 'Customer #' . $payment['customer_id'] : 'Guest';

		// Determine payment type
		$payment_type = 'subscription' === $payment['type'] ? __( 'Subscription', 'sureforms' ) : __( 'One-time', 'sureforms' );

		$payment_front_end_data = [
			// All original payment_data fields
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

			// Additional frontend fields
			'form_title'             => $form_title,
			'form'                   => $form_title, // Keep for backward compatibility
			'customer'               => $customer_name,
			'amount'                 => floatval( $payment['total_amount'] ),
			'frontend_status'        => $this->map_db_status_to_frontend( $payment['status'] ),
			'datetime'               => $payment['created_at'], // Keep for backward compatibility
			'payment_type'           => $payment_type,
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
	 * @return int Total count.
	 * @since 1.0.0
	 */
	private function get_payments_count( $search = '', $status = '', $date_from = '', $date_to = '' ) {
		// Build WHERE conditions similar to get_payments_data
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
	 * AJAX handler for fetching single payment data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function fetch_single_payment() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
			return;
		}

		// Validate payment ID
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		if ( empty( $payment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Payment ID is required.', 'sureforms' ) ] );
			return;
		}

		try {
			// Get single payment from database
			$payment = Payments::get( $payment_id );

			if ( ! $payment ) {
				wp_send_json_error( [ 'message' => __( 'Payment not found.', 'sureforms' ) ] );
				return;
			}

			// Transform payment data for frontend
			$payment_data = $this->transform_payment_for_frontend( $payment );

			wp_send_json_success( $payment_data );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'An error occurred while fetching payment details.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for fetching subscription data with billing history.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function fetch_subscription() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'sureforms' ) ] );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'sureforms' ) ] );
			return;
		}

		// Validate subscription ID - could be main subscription record ID or subscription_id
		$subscription_id = isset( $_POST['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) ) : '';
		if ( empty( $subscription_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Subscription ID is required.', 'sureforms' ) ] );
			return;
		}

		try {
			// Check if the ID is a payment record ID first
			if ( is_numeric( $subscription_id ) ) {
				$payment_record = Payments::get( absint( $subscription_id ) );
				if ( $payment_record && 'subscription' === ( $payment_record['type'] ?? '' ) ) {
					// This is a main subscription record ID, get the Stripe subscription ID
					$stripe_subscription_id = $payment_record['subscription_id'] ?? '';
					if ( empty( $stripe_subscription_id ) ) {
						wp_send_json_error( [ 'message' => __( 'Stripe subscription ID not found in payment record.', 'sureforms' ) ] );
						return;
					}
					$main_subscription = $payment_record;
				} else {
					wp_send_json_error( [ 'message' => __( 'Invalid subscription record.', 'sureforms' ) ] );
					return;
				}
			} else {
				// This should be a Stripe subscription ID, get the main subscription record
				$main_subscription = Payments::get_main_subscription_record( $subscription_id );
				if ( ! $main_subscription ) {
					wp_send_json_error( [ 'message' => __( 'Subscription not found.', 'sureforms' ) ] );
					return;
				}
				$stripe_subscription_id = $subscription_id;
			}

			// Get all related billing transactions for this subscription
			$billing_payments = Payments::get_subscription_related_payments( $stripe_subscription_id );

			// Transform main subscription data for frontend
			$subscription_data = $this->transform_payment_for_frontend( $main_subscription );

			// Transform billing payments for frontend
			$billing_data = [];
			foreach ( $billing_payments as $payment ) {
				$billing_data[] = $this->transform_payment_for_frontend( $payment );
			}

			// Add subscription-specific fields
			$subscription_data['stripe_subscription_id'] = $stripe_subscription_id;
			$subscription_data['interval']               = $this->get_subscription_interval( $main_subscription );
			$subscription_data['next_payment_date']      = $this->get_next_payment_date( $main_subscription );
			$subscription_data['amount_per_cycle']       = $subscription_data['total_amount']; // Use total_amount as cycle amount

			// Combine data
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
	 * Get subscription billing interval from payment data.
	 *
	 * @param array $subscription_record Main subscription payment record.
	 * @return string Billing interval.
	 * @since 1.0.0
	 */
	private function get_subscription_interval( $subscription_record ) {
		// Try to get interval from payment_data
		if ( ! empty( $subscription_record['payment_data'] ) ) {
			$payment_data = \SRFM\Inc\Helper::get_array_value( $subscription_record['payment_data'] );

			// Check various possible locations for interval data
			$interval_paths = [
				'subscription.items.data.0.price.recurring.interval',
				'subscription.plan.interval',
				'price.recurring.interval',
				'plan.interval',
				'interval',
			];

			foreach ( $interval_paths as $path ) {
				$interval = $this->get_nested_array_value( $payment_data, $path );
				if ( ! empty( $interval ) ) {
					return ucfirst( $interval ); // month -> Month, year -> Year
				}
			}
		}

		return __( 'Unknown', 'sureforms' );
	}

	/**
	 * Get next payment date from subscription data.
	 *
	 * @param array $subscription_record Main subscription payment record.
	 * @return string|null Next payment date or null.
	 * @since 1.0.0
	 */
	private function get_next_payment_date( $subscription_record ) {
		// Try to get next payment date from payment_data
		if ( ! empty( $subscription_record['payment_data'] ) ) {
			$payment_data = \SRFM\Inc\Helper::get_array_value( $subscription_record['payment_data'] );

			// Check various possible locations for next payment date
			$date_paths = [
				'subscription.current_period_end',
				'current_period_end',
				'next_payment_attempt',
			];

			foreach ( $date_paths as $path ) {
				$timestamp = $this->get_nested_array_value( $payment_data, $path );
				if ( ! empty( $timestamp ) && is_numeric( $timestamp ) ) {
					return gmdate( 'Y-m-d H:i:s', $timestamp );
				}
			}
		}

		return null;
	}

	/**
	 * Get nested value from array using dot notation.
	 *
	 * @param array  $array Array to search.
	 * @param string $path Dot-separated path.
	 * @return mixed Value or null if not found.
	 * @since 1.0.0
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
	 * @return bool True if valid, false otherwise.
	 * @since 1.0.0
	 */
	private function validate_date( $date ) {
		$parsed_date = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $parsed_date && $parsed_date->format( 'Y-m-d' ) === $date;
	}
}
