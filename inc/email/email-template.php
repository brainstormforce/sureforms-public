<?php
/**
 * Email template loader.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Email;

use SureForms\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Email Class
 *
 * @since X.X.X
 */
class Email_Template {

	use Get_Instance;

	/**
	 * Class Constructor
	 *
	 * @since X.X.X
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Get email header.
	 *
	 * @since X.X.X
	 * @return string
	 */
	public function get_header() {
		$header = '<html>
		<head>
			<meta charset="utf-8">
			<title>' . esc_html_e( 'New form submission', 'sureforms' ) . '</title>
		</head>
		<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
		<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tbody><tr>
			<td style="padding: 20px 0;">
				<table align="center" cellpadding="0" cellspacing="0" border="0" style="min-width: 700px; margin: 0 auto; background-color: #fff; border-radius: 10px; box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);">
				<tbody><tr>
					<td style="padding: 20px;">
					<h1 style="font-size: 24px; color: #333; margin: 0; text-align:center;">' . esc_html__( 'New form submission', 'sureforms' ) . '</h1>
					</td>
				</tr>
				<tr>
					<td style="padding: 20px;">
					<p style="font-size: 16px; color: #555; line-height: 1.6;"> ' . esc_html__( 'Here are the details of the form submission:', 'sureforms' ) . ' </p>
					<table cellpadding="10" cellspacing="0" border="0" style="font-size: 16px; color: #555; width: 100%; border-collapse: collapse;">
						<thead>
						<tr style="background-color: #333; color: #fff;">
							<th style="padding: 10px; text-align:left;">Field Name</th>
							<th style="padding: 10px; text-align:left;">Value</th>
						</tr>
						</thead>
						<tbody>';
		return $header;
	}

	/**
	 * Get email footer.
	 *
	 * @since X.X.X
	 * @return string footer tags.
	 */
	public function get_footer() {
		// Translators: Site URL.
		$site_link = sprintf( __( 'This form was submitted on the website - <a href=%1$s>%2$s</a>', 'sureforms' ), home_url( '/' ), get_bloginfo( 'name' ) );

		$footer = '</tbody></table></tr><tr><td style="padding: 20px; text-align: center; background-color: #f4f4f4;"><p style="font-size: 14px; color: #777;">' .
		$site_link . '</p></td></tr></tbody></table></td></tr></tbody></table></body></html>';

		return $footer;
	}

	/**
	 * Render email template.
	 *
	 * @param array<string, string> $fields Submission fields.
	 * @since X.X.X
	 * @return string
	 */
	public function render( $fields ) {
		$message         = $this->get_header();
		$excluded_fields = [ 'sureforms-honeypot-field', 'g-recaptcha-response', 'sureforms-sender-email-field' ];
		$field_index     = 1;
		foreach ( $fields as $field_name => $value ) {
			$bg_color = ( $field_index % 2 ) === 0 ? '#ffffff' : '#f2f2f2;';
			if ( in_array( $field_name, $excluded_fields, true ) || false !== strpos( $field_name, 'sf-radio' ) ) {
				continue;
			}
			if ( strpos( $field_name, 'SF-upload' ) !== false ) {
				$field_label = ucfirst( explode( 'SF-upload', $field_name )[0] );
				$message    .= sprintf(
					'<tr style="background-color: ' . esc_attr( $bg_color ) . '">
						<td style="padding: 10px;">%s</td>
						<td style="padding: 10px;max-width: 200px;"><a href="%s">View</a></td>
					</tr>',
					$field_label,
					$value
				);
			} elseif ( strpos( $field_name, 'SF-url' ) !== false ) {
				$field_label = ucfirst( explode( 'SF-url', $field_name )[0] );
				if (
					substr( $value, 0, 7 ) !== 'http://' &&
					substr( $value, 0, 8 ) !== 'https://'
				) {
					$value = 'https://' . $value;
				}
				$message .= sprintf(
					'<tr style="background-color: ' . esc_attr( $bg_color ) . '">
						<td style="padding: 10px;">%s</td>
						<td style="padding: 10px;max-width: 200px;"><a href="%s">%s</a></td>
					</tr>',
					$field_label,
					$value,
					$value
				);
			} else {
				$field_label = ucfirst( explode( 'SF-divider', $field_name )[0] );
				$message    .= sprintf(
					'<tr style="background-color: ' . esc_attr( $bg_color ) . '">
						<td style="padding: 10px;">%s</td>
						<td style="padding: 10px;max-width: 200px;">%s</td>
					</tr>',
					$field_label,
					$value
				);
			}
			$field_index++;
		}
		$message .= $this->get_footer();

		return $message;
	}

}
