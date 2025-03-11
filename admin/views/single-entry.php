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
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				/* Translators: %s is the entry id. */
				printf( esc_html__( 'Entry #%s', 'sureforms' ), esc_html( $this->entry_id ) );
				?>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( "admin.php?page=sureforms_entries&entry_id={$this->entry_id}&view=details" ) ); ?>"> <!-- check for nonce, referrer, etc. -->
				<?php
				/**
				 * Action hook right after entry form opening tag.
				 *
				 * @since 1.3.0
				 */
				do_action( 'srfm_after_entry_form_opening_tag', $this->entry, $this );
				?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container">
							<?php
							/**
							 * Action hook right before entry form opening tag.
							 *
							 * @since 1.3.0
							 */
							do_action( 'srfm_before_entry_submission_info', $this->entry, $this );
							$this->render_upsell_placeholder( 'notes' );

							$this->render_submission_info( $form_name, $entry_status, $submitted_on );

							/**
							 * Action hook right after entry form opening tag.
							 *
							 * @since 1.3.0
							 */
							do_action( 'srfm_after_entry_submission_info', $this->entry, $this );
							$this->render_upsell_placeholder( 'resend-notification' );
							?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php $this->render_form_data( $meta_data, $excluded_fields ); ?>
						</div>
						<div id="postbox-container-3" class="postbox-container">
							<?php $this->render_entry_logs( $this->entry['logs'] ); ?>
						</div>
					</div><!-- /post-body -->
					<br class="clear">
				</div>
				<!-- /poststuff -->

				<?php
				/**
				 * Action hook right before entry form closing tag.
				 *
				 * @since 1.3.0
				 */
				do_action( 'srfm_before_entry_form_closing_tag', $this->entry, $this );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add tooltip wrapper.
	 *
	 * @param string   $position Tooltip position.
	 * @param callable $element_cb Element callback.
	 * @param callable $tooltip_cb Tooltip callback.
	 * @since 1.3.0
	 * @return void
	 */
	protected function add_tooltip( $position, $element_cb, $tooltip_cb ) {
		$upsell_url = Helper::get_sureforms_website_url( 'pricing', [ 'utm_medium' => 'srfm_entries_management' ] );
		?>
		<div class="srfm-tooltip">
			<?php call_user_func( $element_cb ); ?>
			<div class="tooltip-wrap <?php echo esc_attr( $position ); ?>">
				<div class="tooltip-content">
					<div class="tooltip-text">
						<?php call_user_func( $tooltip_cb ); ?>
					</div>
					<a target="_blank" href="<?php echo esc_url( $upsell_url ); ?>"><?php esc_html_e( 'Upgrade', 'sureforms' ); ?></a>
				</div>
				<i></i>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the upsell placeholder.
	 *
	 * @param string $for The placeholder type.
	 * @since 1.3.0
	 * @return void
	 */
	protected function render_upsell_placeholder( $for ) {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			return;
		}

		switch ( $for ) {
			case 'edit-button':
				$position   = 'top';
				$element_cb = static function() {
					?>
					<button class="button button-link srfm-edit-entry" type="button">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M11.2411 2.99111L12.3661 1.86612C12.8543 1.37796 13.6457 1.37796 14.1339 1.86612C14.622 2.35427 14.622 3.14573 14.1339 3.63388L7.05479 10.713C6.70234 11.0654 6.26762 11.3245 5.78993 11.4668L4 12L4.53319 10.2101C4.67548 9.73239 4.93456 9.29767 5.28701 8.94522L11.2411 2.99111ZM11.2411 2.99111L13 4.74999M12 9.33333V12.5C12 13.3284 11.3284 14 10.5 14H3.5C2.67157 14 2 13.3284 2 12.5V5.49999C2 4.67157 2.67157 3.99999 3.5 3.99999H6.66667" stroke="#2271b1" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<?php esc_html_e( 'Edit', 'sureforms' ); ?>
					</button>
					<?php
				};
				$tooltip_cb = static function() {
					?>
					<h3><?php esc_html_e( 'Unlock Edit Form Entires', 'sureforms' ); ?></h3>
					<p><?php esc_html_e( 'With the SureForms Starter plan, you can easily edit your entries to suit your needs.', 'sureforms' ); ?></p>
					<?php
				};
				break;

			case 'resend-notification':
				$position   = 'left';
				$element_cb = static function() {
					?>
					<button type="button" class="button srfm-resend-notification-trigger-btn"><?php esc_html_e( 'Resend Notification', 'sureforms' ); ?></button>
					<?php
				};
				$tooltip_cb = static function() {
					?>
					<h3><?php esc_html_e( 'Unlock Resend Email Notification', 'sureforms' ); ?></h3>
					<p><?php esc_html_e( 'With the SureForms Starter plan, you can effortlessly resend email notifications, ensuring your important updates reach their recipients with ease.', 'sureforms' ); ?></p>
					<?php
				};
				break;

			case 'notes':
				$position   = 'left';
				$element_cb = static function() {
					?>
					<div id="submitdiv" class="postbox entry-notes">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Entry Notes', 'sureforms' ); ?></h2>
							<button type="button" id="srfm-add-entry-note" class="srfm-add-entry-note-button">
								<?php esc_html_e( 'Add Note', 'sureforms' ); ?>
								<svg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M8 3.33594V12.6693' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/><path d='M3.33337 8H12.6667' stroke='black' stroke-width='1.25' stroke-linecap='round' stroke-linejoin='round'/></svg>
							</button>
						</div>
						<div class="inside">
							<div class="srfm-entry-note-wrapper">
								<div class="entry-notes-container"></div>
								<div class="add-notes-field">
									<textarea disabled id="srfm-entry-note" rows="5"></textarea>
									<button id="srfm-add-note" type="button" class="button"><?php esc_html_e( 'Submit Note', 'sureforms' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					<?php
				};
				$tooltip_cb = static function() {
					?>
					<h3><?php esc_html_e( 'Unlock Add Note', 'sureforms' ); ?></h3>
					<p><?php esc_html_e( 'With the SureForms Starter plan, enhance your submitted form entries by adding personalized notes for better clarity and tracking.', 'sureforms' ); ?></p>
					<?php
				};
				break;

			default:
				// Do nothing.
				return;
		}

		$this->add_tooltip( $position, $element_cb, $tooltip_cb );
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
						<tr>
							<td><b><?php esc_html_e( 'Entry:', 'sureforms' ); ?></b></td>
							<td>#<?php echo esc_attr( $this->entry_id ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Form Name:', 'sureforms' ); ?></b></td>
							<td><a target="_blank" rel="noopener" href="<?php the_permalink( $this->entry['form_id'] ); ?>"><?php echo esc_attr( $form_name ); ?></a></td>
						</tr>
						<?php if ( ! empty( $this->entry['submission_info']['user_ip'] ) ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'User IP:', 'sureforms' ); ?></b></td>
								<td><a target="_blank" rel="noopener" href="https://ipinfo.io/"><?php echo esc_attr( $this->entry['submission_info']['user_ip'] ); ?></a></td>
							</tr>
						<?php } ?>
						<tr>
							<td><b><?php esc_html_e( 'Browser:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['browser_name'] ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Device:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $this->entry['submission_info']['device_name'] ); ?></td>
						</tr>
						<?php if ( 0 !== $user_id ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'User:', 'sureforms' ); ?></b></td>
								<td><a target="_blank" rel="noopener" href="<?php echo esc_url( $user_profile_url ); ?>"><?php echo esc_attr( $user_name ); ?></a></td>
							</tr>
						<?php } ?>
						<tr>
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
						<tr>
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
		<div id="sureform_entry_meta" class="postbox srfm-form-data">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Form Data', 'sureforms' ); ?></h2>
				<?php
				/**
				 * Action hook right after entry form opening tag.
				 *
				 * @since 1.3.0
				 */
				do_action( 'srfm_after_entry_postbox_title', $this->entry, $this );
				$this->render_upsell_placeholder( 'edit-button' );
				?>
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
							<td><b><?php echo $label ? wp_kses_post( html_entity_decode( Helper::decrypt( $label ) ) ) : ''; ?></b></td>
							<?php
							if ( false !== strpos( $field_name, 'srfm-upload' ) ) {
								?>
										<td>
											<div class="file-cards-container">
											<?php
											$upload_values = $value;
											if ( ! empty( $upload_values ) && is_array( $upload_values ) ) {
												foreach ( $upload_values as $file_url ) {
													$file_url = urldecode( Helper::get_string_value( $file_url ) );

													if ( ! empty( $file_url ) ) {
														if ( ! file_exists( Helper::convert_fileurl_to_filepath( $file_url ) ) ) {
															continue;
														}

														$file_type = pathinfo( $file_url, PATHINFO_EXTENSION );
														$is_image  = in_array( $file_type, [ 'gif', 'png', 'bmp', 'jpg', 'jpeg', 'svg' ], true );
														?>
																<div class="file-card" data-fileurl-hash="<?php echo esc_attr( md5( $file_url ) ); ?>">
															<?php if ( $is_image ) { ?>
																		<div class="file-card-image">
																			<a target="_blank" href="<?php echo esc_attr( $file_url ); ?>">
																				<img src="<?php echo esc_attr( $file_url ); ?>" alt="<?php esc_attr_e( 'Image', 'sureforms' ); ?>" />
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
																		<a target="_blank" href="<?php echo esc_attr( $file_url ); ?>"><?php echo esc_html__( 'Open', 'sureforms' ); ?></a>
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
							<?php } elseif ( apply_filters( 'srfm_entry_render_field_custom_value', false, $field_name ) ) { ?>
								<?php echo wp_kses_post( apply_filters( 'srfm_entry_custom_value', '', $value ) ); ?>
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
		ob_start();
		?>
		<div id="sureform_entry_meta" class="postbox srfm-entry-logs">
			<div class="postbox-header">
				<!-- Removed "hndle ui-sortable-handle" class from h2 to remove the draggable stylings. -->
				<h2><?php esc_html_e( 'Entry Logs', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table class="striped entry-logs-table">
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
													<p><?php echo wp_kses_post( $message ); ?></p>
												<?php } ?>
												</div>
											</div>
										</td>
									</tr>
								<?php } ?>
						<?php } else { ?>
							<p class="no-logs-found"><?php esc_html_e( 'No logs found for this entry.', 'sureforms' ); ?></p>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		$content = ob_get_clean();

		$allowed_tags = array_merge(
			wp_kses_allowed_html( 'post' ),
			[
				'svg'  => [
					'width'   => true,
					'height'  => true,
					'fill'    => true,
					'viewbox' => true,
					'xmlns'   => true,
				],
				'path' => [
					'd'               => true,
					'opacity'         => true,
					'class'           => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
				],
			]
		);

		echo wp_kses( apply_filters( 'srfm_entry_logs_markup', $content, $entry_logs ), $allowed_tags );
	}
}
