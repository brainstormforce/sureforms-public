<?php
/**
 * Email template loader.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Email;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;


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
	 * @return string
	 */
	public function get_header() {
		$header = '<html>
		<head>
			<meta charset="utf-8">
			<title>' . esc_html_e( 'New form submission', 'sureforms' ) . '</title>
		</head>
		<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">';
		return $header;
	}

	/**
	 * Get email footer.
	 *
	 * @since 0.0.1
	 * @return string footer tags.
	 */
	public function get_footer() {
		// Translators: Site URL.
		$site_link = sprintf( __( 'This form was submitted on the website - <a href=%1$s>%2$s</a>', 'sureforms' ), home_url( '/' ), get_bloginfo( 'name' ) );

		$footer = '<p style="font-size: 14px; color: #777;">' .
		$site_link . '</p></body></html>';

		return $footer;
	}

	/**
	 * Render email template.
	 *
	 * @param array<string, string> $fields Submission fields.
	 * @param string                $email_body email body.
	 * @since 0.0.1
	 * @return string
	 */
	public function render( $fields, $email_body ) {
		$message         = $this->get_header();
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
		$field_index     = 1;

		$message .= $email_body;

		if ( strpos( $email_body, '{all_data}' ) !== false ) {

			$table_data = '<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tbody><tr>
				<td style="padding: 20px 0;">
				<table align="center" cellpadding="0" cellspacing="0" border="0" style="min-width: 700px; margin: 0 auto; background-color: #fff; border-radius: 10px; box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);">
				<tbody>
				<tr>
					<td style="padding: 20px;">
					<table cellpadding="10" cellspacing="0" border="0" style="font-size: 16px; color: #555; width: 100%; border-collapse: collapse;">
						<thead>
						<tr style="background-color: #333; color: #fff;">
							<th style="padding: 10px; text-align:left;">' . esc_html__( 'Field Name', 'sureforms' ) . '</th>
							<th style="padding: 10px; text-align:left;">' . esc_html__( 'Value', 'sureforms' ) . '</th>
						</tr>
						</thead>
						<tbody>';

			foreach ( $fields as $field_name => $value ) {
				if ( in_array( $field_name, $excluded_fields, true ) || false === str_contains( $field_name, '-lbl-' ) ) {
					continue;
				}

				$bg_color = ( $field_index % 2 ) === 0 ? '#ffffff' : '#f2f2f2;';
				$label    = explode( '-lbl-', $field_name )[1];

				if ( strpos( $field_name, 'srfm-upload' ) !== false || strpos( $field_name, 'srfm-url' ) !== false ) {
					$field_label = $label ? esc_html( Sureforms_Helper::decrypt( $label ) ) : '';
					$url_value   = strpos( $field_name, 'srfm-url' ) !== false ? '<a href="' . esc_url( $value ) . '">' . esc_url( $value ) . '</a>' : '<a href="' . esc_url( $value ) . '">View</a>';
					$table_data .= sprintf(
						'<tr style="background-color: ' . esc_attr( $bg_color ) . '">
                        <td style="padding: 10px;">%s</td>
                        <td style="padding: 10px;max-width: 200px;">%s</td>
                    </tr>',
						$field_label,
						$url_value
					);
				} else {
					$field_label = $label ? esc_html( Sureforms_Helper::decrypt( $label ) ) : '';
					$table_data .= sprintf(
						'<tr style="background-color: ' . esc_attr( $bg_color ) . '">
                        <td style="padding: 10px;">%s</td>
                        <td style="padding: 10px;max-width: 200px;">%s</td>
                    </tr>',
						$field_label,
						esc_html( $value )
					);
				}
				$field_index++;
			}

			$table_data .= '</tbody></table></tbody></table></tbody></table>';

			$message = str_replace( '{all_data}', $table_data, $message );
		}
		$message .= $this->get_footer();
		return $message;
	}

}
