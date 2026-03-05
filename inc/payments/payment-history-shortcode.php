<?php
/**
 * Payment History Shortcode.
 *
 * Renders a payment history table for logged-in users with detail view and actions.
 *
 * @package sureforms
 * @since 2.6.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Payment History Shortcode class.
 *
 * @since 2.6.0
 */
class Payment_History_Shortcode {
	use Get_Instance;

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const SHORTCODE_TAG = 'srfm_payment_history';

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 */
	public function __construct() {
		add_shortcode( self::SHORTCODE_TAG, [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Frontend AJAX handlers.
		add_action( 'wp_ajax_srfm_frontend_refund_payment', [ $this, 'ajax_refund_payment' ] );
		add_action( 'wp_ajax_srfm_frontend_cancel_subscription', [ $this, 'ajax_cancel_subscription' ] );
	}

	/**
	 * Conditionally enqueue assets when the shortcode is present on the page.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function enqueue_assets() {
		global $post;

		if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, self::SHORTCODE_TAG ) ) {
			return;
		}

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		wp_enqueue_style(
			'srfm-payment-history',
			SRFM_URL . 'assets/css/' . $dir_name . '/payment-history' . $file_prefix . '.css',
			[],
			SRFM_VER
		);

		wp_enqueue_script(
			'srfm-payment-history',
			SRFM_URL . 'assets/js/payment-history.js',
			[],
			SRFM_VER,
			true
		);

		wp_localize_script(
			'srfm-payment-history',
			'srfm_payment_history',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'srfm_frontend_payment_nonce' ),
				'i18n'     => [
					'confirm_refund'            => __( 'Are you sure you want to refund this payment?', 'sureforms' ),
					'confirm_cancel'            => __( 'Are you sure you want to cancel this subscription? This action cannot be undone.', 'sureforms' ),
					'refund_success'            => __( 'Refund processed successfully.', 'sureforms' ),
					'cancel_success'            => __( 'Subscription cancelled successfully.', 'sureforms' ),
					'error'                     => __( 'Something went wrong. Please try again.', 'sureforms' ),
					'invalid_amount'            => __( 'Please enter a valid refund amount.', 'sureforms' ),
					'amount_exceeds_refundable' => __( 'Amount exceeds the refundable amount.', 'sureforms' ),
					'processing'                => __( 'Processing...', 'sureforms' ),
				],
			]
		);
	}

	/**
	 * Render the payment history shortcode.
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @since 2.6.0
	 * @return string HTML output.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'per_page'          => '10',
				'show_subscription' => 'true',
				'show_renewal'      => 'true',
				'gateway'           => '',
			],
			$atts,
			self::SHORTCODE_TAG
		);

		$per_page = absint( $atts['per_page'] );
		if ( $per_page <= 0 ) {
			$per_page = 10;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return $this->get_login_message();
		}

		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
		$user_email   = '';

		// Check if viewing a single payment detail.
		$view_payment_id = isset( $_GET['srfm_view'] ) ? absint( $_GET['srfm_view'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $view_payment_id > 0 ) {
			return $this->render_detail_view( $view_payment_id, $user_id );
		}

		// Build WHERE conditions for the query.
		$where = $this->build_where_conditions( $user_id, $user_email, $atts );

		// Pagination.
		$current_page = isset( $_GET['srfm_page'] ) ? absint( $_GET['srfm_page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $current_page < 1 ) {
			$current_page = 1;
		}
		$offset = ( $current_page - 1 ) * $per_page;

		// Build query args.
		$query_args = [
			'where'   => $where,
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		];

		/**
		 * Filter the query arguments before fetching payments.
		 *
		 * @since 2.6.0
		 * @param array $query_args Query arguments for Payments::get_all().
		 * @param int   $user_id    Current user ID.
		 */
		$query_args = apply_filters( 'srfm_payment_history_query_args', $query_args, $user_id );

		// Fetch payments.
		$payments = Payments::get_all( $query_args );

		// Get total count for pagination.
		$total_count = Payments::get_instance()->get_total_count( $where );
		$total_pages = $per_page > 0 ? (int) ceil( $total_count / $per_page ) : 1;

		// Build HTML output.
		if ( empty( $payments ) ) {
			return $this->get_empty_message();
		}

		ob_start();
		$this->render_table( $payments, $current_page, $total_pages, $atts );
		$html = ob_get_clean();

		/**
		 * Filter the final payment history HTML output.
		 *
		 * @since 2.6.0
		 * @param string $html     The HTML output.
		 * @param array  $payments The payment records.
		 * @param array  $atts     The shortcode attributes.
		 */
		return apply_filters( 'srfm_payment_history_output', $html, $payments, $atts );
	}

	/**
	 * Build WHERE conditions for the payment query.
	 *
	 * @param int                  $user_id    WordPress user ID.
	 * @param string               $user_email User email address.
	 * @param array<string,string> $atts       Shortcode attributes.
	 * @since 2.6.0
	 * @return array WHERE conditions array for Payments::get_all().
	 */
	private function build_where_conditions( $user_id, $user_email, $atts ) {
		$stripe_customer_id = get_user_meta( $user_id, 'srfm_stripe_customer_id', true );

		// OR group: match by customer_id or customer_email.
		$or_conditions = [];

		if ( ! empty( $stripe_customer_id ) && is_string( $stripe_customer_id ) ) {
			$or_conditions[] = [
				'key'     => 'customer_id',
				'compare' => '=',
				'value'   => $stripe_customer_id,
			];
		}

		if ( ! empty( $user_email ) ) {
			$or_conditions[] = [
				'key'     => 'customer_email',
				'compare' => '=',
				'value'   => $user_email,
			];
		}

		$where = [];

		// Add OR group for customer matching.
		if ( ! empty( $or_conditions ) ) {
			$where[] = $or_conditions;
		}

		// Gateway filter.
		$gateway = ! empty( $atts['gateway'] ) ? sanitize_text_field( $atts['gateway'] ) : '';
		if ( ! empty( $gateway ) ) {
			$where[] = [
				[
					'key'     => 'gateway',
					'compare' => '=',
					'value'   => $gateway,
				],
			];
		}

		// Type filter: exclude subscription/renewal if attributes say so.
		$excluded_types = [];
		if ( 'false' === $atts['show_subscription'] ) {
			$excluded_types[] = 'subscription';
		}
		if ( 'false' === $atts['show_renewal'] ) {
			$excluded_types[] = 'renewal';
		}
		if ( ! empty( $excluded_types ) ) {
			foreach ( $excluded_types as $type ) {
				$where[] = [
					[
						'key'     => 'type',
						'compare' => '!=',
						'value'   => $type,
					],
				];
			}
		}

		/**
		 * Filter the WHERE conditions for the payment history query.
		 *
		 * @since 2.6.0
		 * @param array                $where   WHERE conditions array.
		 * @param int                  $user_id WordPress user ID.
		 * @param array<string,string> $atts    Shortcode attributes.
		 */
		return apply_filters( 'srfm_payment_history_where_conditions', $where, $user_id, $atts );
	}

	// =========================================================================
	// List View
	// =========================================================================

	/**
	 * Render the payment history table.
	 *
	 * @param array $payments     Payment records.
	 * @param int   $current_page Current page number.
	 * @param int   $total_pages  Total number of pages.
	 * @param array $atts         Shortcode attributes.
	 * @since 2.6.0
	 * @return void
	 */
	private function render_table( $payments, $current_page, $total_pages, $atts ) {
		$columns = apply_filters(
			'srfm_payment_history_columns',
			[
				'date'           => __( 'Date', 'sureforms' ),
				'transaction_id' => __( 'Transaction ID', 'sureforms' ),
				'amount'         => __( 'Amount', 'sureforms' ),
				'status'         => __( 'Status', 'sureforms' ),
				'type'           => __( 'Type', 'sureforms' ),
				'action'         => __( 'Action', 'sureforms' ),
			]
		);
		?>
		<div class="srfm-payment-history-wrap">
			<div class="srfm-payment-history-table-container">
				<table class="srfm-payment-history-table">
					<thead>
						<tr>
							<?php foreach ( $columns as $key => $label ) : ?>
								<th class="srfm-payment-history-col-<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $payments as $payment ) : ?>
							<?php $this->render_row( $payment, $columns ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
			if ( $total_pages > 1 ) {
				$this->render_pagination( $current_page, $total_pages );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render a single payment row.
	 *
	 * @param array $payment Payment record.
	 * @param array $columns Column definitions.
	 * @since 2.6.0
	 * @return void
	 */
	private function render_row( $payment, $columns ) {
		$currency   = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD';
		$payment_id = isset( $payment['id'] ) ? absint( $payment['id'] ) : 0;
		$view_url   = add_query_arg( 'srfm_view', $payment_id );

		$row_data = [
			'date'           => isset( $payment['created_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $payment['created_at'] ) ) : '—',
			'transaction_id' => isset( $payment['srfm_txn_id'] ) && ! empty( $payment['srfm_txn_id'] ) ? $payment['srfm_txn_id'] : ( isset( $payment['transaction_id'] ) ? $payment['transaction_id'] : '—' ),
			'amount'         => $this->format_amount( $payment['total_amount'] ?? 0, $currency ),
			'status'         => $this->get_status_badge( $payment['status'] ?? 'pending' ),
			'type'           => $this->get_payment_type_label( $payment['type'] ?? 'payment' ),
			'action'         => sprintf(
				'<a href="%s" class="srfm-payment-history-view-link">%s</a>',
				esc_url( $view_url ),
				esc_html__( 'View Details', 'sureforms' )
			),
		];

		/** @since 2.6.0 */
		$row_data = apply_filters( 'srfm_payment_history_row_data', $row_data, $payment );
		?>
		<tr>
			<?php foreach ( $columns as $key => $label ) : ?>
				<td class="srfm-payment-history-col-<?php echo esc_attr( $key ); ?>">
					<?php
					if ( in_array( $key, [ 'status', 'action' ], true ) ) {
						echo wp_kses_post( $row_data[ $key ] ?? '—' );
					} else {
						echo esc_html( $row_data[ $key ] ?? '—' );
					}
					?>
				</td>
			<?php endforeach; ?>
		</tr>
		<?php
	}

	/**
	 * Render pagination links.
	 *
	 * @param int $current_page Current page number.
	 * @param int $total_pages  Total number of pages.
	 * @since 2.6.0
	 * @return void
	 */
	private function render_pagination( $current_page, $total_pages ) {
		$base_url = remove_query_arg( 'srfm_page' );
		?>
		<nav class="srfm-payment-history-pagination" aria-label="<?php esc_attr_e( 'Payment history navigation', 'sureforms' ); ?>">
			<?php if ( $current_page > 1 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'srfm_page', $current_page - 1, $base_url ) ); ?>" class="srfm-payment-history-page-link srfm-payment-history-prev">
					&laquo; <?php esc_html_e( 'Previous', 'sureforms' ); ?>
				</a>
			<?php endif; ?>

			<span class="srfm-payment-history-page-info">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'sureforms' ),
					$current_page,
					$total_pages
				);
				?>
			</span>

			<?php if ( $current_page < $total_pages ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'srfm_page', $current_page + 1, $base_url ) ); ?>" class="srfm-payment-history-page-link srfm-payment-history-next">
					<?php esc_html_e( 'Next', 'sureforms' ); ?> &raquo;
				</a>
			<?php endif; ?>
		</nav>
		<?php
	}

	// =========================================================================
	// Detail View
	// =========================================================================

	/**
	 * Render the single payment detail view.
	 *
	 * @param int $payment_id Payment ID from the database.
	 * @param int $user_id    Current logged-in user ID.
	 * @since 2.6.0
	 * @return string HTML output.
	 */
	private function render_detail_view( $payment_id, $user_id ) {
		$payment = Payments::get( $payment_id );

		if ( ! $payment || ! $this->user_owns_payment( $payment, $user_id ) ) {
			return sprintf(
				'<div class="srfm-payment-history-wrap"><p class="srfm-payment-history-empty">%s</p></div>',
				esc_html__( 'Payment not found.', 'sureforms' )
			);
		}

		$currency        = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD';
		$total_amount    = floatval( $payment['total_amount'] ?? 0 );
		$refunded_amount = floatval( $payment['refunded_amount'] ?? 0 );
		$status          = $payment['status'] ?? 'pending';
		$type            = $payment['type'] ?? 'payment';
		$gateway         = $payment['gateway'] ?? '';
		$is_subscription = in_array( $type, [ 'subscription' ], true );
		$back_url        = remove_query_arg( 'srfm_view' );

		// Determine if refund is possible.
		$refundable_statuses = [ 'succeeded', 'partially_refunded', 'active' ];
		$can_refund          = in_array( $status, $refundable_statuses, true ) && ! empty( $payment['transaction_id'] );
		$refundable_amount   = $total_amount - $refunded_amount;

		// Subscription-specific data.
		$can_cancel_subscription = $is_subscription && in_array( $payment['subscription_status'] ?? '', [ 'active', 'trialing' ], true );

		// Billing history for subscriptions.
		$billing_payments = [];
		if ( $is_subscription && ! empty( $payment['subscription_id'] ) ) {
			$billing_payments = Payments::get_subscription_related_payments( $payment['subscription_id'] );
		}

		// Refund history from payment_data.
		$payment_data = $payment['payment_data'] ?? [];
		$refunds      = [];
		if ( is_array( $payment_data ) && ! empty( $payment_data['refunds'] ) && is_array( $payment_data['refunds'] ) ) {
			$refunds = $payment_data['refunds'];
		}

		// Payment logs.
		$logs = isset( $payment['log'] ) && is_array( $payment['log'] ) ? $payment['log'] : [];

		ob_start();
		?>
		<div class="srfm-payment-history-wrap">
			<!-- Back link -->
			<a href="<?php echo esc_url( $back_url ); ?>" class="srfm-ph-detail-back">
				&larr; <?php esc_html_e( 'Back to Payment History', 'sureforms' ); ?>
			</a>

			<!-- Header -->
			<div class="srfm-ph-detail-header">
				<div class="srfm-ph-detail-header-left">
					<h3 class="srfm-ph-detail-title">
						<?php
						if ( $is_subscription ) {
							printf(
								/* translators: %d: payment ID */
								esc_html__( 'Subscription #%d', 'sureforms' ),
								$payment_id
							);
						} else {
							printf(
								/* translators: %d: payment ID */
								esc_html__( 'Payment #%d', 'sureforms' ),
								$payment_id
							);
						}
						?>
					</h3>
					<?php echo wp_kses_post( $this->get_status_badge( $status ) ); ?>
				</div>
				<div class="srfm-ph-detail-header-right">
					<span class="srfm-ph-detail-amount"><?php echo esc_html( $this->format_amount( $total_amount, $currency ) ); ?></span>
				</div>
			</div>

			<div class="srfm-ph-detail-grid">
				<!-- Left column: Payment Info -->
				<div class="srfm-ph-detail-main">
					<!-- Payment Information -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Payment Information', 'sureforms' ); ?></h4>
						<table class="srfm-ph-detail-info-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Payment ID', 'sureforms' ); ?></th>
									<td><?php echo esc_html( $payment_id ); ?></td>
								</tr>
								<?php if ( ! empty( $payment['srfm_txn_id'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Transaction ID', 'sureforms' ); ?></th>
									<td><code><?php echo esc_html( $payment['srfm_txn_id'] ); ?></code></td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><?php esc_html_e( 'Gateway', 'sureforms' ); ?></th>
									<td><?php echo esc_html( ucfirst( $gateway ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Type', 'sureforms' ); ?></th>
									<td><?php echo esc_html( $this->get_payment_type_label( $type ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Mode', 'sureforms' ); ?></th>
									<td>
										<span class="srfm-ph-detail-mode srfm-ph-detail-mode--<?php echo esc_attr( $payment['mode'] ?? 'test' ); ?>">
											<?php echo esc_html( ucfirst( $payment['mode'] ?? 'test' ) ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Date', 'sureforms' ); ?></th>
									<td><?php echo esc_html( isset( $payment['created_at'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $payment['created_at'] ) ) : '—' ); ?></td>
								</tr>
								<?php if ( ! empty( $payment['customer_name'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Customer', 'sureforms' ); ?></th>
									<td><?php echo esc_html( $payment['customer_name'] ); ?></td>
								</tr>
								<?php endif; ?>
								<?php if ( ! empty( $payment['customer_email'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Email', 'sureforms' ); ?></th>
									<td><?php echo esc_html( $payment['customer_email'] ); ?></td>
								</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<!-- Billing Details -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Billing Details', 'sureforms' ); ?></h4>
						<table class="srfm-ph-detail-info-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Amount', 'sureforms' ); ?></th>
									<td class="srfm-ph-detail-amount-cell">
										<?php echo esc_html( $this->format_amount( $total_amount, $currency ) ); ?>
										<?php if ( $refunded_amount > 0 ) : ?>
											<span class="srfm-ph-detail-refunded-note">
												<?php
												printf(
													/* translators: %s: refunded amount */
													esc_html__( '(%s refunded)', 'sureforms' ),
													esc_html( $this->format_amount( $refunded_amount, $currency ) )
												);
												?>
											</span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Currency', 'sureforms' ); ?></th>
									<td><?php echo esc_html( $currency ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Status', 'sureforms' ); ?></th>
									<td><?php echo wp_kses_post( $this->get_status_badge( $status ) ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<?php if ( $is_subscription ) : ?>
					<!-- Subscription Details -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Subscription Details', 'sureforms' ); ?></h4>
						<table class="srfm-ph-detail-info-table">
							<tbody>
								<?php if ( ! empty( $payment['subscription_id'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Subscription ID', 'sureforms' ); ?></th>
									<td><code><?php echo esc_html( $payment['subscription_id'] ); ?></code></td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><?php esc_html_e( 'Subscription Status', 'sureforms' ); ?></th>
									<td><?php echo wp_kses_post( $this->get_status_badge( $payment['subscription_status'] ?? 'unknown' ) ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<?php if ( ! empty( $billing_payments ) ) : ?>
					<!-- Billing History -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Billing History', 'sureforms' ); ?></h4>
						<div class="srfm-payment-history-table-container">
							<table class="srfm-payment-history-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Amount', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Status', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Type', 'sureforms' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $billing_payments as $bp ) : ?>
									<tr>
										<td><?php echo esc_html( isset( $bp['created_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $bp['created_at'] ) ) : '—' ); ?></td>
										<td><?php echo esc_html( $this->format_amount( $bp['total_amount'] ?? 0, strtoupper( $bp['currency'] ?? $currency ) ) ); ?></td>
										<td><?php echo wp_kses_post( $this->get_status_badge( $bp['status'] ?? 'pending' ) ); ?></td>
										<td><?php echo esc_html( $this->get_payment_type_label( $bp['type'] ?? 'renewal' ) ); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $refunds ) ) : ?>
					<!-- Refund History -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Refund History', 'sureforms' ); ?></h4>
						<div class="srfm-payment-history-table-container">
							<table class="srfm-payment-history-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Amount', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Status', 'sureforms' ); ?></th>
										<th><?php esc_html_e( 'Refund ID', 'sureforms' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $refunds as $refund ) : ?>
									<tr>
										<td><?php echo esc_html( ! empty( $refund['refunded_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $refund['refunded_at'] ) ) : '—' ); ?></td>
										<td><?php echo esc_html( $this->format_amount( Stripe_Helper::amount_from_stripe_format( $refund['amount'] ?? 0, $currency ), $currency ) ); ?></td>
										<td><?php echo wp_kses_post( $this->get_status_badge( $refund['status'] ?? 'processed' ) ); ?></td>
										<td><code><?php echo esc_html( $refund['refund_id'] ?? '—' ); ?></code></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $logs ) ) : ?>
					<!-- Payment Logs -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Payment Logs', 'sureforms' ); ?></h4>
						<div class="srfm-ph-detail-logs">
							<?php foreach ( array_reverse( $logs ) as $log ) : ?>
							<div class="srfm-ph-detail-log-entry">
								<div class="srfm-ph-detail-log-title">
									<?php echo esc_html( $log['title'] ?? '' ); ?>
									<span class="srfm-ph-detail-log-date">
										<?php echo esc_html( ! empty( $log['created_at'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['created_at'] ) ) : '' ); ?>
									</span>
								</div>
								<?php if ( ! empty( $log['messages'] ) && is_array( $log['messages'] ) ) : ?>
								<ul class="srfm-ph-detail-log-messages">
									<?php foreach ( $log['messages'] as $msg ) : ?>
										<li><?php echo esc_html( $msg ); ?></li>
									<?php endforeach; ?>
								</ul>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<!-- Right column: Actions -->
				<div class="srfm-ph-detail-sidebar">
					<!-- Actions -->
					<div class="srfm-ph-detail-section">
						<h4 class="srfm-ph-detail-section-title"><?php esc_html_e( 'Actions', 'sureforms' ); ?></h4>

						<?php if ( $can_refund && $refundable_amount > 0 ) : ?>
						<!-- Refund Form -->
						<div class="srfm-ph-action-block" id="srfm-refund-block">
							<label class="srfm-ph-action-label" for="srfm-refund-amount">
								<?php esc_html_e( 'Refund Amount', 'sureforms' ); ?>
								<span class="srfm-ph-action-hint">
									<?php
									printf(
										/* translators: %s: maximum refundable amount */
										esc_html__( 'Max: %s', 'sureforms' ),
										esc_html( $this->format_amount( $refundable_amount, $currency ) )
									);
									?>
								</span>
							</label>
							<div class="srfm-ph-action-input-group">
								<span class="srfm-ph-action-currency"><?php echo esc_html( Payment_Helper::get_currency_symbol( $currency ) ); ?></span>
								<input
									type="number"
									id="srfm-refund-amount"
									class="srfm-ph-action-input"
									step="0.01"
									min="0.01"
									max="<?php echo esc_attr( number_format( $refundable_amount, 2, '.', '' ) ); ?>"
									value="<?php echo esc_attr( number_format( $refundable_amount, 2, '.', '' ) ); ?>"
									placeholder="0.00"
								/>
							</div>
							<label class="srfm-ph-action-label" for="srfm-refund-notes" style="margin-top: 10px;">
								<?php esc_html_e( 'Refund Notes', 'sureforms' ); ?>
								<span class="srfm-ph-action-hint"><?php esc_html_e( '(Optional)', 'sureforms' ); ?></span>
							</label>
							<textarea id="srfm-refund-notes" class="srfm-ph-action-textarea" rows="2" placeholder="<?php esc_attr_e( 'Reason for refund...', 'sureforms' ); ?>"></textarea>
							<button
								type="button"
								class="srfm-ph-action-btn srfm-ph-action-btn--refund"
								data-payment-id="<?php echo esc_attr( $payment_id ); ?>"
								data-transaction-id="<?php echo esc_attr( $payment['transaction_id'] ?? '' ); ?>"
								data-currency="<?php echo esc_attr( $currency ); ?>"
								data-max-refund="<?php echo esc_attr( number_format( $refundable_amount, 2, '.', '' ) ); ?>"
								data-zero-decimal="<?php echo esc_attr( Payment_Helper::is_zero_decimal_currency( $currency ) ? '1' : '0' ); ?>"
							>
								<?php esc_html_e( 'Process Refund', 'sureforms' ); ?>
							</button>
							<div class="srfm-ph-action-message" id="srfm-refund-message" style="display:none;"></div>
						</div>
						<?php endif; ?>

						<?php if ( $can_cancel_subscription ) : ?>
						<!-- Cancel Subscription -->
						<div class="srfm-ph-action-block">
							<p class="srfm-ph-action-description">
								<?php esc_html_e( 'Cancelling the subscription will stop all future billing. This action cannot be undone.', 'sureforms' ); ?>
							</p>
							<button
								type="button"
								class="srfm-ph-action-btn srfm-ph-action-btn--cancel"
								data-payment-id="<?php echo esc_attr( $payment_id ); ?>"
							>
								<?php esc_html_e( 'Cancel Subscription', 'sureforms' ); ?>
							</button>
							<div class="srfm-ph-action-message" id="srfm-cancel-message" style="display:none;"></div>
						</div>
						<?php endif; ?>

						<?php if ( ! $can_refund && ! $can_cancel_subscription ) : ?>
						<p class="srfm-ph-action-none">
							<?php esc_html_e( 'No actions available for this payment.', 'sureforms' ); ?>
						</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Check if the current user owns the payment record.
	 *
	 * @param array $payment Payment record.
	 * @param int   $user_id WordPress user ID.
	 * @since 2.6.0
	 * @return bool
	 */
	private function user_owns_payment( $payment, $user_id ) {
		$stripe_customer_id = get_user_meta( $user_id, 'srfm_stripe_customer_id', true );
		$user_email         = wp_get_current_user()->user_email;

		// Match by customer_id.
		if ( ! empty( $stripe_customer_id ) && ! empty( $payment['customer_id'] ) && $stripe_customer_id === $payment['customer_id'] ) {
			return true;
		}

		// Match by customer_email.
		if ( ! empty( $user_email ) && ! empty( $payment['customer_email'] ) && $user_email === $payment['customer_email'] ) {
			return true;
		}

		/**
		 * Filter whether the user owns the payment. Allows other gateways to add ownership checks.
		 *
		 * @since 2.6.0
		 * @param bool  $owns    Whether the user owns the payment.
		 * @param array $payment Payment record.
		 * @param int   $user_id WordPress user ID.
		 */
		return apply_filters( 'srfm_payment_history_user_owns_payment', false, $payment, $user_id );
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX handler for frontend refund requests.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_refund_payment() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_frontend_payment_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'sureforms' ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'sureforms' ) );
		}

		$payment_id     = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		$refund_amount  = isset( $_POST['refund_amount'] ) ? absint( $_POST['refund_amount'] ) : 0;
		$refund_notes   = isset( $_POST['refund_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['refund_notes'] ) ) : '';
		$transaction_id = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';

		if ( empty( $payment_id ) || empty( $transaction_id ) || $refund_amount <= 0 ) {
			wp_send_json_error( __( 'Invalid payment data.', 'sureforms' ) );
		}

		// Verify ownership.
		$payment = Payments::get( $payment_id );
		if ( ! $payment || ! $this->user_owns_payment( $payment, get_current_user_id() ) ) {
			wp_send_json_error( __( 'Payment not found.', 'sureforms' ) );
		}

		$gateway = isset( $payment['gateway'] ) && is_string( $payment['gateway'] ) ? $payment['gateway'] : '';
		if ( empty( $gateway ) ) {
			wp_send_json_error( __( 'Payment gateway not found.', 'sureforms' ) );
		}

		/**
		 * Delegate to the same gateway-agnostic filter system used by the admin.
		 *
		 * @since 2.6.0
		 */
		$refund_result = apply_filters(
			'srfm_process_transaction_refund',
			[
				'success' => false,
				'message' => __( 'Refund processing is not supported for this gateway.', 'sureforms' ),
				'data'    => [],
			],
			[
				'payment'        => $payment,
				'payment_id'     => $payment_id,
				'transaction_id' => $transaction_id,
				'refund_amount'  => $refund_amount,
				'refund_notes'   => $refund_notes,
				'gateway'        => $gateway,
			]
		);

		if ( ! empty( $refund_result['success'] ) && true === $refund_result['success'] ) {
			wp_send_json_success( [
				'message' => $refund_result['message'] ?? __( 'Refund processed successfully.', 'sureforms' ),
			] );
		} else {
			wp_send_json_error( $refund_result['message'] ?? __( 'Failed to process refund.', 'sureforms' ) );
		}
	}

	/**
	 * AJAX handler for frontend subscription cancellation.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_cancel_subscription() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_frontend_payment_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'sureforms' ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'sureforms' ) );
		}

		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		if ( empty( $payment_id ) ) {
			wp_send_json_error( __( 'Invalid payment data.', 'sureforms' ) );
		}

		// Verify ownership.
		$payment = Payments::get( $payment_id );
		if ( ! $payment || ! $this->user_owns_payment( $payment, get_current_user_id() ) ) {
			wp_send_json_error( __( 'Payment not found.', 'sureforms' ) );
		}

		// Verify it's a subscription.
		$type = $payment['type'] ?? '';
		if ( 'subscription' !== $type || empty( $payment['subscription_id'] ) ) {
			wp_send_json_error( __( 'This payment is not a subscription.', 'sureforms' ) );
		}

		/**
		 * Filter to process subscription cancellation. Gateways hook into this.
		 *
		 * @since 2.6.0
		 * @param array $result  Default result.
		 * @param array $payment Payment record.
		 */
		$result = apply_filters(
			'srfm_process_subscription_cancellation',
			[ 'success' => false, 'message' => __( 'Cancellation not supported for this gateway.', 'sureforms' ) ],
			$payment
		);

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( [ 'message' => $result['message'] ?? __( 'Subscription cancelled successfully.', 'sureforms' ) ] );
		} else {
			wp_send_json_error( $result['message'] ?? __( 'Failed to cancel subscription.', 'sureforms' ) );
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Format a payment amount with currency symbol.
	 *
	 * @param mixed  $amount   Payment amount.
	 * @param string $currency Currency code (e.g., 'USD').
	 * @since 2.6.0
	 * @return string Formatted amount string.
	 */
	private function format_amount( $amount, $currency ) {
		$amount   = floatval( $amount );
		$symbol   = Payment_Helper::get_currency_symbol( $currency );
		$position = Payment_Helper::get_currency_sign_position();

		if ( Payment_Helper::is_zero_decimal_currency( $currency ) ) {
			$formatted = number_format( $amount, 0 );
		} else {
			$formatted = number_format( $amount, 2 );
		}

		switch ( $position ) {
			case 'right':
				return $formatted . $symbol;
			case 'left_space':
				return $symbol . ' ' . $formatted;
			case 'right_space':
				return $formatted . ' ' . $symbol;
			case 'left':
			default:
				return $symbol . $formatted;
		}
	}

	/**
	 * Get a status badge HTML element.
	 *
	 * @param string $status Payment status.
	 * @since 2.6.0
	 * @return string HTML status badge.
	 */
	private function get_status_badge( $status ) {
		$labels = [
			'pending'                  => __( 'Pending', 'sureforms' ),
			'succeeded'                => __( 'Succeeded', 'sureforms' ),
			'failed'                   => __( 'Failed', 'sureforms' ),
			'canceled'                 => __( 'Canceled', 'sureforms' ),
			'requires_action'          => __( 'Requires Action', 'sureforms' ),
			'requires_payment_method'  => __( 'Requires Payment', 'sureforms' ),
			'processing'               => __( 'Processing', 'sureforms' ),
			'refunded'                 => __( 'Refunded', 'sureforms' ),
			'partially_refunded'       => __( 'Partially Refunded', 'sureforms' ),
			'active'                   => __( 'Active', 'sureforms' ),
			'trialing'                 => __( 'Trialing', 'sureforms' ),
			'past_due'                 => __( 'Past Due', 'sureforms' ),
			'paused'                   => __( 'Paused', 'sureforms' ),
			'incomplete'               => __( 'Incomplete', 'sureforms' ),
		];

		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );

		return sprintf(
			'<span class="srfm-payment-history-status srfm-payment-history-status--%s">%s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Get human-readable payment type label.
	 *
	 * @param string $type Payment type.
	 * @since 2.6.0
	 * @return string Payment type label.
	 */
	private function get_payment_type_label( $type ) {
		$labels = [
			'payment'      => __( 'One-time', 'sureforms' ),
			'subscription' => __( 'Subscription', 'sureforms' ),
			'renewal'      => __( 'Renewal', 'sureforms' ),
		];

		return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type );
	}

	/**
	 * Get the login required message.
	 *
	 * @since 2.6.0
	 * @return string HTML login message.
	 */
	private function get_login_message() {
		$html = sprintf(
			'<div class="srfm-payment-history-wrap"><p class="srfm-payment-history-login">%s</p></div>',
			esc_html__( 'Please log in to view your payment history.', 'sureforms' )
		);

		/** @since 2.6.0 */
		return apply_filters( 'srfm_payment_history_login_message', $html );
	}

	/**
	 * Get the no payments found message.
	 *
	 * @since 2.6.0
	 * @return string HTML empty message.
	 */
	private function get_empty_message() {
		$html = sprintf(
			'<div class="srfm-payment-history-wrap"><p class="srfm-payment-history-empty">%s</p></div>',
			esc_html__( 'No payments found.', 'sureforms' )
		);

		/** @since 2.6.0 */
		return apply_filters( 'srfm_payment_history_empty_message', $html );
	}
}
