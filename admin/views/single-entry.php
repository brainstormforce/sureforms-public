<?php
/**
 * SureForms Single Entries Page.
 *
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
 * @since x.x.x
 */
class Single_Entry {
	/**
	 * Stores the entry ID.
	 *
	 * @var string|null $entry_id ID for the specific entry.
	 * @since x.x.x
	 */
	private $entry_id;

	/**
	 * Stores the entry data for the specified entry ID.
	 *
	 * @var array<mixed>|null $entry Entry data for the specified entry ID.
	 * @since x.x.x
	 */
	private $entry;

	/**
	 * Initialize the properties.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		if ( isset( $_GET['srfm_entries_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_GET['srfm_entries_nonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$this->entry_id = isset( $_GET['entry_id'] ) ? intval( sanitize_key( wp_unslash( $_GET['entry_id'] ) ) ) : null;
		$this->entry    = $this->entry_id ? Entries::get( $this->entry_id ) : null;
	}

	/**
	 * Render the single entry page if an entry is found.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function render() {
		if ( ! $this->entry ) {
			return;
		}
		$entry_status    = $this->entry['status'];
		$submitted_on    = gmdate( 'Y/m/d \a\t g:i a', strtotime( $this->entry['created_at'] ) );
		$form_name       = ! empty( get_the_title( $this->entry['form_id'] ) ) ? get_the_title( $this->entry['form_id'] ) : 'SureForms Form #' . intval( $this->entry['form_id'] );
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
							<?php $this->render_entry_notes(); ?>
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
	 * Render the entry notes for the specific entry.
	 *
	 * @since x.x.x
	 */
	private function render_entry_notes() {
		?>
		<div id="submitdiv" class="postbox ">
			<div class="postbox-header">
				<!-- Removed class "hndle ui-sortable-handle" from the below h2 tag to prevent the draggable cursor. --> 
				<h2><?php esc_html_e( 'Entry Notes', 'sureforms' ); ?></h2>
				<button id="srfm-add-entry-note" class="srfm-add-entry-note-button">
				<?php esc_html_e( 'Add Note', 'sureforms' ); ?>
					<svg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M8 3.33594V12.6693' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/><path d='M3.33337 8H12.6667' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/></svg>
				</button>
			</div>
			<div class="inside">
				<div class="srfm-entry-note-wrapper">
					<!-- TODO: Proper implementation for the entry notes section. -->
					<textarea id="srfm-entry-note" name="srfm_entry_note" rows="5"></textarea>
					<button type="submit" id="srfm-add-note" data-entry-id=<?php echo esc_html( $this->entry_id ); ?>><?php esc_html_e( 'Submit Note', 'sureforms' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the submission information for a specific entry.
	 *
	 * @param string $form_name The form title/name.
	 * @param string $entry_status The entry status (read/unread).
	 * @param string $submitted_on The submission date.
	 * @since x.x.x
	 */
	private function render_submission_info( $form_name, $entry_status, $submitted_on ) {
		?>
		<div id="sureform_form_name_meta" class="postbox ">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Submission Info', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table style="border-collapse: separate; border-spacing: 5px 5px;">
					<tbody>
						<!-- TODO :: Add Type and User info and data for URL. -->
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Entry:', 'sureforms' ); ?></b></td>
							<td>#<?php echo esc_attr( $this->entry_id ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Form Name:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $form_name ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'User IP:', 'sureforms' ); ?></b></td>
							<td><a target="_blank" rel="noopener" href="https://ipinfo.io/"><?php echo esc_attr( $this->entry['submission_info']['user_ip'] ); ?></a></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'URL:', 'sureforms' ); ?></b></td>
							<td><a target="_blank" rel="noopener" href="https://ipinfo.io/"></a></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Browser:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['browser_name'] ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Device:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['device_name'] ); ?></td>
						</tr>
						<tr style="margin-bottom: 10px;">
							<td><b><?php esc_html_e( 'Status:', 'sureforms' ); ?></b></td>
							<td>
								<span style="text-transform: capitalize;">
									<?php echo esc_attr( $entry_status ); ?>
								</span>
								<span> | <a href="#" id="erfm-entry-mark-unread" style="font-size: 12px;"><?php esc_html_e( 'Mark as Unread', 'sureforms' ); ?></a></span>
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
	 * @since x.x.x
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
					foreach ( $meta_data as $field_name => $value ) :
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
							<td><b><?php echo $label ? esc_html( Helper::decrypt( $label ) ) : ''; ?></b></td>
							<?php
							if ( false !== strpos( $field_name, 'srfm-upload' ) ) :
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
															<?php if ( $is_image ) : ?>
																		<div class="file-card-image">
																			<a target="_blank" href="<?php echo esc_attr( urldecode( $file_url ) ); ?>">
																				<img src="<?php echo esc_attr( urldecode( $file_url ) ); ?>" alt="img" />
																			</a>
																		</div>
																	<?php else : ?>
																		<div class="file-card-icon">
																			<?php // Display a file icon for non-image files. ?>
																			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.333V4.667a1.333 1.333 0 011.333-1.333h13.334a1.333 1.333 0 011.333 1.333v11.666a1.333 1.333 0 01-1.333 1.333H5.333A1.333 1.333 0 014 16.333zm8-8h2v6h-2v-6zm-2 8h6v2H10v-2zm-6-6h4v6H4v-6zm0-4h16v2H4V6z"/></svg>
																		</div>
																		<div class="file-card-details">
																			<span><?php echo esc_html( strtoupper( $file_type ) ); ?></span>
																		</div>
																	<?php endif; ?>
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
								<?php elseif ( false !== strpos( $field_name, 'srfm-url' ) ) : ?>
									<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_url( $value ); ?></a></td>
								<?php else : ?>
									<td><?php echo false !== strpos( $value, PHP_EOL ) ? wp_kses_post( wpautop( $value ) ) : wp_kses_post( $value ); ?></td>
								<?php endif; ?>
							</tr>
							<?php endforeach; ?>
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
	 * @since x.x.x
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
						<?php if ( ! empty( $entry_logs ) ) : ?>
								<?php foreach ( $entry_logs as $index => $log ) : ?>
									<tr>
										<td class="entry-log-container">
											<div class="entry-log">
												<h4 class="entry-log-title">
													<?php echo esc_html( $log['title'] ); ?>
													<?php echo esc_html( gmdate( '\a\t Y-m-d H:i:s', $log['timestamp'] ) ); ?>
													<div class="entry-log-delete">
														<button class="delete-log-btn" title="Delete Log" data-entry-id="<?php echo esc_attr( $this->entry_id ); ?>" data-log-index="<?php echo esc_attr( $index ); ?>">
															<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M12.2837 7.5L11.9952 15M8.00481 15L7.71635 7.5M16.023 4.82547C16.308 4.86851 16.592 4.91456 16.875 4.96358M16.023 4.82547L15.1332 16.3938C15.058 17.3707 14.2434 18.125 13.2637 18.125H6.73631C5.75655 18.125 4.94198 17.3707 4.86683 16.3938L3.97696 4.82547M16.023 4.82547C15.0677 4.6812 14.1013 4.57071 13.125 4.49527M3.125 4.96358C3.40798 4.91456 3.69198 4.86851 3.97696 4.82547M3.97696 4.82547C4.93231 4.6812 5.89874 4.57071 6.875 4.49527M13.125 4.49527V3.73182C13.125 2.74902 12.3661 1.92853 11.3838 1.8971C10.9244 1.8824 10.463 1.875 10 1.875C9.53696 1.875 9.07565 1.8824 8.61618 1.8971C7.63388 1.92853 6.875 2.74902 6.875 3.73182V4.49527M13.125 4.49527C12.0938 4.41558 11.0516 4.375 10 4.375C8.94836 4.375 7.9062 4.41558 6.875 4.49527" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
															</svg>
														</button>
													</div>
												</h4>
												<div class="entry-log-messages">
												<?php foreach ( $log['messages'] as $message ) : ?>
													<p><?php echo esc_html( $message ); ?></p>
												<?php endforeach; ?>
												</div>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No logs found for this entry.', 'sureforms' ); ?></p>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
?>
