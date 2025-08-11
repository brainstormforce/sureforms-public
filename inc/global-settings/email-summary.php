<?php
/**
 * Email Summaries.
 *
 * @package sureforms.
 * @since 0.0.2
 */

namespace SRFM\Inc\Global_Settings;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Email Summary Class.
 *
 * @since 0.0.2
 */
class Email_Summary {
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
	 * API endpoint to send test email.
	 *
	 * @return void
	 * @since 0.0.2
	 */
	public function register_custom_endpoint() {
		$sureforms_helper = new Helper();
		register_rest_route(
			'sureforms/v1',
			'/send-test-email-summary',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'send_test_email' ],
				'permission_callback' => [ $sureforms_helper, 'get_items_permissions_check' ],
			]
		);
	}

	/**
	 * Send test email.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.0.2
	 */
	public function send_test_email( $request ) {
		$data = $request->get_body();
		$data = json_decode( $data, true );

		$email_send_to = '';

		if ( is_array( $data ) && isset( $data['srfm_email_sent_to'] ) && is_string( $data['srfm_email_sent_to'] ) ) {
			$email_send_to = sanitize_email( $data['srfm_email_sent_to'] );
			if ( ! is_email( $email_send_to ) ) {
				return new \WP_REST_Response(
					[ 'data' => __( 'Invalid email address.', 'sureforms' ) ],
					400
				);
			}
		}

		$get_email_summary_options = [
			'srfm_email_sent_to' => $email_send_to,
		];

		self::send_entries_to_admin( $get_email_summary_options );

		return new WP_REST_Response(
			[
				'data' => __( 'Test Email Sent Successfully.', 'sureforms' ),
			]
		);
	}

	/**
	 * Function to get the total number of entries for the last week.
	 *
	 * @since 0.0.2
	 * @return string HTML table with entries count.
	 */
	public static function get_total_entries_for_week() {
		// Calculate timestamp for 7 days ago (last week).
		$week_ago_timestamp = strtotime( '-7 days' );

		// Use the common helper function to get forms with entry counts.
		$forms_data = Helper::get_forms_with_entry_counts( $week_ago_timestamp );

		$admin_user_name = get_user_by( 'id', 1 ) ? get_user_by( 'id', 1 )->display_name : 'Admin';

		$total_entries      = 0;
		$forms_with_entries = 0;
		$forms_table_rows   = [];
		$row_index          = 0;

		// Process forms data from the helper function.
		foreach ( $forms_data as $form_data ) {
			if ( $form_data['count'] > 0 ) {
				$forms_with_entries++;
				$total_entries     += $form_data['count'];
				$bg_color           = 0 === $row_index % 2 ? '#ffffff' : '#f2f2f2;';
				$forms_table_rows[] = [
					'title'    => $form_data['title'],
					'count'    => $form_data['count'],
					'bg_color' => $bg_color,
				];
				$row_index++;
			}
		}

		ob_start();
		?>
		<b><?php echo esc_html__( 'Hello', 'sureforms' ); ?> <?php echo esc_html( $admin_user_name ); ?>,</b><br><br>
		<span><?php echo esc_html__( 'Let\'s see how your forms performed in the last week', 'sureforms' ); ?></span><br><br>
		<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
			<thead>
				<tr style="background-color: #333; color: #fff; text-align: left;">
					<th style="padding: 10px;"><?php echo esc_html__( 'Form Name', 'sureforms' ); ?></th>
					<th style="padding: 10px;"><?php echo esc_html__( 'Entries', 'sureforms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $forms_with_entries > 0 ) { ?>
					<?php foreach ( $forms_table_rows as $row_data ) { ?>
						<tr style="background-color: <?php echo esc_attr( $row_data['bg_color'] ); ?>;">
							<td style="padding: 10px;"><?php echo esc_html( $row_data['title'] ); ?></td>
							<td style="padding: 10px;"><?php echo esc_html( Helper::get_string_value( $row_data['count'] ) ); ?></td>
						</tr>
					<?php } ?>
				<?php } else { ?>
					<tr>
						<td colspan="2" style="padding: 10px;"><?php echo esc_html__( 'No entries found in the last week.', 'sureforms' ); ?></td>
					</tr>
				<?php } ?>
			</tbody>
			<?php if ( $forms_with_entries > 0 ) { ?>
				<tfoot>
					<tr style="background-color: #333; color: #fff; text-align: left; font-weight: bold;">
						<td style="padding: 10px;"><?php echo esc_html__( 'Total Entries', 'sureforms' ); ?></td>
						<td style="padding: 10px;"><?php echo esc_html( Helper::get_string_value( $total_entries ) ); ?></td>
					</tr>
				</tfoot>
			<?php } ?>
		</table>
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Function to send the entries to admin mail.
	 *
	 * @param array<mixed>|bool $email_summary_options Email Summary Options.
	 * @since 0.0.2
	 * @return void
	 */
	public static function send_entries_to_admin( $email_summary_options ) {
		$entries_count_table = self::get_total_entries_for_week();

		$recipients_string = '';

		if ( is_array( $email_summary_options ) && isset( $email_summary_options['srfm_email_sent_to'] ) && is_string( $email_summary_options['srfm_email_sent_to'] ) ) {
			$recipients_string = $email_summary_options['srfm_email_sent_to'];
		}

		$recipients = $recipients_string ? explode( ',', $recipients_string ) : [];

		$site_title = get_bloginfo( 'name' );

		// Translators: %s: Site Title.
		$subject = sprintf( __( 'SureForms Email Summary - %s', 'sureforms' ), $site_title );
		$message = $entries_count_table;
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_option( 'admin_email' ),
		];

		wp_mail( $recipients, $subject, $message, $headers );
	}

	/**
	 * Schedule the event action to run weekly.
	 *
	 * @return void
	 * @since 0.0.2
	 */
	public static function schedule_weekly_entries_email() {
		$email_summary_options = get_option( 'srfm_email_summary_settings_options' );

		$time = apply_filters( 'srfm_weekly_email_summary_time', '09:00:00' );

		if ( wp_next_scheduled( 'srfm_weekly_scheduled_events' ) ) {
			wp_clear_scheduled_hook( 'srfm_weekly_scheduled_events' );
		}

		$day = __( 'Monday', 'sureforms' );

		if ( is_array( $email_summary_options ) && isset( $email_summary_options['srfm_schedule_report'] ) && is_string( $email_summary_options['srfm_schedule_report'] ) ) {
			$day = Helper::get_string_value( $email_summary_options['srfm_schedule_report'] );
		}

		$current_time               = time();
		$current_time_user_timezone = Helper::get_integer_value( strtotime( gmdate( 'Y-m-d H:i:s', $current_time ) ) );

		if ( ! preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $time ) ) {
			$time = '09:00:00';
		}

		$next_day_user_timezone = Helper::get_integer_value( strtotime( "next {$day} {$time}", $current_time_user_timezone ) );

		$scheduled_time = Helper::get_integer_value( strtotime( gmdate( 'Y-m-d H:i:s', $next_day_user_timezone ) ) );

		if ( false === as_has_scheduled_action( 'srfm_weekly_scheduled_events' ) ) {
			as_schedule_recurring_action(
				$scheduled_time,
				WEEK_IN_SECONDS,
				'srfm_weekly_scheduled_events',
				[
					'email_summary_options' => $email_summary_options,
				],
				'sureforms',
				true
			);
		}
	}

}
