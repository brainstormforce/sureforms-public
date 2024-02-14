<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Post_Type;
use SureForms\Inc\Traits\Get_Instance;
use WP_Query;

/**
 * Create New Form.
 *
 * @since 0.0.1
 */
class Email_Summaries {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'srfm_weekly_scheduled_events', array( $this, 'send_entries_to_admin' ) );
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
		// add_action( 'init', [ $this, 'schedule_weekly_entries_email' ] );
	}

	/**
	 * Add custom API Route create-new-form.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/save-email-summaries-options',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_email_summary_options' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			)
		);
		register_rest_route(
			'sureforms/v1',
			'/get-email-summary-options',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_email_summary_options' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			)
		);
	}

	/**
	 * Checks whether a given request has permission to create new forms.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
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

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to create forms.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Get email summary options.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 0.0.1
	 */
	public function get_email_summary_options() {
		$email_summary_options = get_option( 'srfm_email_summary_options' );

		// Check if data exists
		if ( $email_summary_options === false ) {
			// Return a 404 response if data doesn't exist
			return new WP_Error( 'no_data', 'Email summary options not found', array( 'status' => 404 ) );
		}

		// Return the email summary data
		return new WP_REST_Response( $email_summary_options, 200 );
	}

	/**
	 * Create new form from selected templates
	 *
	 * @param \WP_REST_Request $data Form Markup Data.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 0.0.1
	 */
	public function save_email_summary_options( $data ) {
		// self::send_entries_to_admin();
		// Retrieve the request body.
		$form_info = $data->get_body();

		// Convert JSON data to PHP object.
		$form_info_obj = json_decode( $form_info );

		// Check if the JSON data is decoded successfully.
		if ( ! is_object( $form_info_obj ) ) {
			return new WP_Error( 'invalid_json', 'Invalid JSON data', array( 'status' => 400 ) );
		}

		// Extract data from the decoded JSON object.
		$enable_email_summary = isset( $form_info_obj->enable_email_summary ) ? $form_info_obj->enable_email_summary : false;
		$emails_send_to       = isset( $form_info_obj->emails_send_to ) ? $form_info_obj->emails_send_to : array();
		$schedule_reports     = isset( $form_info_obj->schedule_reports ) ? $form_info_obj->schedule_reports : '';

		// Save data in a single option using the Options API.
		$prev_email_summary_options = get_option( 'srfm_email_summary_options' );

		 // Check if the previously saved schedule day matches the currently selected day
		 $prev_schedule_day = isset( $prev_email_summary_options['schedule_reports'] ) ? $prev_email_summary_options['schedule_reports'] : 'Monday';

		// Save data in a single option using the Options API.
		update_option(
			'srfm_email_summary_options',
			array(
				'enable_email_summary' => $enable_email_summary,
				'emails_send_to'       => $emails_send_to,
				'schedule_reports'     => $schedule_reports,
			)
		);

		// time in UTC and filter to change the time.
		$time = apply_filters( 'srfm_weekly_scheduled_events_time', '09:00:00' );

		if ( $enable_email_summary || strtolower( $prev_schedule_day ) !== strtolower( $schedule_reports ) || $time !== '09:00:00' ) {
			as_unschedule_all_actions( 'srfm_weekly_scheduled_events' );
			self::schedule_weekly_entries_email( $time );
		}
		
		if ( ! $enable_email_summary ){
			// If email summary is not enabled, unschedule all actions.
			as_unschedule_all_actions( 'srfm_weekly_scheduled_events' );
		}

		// Return success response.
		return new WP_REST_Response( 'Email summary options saved successfully', 200 );
	}

	/**
	 * Function to get the total number of entries for the last week.
	 *
	 * @since 0.0.1
	 * @return int
	 */
	public function get_total_entries_for_week() {
		$one_week_ago = strtotime( '-1 week' );
		$today        = current_time( 'timestamp' );

		$args = array(
			'post_type'      => 'sureforms_entry', // Replace with your custom post type
			'date_query'     => array(
				array(
					'after'     => date( 'Y-m-d H:i:s', $one_week_ago ),
					'before'    => date( 'Y-m-d H:i:s', $today ),
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		);

		$query = new WP_Query( $args );

		$entries_count = $query->post_count;

		return $entries_count;
	}


	/**
	 * Function to send the entries to admin mail.
	 *
	 * @since 0.0.1
	 * @return \error|void
	 */
	public function send_entries_to_admin() {
		$entries_count         = self::get_total_entries_for_week();
		$email_summary_options = get_option( 'srfm_email_summary_options' );

		// Check if email summary options exist and if email summary is enabled
		if ( is_wp_error( $email_summary_options ) || ! isset( $email_summary_options['enable_email_summary'] ) || ! $email_summary_options['enable_email_summary'] ) {
			// Log an error or handle the absence of options or if email summary is not enabled
			wp_send_json_error( 'Email summary options not found or email summary is not enabled' );
			return;
		}

		// Get the recipients' email addresses
		$recipients_string = isset( $email_summary_options['emails_send_to'] ) ? $email_summary_options['emails_send_to'] : array();

		$recipients = explode( ',', $recipients_string );

		// If no recipients are specified, return
		if ( empty( $recipients ) ) {
			wp_send_json_error( 'No recipients specified for email summary' );
			return;
		}

		$site_title = get_bloginfo( 'name' );

		$subject = 'SureForms Email Summary - ' . $site_title;
		$message = 'The total number of entries for the last week are <b>' . $entries_count . '</b>';
		$headers = 'From: ' . get_option( 'admin_email' );

		wp_mail( $recipients, $subject, $message, $headers );
	}


	/**
	 * Schedule the action to run today at 9:00 AM.
	 *
	 * @return \error|void
	 * @since 0.0.1
	 */
	public function schedule_weekly_entries_email( $time ) {
		$email_summary_options = get_option( 'srfm_email_summary_options' );

		// Check if email summary options exist and if email summary is enabled
		if ( is_wp_error( $email_summary_options ) || ! isset( $email_summary_options['enable_email_summary'] ) || ! $email_summary_options['enable_email_summary'] ) {
			// Log an error or handle the absence of options or if email summary is not enabled
			wp_send_json_error( 'Email summary options not found or email summary is not enabled' );
			return;
		}

		// Clear old cron schedule.
		if ( wp_next_scheduled( 'srfm_weekly_scheduled_events' ) ) {
			wp_clear_scheduled_hook( 'srfm_weekly_scheduled_events' );
		}

		// Get the day saved in options, defaulting to Monday if not set
		$day = isset( $email_summary_options['schedule_reports'] ) ? $email_summary_options['schedule_reports'] : 'Monday';

		// Get current timestamp.
		$current_time = current_time( 'timestamp' );

		// Convert the current time to the user's timezone
		$current_time_user_timezone = strtotime( date( 'Y-m-d H:i:s', $current_time ) );

		// Check if the time is in the correct format and represents a valid UTC time
		if ( ! preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $time ) ) {
			// Log an error and change the time to 09:00:00
			error_log( 'The time is not in the correct format' );
			$time = '09:00:00';
		}

		// Calculate the timestamp for the selected day at 9:00 AM in the user's timezone
		// $next_day_user_timezone = strtotime( "next $day $time", $current_time_user_timezone );
		// Added just for testing.
		$next_day_user_timezone = strtotime( "$day $time", $current_time_user_timezone );

		// Convert the calculated time back to the server's timezone
		$scheduled_time = strtotime( date( 'Y-m-d H:i:s', $next_day_user_timezone ) );

		// Create new schedule using Action Scheduler.
		if ( false === as_has_scheduled_action( 'srfm_weekly_scheduled_events' ) ) {
			as_schedule_recurring_action( $scheduled_time, WEEK_IN_SECONDS, 'srfm_weekly_scheduled_events', array(), 'sureforms', true );
		}
	}

}
