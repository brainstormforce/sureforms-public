<?php
/**
 * Payment History Shortcode.
 *
 * Renders a modern payment dashboard with subscriptions section, payment history,
 * and overlay detail panels for logged-in users.
 *
 * @package sureforms
 * @since 2.8.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Payment History Shortcode class.
 *
 * @since 2.8.0
 */
class Payment_History_Shortcode {
	use Get_Instance;

	/**
	 * Shortcode tag.
	 */
	public const SHORTCODE_TAG = 'srfm_payment_history';

	/**
	 * Constructor.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		add_shortcode( self::SHORTCODE_TAG, [ $this, 'render' ] );
		// Register the handles early (priority 1) — before Elementor/Bricks enqueue
		// their widget assets on wp_enqueue_scripts — so the page-builder widgets can
		// enqueue the stylesheet by handle in the <head> via get_style_depends() /
		// enqueue_scripts(). Registration is unconditional and cheap; the actual
		// enqueue below stays gated on the block/shortcode being present.
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 1 );
		// accepted_args 0: WordPress fires this hook with an empty-string sentinel arg,
		// which would land in $from_render (falsy, so harmless, but it contradicts the
		// bool contract). Capping to 0 args means the bool default (false) is used.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 10, 0 );

		// Frontend AJAX handlers.
		add_action( 'wp_ajax_srfm_frontend_cancel_subscription', [ $this, 'ajax_cancel_subscription' ] );
	}

	/**
	 * Register the payment-history stylesheet and script handles unconditionally so
	 * they can later be enqueued by handle. This exists for the Elementor
	 * (get_style_depends()) and Bricks (enqueue_scripts()) payment-history widgets,
	 * whose content lives in postmeta and so isn't caught by the has_block() /
	 * has_shortcode() gate in enqueue_assets(); declaring the style as a widget
	 * dependency lets those builders load it in the <head> and avoid a FOUC.
	 *
	 * Registering (not enqueuing) keeps the assets off pages that don't use the
	 * feature — nothing is printed until something enqueues the handle.
	 *
	 * @since 2.12.3
	 * @return void
	 */
	public function register_assets() {
		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		if ( ! wp_style_is( 'srfm-payment-history', 'registered' ) ) {
			wp_register_style(
				'srfm-payment-history',
				SRFM_URL . 'assets/css/' . $dir_name . '/payment-history' . $file_prefix . '.css',
				[],
				SRFM_VER
			);
		}

		if ( ! wp_script_is( 'srfm-payment-history', 'registered' ) ) {
			wp_register_script(
				'srfm-payment-history',
				SRFM_URL . 'assets/js/payment-history.js',
				[],
				SRFM_VER,
				true
			);
		}
	}

	/**
	 * Conditionally enqueue assets only when the payment history block/shortcode is present.
	 *
	 * Runs on wp_enqueue_scripts (where the global $post is available for detection) and
	 * again at render time (shortcode/block callback). Elementor and Bricks store their
	 * content in postmeta rather than post_content, so has_block()/has_shortcode() can't
	 * detect them here — those widgets instead declare the (pre-registered) stylesheet as
	 * a dependency so it loads in the <head> (see register_assets() + the widget classes).
	 *
	 * Both the stylesheet and the script are gated on the block/shortcode actually being
	 * present, so the CSS is no longer loaded on every frontend page. When enqueued from
	 * render() the stylesheet is printed with the footer styles, which is acceptable for the
	 * rare case of the block placed via an FSE template part or block widget.
	 *
	 * The stylesheet is always enqueued for a detected placement (logged in or out) so the
	 * login message stays styled; the script + localized nonce are enqueued only for
	 * logged-in users, since the cancel handler re-checks auth server-side and there is no
	 * `wp_ajax_nopriv_` endpoint — a logged-out visitor would only receive an inert script.
	 *
	 * @since 2.8.0
	 * @since 2.12.3 Enqueue the stylesheet only when the block/shortcode is present instead of on every frontend page; withhold the script + nonce from logged-out visitors.
	 * @param bool $from_render Whether this is the render()-time fallback call. When
	 *                          true the block/shortcode presence gate is skipped
	 *                          because render() only runs when the widget is on the
	 *                          page. Passed explicitly rather than sniffed via
	 *                          doing_action(), which would also match a nested
	 *                          do_shortcode() invoked inside a wp_enqueue_scripts callback.
	 * @return void
	 */
	public function enqueue_assets( $from_render = false ) {
		// On the wp_enqueue_scripts hook, confirm the shortcode/block is present on the
		// current page. From render() we already know it is needed.
		if ( ! $from_render ) {
			global $post;

			if ( ! $post instanceof \WP_Post ) {
				return;
			}

			$has_shortcode = has_shortcode( $post->post_content, self::SHORTCODE_TAG );
			$has_block     = has_block( 'srfm/payment-history', $post );

			if ( ! $has_shortcode && ! $has_block ) {
				return;
			}
		}

		// Ensure both handles exist (register_assets() is idempotent). Guarding on only
		// the style handle would miss a script handle that was separately deregistered
		// (asset-optimisation plugins do this by handle), leaving wp_localize_script()
		// below with nothing to attach to and silently dropping the nonce.
		$this->register_assets();

		// Enqueue the stylesheet only for pages that actually use payment history.
		if ( ! wp_style_is( 'srfm-payment-history', 'enqueued' ) ) {
			wp_enqueue_style( 'srfm-payment-history' );
		}

		// JS + localized data are only useful to logged-in users: the cancel handler
		// re-checks authentication server-side and there is no `wp_ajax_nopriv_`
		// registration, so an anonymous visitor (who only ever sees the login message)
		// would receive an inert script and a pointless nonce. The CSS above still loads
		// so the login message stays styled; only the script + localize are gated here.
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! wp_script_is( 'srfm-payment-history', 'enqueued' ) ) {
			wp_enqueue_script( 'srfm-payment-history' );
		}

		// Gate the localize on whether the data is already attached, not on the enqueued
		// state: the handle is now registered on every frontend page, so a foreign
		// enqueue-by-handle before this runs must not cause the nonce to be skipped.
		if ( ! wp_scripts()->get_data( 'srfm-payment-history', 'data' ) ) {
			wp_localize_script(
				'srfm-payment-history',
				'srfm_payment_history',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'srfm_frontend_payment_nonce' ),
					'i18n'     => $this->get_i18n_strings(),
				]
			);
		}
	}

	/**
	 * Render the payment history shortcode.
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @since 2.8.0
	 * @return string HTML output.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'per_page'          => '10',
				'show_subscription' => 'true',
			],
			is_array( $atts ) ? $atts : [],
			self::SHORTCODE_TAG
		);

		$per_page = absint( $atts['per_page'] );
		if ( $per_page <= 0 ) {
			$per_page = 10;
		}

		// Enqueue assets at render time — the last-resort fallback for FSE template
		// parts / block widgets where $post can't be detected on wp_enqueue_scripts and
		// there is no builder style-dependency API. Elementor/Bricks widgets enqueue the
		// stylesheet in the <head> via their own dependency hooks, so this mainly serves
		// the genuinely rare FSE case (footer-loaded CSS, acceptable there). Runs before
		// the logged-out early return so the login message is styled too.
		$this->enqueue_assets( true );

		if ( ! is_user_logged_in() ) {
			return $this->get_login_message();
		}

		$user_id = get_current_user_id();
		$where   = $this->build_where_conditions( $user_id, $atts );

		// Fetch subscriptions (deduplicated by subscription_id).
		$subscriptions = [];
		if ( 'true' === $atts['show_subscription'] ) {
			$subscriptions = $this->get_user_subscriptions( $where );
		}

		// Fetch all payments for history section.
		$current_page = isset( $_GET['srfm_page'] ) ? absint( wp_unslash( $_GET['srfm_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $current_page < 1 ) {
			$current_page = 1;
		}
		$offset = ( $current_page - 1 ) * $per_page;

		/** Query arguments for fetching payments. @var array<string,mixed> $query_args */
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
		 * @since 2.8.0
		 * @param array<string,mixed> $query_args Query arguments for Payments::get_all().
		 * @param int                 $user_id    Current user ID.
		 */
		$query_args = apply_filters( 'srfm_payment_history_query_args', $query_args, $user_id );

		$payments    = Payments::get_all( $query_args );
		$total_count = Payments::get_instance()->get_total_count( $where );
		$total_pages = $per_page > 0 ? (int) ceil( $total_count / $per_page ) : 1;

		if ( empty( $subscriptions ) && empty( $payments ) ) {
			return $this->get_empty_message();
		}

		ob_start();
		?>
		<div class="srfm-pd-widget">
			<?php
			if ( ! empty( $subscriptions ) ) {
				$this->render_subscriptions_section( $subscriptions );
			}

			if ( ! empty( $payments ) ) {
				$this->render_payments_section( $payments, $current_page, $total_pages, $total_count );
			}
			?>
			<!-- Overlay containers for JS panels -->
			<div class="srfm-pd-overlay" id="srfm-pd-sub-overlay">
				<div class="srfm-pd-panel" id="srfm-pd-sub-panel"></div>
			</div>
			<div class="srfm-pd-overlay" id="srfm-pd-tx-overlay">
				<div class="srfm-pd-panel" id="srfm-pd-tx-panel"></div>
			</div>
			<div class="srfm-pd-overlay" id="srfm-pd-cancel-overlay">
				<div class="srfm-pd-panel" id="srfm-pd-cancel-panel"></div>
			</div>
		</div>
		<?php
		$this->output_js_data( $subscriptions, $payments );

		$output = ob_get_clean();

		/**
		 * Filter the final payment history HTML output.
		 *
		 * @since 2.8.0
		 * @param string               $output   The HTML output.
		 * @param array<array<string, mixed>> $payments The payment records.
		 * @param array<string,string>  $atts     The shortcode attributes.
		 */
		return apply_filters( 'srfm_payment_history_output', is_string( $output ) ? $output : '', $payments, $atts );
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX handler for frontend subscription cancellation.
	 *
	 * @since 2.8.0
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

		$payment = Payments::get( $payment_id );
		if ( ! $payment || ! $this->user_owns_payment( $payment, get_current_user_id() ) ) {
			wp_send_json_error( __( 'Payment not found.', 'sureforms' ) );
		}

		$type = isset( $payment['type'] ) ? strval( $payment['type'] ) : '';
		if ( 'subscription' !== $type || empty( $payment['subscription_id'] ) ) {
			wp_send_json_error( __( 'This payment is not a subscription.', 'sureforms' ) );
		}

		/**
		 * Filter to process subscription cancellation. Gateways hook into this.
		 *
		 * @since 2.8.0
		 * @param array<string,mixed> $result  Default result.
		 * @param array<string,mixed> $payment Payment record.
		 */
		$result = apply_filters(
			'srfm_process_subscription_cancellation',
			[
				'success' => false,
				'message' => __( 'Cancellation not supported for this gateway.', 'sureforms' ),
			],
			$payment
		);

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( [ 'message' => isset( $result['message'] ) && is_scalar( $result['message'] ) ? strval( $result['message'] ) : __( 'Subscription cancelled successfully.', 'sureforms' ) ] );
		} else {
			wp_send_json_error( isset( $result['message'] ) && is_scalar( $result['message'] ) ? strval( $result['message'] ) : __( 'Failed to cancel subscription.', 'sureforms' ) );
		}
	}

	/**
	 * Get all translatable strings for the JS frontend.
	 *
	 * @since 2.8.0
	 * @return array<string,string>
	 */
	private function get_i18n_strings() {
		return [
			/* translators: %s: subscription name */
			'cancel_confirm_now'        => __( 'Your "%s" will be cancelled immediately. You will lose access right away.', 'sureforms' ),
			'are_you_sure'              => __( 'Are you sure?', 'sureforms' ),
			'keep_subscription'         => __( 'Keep Subscription', 'sureforms' ),
			'yes_cancel'                => __( 'Yes, Cancel', 'sureforms' ),
			'done'                      => __( 'Done', 'sureforms' ),
			'subscription_cancelled'    => __( 'Subscription Cancelled', 'sureforms' ),
			'cancel_subscription'       => __( 'Cancel Subscription', 'sureforms' ),
			'back'                      => __( 'Back', 'sureforms' ),
			'subscription'              => __( 'Subscription', 'sureforms' ),
			'amount'                    => __( 'Amount', 'sureforms' ),
			'next_payment'              => __( 'Next Payment', 'sureforms' ),
			'cancelled_on'              => __( 'Cancelled On', 'sureforms' ),
			'access_until'              => __( 'Access Until', 'sureforms' ),
			'started'                   => __( 'Started', 'sureforms' ),
			'form'                      => __( 'Form', 'sureforms' ),
			'type'                      => __( 'Type', 'sureforms' ),
			'gateway'                   => __( 'Gateway', 'sureforms' ),
			'transaction_id'            => __( 'Transaction ID', 'sureforms' ),
			'parent_subscription'       => __( 'Parent Subscription', 'sureforms' ),
			'plan'                      => __( 'Plan', 'sureforms' ),
			'status'                    => __( 'Status', 'sureforms' ),
			'one_time_note'             => __( 'One-time payment. No recurring subscription associated.', 'sureforms' ),
			'subscription_payment'      => __( 'Subscription Payment', 'sureforms' ),
			'one_time_payment'          => __( 'One-time Payment', 'sureforms' ),
			'processing'                => __( 'Processing...', 'sureforms' ),
			'cancel_success'            => __( 'The subscription has been cancelled successfully.', 'sureforms' ),
			'error'                     => __( 'Something went wrong. Please try again.', 'sureforms' ),
			// Status labels for JS overlay panels.
			'status_active'             => __( 'Active', 'sureforms' ),
			'status_trialing'           => __( 'Trialing', 'sureforms' ),
			'status_canceled'           => __( 'Cancelled', 'sureforms' ),
			'status_past_due'           => __( 'Past Due', 'sureforms' ),
			'status_paused'             => __( 'Paused', 'sureforms' ),
			'status_succeeded'          => __( 'Paid', 'sureforms' ),
			'status_pending'            => __( 'Pending', 'sureforms' ),
			'status_failed'             => __( 'Failed', 'sureforms' ),
			'status_refunded'           => __( 'Refunded', 'sureforms' ),
			'status_partially_refunded' => __( 'Partially Refunded', 'sureforms' ),
			'status_charged_back'       => __( 'Charged Back', 'sureforms' ),
			'status_processing'         => __( 'Processing', 'sureforms' ),
		];
	}

	// =========================================================================
	// Subscriptions Section
	// =========================================================================

	/**
	 * Get user subscriptions, deduplicated by subscription_id.
	 *
	 * @param array<int,array<int|string,array<string,mixed>|string>> $where WHERE conditions.
	 * @since 2.8.0
	 * @return array<int,array<string,mixed>>
	 */
	private function get_user_subscriptions( $where ) {
		$sub_where   = $where;
		$sub_where[] = [
			[
				'key'     => 'type',
				'compare' => '=',
				'value'   => 'subscription',
			],
		];

		// Cap subscription fetch to avoid unbounded queries. Pagination is not
		// currently supported for the subscriptions section.
		$all_subs = Payments::get_all(
			[
				'where'   => $sub_where,
				'orderby' => 'created_at',
				'order'   => 'DESC',
				'limit'   => 100,
			]
		);

		$unique_subs = [];
		$seen_ids    = [];
		foreach ( $all_subs as $sub ) {
			$sub_id = isset( $sub['subscription_id'] ) ? strval( $sub['subscription_id'] ) : '';
			if ( empty( $sub_id ) || in_array( $sub_id, $seen_ids, true ) ) {
				continue;
			}
			$seen_ids[]    = $sub_id;
			$unique_subs[] = $sub;
		}

		return $unique_subs;
	}

	/**
	 * Render the subscriptions section.
	 *
	 * @param array<int,array<string,mixed>> $subscriptions Subscription records.
	 * @since 2.8.0
	 * @return void
	 */
	private function render_subscriptions_section( $subscriptions ) {
		$active_count    = 0;
		$cancelled_count = 0;

		foreach ( $subscriptions as $sub ) {
			$status = isset( $sub['subscription_status'] ) && is_scalar( $sub['subscription_status'] ) ? strval( $sub['subscription_status'] ) : '';
			if ( in_array( $status, [ 'active', 'trialing' ], true ) ) {
				++$active_count;
			} elseif ( 'canceled' === $status ) {
				++$cancelled_count;
			}
		}

		$count_parts = [];
		if ( $active_count > 0 ) {
			/* translators: %d: number of active subscriptions */
			$count_parts[] = sprintf( _n( '%d active', '%d active', $active_count, 'sureforms' ), $active_count );
		}
		if ( $cancelled_count > 0 ) {
			/* translators: %d: number of cancelled subscriptions */
			$count_parts[] = sprintf( _n( '%d cancelled', '%d cancelled', $cancelled_count, 'sureforms' ), $cancelled_count );
		}
		$count_text = implode( ' · ', $count_parts );
		?>
		<div class="srfm-pd-section">
			<div class="srfm-pd-section-header">
				<span class="srfm-pd-section-title"><?php esc_html_e( 'Subscriptions', 'sureforms' ); ?></span>
				<?php if ( ! empty( $count_text ) ) { ?>
					<span class="srfm-pd-section-count"><?php echo esc_html( $count_text ); ?></span>
				<?php } ?>
			</div>
			<?php foreach ( $subscriptions as $index => $sub ) { ?>
				<?php $this->render_subscription_row( $sub, $index ); ?>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Render a single subscription row.
	 *
	 * @param array<string,mixed> $sub   Subscription record.
	 * @param int                 $index Row index.
	 * @since 2.8.0
	 * @return void
	 */
	private function render_subscription_row( $sub, $index ) {
		$status       = isset( $sub['subscription_status'] ) && is_scalar( $sub['subscription_status'] ) ? strval( $sub['subscription_status'] ) : '';
		$is_active    = in_array( $status, [ 'active', 'trialing' ], true );
		$is_cancelled = 'canceled' === $status;
		$currency     = isset( $sub['currency'] ) && is_string( $sub['currency'] ) ? strtoupper( $sub['currency'] ) : 'USD';
		$form_title   = $this->get_form_title( isset( $sub['form_id'] ) && is_numeric( $sub['form_id'] ) ? absint( $sub['form_id'] ) : 0 );
		$sub_data     = $this->extract_subscription_data( $sub );
		$amount_text  = $this->format_amount( isset( $sub['total_amount'] ) && is_numeric( $sub['total_amount'] ) ? floatval( $sub['total_amount'] ) : 0.0, $currency );

		if ( ! empty( $sub_data['interval_label'] ) ) {
			$amount_text .= ' / ' . $sub_data['interval_label'];
		}

		$meta_text = esc_html( $amount_text );
		if ( $is_active && ! empty( $sub_data['next_payment'] ) ) {
			/* translators: %s: next payment date */
			$meta_text .= ' · ' . sprintf( esc_html__( 'Next: %s', 'sureforms' ), esc_html( $sub_data['next_payment'] ) );
		} elseif ( $is_cancelled && ! empty( $sub_data['cancelled_on'] ) ) {
			/* translators: %s: cancellation date */
			$meta_text = '<span class="srfm-pd-strike">' . esc_html( $amount_text ) . '</span> · ' . sprintf( esc_html__( 'Cancelled %s', 'sureforms' ), esc_html( $sub_data['cancelled_on'] ) );
		}

		$badge_class = $is_active ? 'srfm-pd-badge--active' : ( $is_cancelled ? 'srfm-pd-badge--cancelled' : 'srfm-pd-badge--pending' );
		$badge_label = $this->get_subscription_status_label( $status );
		$row_class   = 'srfm-pd-sub-row' . ( $is_cancelled ? ' srfm-pd-sub-row--cancelled' : '' );
		$plan_name   = ! empty( $sub_data['plan_name'] ) ? $sub_data['plan_name'] : $form_title;
		?>
		<div class="<?php echo esc_attr( $row_class ); ?>" data-index="<?php echo esc_attr( strval( $index ) ); ?>" role="button" tabindex="0">
			<div class="srfm-pd-sub-row-left">
				<div class="srfm-pd-sub-row-name"><?php echo esc_html( $plan_name ); ?></div>
				<div class="srfm-pd-sub-row-meta"><?php echo wp_kses_post( $meta_text ); ?></div>
			</div>
			<div class="srfm-pd-sub-row-right">
				<span class="srfm-pd-badge <?php echo esc_attr( $badge_class ); ?>">
					<span class="srfm-pd-badge-dot"></span><?php echo esc_html( $badge_label ); ?>
				</span>
				<span class="srfm-pd-chevron" aria-hidden="true">›</span>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Payments Section
	// =========================================================================

	/**
	 * Render the payment history section.
	 *
	 * @param array<int,array<string,mixed>> $payments      Payment records.
	 * @param int                            $current_page  Current page.
	 * @param int                            $total_pages   Total pages.
	 * @param int                            $total_count   Total payment count.
	 * @since 2.8.0
	 * @return void
	 */
	private function render_payments_section( $payments, $current_page, $total_pages, $total_count ) {
		?>
		<div class="srfm-pd-section">
			<div class="srfm-pd-section-header">
				<span class="srfm-pd-section-title"><?php esc_html_e( 'Payment History', 'sureforms' ); ?></span>
				<span class="srfm-pd-section-count">
					<?php
					/* translators: %d: total number of transactions */
					printf( esc_html( _n( '%d transaction', '%d transactions', $total_count, 'sureforms' ) ), intval( $total_count ) );
					?>
				</span>
			</div>
			<?php foreach ( $payments as $index => $payment ) { ?>
				<?php $this->render_payment_row( $payment, $index ); ?>
			<?php } ?>

			<?php if ( $total_pages > 1 ) { ?>
			<div class="srfm-pd-pagination">
				<span class="srfm-pd-pagination-info">
					<?php
					$start = ( ( $current_page - 1 ) * count( $payments ) ) + 1;
					$end   = $start + count( $payments ) - 1;
					printf(
						/* translators: 1: start number, 2: end number, 3: total number */
						esc_html__( 'Showing %1$d–%2$d of %3$d transactions', 'sureforms' ),
						intval( $start ),
						intval( $end ),
						intval( $total_count )
					);
					?>
				</span>
				<div class="srfm-pd-pagination-links">
					<?php if ( $current_page > 1 ) { ?>
						<a href="<?php echo esc_url( add_query_arg( 'srfm_page', $current_page - 1 ) ); ?>" class="srfm-pd-pagination-link">
							&laquo; <?php esc_html_e( 'Previous', 'sureforms' ); ?>
						</a>
					<?php } ?>
					<?php if ( $current_page < $total_pages ) { ?>
						<a href="<?php echo esc_url( add_query_arg( 'srfm_page', $current_page + 1 ) ); ?>" class="srfm-pd-pagination-link">
							<?php esc_html_e( 'Next', 'sureforms' ); ?> &raquo;
						</a>
					<?php } ?>
				</div>
			</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Render a single payment row.
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param int                 $index   Row index.
	 * @since 2.8.0
	 * @return void
	 */
	private function render_payment_row( $payment, $index ) {
		$currency    = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD';
		$status      = isset( $payment['status'] ) && is_scalar( $payment['status'] ) ? strval( $payment['status'] ) : 'pending';
		$txn_id      = ! empty( $payment['srfm_txn_id'] ) && is_scalar( $payment['srfm_txn_id'] ) ? strval( $payment['srfm_txn_id'] ) : '';
		$form_title  = $this->get_form_title( isset( $payment['form_id'] ) && is_numeric( $payment['form_id'] ) ? absint( $payment['form_id'] ) : 0 );
		$date_format = is_string( get_option( 'date_format' ) ) ? get_option( 'date_format' ) : 'Y-m-d';
		$date        = isset( $payment['created_at'] ) && is_string( $payment['created_at'] )
			? date_i18n( $date_format, strtotime( $payment['created_at'] ) )
			: '—';

		$badge_class = $this->get_payment_badge_class( $status );
		$badge_label = $this->get_payment_status_label( $status );
		?>
		<div class="srfm-pd-pay-row" data-index="<?php echo esc_attr( strval( $index ) ); ?>" role="button" tabindex="0">
			<div class="srfm-pd-pay-row-left">
				<div class="srfm-pd-pay-row-form"><?php echo esc_html( $form_title ); ?></div>
				<div class="srfm-pd-pay-row-id"><?php echo esc_html( $txn_id . ( $txn_id ? ' · ' : '' ) . $date ); ?></div>
			</div>
			<div class="srfm-pd-pay-row-right">
				<span class="srfm-pd-badge <?php echo esc_attr( $badge_class ); ?>">
					<span class="srfm-pd-badge-dot"></span><?php echo esc_html( $badge_label ); ?>
				</span>
				<span class="srfm-pd-pay-row-amount"><?php echo esc_html( $this->format_amount( isset( $payment['total_amount'] ) && is_numeric( $payment['total_amount'] ) ? floatval( $payment['total_amount'] ) : 0.0, $currency ) ); ?></span>
				<span class="srfm-pd-chevron" aria-hidden="true">›</span>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// JS Data Output
	// =========================================================================

	/**
	 * Output subscription and payment data as inline JSON for JS overlays.
	 *
	 * @param array<int,array<string,mixed>> $subscriptions Subscription records.
	 * @param array<int,array<string,mixed>> $payments      Payment records.
	 * @since 2.8.0
	 * @return void
	 */
	private function output_js_data( $subscriptions, $payments ) {
		$date_format_opt = get_option( 'date_format' );
		$date_format     = is_string( $date_format_opt ) ? $date_format_opt : 'Y-m-d';
		$subs_data       = [];

		foreach ( $subscriptions as $sub ) {
			$currency   = isset( $sub['currency'] ) && is_string( $sub['currency'] ) ? strtoupper( $sub['currency'] ) : 'USD';
			$form_title = $this->get_form_title( isset( $sub['form_id'] ) && is_numeric( $sub['form_id'] ) ? absint( $sub['form_id'] ) : 0 );
			$sub_info   = $this->extract_subscription_data( $sub );
			$status     = isset( $sub['subscription_status'] ) && is_scalar( $sub['subscription_status'] ) ? strval( $sub['subscription_status'] ) : '';
			$is_active  = in_array( $status, [ 'active', 'trialing' ], true );

			$amount_display = $this->format_amount( isset( $sub['total_amount'] ) && is_numeric( $sub['total_amount'] ) ? floatval( $sub['total_amount'] ) : 0.0, $currency );
			if ( ! empty( $sub_info['interval_label'] ) ) {
				$amount_display .= ' / ' . $sub_info['interval_label'];
			}

			$sub_id      = isset( $sub['id'] ) && is_numeric( $sub['id'] ) ? $sub['id'] : 0;
			$subs_data[] = [
				'id'             => absint( $sub_id ),
				'name'           => ! empty( $sub_info['plan_name'] ) ? $sub_info['plan_name'] : $form_title,
				'form'           => $form_title,
				'amount'         => $amount_display,
				'next'           => $sub_info['next_payment'],
				'gateway'        => $this->format_gateway_label( isset( $sub['gateway'] ) && is_scalar( $sub['gateway'] ) ? strval( $sub['gateway'] ) : '' ),
				'started'        => isset( $sub['created_at'] ) && is_string( $sub['created_at'] )
					? date_i18n( $date_format, strtotime( $sub['created_at'] ) ) : '—',
				'status'         => $status,
				'cancelledOn'    => $sub_info['cancelled_on'],
				'accessUntil'    => $sub_info['access_until'],
				'canCancel'      => $is_active,
				'subscriptionId' => isset( $sub['subscription_id'] ) && is_scalar( $sub['subscription_id'] ) ? strval( $sub['subscription_id'] ) : '',
				'paymentId'      => absint( $sub_id ),
			];
		}

		$txs_data = [];
		foreach ( $payments as $payment ) {
			$currency   = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD';
			$status     = isset( $payment['status'] ) && is_scalar( $payment['status'] ) ? strval( $payment['status'] ) : 'pending';
			$type       = isset( $payment['type'] ) && is_scalar( $payment['type'] ) ? strval( $payment['type'] ) : 'payment';
			$form_title = $this->get_form_title( isset( $payment['form_id'] ) && is_numeric( $payment['form_id'] ) ? absint( $payment['form_id'] ) : 0 );

			$payment_id = isset( $payment['id'] ) && is_numeric( $payment['id'] ) ? $payment['id'] : 0;
			$tx_item    = [
				'id'        => ! empty( $payment['srfm_txn_id'] ) && is_scalar( $payment['srfm_txn_id'] ) ? strval( $payment['srfm_txn_id'] ) : 'SF-' . absint( $payment_id ),
				'paymentId' => absint( $payment_id ),
				'form'      => $form_title,
				'date'      => isset( $payment['created_at'] ) && is_string( $payment['created_at'] )
					? date_i18n( $date_format, strtotime( $payment['created_at'] ) ) : '—',
				'amount'    => $this->format_amount( isset( $payment['total_amount'] ) && is_numeric( $payment['total_amount'] ) ? floatval( $payment['total_amount'] ) : 0.0, $currency ),
				'status'    => $status,
				'type'      => in_array( $type, [ 'subscription', 'renewal' ], true ) ? 'subscription' : 'single',
				'gateway'   => $this->format_gateway_label( isset( $payment['gateway'] ) && is_scalar( $payment['gateway'] ) ? strval( $payment['gateway'] ) : '' ),
				'txn'       => isset( $payment['transaction_id'] ) && is_scalar( $payment['transaction_id'] ) ? strval( $payment['transaction_id'] ) : '',
			];

			if ( in_array( $type, [ 'subscription', 'renewal' ], true ) && ! empty( $payment['subscription_id'] ) ) {
				$sub_info       = $this->extract_subscription_data( $payment );
				$amount_display = $this->format_amount( isset( $payment['total_amount'] ) && is_numeric( $payment['total_amount'] ) ? floatval( $payment['total_amount'] ) : 0.0, $currency );
				if ( ! empty( $sub_info['interval_label'] ) ) {
					$amount_display .= ' / ' . $sub_info['interval_label'];
				}

				$tx_item['sub'] = [
					'name'     => ! empty( $sub_info['plan_name'] ) ? $sub_info['plan_name'] : $form_title,
					'interval' => $amount_display,
					'next'     => $sub_info['next_payment'],
					'status'   => $this->get_subscription_status_label( isset( $payment['subscription_status'] ) && is_scalar( $payment['subscription_status'] ) ? strval( $payment['subscription_status'] ) : '' ),
				];
			}

			$txs_data[] = $tx_item;
		}
		$inline_data = sprintf(
			'window.srfmDashboardSubs=%s;window.srfmDashboardTxs=%s;',
			wp_json_encode( $subs_data, JSON_HEX_TAG | JSON_HEX_AMP ),
			wp_json_encode( $txs_data, JSON_HEX_TAG | JSON_HEX_AMP )
		);
		wp_add_inline_script( 'srfm-payment-history', $inline_data, 'before' );
	}

	// =========================================================================
	// Data Extraction Helpers
	// =========================================================================

	/**
	 * Extract subscription-specific data from a payment record.
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @since 2.8.0
	 * @return array{plan_name:string,interval_label:string,next_payment:string,cancelled_on:string,access_until:string}
	 */
	private function extract_subscription_data( $payment ) {
		$data = [
			'plan_name'      => '',
			'interval_label' => '',
			'next_payment'   => '',
			'cancelled_on'   => '',
			'access_until'   => '',
		];

		$payment_data = $this->parse_json_field( $payment['payment_data'] ?? '' );
		$extra        = $this->parse_json_field( $payment['extra'] ?? '' );

		$data['plan_name'] = $this->get_string_from_sources( 'plan_name', $payment_data, $extra );

		$interval       = $this->get_string_from_sources( 'interval', $payment_data, $extra );
		$interval_count = $this->get_string_from_sources( 'interval_count', $payment_data, $extra );
		if ( ! empty( $interval ) ) {
			$data['interval_label'] = $this->format_interval( $interval, intval( $interval_count ? $interval_count : '1' ) );
		}

		$next_date = $this->get_string_from_sources( 'current_period_end', $payment_data, $extra );
		if ( ! empty( $next_date ) ) {
			$data['next_payment'] = $this->format_timestamp( $next_date );
		}

		$cancelled_at = $this->get_string_from_sources( 'canceled_at', $payment_data, $extra );
		if ( ! empty( $cancelled_at ) ) {
			$data['cancelled_on'] = $this->format_timestamp( $cancelled_at );
		} elseif ( isset( $payment['updated_at'] ) && is_string( $payment['updated_at'] ) && 'canceled' === ( $payment['subscription_status'] ?? '' ) ) {
			$date_fmt             = get_option( 'date_format' );
			$data['cancelled_on'] = date_i18n( is_string( $date_fmt ) ? $date_fmt : 'Y-m-d', strtotime( $payment['updated_at'] ) );
		}

		if ( ! empty( $next_date ) && 'canceled' === ( $payment['subscription_status'] ?? '' ) ) {
			$data['access_until'] = $this->format_timestamp( $next_date );
		}

		return $data;
	}

	/**
	 * Parse a JSON field that may be a string or already an array.
	 *
	 * @param mixed $value Field value.
	 * @since 2.8.0
	 * @return array<string,mixed>
	 */
	private function parse_json_field( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && ! empty( $value ) ) {
			$decoded = json_decode( $value, true );
			return is_array( $decoded ) ? $decoded : [];
		}
		return [];
	}

	/**
	 * Get a string value from two data source arrays.
	 *
	 * @param string              $key   Key to look for.
	 * @param array<string,mixed> $data1 Primary data source.
	 * @param array<string,mixed> $data2 Fallback data source.
	 * @since 2.8.0
	 * @return string
	 */
	private function get_string_from_sources( $key, $data1, $data2 ) {
		if ( ! empty( $data1[ $key ] ) && is_scalar( $data1[ $key ] ) ) {
			return strval( $data1[ $key ] );
		}
		if ( ! empty( $data2[ $key ] ) && is_scalar( $data2[ $key ] ) ) {
			return strval( $data2[ $key ] );
		}
		return '';
	}

	/**
	 * Format a timestamp (numeric or date string) to a localized date.
	 *
	 * @param string $value Timestamp or date string.
	 * @since 2.8.0
	 * @return string Formatted date.
	 */
	private function format_timestamp( $value ) {
		$date_opt = get_option( 'date_format' );
		$format   = is_string( $date_opt ) ? $date_opt : 'Y-m-d';
		return is_numeric( $value )
			? date_i18n( $format, intval( $value ) )
			: date_i18n( $format, strtotime( $value ) );
	}

	/**
	 * Get the form title from form_id.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.8.0
	 * @return string Form title.
	 */
	private function get_form_title( $form_id ) {
		static $cache = [];

		if ( $form_id <= 0 ) {
			return __( 'Unknown Form', 'sureforms' );
		}

		if ( isset( $cache[ $form_id ] ) ) {
			return $cache[ $form_id ];
		}
		$title             = get_the_title( $form_id );
		$cache[ $form_id ] = ! empty( $title ) ? $title : __( 'Unknown Form', 'sureforms' );
		return $cache[ $form_id ];
	}

	// =========================================================================
	// Formatting Helpers
	// =========================================================================

	/**
	 * Format a payment amount with currency symbol.
	 *
	 * @param float  $amount   Payment amount.
	 * @param string $currency Currency code.
	 * @since 2.8.0
	 * @return string Formatted amount.
	 */
	private function format_amount( $amount, $currency ) {
		$symbol   = Payment_Helper::get_currency_symbol( $currency );
		$position = Payment_Helper::get_currency_sign_position();

		$formatted = Payment_Helper::is_zero_decimal_currency( $currency )
			? number_format( $amount, 0 )
			: number_format( $amount, 2 );

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
	 * Format subscription interval to human-readable label.
	 *
	 * @param string $interval       Interval type (day, week, month, year).
	 * @param int    $interval_count Interval count.
	 * @since 2.8.0
	 * @return string
	 */
	private function format_interval( $interval, $interval_count = 1 ) {
		$labels = [
			'day'   => _x( 'day', 'billing interval', 'sureforms' ),
			'week'  => _x( 'wk', 'billing interval', 'sureforms' ),
			'month' => _x( 'mo', 'billing interval', 'sureforms' ),
			'year'  => _x( 'yr', 'billing interval', 'sureforms' ),
		];

		$label = $labels[ $interval ] ?? $interval;
		return $interval_count > 1 ? $interval_count . ' ' . $label : $label;
	}

	/**
	 * Format a gateway identifier into a display label.
	 *
	 * @param string $gateway Gateway identifier (e.g., 'stripe', 'paypal').
	 * @since 2.8.0
	 * @return string Display label.
	 */
	private function format_gateway_label( $gateway ) {
		$labels = [
			'stripe' => 'Stripe',
			'paypal' => 'PayPal',
		];

		/**
		 * Filter the gateway display labels map.
		 *
		 * @since 2.8.0
		 * @param array<string,string> $labels Gateway ID to display label map.
		 */
		$labels = apply_filters( 'srfm_payment_history_gateway_labels', $labels );

		return $labels[ $gateway ] ?? ucfirst( $gateway );
	}

	/**
	 * Get subscription status label.
	 *
	 * @param string $status Subscription status.
	 * @since 2.8.0
	 * @return string
	 */
	private function get_subscription_status_label( $status ) {
		$labels = [
			'active'   => __( 'Active', 'sureforms' ),
			'trialing' => __( 'Trialing', 'sureforms' ),
			'canceled' => __( 'Cancelled', 'sureforms' ),
			'past_due' => __( 'Past Due', 'sureforms' ),
			'paused'   => __( 'Paused', 'sureforms' ),
		];
		return $labels[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) );
	}

	/**
	 * Get payment status label.
	 *
	 * @param string $status Payment status.
	 * @since 2.8.0
	 * @return string
	 */
	private function get_payment_status_label( $status ) {
		$labels = [
			'succeeded'          => __( 'Paid', 'sureforms' ),
			'pending'            => __( 'Pending', 'sureforms' ),
			'failed'             => __( 'Failed', 'sureforms' ),
			'canceled'           => __( 'Cancelled', 'sureforms' ),
			'refunded'           => __( 'Refunded', 'sureforms' ),
			'partially_refunded' => __( 'Partially Refunded', 'sureforms' ),
			'charged_back'       => __( 'Charged Back', 'sureforms' ),
			'processing'         => __( 'Processing', 'sureforms' ),
			'active'             => __( 'Active', 'sureforms' ),
		];
		return $labels[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) );
	}

	/**
	 * Get payment status badge CSS class.
	 *
	 * @param string $status Payment status.
	 * @since 2.8.0
	 * @return string
	 */
	private function get_payment_badge_class( $status ) {
		$map = [
			'succeeded'          => 'srfm-pd-badge--paid',
			'active'             => 'srfm-pd-badge--paid',
			'pending'            => 'srfm-pd-badge--pending',
			'processing'         => 'srfm-pd-badge--pending',
			'failed'             => 'srfm-pd-badge--cancelled',
			'canceled'           => 'srfm-pd-badge--cancelled',
			'charged_back'       => 'srfm-pd-badge--cancelled',
			'refunded'           => 'srfm-pd-badge--refunded',
			'partially_refunded' => 'srfm-pd-badge--refunded',
		];
		return $map[ $status ] ?? 'srfm-pd-badge--pending';
	}

	// =========================================================================
	// Query Helpers
	// =========================================================================

	/**
	 * Build WHERE conditions for the payment query.
	 *
	 * @param int                  $user_id WordPress user ID.
	 * @param array<string,string> $atts    Shortcode attributes.
	 * @since 2.8.0
	 * @return array<int,array<int|string,array<string,mixed>|string>> WHERE conditions.
	 */
	private function build_where_conditions( $user_id, $atts ) {
		$stripe_customer_id = get_user_meta( $user_id, 'srfm_stripe_customer_id', true );
		$or_conditions      = [];

		if ( ! empty( $stripe_customer_id ) && is_string( $stripe_customer_id ) ) {
			$or_conditions[] = [
				'key'     => 'customer_id',
				'compare' => '=',
				'value'   => $stripe_customer_id,
			];
		}

		$where = [];
		if ( ! empty( $or_conditions ) ) {
			$or_conditions['RELATION'] = 'OR';
			$where[]                   = $or_conditions;
		} else {
			// No customer ID found — return a zero-result condition to prevent data leakage.
			// Pro gateways (e.g., PayPal) may add their own customer_id conditions via the filter below.
			$where[] = [
				[
					'key'     => 'customer_id',
					'compare' => '=',
					'value'   => 'no_customer_' . $user_id,
				],
				'RELATION' => 'OR',
			];
		}

		/**
		 * Filter the supported payment gateways for payment history.
		 *
		 * Free plugin defaults to ['stripe']. Pro can add additional gateways
		 * (e.g., 'paypal') by hooking into this filter.
		 *
		 * @since 2.8.0
		 * @param array<string> $gateways Array of supported gateway identifiers.
		 */
		$supported_gateways = apply_filters( 'srfm_payment_history_supported_gateways', [ 'stripe' ] );
		$supported_gateways = array_map( 'sanitize_text_field', $supported_gateways );
		$supported_gateways = array_filter( $supported_gateways );

		if ( ! empty( $supported_gateways ) ) {
			if ( 1 === count( $supported_gateways ) ) {
				$where[] = [
					[
						'key'     => 'gateway',
						'compare' => '=',
						'value'   => reset( $supported_gateways ),
					],
				];
			} else {
				$where[] = [
					[
						'key'     => 'gateway',
						'compare' => 'IN',
						'value'   => array_values( $supported_gateways ),
					],
				];
			}
		}

		/**
		 * Filter the WHERE conditions for the payment history query.
		 *
		 * @since 2.8.0
		 * @param array<int,array<int|string,array<string,mixed>|string>> $where   WHERE conditions array.
		 * @param int                                                     $user_id WordPress user ID.
		 * @param array<string,string>                                    $atts    Shortcode attributes.
		 */
		return apply_filters( 'srfm_payment_history_where_conditions', $where, $user_id, $atts );
	}

	/**
	 * Check if the current user owns the payment record.
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param int                 $user_id WordPress user ID.
	 * @since 2.8.0
	 * @return bool
	 */
	private function user_owns_payment( $payment, $user_id ) {
		$stripe_customer_id = get_user_meta( $user_id, 'srfm_stripe_customer_id', true );

		// Only use system-assigned customer_id for ownership — email matching is not safe
		// for destructive actions (e.g., cancellation) because WP account email is user-controlled.
		if ( ! empty( $stripe_customer_id ) && ! empty( $payment['customer_id'] ) && $stripe_customer_id === $payment['customer_id'] ) {
			return true;
		}

		/**
		 * Filter whether the user owns the payment.
		 *
		 * @since 2.8.0
		 * @param bool                $owns    Whether the user owns the payment.
		 * @param array<string,mixed> $payment Payment record.
		 * @param int                 $user_id WordPress user ID.
		 */
		return (bool) apply_filters( 'srfm_payment_history_user_owns_payment', false, $payment, $user_id );
	}

	// =========================================================================
	// Messages
	// =========================================================================

	/**
	 * Get the login required message.
	 *
	 * @since 2.8.0
	 * @return string HTML login message.
	 */
	private function get_login_message() {
		$login_url = wp_login_url( (string) get_permalink() );
		$html      = sprintf(
			'<div class="srfm-pd-widget"><div class="srfm-pd-message">%s <a href="%s">%s</a> %s</div></div>',
			esc_html__( 'Please', 'sureforms' ),
			esc_url( $login_url ),
			esc_html__( 'log in', 'sureforms' ),
			esc_html__( 'to view your payment dashboard.', 'sureforms' )
		);

		/**
		 * Filter the login required message HTML.
		 *
		 * @since 2.8.0
		 */
		return apply_filters( 'srfm_payment_history_login_message', $html );
	}

	/**
	 * Get the no payments found message.
	 *
	 * @since 2.8.0
	 * @return string HTML empty message.
	 */
	private function get_empty_message() {
		$html = sprintf(
			'<div class="srfm-pd-widget"><div class="srfm-pd-message">%s</div></div>',
			esc_html__( 'No payments found.', 'sureforms' )
		);

		/**
		 * Filter the no payments found message HTML.
		 *
		 * @since 2.8.0
		 */
		return apply_filters( 'srfm_payment_history_empty_message', $html );
	}
}
