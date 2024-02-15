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
		add_action( 'srfm_weekly_scheduled_events', [ $this, 'send_entries_to_admin' ] );
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
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
	 * Checks whether a given request has permissions.
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
	 * @return WP_REST_Response
	 * @since 0.0.1
	 */
	public function get_email_summary_options() {
		$email_summary_options = get_option( 'srfm_email_summary_options' );
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
		$form_info     = $data->get_body();
		$form_info_obj = json_decode( $form_info );

		if ( ! is_object( $form_info_obj ) ) {
			return new WP_Error( 'invalid_json', 'Invalid JSON data', array( 'status' => 400 ) );
		}

		$enable_email_summary = isset( $form_info_obj->enable_email_summary ) ? $form_info_obj->enable_email_summary : false;
		$emails_send_to       = isset( $form_info_obj->emails_send_to ) ? $form_info_obj->emails_send_to : array();
		$schedule_reports     = isset( $form_info_obj->schedule_reports ) ? $form_info_obj->schedule_reports : '';

		update_option(
			'srfm_email_summary_options',
			array(
				'enable_email_summary' => $enable_email_summary,
				'emails_send_to'       => $emails_send_to,
				'schedule_reports'     => $schedule_reports,
			)
		);

		$time = apply_filters( 'srfm_weekly_scheduled_events_time', '09:00:00' );

		if ( $enable_email_summary ) {
			as_unschedule_all_actions( 'srfm_weekly_scheduled_events' );
			self::schedule_weekly_entries_email( $time );
		} else {
			as_unschedule_all_actions( 'srfm_weekly_scheduled_events' );
		}

		return new WP_REST_Response( 'Email summary options saved successfully', 200 );
	}

	/**
	 * Function to get the total number of entries for the last week.
	 *
	 * @since 0.0.1
	 * @return string HTML table with entries count.
	 */
	public function get_total_entries_for_week() {
		// Define the post type we want to query.
		$post_type = 'sureforms_form';

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
		);

		$query = new WP_Query( $args );

		$admin_user_name = get_user_by( 'id', 1 )->display_name;
		$table_html      = '<b>' . __( 'Hello', 'sureforms' ) . ' ' . $admin_user_name . ',</b><br><br>';
		$table_html     .= '<span>' . __( 'Let\'s see how your forms performed in the last week', 'sureforms' ) . '</span><br><br>';
		$table_html     .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
		$table_html     .= '<thead>';
		$table_html     .= '<tr style="background-color: #333; color: #fff; text-align: left;">';
		$table_html     .= '<th style="padding: 10px;">' . __( 'Form Name', 'sureforms' ) . '</th>';
		$table_html     .= '<th style="padding: 10px;">' . __( 'Entries', 'sureforms' ) . '</th>';
		$table_html     .= '</tr>';
		$table_html     .= '</thead>';
		$table_html     .= '<tbody>';

		if ( $query->have_posts() ) {
			$row_index = 0;
			while ( $query->have_posts() ) {
				$query->the_post();
				global $post;

				$post_id_formatted = strval( $post->ID );

				$previous_week_start = date( 'Y-m-d', strtotime( '-1 week last monday' ) );
				$previous_week_end   = date( 'Y-m-d', strtotime( '-1 week next sunday' ) );

				$taxonomy      = 'sureforms_tax';
				$entries_args  = array(
					'post_type'  => SUREFORMS_ENTRIES_POST_TYPE,
					'tax_query'  => array(
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'slug',
							'terms'    => $post_id_formatted,
						),
					),
					'date_query' => array(
						array(
							'after'     => $previous_week_start,
							'before'    => $previous_week_end,
							'inclusive' => true,
						),
					),
				);
				$entries_query = new WP_Query( $entries_args );
				$entry_count   = $entries_query->post_count;

				$bg_color = $row_index % 2 === 0 ? '#ffffff' : '#f2f2f2;';

				$table_html .= '<tr style="background-color: ' . $bg_color . ';">';
				$table_html .= '<td style="padding: 10px;">' . esc_html( get_the_title() ) . '</td>';
				$table_html .= '<td style="padding: 10px;">' . esc_html( $entry_count ) . '</td>';
				$table_html .= '</tr>';

				$row_index++;
			}
		} else {
			$table_html .= '<tr>';
			$table_html .= '<td colspan="2" style="padding: 10px;">' . __( 'No forms found.', 'sureforms' ) . '</td>';
			$table_html .= '</tr>';
		}

		$table_html .= '</tbody>';
		$table_html .= '</table>';

		wp_reset_postdata();

		return $table_html;
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
		$message = $entries_count;
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_option( 'admin_email' ),
		);

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
		$next_day_user_timezone = strtotime( "next $day $time", $current_time_user_timezone );

		// Convert the calculated time back to the server's timezone
		$scheduled_time = strtotime( date( 'Y-m-d H:i:s', $next_day_user_timezone ) );

		// Create new schedule using Action Scheduler.
		if ( false === as_has_scheduled_action( 'srfm_weekly_scheduled_events' ) ) {
			as_schedule_recurring_action( $scheduled_time, WEEK_IN_SECONDS, 'srfm_weekly_scheduled_events', array(), 'sureforms', true );
		}
	}

}
