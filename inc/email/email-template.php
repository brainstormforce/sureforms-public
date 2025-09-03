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
		$message         = $this->get_header();
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];

		$td_style = 'font-weight: 500;font-size: 14px;line-height: 20px;padding: 12px;text-align:left;word-break: break-word;border-bottom: 1px solid #E5E7EB;';

		$message .= $email_body;
		if ( strpos( $email_body, '{all_data}' ) !== false ) {

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

						if ( is_array( $value ) ) {
							$values_array = $value;
						} else {
							$value = Helper::get_string_value( $value );
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
						} elseif ( ! empty( $value ) && is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
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
									'block_type' => $field_name,
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
			$message                = str_replace( '{all_data}', $current_table_data, $message );
		}
		return $message . $this->get_footer();
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
}
