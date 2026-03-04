<?php
/**
 * Payment History Shortcode.
 *
 * Renders a payment history table for logged-in users.
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

		wp_enqueue_style(
			'srfm-payment-history',
			SRFM_URL . 'assets/css/payment-history.css',
			[],
			SRFM_VER
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
		// $user_email   = $current_user->user_email;
		$user_email   = '';

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

		// Payment mode filter (default: live).
		// $mode = apply_filters( 'srfm_payment_history_mode', 'live' );
		// if ( ! empty( $mode ) ) {
		// 	$where[] = [
		// 		[
		// 			'key'     => 'mode',
		// 			'compare' => '=',
		// 			'value'   => sanitize_text_field( $mode ),
		// 		],
		// 	];
		// }

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
		 * Other gateways can hook into this to add their own customer matching logic.
		 *
		 * @since 2.6.0
		 * @param array                $where   WHERE conditions array.
		 * @param int                  $user_id WordPress user ID.
		 * @param array<string,string> $atts    Shortcode attributes.
		 */
		return apply_filters( 'srfm_payment_history_where_conditions', $where, $user_id, $atts );
	}

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
		/**
		 * Filter the columns displayed in the payment history table.
		 *
		 * @since 2.6.0
		 * @param array $columns Associative array of column_key => column_label.
		 */
		$columns = apply_filters(
			'srfm_payment_history_columns',
			[
				'date'           => __( 'Date', 'sureforms' ),
				'transaction_id' => __( 'Transaction ID', 'sureforms' ),
				'amount'         => __( 'Amount', 'sureforms' ),
				'status'         => __( 'Status', 'sureforms' ),
				'type'           => __( 'Type', 'sureforms' ),
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
		$currency = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD';

		$row_data = [
			'date'           => isset( $payment['created_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $payment['created_at'] ) ) : '—',
			'transaction_id' => isset( $payment['srfm_txn_id'] ) && ! empty( $payment['srfm_txn_id'] ) ? $payment['srfm_txn_id'] : ( isset( $payment['transaction_id'] ) ? $payment['transaction_id'] : '—' ),
			'amount'         => $this->format_amount( $payment['total_amount'] ?? 0, $currency ),
			'status'         => $this->get_status_badge( $payment['status'] ?? 'pending' ),
			'type'           => $this->get_payment_type_label( $payment['type'] ?? 'payment' ),
		];

		/**
		 * Filter the row data before rendering.
		 *
		 * @since 2.6.0
		 * @param array $row_data Associative array of column_key => display_value.
		 * @param array $payment  The raw payment record.
		 */
		$row_data = apply_filters( 'srfm_payment_history_row_data', $row_data, $payment );
		?>
		<tr>
			<?php foreach ( $columns as $key => $label ) : ?>
				<td class="srfm-payment-history-col-<?php echo esc_attr( $key ); ?>">
					<?php
					// Status badge contains HTML, so we use wp_kses_post. Others are escaped.
					if ( 'status' === $key ) {
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
			'pending'                 => __( 'Pending', 'sureforms' ),
			'succeeded'              => __( 'Succeeded', 'sureforms' ),
			'failed'                 => __( 'Failed', 'sureforms' ),
			'canceled'               => __( 'Canceled', 'sureforms' ),
			'requires_action'        => __( 'Requires Action', 'sureforms' ),
			'requires_payment_method' => __( 'Requires Payment', 'sureforms' ),
			'processing'             => __( 'Processing', 'sureforms' ),
			'refunded'               => __( 'Refunded', 'sureforms' ),
			'partially_refunded'     => __( 'Partially Refunded', 'sureforms' ),
			'active'                 => __( 'Active', 'sureforms' ),
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

		/**
		 * Filter the login required message.
		 *
		 * @since 2.6.0
		 * @param string $html Login message HTML.
		 */
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

		/**
		 * Filter the no payments found message.
		 *
		 * @since 2.6.0
		 * @param string $html Empty message HTML.
		 */
		return apply_filters( 'srfm_payment_history_empty_message', $html );
	}
}
