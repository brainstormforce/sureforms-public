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

		$blocks = $this->prepare_blocks_for_editing();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Entry #<?php echo esc_attr( $this->entry_id ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( "admin.php?page=sureforms_entries&entry_id={$this->entry_id}&view=details" ) ); ?>"> <!-- check for nonce, referrer, etc. -->
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container">
							<?php $this->render_entry_notes(); ?>
							<?php $this->render_submission_info( $form_name, $entry_status, $submitted_on ); ?>
							<?php Entries_List_Table::get_instance()->display_bulk_resend_notification_button( $this->entry['form_id'], [ $this->entry_id ] ); ?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php $this->render_form_data( $meta_data, $excluded_fields ); ?>
						</div>
						<div id="postbox-container-3" class="postbox-container">
							<?php $this->render_entry_logs(); ?>
						</div>
					</div><!-- /post-body -->
					<br class="clear">
				</div>
				<!-- /poststuff -->
				<dialog id="srfm-edit-entry-modal">
					<div class="srfm-edit-entry-modal-container">
						<div class="edit-entry-header">
							<h2><?php esc_html_e( 'Edit Entry Data', 'sureforms' ); ?></h2>
						</div>
						<div class="edit-entry-content">
							<?php
							if ( ! empty( $blocks ) && is_array( $blocks ) ) {
								foreach ( $blocks as $block ) {
									echo render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to print the form blocks markup.
								}
							}
							?>
						</div>
						<div class="edit-entry-footer">
							<?php wp_nonce_field( 'srfm-edit-entry-' . $this->entry_id, 'srfm-edit-entry-nonce' ); ?>
							<input type="hidden" name="entry_id" value="<?php echo esc_attr( $this->entry_id ); ?>">
							<input class="button button-primary srfm-update-entry-btn" type="submit" value="<?php esc_attr_e( 'Update Entry', 'sureforms' ); ?>">
							<button type="button" class="button srfm-cancel-entry-btn"><?php esc_html_e( 'Cancel', 'sureforms' ); ?></button>
						</div>
					</div>
				</dialog>
			</form>
		</div>
		<?php
	}

	/**
	 * Prints entry note item markup.
	 *
	 * @param array $note Single note array.
	 * @since x.x.x
	 * @return void
	 */
	public static function entry_note_item_markup( $note ) {
		$submitted_by_user = get_user_by( 'ID', absint( $note['submitted_by'] ) )->display_name;
		?>
		<div class="entry-note-item">
			<strong class="entry-log-title">
				<?php
					/* translators: Here %s means the users display name. */
					printf( esc_html__( 'Submitted by %s', 'sureforms' ), esc_html( $submitted_by_user ) );
				?>
			</strong>
			<br/>
			<small><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $note['timestamp'] ) ); ?></small>
			<?php echo wp_kses_post( wpautop( $note['note'] ) ); ?>
		</div>
		<?php
	}

	/**
	 * Provides table markup for the entry logs.
	 *
	 * @param array<mixed> $entry_logs Entry logs stored in the database.
	 * @since x.x.x
	 * @return void
	 */
	public static function entry_logs_table_markup( $entry_logs ) {
		?>
		<table class="entry-logs-table">
			<tbody>
				<?php if ( ! empty( $entry_logs ) ) { ?>
						<?php foreach ( $entry_logs as $key => $log ) { ?>
							<tr>
								<td class="entry-log-container">
									<div class="entry-log">
										<div class="info-wrap">
											<h4 class="entry-log-title">
												<?php echo esc_html( $log['title'] ); ?>
												<?php echo esc_html( gmdate( '\a\t Y-m-d H:i:s', $log['timestamp'] ) ); ?>
											</h4>
											<div class="entry-log-messages">
											<?php foreach ( $log['messages'] as $message ) { ?>
												<p><?php echo wp_kses_post( $message ); ?></p>
											<?php } ?>
											</div>
										</div>
										<button type="button" class="btn-delete-log" data-log-key="<?php echo esc_attr( $key ); ?>">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M12.2837 7.5L11.9952 15M8.00481 15L7.71635 7.5M16.023 4.82547C16.308 4.86851 16.592 4.91456 16.875 4.96358M16.023 4.82547L15.1332 16.3938C15.058 17.3707 14.2434 18.125 13.2637 18.125H6.73631C5.75655 18.125 4.94198 17.3707 4.86683 16.3938L3.97696 4.82547M16.023 4.82547C15.0677 4.6812 14.1013 4.57071 13.125 4.49527M3.125 4.96358C3.40798 4.91456 3.69198 4.86851 3.97696 4.82547M3.97696 4.82547C4.93231 4.6812 5.89874 4.57071 6.875 4.49527M13.125 4.49527V3.73182C13.125 2.74902 12.3661 1.92853 11.3838 1.8971C10.9244 1.8824 10.463 1.875 10 1.875C9.53696 1.875 9.07565 1.8824 8.61618 1.8971C7.63388 1.92853 6.875 2.74902 6.875 3.73182V4.49527M13.125 4.49527C12.0938 4.41558 11.0516 4.375 10 4.375C8.94836 4.375 7.9062 4.41558 6.875 4.49527" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</button>
									</div>
								</td>
							</tr>
						<?php } ?>
				<?php } else { ?>
					<p><?php esc_html_e( 'No logs found for this entry.', 'sureforms' ); ?></p>
				<?php } ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Prepares the form blocks for entry editing mode.
	 *
	 * @return array
	 */
	protected function prepare_blocks_for_editing() {
		$parsed = parse_blocks( get_post( absint( $this->entry['form_id'] ) )->post_content );
		$blocks = array_map(
			function( $block ) {
				if ( ! $block['blockName'] ) {
					return null;
				}

				$meta_data = $this->entry['form_data'];

				$attrs = [];

				foreach ( $meta_data as $field_name => $value ) {
					if ( false !== strpos( $field_name, "-{$block['attrs']['block_id']}-" ) ) {
						$attrs = [
							'fieldName'    => $field_name,
							'defaultValue' => $value,
						];
						break;
					}
				}

				if ( empty( $attrs ) ) {
					return null;
				}

				$block['attrs']['entryID']      = $this->entry_id;
				$block['attrs']['isEditing']    = true;
				$block['attrs']['fieldName']    = $attrs['fieldName'];
				$block['attrs']['defaultValue'] = $attrs['defaultValue'];

				return $block;
			},
			_flatten_blocks( $parsed )
		);

		return array_filter( $blocks, 'is_array' );
	}

	/**
	 * Render the entry notes for the specific entry.
	 *
	 * @since x.x.x
	 */
	private function render_entry_notes() {
		$notes = $this->entry['notes'];
		?>
		<div id="submitdiv" class="postbox entry-notes">
			<div class="in-progress-overlay">
				<svg width="45" height="45" fill="#2271b1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z" class="spinner_z9k8"/></svg>
			</div>
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Entry Notes', 'sureforms' ); ?></h2>
				<button type="button" id="srfm-add-entry-note" class="srfm-add-entry-note-button <?php echo empty( $notes ) ? 'hidden' : ''; ?>">
					<?php esc_html_e( 'Add Note', 'sureforms' ); ?>
					<svg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M8 3.33594V12.6693' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/><path d='M3.33337 8H12.6667' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/></svg>
				</button>
			</div>
			<div class="inside">
				<div class="srfm-entry-note-wrapper">
					<div class="entry-notes-container">
						<?php // We will populate this div using AJAX. ?>
					</div>
					<div class="add-notes-field <?php echo ! empty( $notes ) ? 'hidden' : ''; ?>">
						<textarea id="srfm-entry-note" rows="5"></textarea>
						<button type="submit" class="button" data-entry-id=<?php echo esc_html( $this->entry_id ); ?>><?php esc_html_e( 'Submit Note', 'sureforms' ); ?></button>
					</div>
				</div>
			</div>

			<div class="entry-navigation-btn-wrapper">
				<button type="button" class="entry-navigation-btn entry-notes-nav-btn" data-type="prev">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M10 12L6 8L10 4" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span>
						<?php esc_html_e( 'Previous', 'sureforms' ); ?>
					</span>
				</button>
				<button type="button" class="entry-navigation-btn entry-notes-nav-btn" data-type="next">
					<span>
						<?php esc_html_e( 'Next', 'sureforms' ); ?>
					</span>
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M6 12L10 8L6 4" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
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
				<button class="button srfm-edit-entry" type="button">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M11.2411 2.99111L12.3661 1.86612C12.8543 1.37796 13.6457 1.37796 14.1339 1.86612C14.622 2.35427 14.622 3.14573 14.1339 3.63388L7.05479 10.713C6.70234 11.0654 6.26762 11.3245 5.78993 11.4668L4 12L4.53319 10.2101C4.67548 9.73239 4.93456 9.29767 5.28701 8.94522L11.2411 2.99111ZM11.2411 2.99111L13 4.74999M12 9.33333V12.5C12 13.3284 11.3284 14 10.5 14H3.5C2.67157 14 2 13.3284 2 12.5V5.49999C2 4.67157 2.67157 3.99999 3.5 3.99999H6.66667" stroke="#2271b1" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php esc_html_e( 'Edit', 'sureforms' ); ?>
				</button>
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
	 * @since 0.0.13
	 * @return void
	 */
	private function render_entry_logs() {
		?>
		<div id="sureform_entry_meta" class="postbox srfm-entry-logs">
			<div class="in-progress-overlay">
				<svg width="45" height="45" fill="#2271b1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z" class="spinner_z9k8"/></svg>
			</div>
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Entry Logs', 'sureforms' ); ?></h2>
				<!-- <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-higher-description">Move Form Data box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-lower-description">Move Form Data box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Form Data</span><span class="toggle-indicator" aria-hidden="true"></span></button></div> -->
			</div>
			<div class="inside">
				<?php // We will populate this div using AJAX. ?>
			</div>
			<div class="entry-navigation-btn-wrapper">
				<button type="button" class="entry-navigation-btn entry-logs-navigation-btn" data-type="prev">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M10 12L6 8L10 4" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span>
						<?php esc_html_e( 'Previous', 'sureforms' ); ?>
					</span>
				</button>
				<button type="button" class="entry-navigation-btn entry-logs-navigation-btn" data-type="next">
					<span>
						<?php esc_html_e( 'Next', 'sureforms' ); ?>
					</span>
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M6 12L10 8L6 4" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>
		<?php
	}
}
