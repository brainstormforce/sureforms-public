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
	 * Get or copy image to uploads folder.
	 *
	 * @param string $filename The image filename.
	 * @param string $source_path The source path relative to plugin directory.
	 * @return string The public URL of the image.
	 *
	 * @since 1.10.1
	 */
	public static function get_public_image_url( $filename, $source_path ) {
		// Sanitize filename.
		$filename = basename( $filename );

		// Validate allowed extensions.
		$allowed_ext = [ 'png', 'jpg', 'jpeg', 'gif', 'svg' ];
		$ext         = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			return '';
		}

		// Whitelist or sanitize source_path if possible.
		$source_path = trailingslashit( ltrim( $source_path, '/' ) );

		// Upload directory info.
		$upload_dir           = wp_upload_dir();
		$sureforms_upload_dir = trailingslashit( $upload_dir['basedir'] ) . 'sureforms/images/';
		$sureforms_upload_url = trailingslashit( $upload_dir['baseurl'] ) . 'sureforms/images/';

		// Create directory if it doesn't exist.
		if ( ! file_exists( $sureforms_upload_dir ) && ! wp_mkdir_p( $sureforms_upload_dir ) ) {
			// Fallback URL.
			return esc_url( SRFM_URL . $source_path . $filename );
		}

		$target_file = $sureforms_upload_dir . $filename;
		$target_url  = $sureforms_upload_url . $filename;

		// Copy if doesn't exist.
		if ( ! file_exists( $target_file ) ) {
			$source_file = SRFM_DIR . $source_path . $filename;
			if ( ! file_exists( $source_file ) || ! copy( $source_file, $target_file ) ) {
				// Fallback URL.
				return esc_url( SRFM_URL . $source_path . $filename );
			}
		}

		return esc_url( $target_url );
	}

	/**
	 * Function to get the total number of entries for the last week.
	 *
	 * @param array<array{form_id:int,title:string,count:int}>|null $forms_data Optional. Forms data with entry counts. If null, fetches from database.
	 *
	 * @since 0.0.2
	 * @return string HTML table with entries count.
	 */
	public static function get_total_entries_for_week( $forms_data = null ) {
		// Calculate timestamp for 7 days ago (last week).
		$week_ago_timestamp = strtotime( '-7 days' );

		if ( null === $forms_data ) {
			// Use the helper function to get forms with entry counts.
			$forms_data = Helper::get_forms_with_entry_counts( $week_ago_timestamp );
		}

		$logs_url = admin_url( 'admin.php?page=sureforms_entries' );

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
			<style>
				/* Mobile-specific styles */
				@media only screen and (max-width: 600px) {
					.email-greeting {
						font-size: 16px !important;
					}

					/* Padding reductions */
					.pad-32 { padding: 32px !important; }
					.pad-24 { padding: 24px !important; }
					.pad-16 { padding: 16px !important; }
					.margin-mob { margin: 24px 16px !important; }
				}
			</style>
		</head>
		<body class="pad-24" style="font-family:Figtree,Arial,sans-serif;background-color:#F1F5F9;margin:0;padding:32px;">
			<div style="max-width:640px;margin:0 auto;">
				<div style="margin-bottom:24px;text-align:left;">
					<img
						src="<?php echo esc_url( self::get_public_image_url( 'sureforms-logo-full.png', 'admin/assets/' ) ); ?>"
						alt="<?php esc_attr_e( 'SureForms Logo', 'sureforms' ); ?>"
						width="192" height="32"
						style="display:block;">
				</div>
				<div style="background-color:#FFFFFF;padding-bottom:40px;">
					<div class="pad-16" style="padding:24px;">
						<p class="email-greeting" style="font-size:18px;font-weight:600;color:#111827;margin:0 0 8px;">
							<?php
								echo esc_html__( 'Hey There,', 'sureforms' );
							?>
						</p>
						<p style="font-size:14px;color:#4B5563;margin:0 0 16px;line-height:20px;">
							<?php
							$site_url  = home_url();
							$site_name = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : $site_url;
								printf(
									/* translators: 1: site URL, 2: site name */
									esc_html__( "Here's your SureForms report for the last 7 days of %s.", 'sureforms' ),
									sprintf(
										'<a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:#4B5563;text-decoration:underline;">%2$s</a>',
										esc_url( $site_url ),
										esc_html( $site_name )
									)
								);
							?>
						</p>

						<?php
						ob_start();
						?>
						<table style="border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 1px rgba(0,0,0,0.05);margin-top:16px;width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed;">
							<thead>
								<tr style="background-color:#F9FAFB;">
									<th style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:left;border-top-left-radius:8px;border-bottom:0.5px solid #E5E7EB;white-space:nowrap;width:auto;"><?php esc_html_e( 'Form Name', 'sureforms' ); ?></th>
									<th style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:right;width:80px;border-top-right-radius:8px;border-bottom:0.5px solid #E5E7EB;white-space:nowrap;"><?php esc_html_e( 'Entries', 'sureforms' ); ?></th>
								</tr>
							</thead>
							<tbody>
						<?php
						$table_html = ob_get_clean();

						$total_entries = 0;

						if ( ! empty( $forms_data ) ) {
							foreach ( $forms_data as $form ) {
								if ( $form['count'] <= 0 ) {
									continue;
								}

								$total_entries += $form['count'];

								ob_start();
								?>
								<tr style="background-color:#FFFFFF;">
									<td style="padding:12px;font-size:14px;color:#4B5563;border-bottom:0.5px solid #E5E7EB;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html( $form['title'] ); ?></td>
									<td style="padding:12px;font-size:14px;color:#4B5563;text-align:right;border-bottom:0.5px solid #E5E7EB;white-space:nowrap;width:80px;"><?php echo esc_html( Helper::get_string_value( $form['count'] ) ); ?></td>
								</tr>
								<?php
								$table_html .= ob_get_clean();
							}

							ob_start();
							?>
							</tbody>
							<tfoot>
								<tr style="background-color:#F9FAFB;font-weight:bold;">
									<td style="padding:8px 12px;font-size:14px;color:#111827;border-bottom-left-radius:8px;white-space:nowrap;"><?php echo esc_html__( 'Total Entries', 'sureforms' ); ?></td>
									<td style="padding:8px 12px;font-size:14px;color:#111827;text-align:right;border-bottom-right-radius:8px;white-space:nowrap;width:80px;"><?php echo esc_html( Helper::get_string_value( $total_entries ) ); ?></td>
								</tr>
							</tfoot>
							<?php
							$table_html .= ob_get_clean();
						}

						ob_start();
						?>
						</table>
						<?php
						$table_html .= ob_get_clean();

						echo wp_kses_post( $table_html );
						?>

						<a href="<?php echo esc_url( $logs_url ); ?>"
						style="display:inline-block;background-color:#2563EB;color:#FFFFFF;padding:8px 12px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;margin-top:16px;">
							<?php esc_html_e( 'View Entries', 'sureforms' ); ?>
						</a>
					</div>

					<hr style="border:none;border-top:1px solid #eee;">

					<!-- Promotion Section -->
					<?php
						$plugin_key = self::get_next_promo_plugin();
						self::render_promo_banner( $plugin_key );
					?>

					<p style="font-size:12px;color:#9CA3AF;text-align:center;margin:16px 16px;">
						<?php
						printf(
							/* translators: %s: opening and closing anchor tag for SureForms settings link */
							esc_html__( 'Manage Email Summaries from your %1$sSureForms settings%2$s', 'sureforms' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ) . '" style="color:#9CA3AF;text-decoration:underline;">',
							'</a>'
						);
						?>
					</p>

					<hr style="margin:16px 24px;border:none;border-top:1px solid #eee;">

					<div style="text-align:center;margin-top:16px;">
						<img src="<?php echo esc_url( self::get_public_image_url( 'sureforms-logo-full.png', 'admin/assets/' ) ); ?>" alt="<?php esc_attr_e( 'SureForms Logo', 'sureforms' ); ?>" height="20" style="display:block;margin:0 auto;">
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		self::mark_promo_sent( $plugin_key );
		$content = ob_get_clean();
		return false !== $content ? $content : '';
	}

	/**
	 * Retrieve all available promo banners.
	 *
	 * Returns an associative array of promo plugin data,
	 * including logo, title, description, and link info.
	 *
	 * @since 1.12.1
	 *
	 * @return array List of promo banners with details.
	 */
	public static function get_promo_banners() {
		return [
			'ottokit'  => [
				'logo'        => 'ottokit.png',
				'title'       => __( 'Automate Workflows with OttoKit', 'sureforms' ),
				'description' => __( 'Connect your apps and automate repetitive tasks with ease. Build workflows that save time, reduce errors, and keep your business running smoothly around the clock.', 'sureforms' ),
				'link'        => 'https://ottokit.com?utm_medium=sureforms-email-summary',
				'link_text'   => __( 'Explore OttoKit', 'sureforms' ),
			],
			'surerank' => [
				'logo'        => 'surerank.png',
				'title'       => __( 'Optimize Your Site with SureRank', 'sureforms' ),
				'description' => __( 'Fix common SEO issues and make your site search-friendly without the clutter. Check pages, add titles, meta, and schema — all in one simple plugin.', 'sureforms' ),
				'link'        => 'https://surerank.com?utm_medium=sureforms-email-summary',
				'link_text'   => __( 'Explore SureRank', 'sureforms' ),
			],
			'suremail' => [
				'logo'        => 'suremail.png',
				'title'       => __( 'Send Emails Reliably with SureMail', 'sureforms' ),
				'description' => __( 'Make sure every WordPress email gets delivered. View logs, debug errors, and send with confidence using a lightweight SMTP solution.', 'sureforms' ),
				'link'        => 'https://suremails.com?utm_medium=sureforms-email-summary',
				'link_text'   => __( 'Explore SureMail', 'sureforms' ),
			],
			'surecart' => [
				'logo'        => 'surecart.png',
				'title'       => __( 'Sell with Ease using SureCart', 'sureforms' ),
				'description' => __( 'Run your online store on WordPress with a modern checkout, subscriptions, and payments. Fast, flexible, and built for growth.', 'sureforms' ),
				'link'        => 'https://surecart.com?utm_medium=sureforms-email-summary',
				'link_text'   => __( 'Explore SureCart', 'sureforms' ),
			],
			'suredash' => [
				'logo'        => 'suredash.png',
				'title'       => __( 'Build Your Community with SureDash', 'sureforms' ),
				'description' => __( 'Create a central hub where members can connect, share, and grow together. Manage discussions, courses, and events — all from your own WordPress site.', 'sureforms' ),
				'link'        => 'https://suredash.com?utm_medium=sureforms-email-summary',
				'link_text'   => __( 'Explore SureDash', 'sureforms' ),
			],
		];
	}

	/**
	 * Render a promo banner for a given plugin.
	 *
	 * Outputs the banner HTML for the specified plugin key.
	 * If the key is invalid, nothing is rendered.
	 *
	 * @since 1.12.1
	 *
	 * @param string $plugin_key The key of the promo plugin to render.
	 *
	 * @return void
	 */
	public static function render_promo_banner( $plugin_key ) {
		$banners = self::get_promo_banners();

		if ( ! isset( $banners[ $plugin_key ] ) ) {
			return;
		}

		$args = $banners[ $plugin_key ];
		?>
		<div class="margin-mob" style="margin:32px 24px;padding:16px;border:0.5px solid #E5E7EB;border-radius:8px;background:#FFFFFF;text-align:left;">
			<div style="margin-bottom:4px;">
				<img
					src="<?php echo esc_url( self::get_public_image_url( $args['logo'], 'admin/assets/' ) ); ?>"
					alt="<?php echo esc_attr( $args['title'] ); ?> Logo"
					width="20" height="20"
					style="display:block;">
			</div>

			<p style="font-size:14px;line-height:20px;font-weight:600;color:#111827;margin:0 0 4px;">
				<?php echo esc_html( $args['title'] ); ?>
			</p>
			<p style="font-size:12px;color:#4B5563;margin:0 0 4px;line-height:16px;font-weight:400;">
				<?php echo esc_html( $args['description'] ); ?>
			</p>
			<a href="<?php echo esc_url( $args['link'] ); ?>" target="_blank" rel="noopener noreferrer"
				style="font-size:12px;font-weight:600;color:#EF4444;text-decoration:none;line-height:16px;">
				<?php echo esc_html( $args['link_text'] ); ?> →
			</a>
		</div>
		<?php
	}

	/**
	 * Get the next promo plugin.
	 *
	 * @return string The plugin key for the next promo banner.
	 * @since 1.12.1
	 */
	public static function get_next_promo_plugin() {
		$all_plugins = array_keys( self::get_promo_banners() );

		$remaining = Helper::get_array_value( Helper::get_srfm_option( 'remaining_promos', [] ) );

		if ( empty( $remaining ) ) {
			$remaining = $all_plugins;
		}

		return Helper::get_string_value( reset( $remaining ) );
	}

	/**
	 * Mark a promo as sent by removing it from the remaining list.
	 *
	 * @param string $plugin_key The plugin key to mark as sent.
	 * @return void
	 * @since 1.12.1
	 */
	public static function mark_promo_sent( $plugin_key ) {
		$remaining = Helper::get_array_value( Helper::get_srfm_option( 'remaining_promos', [] ) );

		if ( empty( $remaining ) ) {
			$remaining = array_keys( self::get_promo_banners() );
		}

		$remaining = array_diff( $remaining, [ $plugin_key ] );

		Helper::update_srfm_option( 'remaining_promos', $remaining );
	}

	/**
	 * Function to send the entries to admin mail.
	 *
	 * @param array<mixed>|bool $email_summary_options Email Summary Options.
	 * @since 0.0.2
	 * @return void
	 */
	public static function send_entries_to_admin( $email_summary_options ) {
		// Calculate timestamp for 7 days ago (last week).
		$week_ago_timestamp = strtotime( '-7 days' );

		$forms_data = Helper::get_forms_with_entry_counts( $week_ago_timestamp );

		// Calculate total entries.
		$total_entries = array_sum( wp_list_pluck( $forms_data, 'count' ) );

		// If no entries at all, don't send the email.
		if ( $total_entries <= 0 ) {
			return;
		}

		$entries_count_table = self::get_total_entries_for_week( $forms_data );

		$recipients_string = '';

		if ( is_array( $email_summary_options ) && isset( $email_summary_options['srfm_email_sent_to'] ) && is_string( $email_summary_options['srfm_email_sent_to'] ) ) {
			$recipients_string = $email_summary_options['srfm_email_sent_to'];
		}

		$recipients = $recipients_string ? explode( ',', $recipients_string ) : [];

		$from_date = date_i18n( 'F j, Y', $week_ago_timestamp );
		$to_date   = date_i18n( 'F j, Y' );

		// Translators: %1$s: From Date, %2$s: To Date.
		$subject = sprintf( __( 'Email Summary of your last week -  %1$s to %2$s', 'sureforms' ), $from_date, $to_date );
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
