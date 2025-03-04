<?php
/**
 * Email template loader.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Email;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Email Class
 *
 * @since 0.0.1
 */
class Email_Template {
	use Get_Instance;

	/**
	 * Class Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Get email header.
	 *
	 * @since 0.0.1
	 * @return string|false
	 */
	public function get_header() {
		ob_start(); ?>
		<html>

		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html__( 'New form submission', 'sureforms' ); ?></title>
		</head>

		<body style="margin: 0; padding: 0;">
			<div id="srfm_wrapper" dir="ltr" style="margin: 0; background-color: #F8F8FC; padding: 40px 0 0 0; width: 100%">
				<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tbody>
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="srfm_template_container" style="background-color: #ffffff;border: 1px solid #dce0e6;margin-bottom: 25px;
									">
									<tbody>
										<tr>
											<td align="center" valign="top">
												<table border="0" cellpadding="0" cellspacing="0" width="600"
													id="srfm_template_body">
													<tbody>
														<tr>
															<td valign="top" id="srfm_body_content"
																style="background-color: #ffffff">
																<table border="0" cellpadding="20" cellspacing="0" width="100%">
																	<tbody>
																		<tr>
																			<td valign="top" style="padding:32px">
																				<div id="srfm_body_content_inner" style="color: #384860;font-family: Roboto-Medium,Roboto,-apple-system,BlinkMacSystemFont,Helvetica Neue,Helvetica,Arial,sans-serif;font-size: 14px;line-height: 1;text-align: left;">
		<?php
		return ob_get_clean();
	}

	/**
	 * Get email footer.
	 *
	 * @since 0.0.1
	 * @return string|false footer tags.
	 */
	public function get_footer() {
		ob_start();
		?>
																				</div>
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</body>
	</html>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render email template.
	 *
	 * @param array<mixed> $fields Submission fields.
	 * @param string       $email_body email body.
	 * @since 0.0.1
	 * @return string
	 */
	public function render( $fields, $email_body ) {
		$message         = $this->get_header();
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];

		$message .= $email_body;
		if ( strpos( $email_body, '{all_data}' ) !== false ) {

			ob_start();

			?>
			<table class="srfm_all_data" width="536" cellpadding="0" cellspacing="0" style="border: 1px solid #dce0e6;border-radius: 6px;margin-top: 25px;margin-bottom: 25px;">
				<tbody>
					<?php
					foreach ( $fields as $field_name => $value ) {
						if ( is_array( $value ) ) {
							$values_array = $value;
						} else {
							$value = Helper::get_string_value( $value );
						}
						if ( in_array( $field_name, $excluded_fields, true ) || false === str_contains( $field_name, '-lbl-' ) ) {
							continue;
						}

						$label       = explode( '-lbl-', $field_name )[1];
						$label       = explode( '-', $label )[0];
						$field_label = $label ? Helper::decrypt( $label ) : '';
						?>
					<tr class="field-label">
						<th style="font-weight: 500;font-size: 14px;color: #1E293B;padding: 8px 16px;background-color: #F1F5F9;text-align: left;">
							<strong><?php echo wp_kses_post( html_entity_decode( $field_label ) ); ?>:<strong/>
						</th>
					</tr>
					<tr class="field-value">
						<td style="font-size: 14px;color: #475569;padding: 8px 16px 16px 16px;padding-bottom: 10px;">
						<?php
						if ( ! empty( $values_array ) && is_array( $values_array ) ) {
							foreach ( $values_array as $value ) {
								$value = Helper::get_string_value( $value );
								if ( ! empty( $value ) && is_string( $value ) ) {
									?>
									<a target="_blank" href="<?php echo esc_attr( urldecode( $value ) ); ?>"><?php echo esc_html__( 'View', 'sureforms' ); ?></a>
									<?php
								}
							}
						} elseif ( ! empty( $value ) && is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
							?>
							<a target="_blank" href="<?php echo esc_attr( urldecode( $value ) ); ?>"><?php echo esc_html__( 'View', 'sureforms' ); ?></a>
							<?php
						} else {
							if ( is_string( $value ) ) {
								echo false !== strpos( $value, PHP_EOL ) ? wp_kses_post( wpautop( $value ) ) : wp_kses(
									$value,
									[
										'a' => [
											'href'   => [],
											'target' => [],
										],
									]
								);
							}
						}
						?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
				$table_data         = ob_get_clean();
				$current_table_data = $table_data ? $table_data : ''; // This is done as str_replace expects array|string but ob_get_clean() returns string|false.
			$message                = str_replace( '{all_data}', $current_table_data, $message );
		}
		return $message . $this->get_footer();
	}

}
