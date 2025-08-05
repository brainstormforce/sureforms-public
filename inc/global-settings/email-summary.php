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

		// Use the helper function to get forms with entry counts.
		$forms_data = Helper::get_forms_with_entry_counts( $week_ago_timestamp );

		$admin_user_name = get_user_by( 'id', 1 ) ? get_user_by( 'id', 1 )->display_name : 'Admin';
		$from_date       = date_i18n( 'F j, Y', $week_ago_timestamp );
		$to_date         = date_i18n( 'F j, Y' );
		$logs_url        = admin_url( 'admin.php?page=sureforms_entries' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<title><?php esc_html_e( 'Weekly Summary', 'sureforms' ); ?></title>
			<?php
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Required in email HTML; wp_enqueue_style() can't be used for emails.
				echo '<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">';
			?>
		</head>
		<body style="font-family:Figtree,Arial,sans-serif;background-color:#F1F5F9;margin:0;padding:32px;">
			<div style="max-width:640px;margin:0 auto;">
				<div style="margin-bottom:24px;text-align:left;">
					<img src="<?php echo esc_url( SRFM_URL . 'admin/assets/sureforms-logo-full.png' ); ?>"
						alt="<?php esc_attr_e( 'SureForms Logo', 'sureforms' ); ?>"
						width="192" height="32"
						style="display:inline-block;">
				</div>
				<div style="background-color:#FFFFFF;padding-bottom:40px;">
					<div style="padding:24px;">
						<p style="font-size:18px;font-weight:600;color:#111827;margin:0 0 8px;">
							<?php
								printf(
									/* translators: %1$s is the admin user name */
									esc_html__( 'Hey %1$s,', 'sureforms' ),
									esc_html( $admin_user_name )
								);
							?>
						</p>
						<p style="font-size:14px;color:#4B5563;margin:0 0 16px;">
							<?php
							printf(
								esc_html__( "Here's your SureForms report for the last 7 days, from %1\$s to %2\$s.", 'sureforms' ),
								'<strong>' . esc_html( $from_date ) . '</strong>',
								'<strong>' . esc_html( $to_date ) . '</strong>'
							);
							?>
						</p>

						<?php
						$table_html = '<table style="border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 1px rgba(0,0,0,0.05);margin-top:16px;width:100%;border-collapse:separate;border-spacing:0;">
							<thead>
								<tr style="background-color:#F9FAFB;">
									<th style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:left;border-top-left-radius:8px;">' . esc_html__( 'Form Name', 'sureforms' ) . '</th>
									<th style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:right;width:146px;border-top-right-radius:8px;">' . esc_html__( 'Entries', 'sureforms' ) . '</th>
								</tr>
							</thead>
							<tbody>';

						$total_entries = 0;

						if ( ! empty( $forms_data ) ) {
							foreach ( $forms_data as $index => $form ) {
								if ( $form['count'] <= 0 ) {
									continue;
								}

								$total_entries += $form['count'];
								$bg_color       = 0 === $index % 2 ? '#FFFFFF' : '#F9FAFB';

								$table_html .= '<tr style="background-color:' . esc_attr( $bg_color ) . ';">
									<td style="padding:12px;font-size:14px;color:#4B5563;">' . esc_html( $form['title'] ) . '</td>
									<td style="padding:12px;font-size:14px;color:#4B5563;text-align:right;">' . esc_html( Helper::get_string_value( $form['count'] ) ) . '</td>
								</tr>';
							}

							$table_html .= '</tbody><tfoot><tr style="background-color:#F9FAFB;font-weight:bold;">
								<td style="padding:12px;font-size:14px;color:#111827;">' . esc_html__( 'Total Entries', 'sureforms' ) . '</td>
								<td style="padding:12px;font-size:14px;color:#111827;text-align:right;">' . esc_html( Helper::get_string_value( $total_entries ) ) . '</td>
							</tr></tfoot>';
						} else {
							$table_html .= '<tr><td colspan="2" style="padding:12px;font-size:14px;color:#4B5563;">' . esc_html__( 'No entries found in the last week.', 'sureforms' ) . '</td></tr></tbody>';
						}

						$table_html .= '</table>';

						echo wp_kses_post( $table_html );
						?>

						<a href="<?php echo esc_url( $logs_url ); ?>"
						style="display:inline-block;background-color:#2563EB;color:#FFFFFF;padding:8px 12px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;margin-top:16px;">
							<?php esc_html_e( 'View Entries', 'sureforms' ); ?>
						</a>
					</div>

					<hr style="border:none;border-top:1px solid #eee;">

					<!-- OttoKit Promotion Section -->
					<div style="margin:32px 24px;padding:16px;border:0.5px solid #E5E7EB;border-radius:8px;background:#FFFFFF;text-align:left;">
						<div style="margin-bottom:4px;">
							<img src="<?php echo esc_url( SRFM_URL . 'images/suretriggers.svg' ); ?>" alt="OttoKit Logo" width="20" height="20" style="border-radius:6px;">
						</div>
						<p style="font-size:14px;line-height:20px;font-weight:600;color:#111827;margin:0 0 4px;">
							<?php esc_html_e( 'Automate Workflows with OttoKit', 'sureforms' ); ?>
						</p>
						<p style="font-size:12px;color:#4B5563;margin:0 0 4px;line-height:16px;font-weight:400;">
							<?php esc_html_e( 'Connect your apps and automate repetitive tasks with ease. Build workflows that save time, reduce errors, and keep your business running smoothly around the clock.', 'sureforms' ); ?>
						</p>
						<a href="https://ottokit.com" target="_blank" rel="noopener noreferrer"
							style="font-size:12px;font-weight:600;color:#EF4444;text-decoration:none;line-height:16px;">
							<?php esc_html_e( 'Explore OttoKit', 'sureforms' ); ?> →
						</a>
					</div>

					<p style="font-size:12px;color:#9CA3AF;text-align:center;margin:16px 0;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ); ?>"
						style="color:#9CA3AF;text-decoration:none;">
							<?php esc_html_e( 'Manage Email Summaries from your SureForms settings', 'sureforms' ); ?>
						</a>
					</p>

					<hr style="margin:16px 24px;border:none;border-top:1px solid #eee;">

					<div style="text-align:center;margin-top:16px;">
						<img src="<?php echo esc_url( SRFM_URL . 'admin/assets/sureforms-logo-full.png' ); ?>"
							alt="<?php esc_attr_e( 'SureForms Logo', 'sureforms' ); ?>"
							height="20"
							style="display:block;margin:0 auto;">
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		$content = ob_get_clean();
		return false !== $content ? $content : '';
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
