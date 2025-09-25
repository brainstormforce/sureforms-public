<?php
/**
 * SureForms Single Subscription Page.
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
 * Single subscription payment management page.
 *
 * @since x.x.x
 */
class Single_Subscription {
	/**
	 * Stores the subscription payment ID.
	 *
	 * @var string|null $payment_id ID for the specific subscription payment.
	 * @since x.x.x
	 */
	private $payment_id;

	/**
	 * Stores the subscription payment data for the specified payment ID.
	 *
	 * @var array<mixed>|null $payment Subscription payment data for the specified payment ID.
	 * @since x.x.x
	 */
	private $payment;

	/**
	 * Initialize subscription page properties.
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
	 * Print refunds table for subscription payments.
	 *
	 * @param array $value Refund data array.
	 * @since x.x.x
	 * @return string HTML for refunds table.
	 */
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
	 * Render the single subscription page if a subscription is found.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function render() {
		if ( ! $this->payment || 'subscription' !== ( $this->payment['type'] ?? '' ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Subscription Not Found', 'sureforms' ) . '</h1>';
			echo '<p>' . esc_html__( 'The subscription you are looking for was not found or is not a valid subscription record.', 'sureforms' ) . '</p></div>';
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
				/* Translators: %s is the subscription payment id. */
				printf( esc_html__( 'Subscription #%s', 'sureforms' ), esc_html( $this->payment_id ) );
				?>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( "admin.php?page=sureforms_payments&payment_id={$this->payment_id}&view=subscription" ) ); ?>">
				<?php
				/**
				 * Action hook right after subscription form opening tag.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_after_subscription_form_opening_tag', $this->payment, $this );
				?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container"></div>
						<div id="postbox-container-2" class="postbox-container">
							<?php
							/**
							 * Action hook right before subscription info.
							 *
							 * @since x.x.x
							 */
							do_action( 'srfm_before_subscription_info', $this->payment, $this );

							$this->render_subscription_info( $form_name, $payment_status, $created_at );

							/**
							 * Action hook right after subscription info.
							 *
							 * @since x.x.x
							 */
							do_action( 'srfm_after_subscription_info', $this->payment, $this );
							?>
						</div>
						<div id="postbox-container-3" class="postbox-container">
							<?php
							// $this->render_subscription_details( $payment_data, $extra_data );
							?>
						</div>
						<div id="postbox-container-4" class="postbox-container">
							<?php $this->render_subscription_payment_history(); ?>
						</div>
						<div id="postbox-container-5" class="postbox-container">
							<?php $this->render_subscription_logs( $logs ); ?>
						</div>
					</div><!-- /post-body -->
					<br class="clear">
				</div>
				<!-- /poststuff -->

				<?php
				/**
				 * Action hook right before subscription form closing tag.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_before_subscription_form_closing_tag', $this->payment, $this );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the subscription information postbox.
	 *
	 * @param string $form_name The form title/name.
	 * @param string $payment_status The subscription status.
	 * @param string $created_at The subscription creation date.
	 * @since x.x.x
	 * @return void
	 */
	private function render_subscription_info( $form_name, $payment_status, $created_at ) {
		$amount   = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
		$currency = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';
		$gateway  = ! empty( $this->payment['gateway'] ) ? ucfirst( $this->payment['gateway'] ) : __( 'Unknown', 'sureforms' );
		$mode     = ! empty( $this->payment['mode'] ) ? ucfirst( $this->payment['mode'] ) : __( 'Unknown', 'sureforms' );

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

		// Subscription status labels for display
		$status_labels = [
			'active'     => __( 'Active', 'sureforms' ),
			'trialing'   => __( 'Trialing', 'sureforms' ),
			'past_due'   => __( 'Past Due', 'sureforms' ),
			'canceled'   => __( 'Canceled', 'sureforms' ),
			'unpaid'     => __( 'Unpaid', 'sureforms' ),
			'incomplete' => __( 'Incomplete', 'sureforms' ),
			'succeeded'  => __( 'Active', 'sureforms' ),
			'pending'    => __( 'Pending', 'sureforms' ),
			'failed'     => __( 'Failed', 'sureforms' ),
		];

		$subscription_status = ! empty( $this->payment['subscription_status'] ) ? $this->payment['subscription_status'] : $payment_status;
		$status_display      = $status_labels[ $subscription_status ] ?? ucfirst( $subscription_status );
		?>
		<div id="sureform_subscription_info_meta" class="postbox">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Subscription Info', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table style="border-collapse: separate; border-spacing: 5px 5px;">
					<tbody>
						<tr>
							<td><b><?php esc_html_e( 'Subscription ID:', 'sureforms' ); ?></b></td>
							<td>#<?php echo esc_attr( $this->payment_id ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Billing Amount:', 'sureforms' ); ?></b></td>
							<td>
								<strong style="color: #0073aa;">
									<?php echo esc_html( $currency . ' ' . number_format( $amount, 2 ) ); ?>
								</strong>
								<span style="color: #666; font-size: 13px; margin-left: 8px;">
									<?php esc_html_e( 'per billing cycle', 'sureforms' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Status:', 'sureforms' ); ?></b></td>
							<td>
								<?php
								// Add status indicator color
								$status_color = '#666';
								if ( in_array( $subscription_status, [ 'active', 'trialing', 'succeeded' ] ) ) {
									$status_color = '#00a32a'; // Green
								} elseif ( in_array( $subscription_status, [ 'past_due', 'unpaid', 'failed' ] ) ) {
									$status_color = '#d63638'; // Red
								} elseif ( 'canceled' === $subscription_status ) {
									$status_color = '#646970'; // Gray
								}
								?>
								<span style="color: <?php echo esc_attr( $status_color ); ?>; font-weight: 600;">
									<?php echo esc_html( $status_display ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Gateway:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $gateway ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Mode:', 'sureforms' ); ?></b></td>
							<td><?php echo esc_html( $mode ); ?></td>
						</tr>
						<?php if ( ! empty( $this->payment['subscription_id'] ) ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Stripe Subscription ID:', 'sureforms' ); ?></b></td>
								<td><code><?php echo esc_html( $this->payment['subscription_id'] ); ?></code></td>
							</tr>
						<?php } ?>
						<?php if ( ! empty( $this->payment['transaction_id'] ) ) { ?>
							<tr>
								<td><b><?php esc_html_e( 'Setup Intent ID:', 'sureforms' ); ?></b></td>
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
				
				<?php $this->render_subscription_management(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render subscription management postbox with billing controls and refunds.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function render_subscription_management() {
		$subscription_status = ! empty( $this->payment['subscription_status'] ) ? $this->payment['subscription_status'] : '';
		$subscription_id     = ! empty( $this->payment['subscription_id'] ) ? $this->payment['subscription_id'] : '';

		if ( empty( $subscription_id ) ) {
			return;
		}
		?>
		<div class="srfm-subscription-management" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
			<strong><?php esc_html_e( 'Subscription Management', 'sureforms' ); ?></strong>
			<p style="margin: 5px 0 15px 0; color: #666; font-size: 13px;">
				<?php esc_html_e( 'Manage this subscription including cancellation and monitoring billing cycles.', 'sureforms' ); ?>
			</p>
			
			<?php
			// Add subscription refund functionality
			$amount   = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
			$currency = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';

			if ( ( 'succeeded' === $this->payment['status'] || 'partially_refunded' === $this->payment['status'] ) && ! empty( $this->payment['transaction_id'] ) && 'stripe' === $this->payment['gateway'] ) {
				// Calculate refundable amount using the new column
				$total_refunded    = floatval( $this->payment['refunded_amount'] ?? 0 );
				$refundable_amount = $amount - $total_refunded;
				$currency_symbol   = $currency === 'USD' ? '$' : strtoupper( $currency ) . ' ';
				?>
				<div class="srfm-subscription-refund-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
					<strong><?php esc_html_e( 'Subscription Billing Refund', 'sureforms' ); ?></strong>
					<p style="margin: 5px 0 10px 0; color: #666; font-size: 13px;">
						<?php esc_html_e( 'Refund the current billing amount for this subscription.', 'sureforms' ); ?>
					</p>
					
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
								<label for="srfm-subscription-refund-type" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
									<?php esc_html_e( 'Refund Type:', 'sureforms' ); ?>
								</label>
								<select id="srfm-subscription-refund-type" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px;">
									<option value="full"><?php esc_html_e( 'Full Refund', 'sureforms' ); ?> (<?php echo esc_html( $currency_symbol . number_format( $refundable_amount, 2 ) ); ?>)</option>
									<option value="partial"><?php esc_html_e( 'Partial Refund', 'sureforms' ); ?></option>
								</select>
							</div>
							
							<div id="srfm-subscription-partial-amount-container" style="flex: 1; min-width: 200px; display: none;">
								<label for="srfm-subscription-refund-amount" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
									<?php esc_html_e( 'Refund Amount:', 'sureforms' ); ?>
								</label>
								<div style="position: relative;">
									<input type="number" id="srfm-subscription-refund-amount" 
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
								<button type="button" id="srfm-subscription-refund-button" class="button button-secondary" 
										data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
										data-transaction-id="<?php echo esc_attr( $this->payment['transaction_id'] ); ?>"
										data-amount="<?php echo esc_attr( $amount ); ?>"
										data-currency="<?php echo esc_attr( $currency ); ?>"
										data-refundable-amount="<?php echo esc_attr( $refundable_amount ); ?>"
										data-currency-symbol="<?php echo esc_attr( $currency_symbol ); ?>">
									<?php esc_html_e( 'Refund Billing Amount', 'sureforms' ); ?>
								</button>
							</div>
						</div>
					<?php } else { ?>
						<div style="padding: 8px 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
							<strong><?php esc_html_e( 'This subscription billing has been fully refunded.', 'sureforms' ); ?></strong>
						</div>
					<?php } ?>
				</div>
				<?php
			}
			?>
			
			<div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
				<?php if ( 'canceled' !== $subscription_status && 'cancelled' !== $subscription_status ) { ?>
					<button type="button" id="srfm-cancel-subscription-button" class="button button-primary" 
							style="background-color: #dc3545; border-color: #dc3545;"
							data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
							data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
						<?php esc_html_e( 'Cancel Subscription', 'sureforms' ); ?>
					</button>
				<?php } else { ?>
					<span style="padding: 8px 15px; background: #f8d7da; color: #721c24; border-radius: 4px; font-size: 13px; font-weight: 600;">
						<?php esc_html_e( 'Subscription Cancelled', 'sureforms' ); ?>
					</span>
				<?php } ?>
				
				<button type="button" id="srfm-refresh-subscription-button" class="button button-secondary"
						data-payment-id="<?php echo esc_attr( $this->payment_id ); ?>"
						data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
					<?php esc_html_e( 'Refresh Status', 'sureforms' ); ?>
				</button>
			</div>
		</div>
		
		<?php
		// Add JavaScript data for subscription management
		$amount            = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
		$currency          = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';
		$total_refunded    = floatval( $this->payment['refunded_amount'] ?? 0 );
		$refundable_amount = $amount - $total_refunded;
		$currency_symbol   = $currency === 'USD' ? '$' : strtoupper( $currency ) . ' ';

		wp_localize_script(
			'srfm-payment-entries',
			'sureformsSubscriptionData',
			[
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'sureforms_admin_nonce' ),
				'subscription' => [
					'id'                => $subscription_id,
					'status'            => $subscription_status,
					'original_amount'   => $amount,
					'refundable_amount' => $refundable_amount,
					'currency'          => $currency,
					'currency_symbol'   => $currency_symbol,
				],
				'strings'      => [
					'confirm_subscription_cancel'         => __( 'Are you sure you want to cancel this subscription? This will stop all future billing and cannot be undone.', 'sureforms' ),
					'subscription_cancel_success'         => __( 'Subscription cancelled successfully!', 'sureforms' ),
					'subscription_cancel_failed'          => __( 'Subscription cancellation failed. Please try again.', 'sureforms' ),
					'subscription_refresh_success'        => __( 'Subscription status refreshed successfully!', 'sureforms' ),
					'subscription_refresh_failed'         => __( 'Failed to refresh subscription status. Please try again.', 'sureforms' ),
					'processing'                          => __( 'Processing...', 'sureforms' ),
					'error_prefix'                        => __( 'Error: ', 'sureforms' ),
					'network_error'                       => __( 'Network error. Please try again.', 'sureforms' ),
					'cancel_subscription'                 => __( 'Cancel Subscription', 'sureforms' ),
					'refresh_status'                      => __( 'Refresh Status', 'sureforms' ),

					// Subscription refund strings
					'confirm_subscription_refund'         => __( 'Are you sure you want to refund this subscription billing? This will refund the current billing cycle only.', 'sureforms' ),
					'confirm_partial_subscription_refund' => __( 'Are you sure you want to refund %amount% from this subscription billing?', 'sureforms' ),
					'subscription_refund_success'         => __( 'Subscription billing refunded successfully!', 'sureforms' ),
					'subscription_refund_failed'          => __( 'Subscription refund failed. Please try again.', 'sureforms' ),
					'amount_required'                     => __( 'Please enter a refund amount.', 'sureforms' ),
					'amount_invalid'                      => __( 'Please enter a valid amount.', 'sureforms' ),
					'amount_too_low'                      => __( 'Refund amount must be at least $0.01.', 'sureforms' ),
					'amount_too_high'                     => sprintf( __( 'Refund amount cannot exceed %s.', 'sureforms' ), $currency_symbol . number_format( $refundable_amount, 2 ) ),
					'select_refund_type'                  => __( 'Please select a refund type.', 'sureforms' ),
					'refund_billing_amount'               => __( 'Refund Billing Amount', 'sureforms' ),
				],
			]
		);
	}

	/**
	 * Render the subscription details postbox.
	 *
	 * @param array<mixed> $payment_data The subscription payment data.
	 * @param array<mixed> $extra_data The extra data.
	 * @since x.x.x
	 * @return void
	 */
	private function render_subscription_details( $payment_data, $extra_data ) {
		?>
		<div id="sureform_subscription_details_meta" class="postbox srfm-subscription-details">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Subscription Details', 'sureforms' ); ?></h2>
				<?php
				/**
				 * Action hook right after subscription details postbox title.
				 *
				 * @since x.x.x
				 */
				do_action( 'srfm_after_subscription_details_postbox_title', $this->payment, $this );
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
						// Always show subscription amount and status first
						$amount              = ! empty( $this->payment['total_amount'] ) ? floatval( $this->payment['total_amount'] ) : 0;
						$currency            = ! empty( $this->payment['currency'] ) ? strtoupper( $this->payment['currency'] ) : 'USD';
						$subscription_status = ! empty( $this->payment['subscription_status'] ) ? $this->payment['subscription_status'] : $this->payment['status'];

						// Status labels for display
						$status_labels  = [
							'active'     => __( 'Active', 'sureforms' ),
							'trialing'   => __( 'Trialing', 'sureforms' ),
							'past_due'   => __( 'Past Due', 'sureforms' ),
							'canceled'   => __( 'Canceled', 'sureforms' ),
							'unpaid'     => __( 'Unpaid', 'sureforms' ),
							'incomplete' => __( 'Incomplete', 'sureforms' ),
							'succeeded'  => __( 'Active', 'sureforms' ),
							'pending'    => __( 'Pending', 'sureforms' ),
							'failed'     => __( 'Failed', 'sureforms' ),
						];
						$status_display = $status_labels[ $subscription_status ] ?? ucfirst( $subscription_status );
						?>
						
						<tr>
							<td><b><?php esc_html_e( 'Billing Amount', 'sureforms' ); ?></b></td>
							<td>
								<strong style="color: #0073aa;">
									<?php echo esc_html( $currency . ' ' . number_format( $amount, 2 ) ); ?>
								</strong>
								<span style="color: #666; font-size: 13px; margin-left: 8px;">
									<?php esc_html_e( 'per billing cycle', 'sureforms' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_html_e( 'Subscription Status', 'sureforms' ); ?></b></td>
							<td>
								<?php
								// Add status indicator color
								$status_color = '#666';
								$bg_color     = '#f9f9f9';
								$border_color = '#ddd';

								if ( in_array( $subscription_status, [ 'active', 'trialing', 'succeeded' ] ) ) {
									$status_color = '#155724';
									$bg_color     = '#d4edda';
									$border_color = '#c3e6cb';
								} elseif ( in_array( $subscription_status, [ 'past_due', 'unpaid', 'failed' ] ) ) {
									$status_color = '#721c24';
									$bg_color     = '#f8d7da';
									$border_color = '#f5c6cb';
								} elseif ( 'canceled' === $subscription_status ) {
									$status_color = '#856404';
									$bg_color     = '#fff3cd';
									$border_color = '#ffeaa7';
								}
								?>
								<span style="
									padding: 3px 8px; 
									border-radius: 3px; 
									font-size: 11px; 
									font-weight: bold; 
									text-transform: uppercase;
									background-color: <?php echo esc_attr( $bg_color ); ?>;
									color: <?php echo esc_attr( $status_color ); ?>;
									border: 1px solid <?php echo esc_attr( $border_color ); ?>;
								">
									<?php echo esc_html( $status_display ); ?>
								</span>
							</td>
						</tr>
						
						<?php
						// Display payment data
						if ( ! empty( $payment_data ) && is_array( $payment_data ) ) {
							foreach ( $payment_data as $key => $value ) {
								$display_key   = ucwords( str_replace( [ '_', '-' ], ' ', $key ) );
								$display_value = $this->format_subscription_value( $value, $key );
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
								$display_value = $this->format_subscription_value( $value );
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
										<?php esc_html_e( 'No additional subscription details available.', 'sureforms' ); ?>
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
	 * Render the subscription payment history postbox.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function render_subscription_payment_history() {
		if ( empty( $this->payment['subscription_id'] ) ) {
			return;
		}

		// Get all individual payment transactions related to this subscription
		$related_payments = Payments::get_subscription_related_payments( $this->payment['subscription_id'] );
		?>
		<div id="sureform_subscription_payments_meta" class="postbox srfm-subscription-payments">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Billing History', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<?php if ( empty( $related_payments ) ) { ?>
					<p style="margin: 15px 0; color: #666; font-size: 14px; font-style: italic;">
						<?php esc_html_e( 'No billing transactions found for this subscription yet.', 'sureforms' ); ?>
					</p>
				<?php } else { ?>
					<p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
						<?php
						printf(
							/* translators: %d: number of payment transactions */
							esc_html__( 'Individual billing transactions for this subscription (%d total)', 'sureforms' ),
							count( $related_payments )
						);
						?>
					</p>
					
					<div style="overflow-x: auto;">
						<table class="widefat striped" style="margin: 0;">
							<thead>
								<tr>
									<th style="padding: 10px; text-align: left; font-weight: 600;">
										<?php esc_html_e( 'Payment ID', 'sureforms' ); ?>
									</th>
									<th style="padding: 10px; text-align: left; font-weight: 600;">
										<?php esc_html_e( 'Date', 'sureforms' ); ?>
									</th>
									<th style="padding: 10px; text-align: left; font-weight: 600;">
										<?php esc_html_e( 'Amount', 'sureforms' ); ?>
									</th>
									<th style="padding: 10px; text-align: left; font-weight: 600;">
										<?php esc_html_e( 'Status', 'sureforms' ); ?>
									</th>
									<th style="padding: 10px; text-align: left; font-weight: 600;">
										<?php esc_html_e( 'Action', 'sureforms' ); ?>
									</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $related_payments as $payment ) { ?>
									<tr>
										<td style="padding: 10px; font-size: 13px;">
											<strong>#<?php echo esc_html( $payment['id'] ); ?></strong>
										</td>
										<td style="padding: 10px; font-size: 13px;">
											<?php echo esc_html( gmdate( 'M j, Y \\a\\t g:i a', strtotime( $payment['created_at'] ) ) ); ?>
										</td>
										<td style="padding: 10px; font-size: 13px;">
											<strong>
												<?php
												$amount          = floatval( $payment['total_amount'] ?? 0 );
												$currency        = strtoupper( $payment['currency'] ?? 'USD' );
												$refunded_amount = floatval( $payment['refunded_amount'] ?? 0 );

												if ( $refunded_amount > 0 ) {
													$net_amount = $amount - $refunded_amount;
													echo sprintf(
														'<span style="display: flex;gap: 8px;"><span style="text-decoration: line-through; color: #6c757d;">%1$s %3$s</span><span>%1$s %2$s</span></span>',
														esc_html( $currency ),
														number_format( $net_amount, 2 ),
														number_format( $amount, 2 )
													);
												} else {
													echo esc_html( $currency . ' ' . number_format( $amount, 2 ) );
												}
												?>
											</strong>
										</td>
										<td style="padding: 10px; font-size: 13px;">
											<?php
											$status         = $payment['status'] ?? 'unknown';
											$status_labels  = [
												'pending'  => __( 'Pending', 'sureforms' ),
												'succeeded' => __( 'Succeeded', 'sureforms' ),
												'failed'   => __( 'Failed', 'sureforms' ),
												'canceled' => __( 'Canceled', 'sureforms' ),
												'requires_action' => __( 'Requires Action', 'sureforms' ),
												'requires_payment_method' => __( 'Requires Payment Method', 'sureforms' ),
												'processing' => __( 'Processing', 'sureforms' ),
												'refunded' => __( 'Refunded', 'sureforms' ),
												'partially_refunded' => __( 'Partially Refunded', 'sureforms' ),
											];
											$status_display = $status_labels[ $status ] ?? ucfirst( $status );

											// Status styling
											$status_color = '#666';
											$bg_color     = '#f9f9f9';
											$border_color = '#ddd';

											if ( 'succeeded' === $status ) {
												$status_color = '#155724';
												$bg_color     = '#d4edda';
												$border_color = '#c3e6cb';
											} elseif ( in_array( $status, [ 'failed', 'canceled' ] ) ) {
												$status_color = '#721c24';
												$bg_color     = '#f8d7da';
												$border_color = '#f5c6cb';
											} elseif ( in_array( $status, [ 'refunded', 'partially_refunded' ] ) ) {
												$status_color = '#856404';
												$bg_color     = '#fff3cd';
												$border_color = '#ffeaa7';
											}
											?>
											<span style="
												padding: 2px 6px; 
												border-radius: 3px; 
												font-size: 11px; 
												font-weight: 600; 
												text-transform: uppercase;
												background-color: <?php echo esc_attr( $bg_color ); ?>;
												color: <?php echo esc_attr( $status_color ); ?>;
												border: 1px solid <?php echo esc_attr( $border_color ); ?>;
											">
												<?php echo esc_html( $status_display ); ?>
											</span>
										</td>
										<td style="padding: 10px; font-size: 13px;">
											<?php
											$single_payment_url = wp_nonce_url(
												add_query_arg(
													[
														'payment_id' => $payment['id'],
														'view'       => 'details',
													],
													admin_url( 'admin.php?page=sureforms_payments' )
												),
												'srfm_payments_action'
											);
											?>
											<a href="<?php echo esc_url( $single_payment_url ); ?>" 
											   class="button button-small" 
											   style="font-size: 12px; padding: 3px 8px;">
												<?php esc_html_e( 'View Details', 'sureforms' ); ?>
											</a>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the subscription logs postbox.
	 *
	 * @param array<mixed> $subscription_logs Subscription logs stored in the database.
	 * @since x.x.x
	 * @return void
	 */
	private function render_subscription_logs( $subscription_logs ) {
		ob_start();
		?>
		<div id="sureform_subscription_logs_meta" class="postbox srfm-subscription-logs">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Subscription Logs', 'sureforms' ); ?></h2>
			</div>
			<div class="inside">
				<table class="striped subscription-logs-table">
					<tbody>
						<?php if ( ! empty( $subscription_logs ) && is_array( $subscription_logs ) ) { ?>
							<?php foreach ( $subscription_logs as $log ) { ?>
								<tr>
									<td class="subscription-log-container">
										<div class="subscription-log">
											<h4 class="subscription-log-title">
												<?php
												if ( isset( $log['title'] ) ) {
													echo esc_html( $log['title'] );
												}
												if ( isset( $log['timestamp'] ) ) {
													echo ' ' . esc_html( gmdate( '\\a\\t Y-m-d H:i:s', $log['timestamp'] ) );
												}
												?>
											</h4>
											<div class="subscription-log-messages">
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
									<p class="no-logs-found"><?php esc_html_e( 'No logs found for this subscription.', 'sureforms' ); ?></p>
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

		echo wp_kses( apply_filters( 'srfm_subscription_logs_markup', $content, $subscription_logs ), $allowed_tags );
	}

	/**
	 * Format subscription payment value for display.
	 *
	 * @param mixed       $value The value to format.
	 * @param string|null $key Optional key for special formatting.
	 * @since x.x.x
	 * @return string Formatted value.
	 */
	private function format_subscription_value( $value, $key = null ) {
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
