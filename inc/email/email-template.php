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
																				<div id="srfm_body_content_inner" style="color: #384860;font-family: Roboto-Medium,Roboto,-apple-system,BlinkMacSystemFont,Helvetica Neue,Helvetica,Arial,sans-serif;font-size: 14px;line-height: 20px;text-align: left;">
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
		$message  = $this->get_header();
		$message .= $this->process_all_data_tag( $fields, $email_body );
		return $message . $this->get_footer();
	}

	/**
	 * Render email template in raw HTML format.
	 *
	 * Preserves the email body HTML exactly as written and wraps it in the
	 * advanced SureForms email template with full DOCTYPE, inline CSS,
	 * dark mode support, mobile responsiveness, and RTL language support.
	 *
	 * @param array<mixed> $fields Submission fields.
	 * @param string       $email_body Email body content.
	 * @since x.x.x
	 * @return string The rendered email HTML.
	 */
	public function render_raw( $fields, $email_body ) {
		$email_body = $this->process_all_data_tag( $fields, $email_body );

		return $this->get_raw_header() . $email_body . $this->get_raw_footer();
	}

	/**
	 * Get raw HTML email header.
	 *
	 * Returns a professional HTML email header with DOCTYPE, meta tags,
	 * inline CSS, dark mode support, and table-based layout.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_raw_header() {
		$is_rtl      = is_rtl();
		$dir         = $is_rtl ? 'rtl' : 'ltr';
		$text_align  = $is_rtl ? 'right' : 'left';
		$margin_attr = $is_rtl ? 'rightmargin' : 'leftmargin';

		ob_start();
		?>
<!DOCTYPE html>
<html dir="<?php echo esc_attr( $dir ); ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="x-apple-disable-message-reformatting" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		/* Reset */
		body, #srfm_raw_wrapper { margin: 0; padding: 0; }
		img { border: none; display: inline; font-size: 14px; height: auto; outline: none; text-decoration: none; }
		a { color: #5850EC; text-decoration: underline; }
		h1 { font-size: 30px; font-weight: 300; line-height: 150%; margin: 0 0 .5em 0; }
		h2 { font-size: 18px; font-weight: bold; line-height: 130%; margin: .5em 0; }
		h3 { font-size: 16px; font-weight: bold; line-height: 130%; margin: .5em 0; }
		p { margin: 0 0 16px; }

		/* Dark mode support */
		@media (prefers-color-scheme: dark) {
			body, #srfm_raw_wrapper { background-color: #1a1a2e !important; }
			#srfm_raw_template_container { background-color: #16213e !important; border-color: #2a2a4a !important; }
			#srfm_raw_body_content { background-color: #16213e !important; }
			#srfm_raw_body_content_inner { color: #e0e0e0 !important; }
			#srfm_raw_body_content_inner h1,
			#srfm_raw_body_content_inner h2,
			#srfm_raw_body_content_inner h3 { color: #f0f0f0 !important; }
			.srfm_all_data { border-color: #2a2a4a !important; }
			.srfm_all_data .field-label th { background-color: #1a1a2e !important; color: #e0e0e0 !important; }
			.srfm_all_data .field-value td { color: #c0c0c0 !important; border-color: #2a2a4a !important; }
			#srfm_raw_footer_credit { color: #888888 !important; }
			#srfm_raw_footer_credit a { color: #7c78f0 !important; }
		}

		/* iOS font size compensation */
		@media screen and (max-device-width: 768px) and (-webkit-min-device-pixel-ratio: 2) {
			* { font-size: 28px; line-height: 1.3em; }
		}

		/* Mobile responsive */
		@media only screen and (max-width: 640px) {
			body { padding: 0 !important; }
			#srfm_raw_template_container { width: 100% !important; }
			#srfm_raw_template_body { width: 100% !important; }
			#srfm_raw_body_content_inner { padding: 20px !important; }
			h1, h2, h3, h4 { font-weight: 800 !important; margin: 20px 0 5px !important; }
			.srfm_all_data { width: 100% !important; }
		}
	</style>
</head>
<body <?php echo esc_attr( $margin_attr ); ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0; padding: 0; background-color: #F8F8FC; -webkit-text-size-adjust: none; font-family: Roboto, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; width: 100%;">
	<div id="srfm_raw_wrapper" dir="<?php echo esc_attr( $dir ); ?>" style="margin: 0; background-color: #F8F8FC; padding: 40px 0 0 0; -webkit-text-size-adjust: none !important; width: 100%;">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" width="600" id="srfm_raw_template_container" style="background-color: #ffffff; border: 1px solid #dce0e6; margin-bottom: 25px;">
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="srfm_raw_template_body">
									<tr>
										<td valign="top" id="srfm_raw_body_content" style="background-color: #ffffff;">
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top" style="padding: 32px;">
														<div id="srfm_raw_body_content_inner" style="color: #384860; font-family: Roboto, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 170%; text-align: <?php echo esc_attr( $text_align ); ?>;">
		<?php
		$output = ob_get_clean();
		return $output ? $output : '';
	}

	/**
	 * Get raw HTML email footer.
	 *
	 * Returns a professional HTML email footer with site credits
	 * and proper closing tags.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_raw_footer() {
		$site_name = get_bloginfo( 'name', 'display' );
		$site_url  = home_url();
		$year      = gmdate( 'Y' );

		ob_start();
		?>
														</div>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<!-- Footer -->
					<table border="0" cellpadding="10" cellspacing="0" width="600" id="srfm_raw_template_footer">
						<tr>
							<td valign="top">
								<table border="0" cellpadding="10" cellspacing="0" width="100%">
									<tr>
										<td id="srfm_raw_footer_credit" colspan="2" valign="middle" style="border: 0; color: #999999; font-family: Arial, sans-serif; font-size: 12px; line-height: 125%; text-align: center; padding: 0 48px 48px 48px;">
											<span>&copy; <?php echo esc_html( $year ); ?> <a href="<?php echo esc_url( $site_url ); ?>" style="color: #999999; text-decoration: none;"><?php echo esc_html( $site_name ); ?></a></span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
		<?php
		$output = ob_get_clean();
		return $output ? $output : '';
	}

	/**
	 * Remove border from the last table row in repeater table HTML.
	 *
	 * This method finds the last <tr> element in the provided HTML content
	 * and removes the border-bottom style from all <td> elements within it.
	 * This is used to clean up the visual appearance of repeater tables.
	 *
	 * @param string $content HTML content containing table structure.
	 *
	 * @since 1.11.0
	 * @return string Modified HTML content with border removed from last row.
	 */
	public function remove_border_from_last_tr_td_table( $content ) {
		// Check if content contains table and tr elements.
		if ( empty( $content ) || ! preg_match( '/<tr[^>]*>/i', $content ) ) {
			return $content;
		}

		// Find and modify the last tr in one go.
		$modified_html = preg_replace_callback(
			'/(.*)(<tr[^>]*>.*?<\/tr>)(?!.*<tr)/is',
			static function( $matches ) {
				$before_last_tr = $matches[1];
				$last_tr        = $matches[2];

				// Remove border-bottom from all td elements in this tr.
				$modified_tr = preg_replace_callback(
					'/(<td[^>]*style\s*=\s*["\'])([^"\']*?)(["\'][^>]*>)/i',
					static function( $td_match ) {
						$start = $td_match[1];
						$style = $td_match[2];

						// Remove ONLY border-bottom (not border-bottom-width, etc.).
						$style = preg_replace( '/\s*border-bottom\s*:[^;]*;?/i', '', $style );
						$style = is_string( $style ) ? $style : '';

						// Clean up multiple semicolons and trim.
						$style = preg_replace( '/;+/', ';', $style );
						$style = is_string( $style ) ? trim( $style, '; ' ) : '';

						$end = $td_match[3];
						return $start . $style . $end;
					},
					$last_tr
				);

				return $before_last_tr . $modified_tr;
			},
			$content
		);

		return is_string( $modified_html ) ? $modified_html : '';
	}

	/**
	 * Process the {all_data} smart tag in the email body.
	 *
	 * Replaces {all_data} with a formatted HTML table of all submission fields.
	 *
	 * @param array<mixed> $fields Submission fields.
	 * @param string       $email_body Email body content.
	 * @since x.x.x
	 * @return string Email body with {all_data} replaced.
	 */
	private function process_all_data_tag( $fields, $email_body ) {
		if ( strpos( $email_body, '{all_data}' ) === false ) {
			return $email_body;
		}

		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
		$td_style        = 'font-weight: 500;font-size: 14px;line-height: 20px;padding: 12px;text-align:left;word-break: break-word;border-bottom: 1px solid #E5E7EB;';

		ob_start();

		?>
		<table class="srfm_all_data" width="536" cellpadding="0" cellspacing="0" style="border: 1px solid #dce0e6;border-radius: 6px;margin-top: 25px;margin-bottom: 25px;overflow:hidden;">
			<style>
				.srfm_all_data tr:last-child td {
					border: none !important;
				}
			</style>
			<tbody>
				<?php
				foreach ( $fields as $field_name => $value ) {
					$values_array = [];
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

					$field_block_name = Helper::get_block_name_from_field( $field_name );

					/**
					 * Fires before rendering a field in the all data section of emails.
					 *
					 * This action allows other packages (like Pro, Business) to process and render fields
					 * with custom data structures that the core plugin cannot handle. Since the core plugin
					 * does not know the structure of data from other packages, this action provides a way
					 * for those packages to properly process and display their field data.
					 *
					 * @since 1.11.0
					 *
					 * @param array $field_data Field data containing:
					 *                         'value'           => mixed  The field value
					 *                         'label'           => string The field name/key
					 *                         'block_name'      => string The block type identifier
					 *                         'processed_label' => string The decrypted human readable label
					 */
					do_action(
						'srfm_before_processing_all_data_field',
						[
							'value'           => $value,
							'label'           => $field_name,
							'block_name'      => $field_block_name,
							'processed_label' => $field_label,
						]
					);

					/**
					 * Filters whether to add a field row in the all data section.
					 *
					 * This filter allows skipping rows for fields that cannot be processed with the
					 * core plugin's structure. Fields from other packages may have complex data structures
					 * that could cause fatal errors if processed normally. Those packages can use the
					 * 'srfm_before_processing_all_data_field' action to render their fields and return false here
					 * to prevent the core plugin from attempting to process them.
					 *
					 * @since 1.11.0
					 *
					 * @param bool  $should_add_field_row Whether to add the field row. Default true.
					 * @param array $field_data          Field data containing:
					 *                                   'value'      => mixed  The field value
					 *                                   'field_name' => string The field name/key
					 *                                   'block_name' => string The block type identifier
					 *
					 * @return bool Whether to add the field row to the table.
					 */
					$should_add_field_row = apply_filters(
						'srfm_all_data_field_row',
						true,
						[
							'value'      => $value,
							'field_name' => $field_name,
							'block_name' => $field_block_name,
						]
					);

					if ( true !== $should_add_field_row ) {
						continue;
					}

					$is_array_value = is_array( $value );

					if ( $is_array_value ) {
						$values_array = array_filter(
							$value,
							static function( $input_value ) {
								return ! empty( Helper::get_string_value( $input_value ) );
							}
						);
					} else {
						$value = Helper::get_string_value( $value );
					}

					// Skip if both $value and $values_array are empty.
					if ( empty( $value ) || ( $is_array_value && empty( $values_array ) ) ) {
						continue;
					}

					?>
				<tr class="field-label">
					<th style="<?php echo esc_attr( $td_style ); ?>color: #1E293B;background-color: #F1F5F9;">
						<strong><?php echo wp_kses_post( html_entity_decode( $field_label ) ); ?>:</strong>
					</th>
				</tr>
				<tr class="field-value">
					<td style="<?php echo esc_attr( $td_style ); ?>color: #475569;">
					<?php
					if ( ! empty( $values_array ) && is_array( $values_array ) ) {
						$clean_values = [];

						foreach ( $values_array as $value ) {
							$value = Helper::get_string_value( $value );
							if ( ! empty( $value ) && is_string( $value ) ) {
								$clean_values[] = $value;
							}
						}

						if ( count( $clean_values ) === 1 ) {
							$value         = reset( $clean_values );
							$decoded_value = urldecode( $value );
							?>
							<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $decoded_value ); ?>">
								<?php echo esc_html( $decoded_value ); ?>
							</a>
							<?php
						} elseif ( count( $clean_values ) > 1 ) {
							?>
							<ol style="list-style: decimal; padding-left: 20px; margin: 0;">
								<?php foreach ( $clean_values as $value ) { ?>
									<?php $decoded_value = urldecode( $value ); ?>
									<li style="margin-bottom: 6px;">
										<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $decoded_value ); ?>">
											<?php echo esc_html( $decoded_value ); ?>
										</a>
									</li>
								<?php } ?>
							</ol>
							<?php
						}
					} elseif ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
						ob_start();
						?>
							<a target="_blank" href="<?php echo esc_url( $value ); ?>">
								<?php echo esc_html( esc_url( $value ) ); ?>
							</a>
						<?php
						$template_html = ob_get_clean();
						// Apply filter.
						$render_url = apply_filters(
							'srfm_email_template_render_url',
							$template_html,
							[
								'block_type'            => $field_name,
								'submission_item_value' => $value,
							]
						);
						// Validate fallback.
						if ( empty( $render_url ) ) {
							ob_start();
							?>
								<a target="_blank" href="<?php echo esc_url( $value ); ?>">
									<?php echo esc_html( esc_url( $value ) ); ?>
								</a>
							<?php
							$render_url = ob_get_clean();
						}

						echo wp_kses(
							Helper::get_string_value( $render_url ),
							[
								'a'   => [
									'href'   => [],
									'target' => [],
								],
								'img' => [
									'src'   => [],
									'alt'   => [],
									'width' => [],
								],
							]
						);

					} else {
						if ( strpos( Helper::get_string_value( $field_name ), 'srfm-input-multi-choice' ) !== false && strpos( Helper::get_string_value( $value ), '|' ) !== false ) {
							$options = array_map( 'trim', explode( '|', Helper::get_string_value( $value ) ) );
							foreach ( $options as $index => $option ) {
								$border_style = $index < count( $options ) - 1 ? 'border-bottom:1px solid #E5E7EB;padding-bottom:4px;margin-bottom:4px;' : '';
								echo '<div style="' . esc_attr( $border_style ) . '">' . esc_html( $option ) . '</div>';
							}
							continue;
						}

						if ( is_string( $value ) ) {
							if ( false !== strpos( $field_name, 'srfm-textarea' ) ) {
								echo Helper::esc_textarea( html_entity_decode( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- using a custom escaping function.
							} else {
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
		$current_table_data = $this->remove_border_from_last_tr_td_table( $current_table_data );

		return str_replace( '{all_data}', $current_table_data, $email_body );
	}
}
