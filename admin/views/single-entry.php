<?php
/**
 * SureForms Single Entries Page.
 *
 * @since 0.0.13
 * @package sureforms.
 */

namespace SRFM\Admin\Views;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single entry page.
 *
 * @since 0.0.13
 */
class Single_Entry {
	/**
	 * Stores the entry ID.
	 *
	 * @var string|null $entry_id ID for the specific entry.
	 * @since 0.0.13
	 */
	private $entry_id;

	/**
	 * Stores the entry data for the specified entry ID.
	 *
	 * @var array<mixed>|null $entry Entry data for the specified entry ID.
	 * @since 0.0.13
	 */
	private $entry;

	/**
	 * Initialize the properties.
	 *
	 * @since 0.0.13
	 */
	public function __construct() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) {
			return;
		}
		$this->entry_id = isset( $_GET['entry_id'] ) ? intval( sanitize_text_field( wp_unslash( $_GET['entry_id'] ) ) ) : null;
		$this->entry    = $this->entry_id ? Entries::get( $this->entry_id ) : null;
	}

	/**
	 * Render the single entry page if an entry is found.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function render() {
		if ( ! $this->entry ) {
			return;
		}
		$entry_status = $this->entry['status'];
		$submitted_on = gmdate( 'Y/m/d \a\t g:i a', strtotime( $this->entry['created_at'] ) );
		// Translators: %d is the form ID.
		$form_name       = ! empty( get_the_title( $this->entry['form_id'] ) ) ? get_the_title( $this->entry['form_id'] ) : sprintf( esc_html__( 'SureForms Form #%d', 'sureforms' ), intval( $this->entry['form_id'] ) );
		$meta_data       = $this->entry['form_data'];
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
		$entry_logs      = $this->entry['logs'];
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'sureforms' ); ?></h1>
			<form method="get" id="get"> <!-- check for nonce, referrer, etc. -->
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div id="titlediv">
								<div id="titlewrap">
									<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Add title', 'sureforms' ); ?></label>
									<input type="text" name="post_title" size="30" value="Entry #<?php echo esc_attr( $this->entry_id ); ?>" id="title" spellcheck="true" autocomplete="off" readonly>
								</div>
							</div><!-- /titlediv -->
						</div><!-- /post-body-content -->
						<div id="postbox-container-1" class="postbox-container">
							<?php $this->render_submission_info( $form_name, $entry_status, $submitted_on ); ?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php $this->render_form_data( $meta_data, $excluded_fields ); ?>
						</div>
						<div id="postbox-container-3" class="postbox-container">
							<?php $this->render_entry_logs( $entry_logs ); ?>
						</div>
					</div><!-- /post-body -->
					<br class="clear">
				</div><!-- /poststuff -->
			</form>
		</div>
		<?php
	}

	/**
	 * Render the submission information for a specific entry.
	 *
	 * @param string $form_name The form title/name.
	 * @param string $entry_status The entry status (read/unread).
	 * @param string $submitted_on The submission date.
	 * @since 0.0.13
	 * @return void
	 */
	private function render_submission_info( $form_name, $entry_status, $submitted_on ) {
		$mark_as_unread_url = add_query_arg( 'action', 'unread' );
		$user_id            = Helper::get_integer_value( $this->entry['user_id'] );
		$user_info          = 0 !== $user_id ? get_userdata( $user_id ) : null;
		$user_name          = $user_info ? $user_info->display_name : '';
		$user_profile_url   = $user_info ? get_author_posts_url( $user_id ) : '';
		?>
		<div id="sureform_form_name_meta" class="postbox ">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Submission Info', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table style="border-collapse: separate; border-spacing: 5px 5px;">
					<tbody>
						<!-- TODO: Add Type and User info. -->
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Entry:', 'sureforms' ); ?></b></td>
							<td>#<?php echo esc_attr( $this->entry_id ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Form Name:', 'sureforms' ); ?></b></td>
							<td><a target="_blank" rel="noopener" href="<?php the_permalink( $this->entry['form_id'] ); ?>"><?php echo esc_attr( $form_name ); ?></a></td>
						</tr>
						<?php if ( ! empty( $this->entry['submission_info']['user_ip'] ) ) { ?>
							<tr style="margin-bottom: 10px;">
								<td><b><?php esc_html_e( 'User IP:', 'sureforms' ); ?></b></td>
								<td><a target="_blank" rel="noopener" href="https://ipinfo.io/"><?php echo esc_attr( $this->entry['submission_info']['user_ip'] ); ?></a></td>
							</tr>
						<?php } ?>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Browser:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['browser_name'] ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Device:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['device_name'] ); ?></td>
						</tr>
						<?php if ( 0 !== $user_id ) { ?>
							<tr style="margin-bottom: 10px;">
								<td><b><?php esc_html_e( 'User:', 'sureforms' ); ?></b></td>
								<td><a target="_blank" rel="noopener" href="<?php echo esc_url( $user_profile_url ); ?>"><?php echo esc_attr( $user_name ); ?></a></td>
							</tr>
						<?php } ?>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Status:', 'sureforms' ); ?></b></td>
							<td>
								<span style="text-transform: capitalize;">
									<?php echo esc_attr( $entry_status ); ?>
								</span>
								<?php if ( 'read' === $entry_status ) { ?>
									<span> | <a href="<?php echo esc_url( $mark_as_unread_url ); ?>" id="srfm-entry-mark-unread" style="font-size: 12px;"><?php esc_html_e( 'Mark as Unread', 'sureforms' ); ?></a></span>
								<?php } ?>
							</td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Submitted On:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $submitted_on ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the form data for a specific entry.
	 *
	 * @param array<mixed>  $meta_data The form meta data.
	 * @param array<string> $excluded_fields Fields to exlude from display.
	 * @since 0.0.13
	 * @return void
	 */
	private function render_form_data( $meta_data, $excluded_fields ) {
		?>
		<div id="sureform_entry_meta" class="postbox">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Form Data', 'sureforms' ); ?></h2>
				<!-- <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-higher-description">Move Form Data box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-lower-description">Move Form Data box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Form Data</span><span class="toggle-indicator" aria-hidden="true"></span></button></div> -->
			</div>
			<div class="inside">
				<table class="widefat striped">
					<tbody>
						<tr>
							<th><b><?php esc_html_e( 'Fields', 'sureforms' ); ?></b></th>
							<th><b><?php esc_html_e( 'Values', 'sureforms' ); ?></b></th>
						</tr>
					<?php
					foreach ( $meta_data as $field_name => $value ) {
						if ( in_array( $field_name, $excluded_fields, true ) ) {
							continue;
						}
						if ( false === str_contains( $field_name, '-lbl-' ) ) {
							continue;
						}
						$label = explode( '-lbl-', $field_name )[1];
						// Getting the encrypted label. we are removing the block slug here.
						$label = explode( '-', $label )[0];
						?>
						<tr>
							<td><b><?php echo $label ? wp_kses_post( html_entity_decode( Helper::decrypt( $label ) ) ) : ''; ?></b></td>
							<?php
							if ( false !== strpos( $field_name, 'srfm-upload' ) ) {
								?>
										<style>
											.file-cards-container {
												display: flex;
												flex-wrap: wrap;
												gap: 10px;
											}
											.file-card {
												border: 1px solid #ddd;
												border-radius: 4px;
												padding: 10px;
												width: 100px; /* Reduced width */
												text-align: center;
												background: #f9f9f9;
												font-size: 12px; /* Reduced font size for smaller cards */
											}
											.file-card-image img {
												max-width: 80px; /* Reduced max width */
												max-height: 80px; /* Reduced max height */
												object-fit: cover;
											}
											.file-card-icon {
												font-size: 24px; /* Reduced icon size */
												margin-bottom: 5px;
											}
											.file-card-details {
												margin-bottom: 5px;
												font-weight: bold;
											}
											.file-card-url a {
												color: #007bff;
												text-decoration: none;
												font-size: 12px; /* Reduced font size */
											}
											.file-card-url a:hover {
												text-decoration: underline;
											}
										</style>
										<td>
											<div class="file-cards-container">
											<?php
											$upload_values = $value;
											if ( ! empty( $upload_values ) && is_array( $upload_values ) ) {
												foreach ( $upload_values as $file_url ) {
													$file_url = Helper::get_string_value( $file_url );
													if ( ! empty( $file_url ) ) {
														$file_type = pathinfo( $file_url, PATHINFO_EXTENSION );
														$is_image  = in_array( $file_type, [ 'gif', 'png', 'bmp', 'jpg', 'jpeg', 'svg' ], true );
														?>
																<div class="file-card">
															<?php if ( $is_image ) { ?>
																		<div class="file-card-image">
																			<a target="_blank" href="<?php echo esc_attr( urldecode( $file_url ) ); ?>">
																				<img src="<?php echo esc_attr( urldecode( $file_url ) ); ?>" alt="<?php esc_attr_e( 'Image', 'sureforms' ); ?>" />
																			</a>
																		</div>
															<?php } else { ?>
																		<div class="file-card-icon">
																			<?php // Display a file icon for non-image files. ?>
																			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.333V4.667a1.333 1.333 0 011.333-1.333h13.334a1.333 1.333 0 011.333 1.333v11.666a1.333 1.333 0 01-1.333 1.333H5.333A1.333 1.333 0 014 16.333zm8-8h2v6h-2v-6zm-2 8h6v2H10v-2zm-6-6h4v6H4v-6zm0-4h16v2H4V6z"/></svg>
																		</div>
																		<div class="file-card-details">
																			<span><?php echo esc_html( strtoupper( $file_type ) ); ?></span>
																		</div>
															<?php } ?>
																	<div class="file-card-url">
																		<a target="_blank" href="<?php echo esc_attr( urldecode( $file_url ) ); ?>"><?php echo esc_html__( 'Open', 'sureforms' ); ?></a>
																	</div>
																</div>
															<?php
													}
												}
											}
											?>
											</div>
										</td>
							<?php } elseif ( false !== strpos( $field_name, 'srfm-url' ) ) { ?>
									<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_url( $value ); ?></a></td>
							<?php } else { ?>
									<td><?php echo false !== strpos( $value, PHP_EOL ) ? wp_kses_post( wpautop( $value ) ) : wp_kses_post( $value ); ?></td>
							<?php } ?>
							</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the entry logs for a specific entry.
	 *
	 * @param array<mixed> $entry_logs Entry logs stored in the database.
	 * @since 0.0.13
	 * @return void
	 */
	private function render_entry_logs( $entry_logs ) {
		?>
		<div id="sureform_entry_meta" class="postbox">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Entry Logs', 'sureforms' ); ?></h2>
				<!-- <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-higher-description">Move Form Data box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-lower-description">Move Form Data box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Form Data</span><span class="toggle-indicator" aria-hidden="true"></span></button></div> -->
			</div>
			<div class="inside">
				<table class="widefat striped entry-logs-table">
					<tbody>
						<?php if ( ! empty( $entry_logs ) ) { ?>
								<?php foreach ( $entry_logs as $log ) { ?>
									<tr>
										<td class="entry-log-container">
											<div class="entry-log">
												<h4 class="entry-log-title">
													<?php echo esc_html( $log['title'] ); ?>
													<?php echo esc_html( gmdate( '\a\t Y-m-d H:i:s', $log['timestamp'] ) ); ?>
												</h4>
												<div class="entry-log-messages">
												<?php foreach ( $log['messages'] as $message ) { ?>
													<p><?php echo esc_html( $message ); ?></p>
												<?php } ?>
												</div>
											</div>
										</td>
									</tr>
								<?php } ?>
						<?php } else { ?>
							<p><?php esc_html_e( 'No logs found for this entry.', 'sureforms' ); ?></p>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
