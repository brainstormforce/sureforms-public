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

	public function print_refunds( $value ) {
		ob_start();
		if ( ! empty( $value ) && is_array( $value ) ) {
			?>
			<table class="refund-details" style="width: 100%; border-collapse: collapse; margin: 10px 0; border: 1px solid #ddd;">
				<thead>
					<tr>
						<th style="background-color: #f9f9f9; padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;">
							<?php esc_html_e( 'Refunded Amount', 'sureforms' ); ?>
						</th>
						<th style="background-color: #f9f9f9; padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;">
							<?php esc_html_e( 'Refund ID', 'sureforms' ); ?>
						</th>
						<th style="background-color: #f9f9f9; padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;">
							<?php esc_html_e( 'Status', 'sureforms' ); ?>
						</th>
						<th style="background-color: #f9f9f9; padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;">
							<?php esc_html_e( 'Refunded By', 'sureforms' ); ?>
						</th>
						<th style="background-color: #f9f9f9; padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;">
							<?php esc_html_e( 'Time', 'sureforms' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $value as $refund ) { ?>
						<tr>
							<td style="padding: 8px; border-bottom: 1px solid #eee;">
								<?php
								$amount   = $refund['amount'] ?? 0;
								$currency = isset( $refund['currency'] ) ? strtoupper( $refund['currency'] ) : 'USD';
								echo esc_html( $currency . ' ' . number_format( $amount / 100, 2 ) );
								?>
							</td>
							<td style="padding: 8px; border-bottom: 1px solid #eee;">
								<?php echo esc_html( $refund['refund_id'] ?? 'N/A' ); ?>
							</td>
							<td style="padding: 8px; border-bottom: 1px solid #eee;">
								<?php echo esc_html( ucfirst( $refund['status'] ?? 'unknown' ) ); ?>
							</td>
							<td style="padding: 8px; border-bottom: 1px solid #eee;">
								<?php echo esc_html( $refund['refunded_by'] ?? 'System' ); ?>
							</td>
							<td style="padding: 8px; border-bottom: 1px solid #eee;">
								<?php echo esc_html( $refund['refunded_at'] ?? 'N/A' ); ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
			return ob_get_clean();
		}
		return '';
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
			'partially_refunded'      => __( 'Partially Refunded', 'sureforms' ),
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
							<td>
								<?php
								$refunded_amount = floatval( $this->payment['refunded_amount'] ?? 0 );
								if ( $refunded_amount > 0 ) {
									$net_amount = $amount - $refunded_amount;
									echo sprintf(
										'<span style="display: flex;gap: 8px;"><span style="text-decoration: line-through; color: #6c757d;">%1$s %3$s</span><strong>%1$s %2$s</strong></span>',
										esc_html( $currency ),
										number_format( $net_amount, 2 ),
										number_format( $amount, 2 )
									);
								} else {
									echo esc_html( $currency . ' ' . number_format( $amount, 2 ) );
								}
								?>
							</td>
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
				// Add refund section for succeeded and partially refunded payments
				$is_subscription = ! empty( $this->payment['type'] ) && 'subscription' === $this->payment['type'];
				if ( ( 'succeeded' === $payment_status || 'partially_refunded' === $payment_status ) && ! empty( $this->payment['transaction_id'] ) && 'stripe' === $this->payment['gateway'] ) {
					// Calculate refundable amount using the new column
					$total_refunded    = floatval( $this->payment['refunded_amount'] ?? 0 );
					$refundable_amount = $amount - $total_refunded;
					$currency_symbol   = $currency === 'USD' ? '$' : strtoupper( $currency ) . ' ';

					// Get refund history from payment_data for display
					$payment_data   = Helper::get_array_value( $this->payment['payment_data'] );
					$refund_history = [];
					if ( ! empty( $payment_data['refunds'] ) && is_array( $payment_data['refunds'] ) ) {
						$refund_history = $payment_data['refunds'];
					}
					?>
					<div class="srfm-refund-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
						<div style="margin-bottom: 15px;">
							<?php if ( $is_subscription ) { ?>
								<strong><?php esc_html_e( 'Subscription Management', 'sureforms' ); ?></strong>
								<p style="margin: 5px 0 10px 0; color: #666; font-size: 13px;">
									<?php esc_html_e( 'Refund the current billing payment or cancel the entire subscription.', 'sureforms' ); ?>
								</p>
							<?php } else { ?>
								<strong><?php esc_html_e( 'Refund Payment', 'sureforms' ); ?></strong>
								<p style="margin: 5px 0 10px 0; color: #666; font-size: 13px;">
									<?php esc_html_e( 'Issue a full or partial refund for this payment through Stripe.', 'sureforms' ); ?>
								</p>
							<?php } ?>
							
							<?php if ( $total_refunded > 0 ) { ?>
								<div style="margin: 10px 0; padding: 8px 12px; background: #f0f6fc; border: 1px solid #c3ddfd; border-radius: 4px; font-size: 13px;">
									<strong><?php esc_html_e( 'Refund History:', 'sureforms' ); ?></strong>
									<?php
									printf(
										/* translators: %1$s currency symbol, %2$s refunded amount, %3$s original amount */
										esc_html__( '%1$s%2$s of %1$s%3$s has been refunded', 'sureforms' ),
										esc_html( $currency_symbol ),
										esc_html( number_format( $total_refunded, 2 ) ),
										esc_html( number_format( $amount, 2 ) )
									);
									?>
								</div>
							<?php } ?>
							
							<?php if ( $refundable_amount > 0 ) { ?>
								<div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
									<div style="flex: 1; min-width: 200px;">
										<label for="srfm-refund-type" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
											<?php esc_html_e( 'Refund Type:', 'sureforms' ); ?>
										</label>
										<select id="srfm-refund-type" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px;">
											<option value="full"><?php esc_html_e( 'Full Refund', 'sureforms' ); ?> (<?php echo esc_html( $currency_symbol . number_format( $refundable_amount, 2 ) ); ?>)</option>
											<option value="partial"><?php esc_html_e( 'Partial Refund', 'sureforms' ); ?></option>
										</select>
									</div>
									
									<div id="srfm-partial-amount-container" style="flex: 1; min-width: 200px; display: none;">
										<label for="srfm-refund-amount" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
											<?php esc_html_e( 'Refund Amount:', 'sureforms' ); ?>
										</label>
										<div style="position: relative;">
											<input type="number" id="srfm-refund-amount" 
												   min="0.01" 
												   max="<?php echo esc_attr( $refundable_amount ); ?>" 
												   step="0.01"
												   placeholder="0.00"
												   style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px;" />
											<small style="display: block; margin-top: 2px; color: #666; font-size: 12px;">
												<?php
												printf(
													/* translators: %s currency and maximum amount */
													esc_html__( 'Maximum: %s', 'sureforms' ),
													esc_html( $currency_symbol . number_format( $refundable_amount, 2 ) )
												);
												?>
											</small>
										</div>
									</div>
									
									<div style="display: flex; gap: 10px; flex-wrap: wrap;">
										<?php if ( $is_subscription ) { ?>
											<!-- Subscription Payment Refund Button -->
											<button type="button" id="srfm-subscription-refund-button" class="button button-secondary" 
													data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
													data-transaction-id="<?php echo esc_attr( $this->payment['transaction_id'] ); ?>"
													data-amount="<?php echo esc_attr( $amount ); ?>"
													data-currency="<?php echo esc_attr( $currency ); ?>"
													data-refundable-amount="<?php echo esc_attr( $refundable_amount ); ?>"
													data-currency-symbol="<?php echo esc_attr( $currency_symbol ); ?>">
												<?php esc_html_e( 'Refund Payment', 'sureforms' ); ?>
											</button>
											<!-- Cancel Subscription Button -->
											<?php if ( ! empty( $this->payment['subscription_id'] ) && ! empty( $this->payment['subscription_status'] ) && 'cancelled' !== $this->payment['subscription_status'] ) { ?>
												<button type="button" id="srfm-cancel-subscription-button" class="button button-primary" 
														style="background-color: #dc3545; border-color: #dc3545;"
														data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
														data-subscription-id="<?php echo esc_attr( $this->payment['subscription_id'] ); ?>">
													<?php esc_html_e( 'Cancel Subscription', 'sureforms' ); ?>
												</button>
											<?php } elseif ( ! empty( $this->payment['subscription_status'] ) && 'cancelled' === $this->payment['subscription_status'] ) { ?>
												<span style="padding: 6px 12px; background: #f8d7da; color: #721c24; border-radius: 4px; font-size: 13px; font-weight: 500;">
													<?php esc_html_e( 'Subscription Cancelled', 'sureforms' ); ?>
												</span>
											<?php } ?>
										<?php } else { ?>
											<!-- Regular Payment Refund Button -->
											<button type="button" id="srfm-refund-button" class="button button-secondary" 
													data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
													data-transaction-id="<?php echo esc_attr( $this->payment['transaction_id'] ); ?>"
													data-amount="<?php echo esc_attr( $amount ); ?>"
													data-currency="<?php echo esc_attr( $currency ); ?>"
													data-refundable-amount="<?php echo esc_attr( $refundable_amount ); ?>"
													data-currency-symbol="<?php echo esc_attr( $currency_symbol ); ?>">
												<?php esc_html_e( 'Issue Refund', 'sureforms' ); ?>
											</button>
										<?php } ?>
									</div>
								</div>
							<?php } else { ?>
								<div style="padding: 8px 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
									<strong><?php esc_html_e( 'This payment has been fully refunded.', 'sureforms' ); ?></strong>
								</div>
							<?php } ?>
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
		if ( ( 'succeeded' === $payment_status || 'partially_refunded' === $payment_status ) && ! empty( $this->payment['transaction_id'] ) && 'stripe' === $this->payment['gateway'] ) {
			// Calculate refundable amount for JS using the column
			$total_refunded    = floatval( $this->payment['refunded_amount'] ?? 0 );
			$refundable_amount = $amount - $total_refunded;
			$currency_symbol   = $currency === 'USD' ? '$' : strtoupper( $currency ) . ' ';

			wp_localize_script(
				'srfm-payment-entries',
				'sureformsRefundData',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'sureforms_admin_nonce' ),
					'payment' => [
						'original_amount'   => $amount,
						'refundable_amount' => $refundable_amount,
						'currency'          => $currency,
						'currency_symbol'   => $currency_symbol,
						'is_subscription'   => $is_subscription,
						'subscription_id'   => $this->payment['subscription_id'] ?? '',
						'subscription_status' => $this->payment['subscription_status'] ?? '',
					],
					'strings' => [
						'confirm_message'        => __( 'Are you sure you want to refund this payment? This action cannot be undone.', 'sureforms' ),
						'confirm_full_refund'    => sprintf( __( 'Are you sure you want to refund the full amount (%s)? This action cannot be undone.', 'sureforms' ), $currency_symbol . number_format( $refundable_amount, 2 ) ),
						'confirm_partial_refund' => __( 'Are you sure you want to refund %amount%? This action cannot be undone.', 'sureforms' ),
						'processing'             => __( 'Processing...', 'sureforms' ),
						'success_message'        => __( 'Payment refunded successfully!', 'sureforms' ),
						'error_prefix'           => __( 'Error: ', 'sureforms' ),
						'error_fallback'         => __( 'Failed to process refund.', 'sureforms' ),
						'network_error'          => __( 'Network error. Please try again.', 'sureforms' ),
						'issue_refund'           => __( 'Issue Refund', 'sureforms' ),
						
						// Subscription-specific strings (following WPForms pattern)
						'confirm_subscription_refund' => __( 'Are you sure you want to refund this subscription payment? This will refund the current billing cycle only.', 'sureforms' ),
						'confirm_partial_subscription_refund' => __( 'Are you sure you want to refund %amount% from this subscription payment?', 'sureforms' ),
						'confirm_subscription_cancel' => __( 'Are you sure you want to cancel this subscription? This will stop all future billing and cannot be undone.', 'sureforms' ),
						'subscription_refund_success' => __( 'Subscription payment refunded successfully!', 'sureforms' ),
						'subscription_refund_failed' => __( 'Subscription refund failed. Please try again.', 'sureforms' ),
						'subscription_cancel_success' => __( 'Subscription cancelled successfully!', 'sureforms' ),
						'subscription_cancel_failed' => __( 'Subscription cancellation failed. Please try again.', 'sureforms' ),
						'refund_subscription' => __( 'Refund Payment', 'sureforms' ),
						'cancel_subscription' => __( 'Cancel Subscription', 'sureforms' ),
						'amount_required'        => __( 'Please enter a refund amount.', 'sureforms' ),
						'amount_invalid'         => __( 'Please enter a valid amount.', 'sureforms' ),
						'amount_too_low'         => __( 'Refund amount must be at least $0.01.', 'sureforms' ),
						'amount_too_high'        => sprintf( __( 'Refund amount cannot exceed %s.', 'sureforms' ), $currency_symbol . number_format( $refundable_amount, 2 ) ),
						'select_refund_type'     => __( 'Please select a refund type.', 'sureforms' ),
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
				<table class="widefat striped">
					<tbody>
						<tr>
							<th><b><?php esc_html_e( 'Field', 'sureforms' ); ?></b></th>
							<th><b><?php esc_html_e( 'Value', 'sureforms' ); ?></b></th>
						</tr>
						
						<?php
						// Always show payment amount and status first
						$amount         = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
						$currency       = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';
						$payment_status = $this->payment['status'];

						// Status labels for display
						$status_labels  = [
							'pending'                 => __( 'Pending', 'sureforms' ),
							'succeeded'               => __( 'Succeeded', 'sureforms' ),
							'failed'                  => __( 'Failed', 'sureforms' ),
							'canceled'                => __( 'Canceled', 'sureforms' ),
							'requires_action'         => __( 'Requires Action', 'sureforms' ),
							'requires_payment_method' => __( 'Requires Payment Method', 'sureforms' ),
							'processing'              => __( 'Processing', 'sureforms' ),
							'refunded'                => __( 'Refunded', 'sureforms' ),
							'partially_refunded'      => __( 'Partially Refunded', 'sureforms' ),
						];
						$status_display = $status_labels[ $payment_status ] ?? ucfirst( $payment_status );
						?>
						
						<tr>
							<td><b><?php esc_html_e( 'Payment Amount', 'sureforms' ); ?></b></td>
							<td>
								<?php
								$refunded_amount = floatval( $this->payment['refunded_amount'] ?? 0 );
								if ( $refunded_amount > 0 ) {
									$net_amount = $amount - $refunded_amount;
									echo sprintf(
										'<span style="display: flex;gap: 8px;"><span style="text-decoration: line-through; color: #6c757d;"><strong>%1$s %3$s</strong></span><strong>%1$s %2$s</strong></span>',
										esc_html( $currency ),
										number_format( $net_amount, 2 ),
										number_format( $amount, 2 )
									);
								} else {
									echo '<strong>' . esc_html( $currency . ' ' . number_format( $amount, 2 ) ) . '</strong>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Payment Status', 'sureforms' ); ?></b></td>
							<td>
								<span class="payment-status-<?php echo esc_attr( $payment_status ); ?>" style="
									padding: 3px 8px; 
									border-radius: 3px; 
									font-size: 11px; 
									font-weight: bold; 
									text-transform: uppercase;
									background-color: 
									<?php
										echo esc_attr(
											'succeeded' === $payment_status ? '#d4edda' :
											( 'refunded' === $payment_status ? '#f8d7da' :
											( 'failed' === $payment_status ? '#f8d7da' :
											( 'partially_refunded' === $payment_status ? '#fff3cd' : '#fff3cd' ) ) )
										);
									?>
									;
									color: 
									<?php
										echo esc_attr(
											'succeeded' === $payment_status ? '#155724' :
											( 'refunded' === $payment_status ? '#721c24' :
											( 'failed' === $payment_status ? '#721c24' :
											( 'partially_refunded' === $payment_status ? '#856404' : '#856404' ) ) )
										);
									?>
									;
									border: 1px solid 
									<?php
										echo esc_attr(
											'succeeded' === $payment_status ? '#c3e6cb' :
											( 'refunded' === $payment_status ? '#f5c6cb' :
											( 'failed' === $payment_status ? '#f5c6cb' :
											( 'partially_refunded' === $payment_status ? '#ffeaa7' : '#ffeaa7' ) ) )
										);
									?>
									;">
									<?php echo esc_html( $status_display ); ?>
								</span>
							</td>
						</tr>
						
						<?php
						// Display payment data
						if ( ! empty( $payment_data ) && is_array( $payment_data ) ) {
							foreach ( $payment_data as $key => $value ) {
								$display_key   = ucwords( str_replace( [ '_', '-' ], ' ', $key ) );
								$display_value = $this->format_payment_value( $value, $key );
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

						// Show message if no additional data
						if ( empty( $payment_data ) && empty( $extra_data ) ) {
							?>
							<tr>
								<td colspan="2">
									<p style="margin: 10px 0; font-style: italic; color: #666;">
										<?php esc_html_e( 'No additional payment details available.', 'sureforms' ); ?>
									</p>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
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
	private function format_payment_value( $value, $key = null ) {
		if ( is_array( $value ) ) {
			$reversed_value = array_reverse( $value );

			if ( 'refunds' === $key ) {
				return $this->print_refunds( $reversed_value );
			}

			return '<pre>' . esc_html( wp_json_encode( $reversed_value, JSON_PRETTY_PRINT ) ) . '</pre>';
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
