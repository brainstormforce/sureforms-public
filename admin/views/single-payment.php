<?php
/**
 * SureForms Single Payment Page.
 *
 * @since x.x.x
 * @package sureforms.
 */

namespace SRFM\Admin\Views;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Helper;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single payment page.
 *
 * @since x.x.x
 */
class Single_Payment {
	/**
	 * Stores the payment ID.
	 *
	 * @var string|null $payment_id ID for the specific payment.
	 * @since x.x.x
	 */
	private $payment_id;

	/**
	 * Stores the payment data for the specified payment ID.
	 *
	 * @var array<mixed>|null $payment Payment data for the specified payment ID.
	 * @since x.x.x
	 */
	private $payment;

	/**
	 * Initialize the properties.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_payments_action' ) ) {
			return;
		}
		$this->payment_id = isset( $_GET['payment_id'] ) ? intval( sanitize_text_field( wp_unslash( $_GET['payment_id'] ) ) ) : null;
		$this->payment    = $this->payment_id ? Payments::get( $this->payment_id ) : null;
	}

	/**
	 * Render the single payment page if a payment is found.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function render() {
		if ( ! $this->payment ) {
			return;
		}

		$payment_status = $this->payment['status'];
		$created_at     = gmdate( 'Y/m/d \\a\\t g:i a', strtotime( $this->payment['created_at'] ) );
		// Translators: %d is the form ID.
		$form_name    = ! empty( get_the_title( $this->payment['form_id'] ) ) ? get_the_title( $this->payment['form_id'] ) : sprintf( esc_html__( 'SureForms Form #%d', 'sureforms' ), intval( $this->payment['form_id'] ) );
		$payment_data = Helper::get_array_value( $this->payment['payment_data'] );
		$extra_data   = Helper::get_array_value( $this->payment['extra'] );
		$logs         = Helper::get_array_value( $this->payment['log'] );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				/* Translators: %s is the payment id. */
				printf( esc_html__( 'Payment #%s', 'sureforms' ), esc_html( $this->payment_id ) );
				?>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( "admin.php?page=sureforms_payments&payment_id={$this->payment_id}&view=details" ) ); ?>">
				<?php
				/**
				 * Action hook right after payment form opening tag.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_after_payment_form_opening_tag', $this->payment, $this );
				?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container">
							<?php
							/**
							 * Action hook right before payment submission info.
							 *
							 * @since x.x.x
							 */
							do_action( 'srfm_before_payment_submission_info', $this->payment, $this );

							$this->render_payment_info( $form_name, $payment_status, $created_at );

							/**
							 * Action hook right after payment submission info.
							 *
							 * @since x.x.x
							 */
							do_action( 'srfm_after_payment_submission_info', $this->payment, $this );
							?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php $this->render_payment_details( $payment_data, $extra_data ); ?>
						</div>
						<div id="postbox-container-3" class="postbox-container">
							<?php $this->render_payment_logs( $logs ); ?>
						</div>
					</div><!-- /post-body -->
					<br class="clear">
				</div>
				<!-- /poststuff -->

				<?php
				/**
				 * Action hook right before payment form closing tag.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_before_payment_form_closing_tag', $this->payment, $this );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the payment information for a specific payment.
	 *
	 * @param string $form_name The form title/name.
	 * @param string $payment_status The payment status.
	 * @param string $created_at The payment creation date.
	 * @since x.x.x
	 * @return void
	 */
	private function render_payment_info( $form_name, $payment_status, $created_at ) {
		$amount       = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
		$currency     = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';
		$gateway      = ! empty( $this->payment['gateway'] ) ? ucfirst( $this->payment['gateway'] ) : __( 'Unknown', 'sureforms' );
		$payment_type = ! empty( $this->payment['type'] ) ? ucfirst( $this->payment['type'] ) : __( 'One-time', 'sureforms' );
		$mode         = ! empty( $this->payment['mode'] ) ? ucfirst( $this->payment['mode'] ) : __( 'Unknown', 'sureforms' );

		// Get related entry if exists
		$entry_link = '';
		if ( ! empty( $this->payment['entry_id'] ) ) {
			$entry_url  = wp_nonce_url(
				add_query_arg(
					[
						'entry_id' => $this->payment['entry_id'],
						'view'     => 'details',
					],
					admin_url( 'admin.php?page=sureforms_entries' )
				),
				'srfm_entries_action'
			);
			$entry_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $entry_url ),
				sprintf( esc_html__( 'Entry #%d', 'sureforms' ), intval( $this->payment['entry_id'] ) )
			);
		}

		// Status labels for display
		$status_labels = [
			'pending'                 => __( 'Pending', 'sureforms' ),
			'succeeded'               => __( 'Succeeded', 'sureforms' ),
			'failed'                  => __( 'Failed', 'sureforms' ),
			'canceled'                => __( 'Canceled', 'sureforms' ),
			'requires_action'         => __( 'Requires Action', 'sureforms' ),
			'requires_payment_method' => __( 'Requires Payment Method', 'sureforms' ),
			'processing'              => __( 'Processing', 'sureforms' ),
			'refunded'                => __( 'Refunded', 'sureforms' ),
		];

		$status_display = $status_labels[ $payment_status ] ?? ucfirst( $payment_status );
		?>
		<div id="sureform_payment_info_meta" class="postbox">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Payment Info', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table style="border-collapse: separate; border-spacing: 5px 5px;">
					<tbody>
						<tr>
							<td><b><?php esc_html_e( 'Payment ID:', 'sureforms' ); ?></b></td>
							<td>#<?php echo esc_attr( $this->payment_id ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Amount:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $currency . ' ' . number_format( $amount, 2 ) ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Status:', 'sureforms' ); ?></b></td>
							<td>
								<span class="payment-status-<?php echo esc_attr( $payment_status ); ?>">
									<?php echo esc_html( $status_display ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Gateway:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $gateway ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Type:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $payment_type ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Mode:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $mode ); ?></td>
						</tr>
						<?php if ( ! empty( $this->payment['transaction_id'] ) ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Transaction ID:', 'sureforms' ); ?></b></td>
								<td><code><?php echo esc_html( $this->payment['transaction_id'] ); ?></code></td>
							</tr>
						<?php } ?>
						<?php if ( ! empty( $this->payment['customer_id'] ) ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Customer ID:', 'sureforms' ); ?></b></td>
								<td><code><?php echo esc_html( $this->payment['customer_id'] ); ?></code></td>
							</tr>
						<?php } ?>
						<tr>
							<td><b><?php esc_html_e( 'Form Name:', 'sureforms' ); ?></b></td>
							<td><a target="_blank" rel="noopener" href="<?php the_permalink( $this->payment['form_id'] ); ?>"><?php echo esc_attr( $form_name ); ?></a></td>
						</tr>
						<?php if ( $entry_link ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Related Entry:', 'sureforms' ); ?></b></td>
								<td><?php echo wp_kses_post( $entry_link ); ?></td>
							</tr>
						<?php } ?>
						<tr>
							<td><b><?php esc_html_e( 'Created On:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_attr( $created_at ); ?></td>
						</tr>
						<?php if ( ! empty( $this->payment['updated_at'] ) && $this->payment['updated_at'] !== $this->payment['created_at'] ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Updated On:', 'sureforms' ); ?></b></td>
								<td><?php echo esc_attr( gmdate( 'Y/m/d \\a\\t g:i a', strtotime( $this->payment['updated_at'] ) ) ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php
				// Add refund section for succeeded payments
				if ( 'succeeded' === $payment_status && ! empty( $this->payment['transaction_id'] ) && 'stripe' === $this->payment['gateway'] ) {
					?>
					<div class="srfm-refund-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
						<div style="display: flex; justify-content: space-between; align-items: center;">
							<div>
								<strong><?php esc_html_e( 'Refund Payment', 'sureforms' ); ?></strong>
								<p style="margin: 5px 0; color: #666; font-size: 13px;">
									<?php esc_html_e( 'Issue a refund for this payment through Stripe.', 'sureforms' ); ?>
								</p>
							</div>
							<div>
								<button type="button" id="srfm-refund-button" class="button button-secondary" 
										data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
										data-transaction-id="<?php echo esc_attr( $this->payment['transaction_id'] ); ?>"
										data-amount="<?php echo esc_attr( $amount ); ?>"
										data-currency="<?php echo esc_attr( $currency ); ?>">
									<?php esc_html_e( 'Issue Refund', 'sureforms' ); ?>
								</button>
							</div>
						</div>
					</div>
					<?php
				} elseif ( 'refunded' === $payment_status ) {
					?>
					<div class="srfm-refund-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
						<div style="display: flex; align-items: center; color: #d63384;">
							<span class="dashicons dashicons-undo" style="margin-right: 5px;"></span>
							<strong><?php esc_html_e( 'This payment has been refunded', 'sureforms' ); ?></strong>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		
		<?php
		// Add refund nonce for JavaScript if payment can be refunded
		if ( 'succeeded' === $payment_status && ! empty( $this->payment['transaction_id'] ) && 'stripe' === $this->payment['gateway'] ) {
			wp_localize_script(
				'srfm-payment-entries',
				'sureformsRefundData',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'srfm_stripe_payment_nonce' ),
					'strings' => [
						'confirm_message' => __( 'Are you sure you want to refund this payment? This action cannot be undone.', 'sureforms' ),
						'processing'      => __( 'Processing Refund...', 'sureforms' ),
						'success_message' => __( 'Payment refunded successfully!', 'sureforms' ),
						'error_prefix'    => __( 'Error: ', 'sureforms' ),
						'error_fallback'  => __( 'Failed to process refund.', 'sureforms' ),
						'network_error'   => __( 'Network error. Please try again.', 'sureforms' ),
						'issue_refund'    => __( 'Issue Refund', 'sureforms' ),
					],
				]
			);
		}
		?>
		
		<?php
	}

	/**
	 * Render the payment details for a specific payment.
	 *
	 * @param array<mixed> $payment_data The payment data.
	 * @param array<mixed> $extra_data The extra data.
	 * @since x.x.x
	 * @return void
	 */
	private function render_payment_details( $payment_data, $extra_data ) {
		?>
		<div id="sureform_payment_details_meta" class="postbox srfm-payment-details">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Payment Details', 'sureforms' ); ?></h2>
				<?php
				/**
				 * Action hook right after payment details postbox title.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_after_payment_details_postbox_title', $this->payment, $this );
				?>
			</div>
			<div class="inside">
				<?php if ( ! empty( $payment_data ) || ! empty( $extra_data ) ) { ?>
					<table class="widefat striped">
						<tbody>
							<tr>
								<th><b><?php esc_html_e( 'Field', 'sureforms' ); ?></b></th>
								<th><b><?php esc_html_e( 'Value', 'sureforms' ); ?></b></th>
							</tr>
							<?php
							// Display payment data
							if ( ! empty( $payment_data ) && is_array( $payment_data ) ) {
								foreach ( $payment_data as $key => $value ) {
									$display_key   = ucwords( str_replace( [ '_', '-' ], ' ', $key ) );
									$display_value = $this->format_payment_value( $value );
									?>
									<tr>
										<td><b><?php echo esc_html( $display_key ); ?></b></td>
										<td><?php echo wp_kses_post( $display_value ); ?></td>
									</tr>
									<?php
								}
							}

							// Display extra data
							if ( ! empty( $extra_data ) && is_array( $extra_data ) ) {
								foreach ( $extra_data as $key => $value ) {
									$display_key   = ucwords( str_replace( [ '_', '-' ], ' ', $key ) );
									$display_value = $this->format_payment_value( $value );
									?>
									<tr>
										<td><b><?php echo esc_html( $display_key ); ?></b></td>
										<td><?php echo wp_kses_post( $display_value ); ?></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
				<?php } else { ?>
					<p><?php esc_html_e( 'No additional payment details available.', 'sureforms' ); ?></p>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the payment logs for a specific payment.
	 *
	 * @param array<mixed> $payment_logs Payment logs stored in the database.
	 * @since x.x.x
	 * @return void
	 */
	private function render_payment_logs( $payment_logs ) {
		ob_start();
		?>
		<div id="sureform_payment_logs_meta" class="postbox srfm-payment-logs">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Payment Logs', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table class="striped payment-logs-table">
					<tbody>
						<?php if ( ! empty( $payment_logs ) && is_array( $payment_logs ) ) { ?>
							<?php foreach ( $payment_logs as $log ) { ?>
								<tr>
									<td class="payment-log-container">
										<div class="payment-log">
											<h4 class="payment-log-title">
												<?php
												if ( isset( $log['title'] ) ) {
													echo esc_html( $log['title'] );
												}
												if ( isset( $log['timestamp'] ) ) {
													echo ' ' . esc_html( gmdate( '\\a\\t Y-m-d H:i:s', $log['timestamp'] ) );
												}
												?>
											</h4>
											<div class="payment-log-messages">
												<?php
												if ( isset( $log['messages'] ) && is_array( $log['messages'] ) ) {
													foreach ( $log['messages'] as $message ) {
														?>
														<p><?php echo wp_kses_post( $message ); ?></p>
														<?php
													}
												} elseif ( isset( $log['message'] ) ) {
													?>
													<p><?php echo wp_kses_post( $log['message'] ); ?></p>
													<?php
												}
												?>
											</div>
										</div>
									</td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr>
								<td>
									<p class="no-logs-found"><?php esc_html_e( 'No logs found for this payment.', 'sureforms' ); ?></p>
								</td>
							</tr>
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

		echo wp_kses( apply_filters( 'srfm_payment_logs_markup', $content, $payment_logs ), $allowed_tags );
	}

	/**
	 * Format payment value for display.
	 *
	 * @param mixed $value The value to format.
	 * @since x.x.x
	 * @return string Formatted value.
	 */
	private function format_payment_value( $value ) {
		if ( is_array( $value ) ) {
			return '<pre>' . esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT ) ) . '</pre>';
		}

		if ( is_bool( $value ) ) {
			return $value ? esc_html__( 'Yes', 'sureforms' ) : esc_html__( 'No', 'sureforms' );
		}

		if ( is_null( $value ) ) {
			return '<em>' . esc_html__( 'null', 'sureforms' ) . '</em>';
		}

		// Check if it's a URL
		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $value ), esc_html( $value ) );
		}

		// Check if it's an email
		if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			return sprintf( '<a href="mailto:%s">%s</a>', esc_attr( $value ), esc_html( $value ) );
		}

		return esc_html( $value );
	}
}
