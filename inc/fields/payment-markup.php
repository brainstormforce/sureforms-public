<?php
/**
 * SureForms Payment Markup Class file.
 *
 * @package sureforms.
 * @since 2.0.0
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Payments\Stripe\Stripe_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payment Markup Class.
 *
 * @since 2.0.0
 */
class Payment_Markup extends Base {
	/**
	 * Payment amount.
	 *
	 * @var float
	 * @since 2.0.0
	 */
	protected $amount;

	/**
	 * Payment currency.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $currency;

	/**
	 * Stripe publishable key.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $stripe_publishable_key;

	/**
	 * Whether Stripe is connected.
	 *
	 * @var bool
	 * @since 2.0.0
	 */
	protected $stripe_connected;

	/**
	 * Payment mode (live or test).
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $payment_mode;

	/**
	 * Payment type.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $payment_type;

	/**
	 * Subscription plans.
	 *
	 * @var array
	 * @since 2.0.0
	 */
	protected $subscription_plan;

	/**
	 * Amount type.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $amount_type;

	/**
	 * Fixed amount.
	 *
	 * @var float
	 * @since 2.0.0
	 */
	protected $fixed_amount;

	/**
	 * Minimum amount.
	 *
	 * @var float
	 * @since 2.0.0
	 */
	protected $minimum_amount;

	/**
	 * Customer name field slug.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $customer_name_field;

	/**
	 * Customer email field slug.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $customer_email_field;

	/**
	 * Variable amount field slug.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $variable_amount_field;

	/**
	 * Constructor for the Payment Markup class.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 2.0.0
	 */
	public function __construct( $attributes ) {
		// Get payment settings from Stripe Helper.
		$this->stripe_connected = Stripe_Helper::is_stripe_connected();
		$this->payment_mode     = Stripe_Helper::get_stripe_mode();

		$this->slug = 'payment';
		$this->set_properties( $attributes );
		$this->set_input_label( 'Payment' );
		$this->set_error_msg( $attributes, 'srfm_payment_block_required_text' );
		$this->set_unique_slug();
		$this->set_markup_properties();
		$this->set_aria_described_by();

		$this->set_field_name( $this->unique_slug );

		// Set payment-specific properties.
		$this->amount   = $attributes['amount'] ?? 10;
		$this->currency = $attributes['currency'] ?? 'USD';

		// Use currency from settings if not specified in block.
		if ( empty( $this->currency ) || 'USD' === $this->currency ) {
			$this->currency = Stripe_Helper::get_currency();
		}

		// Get appropriate Stripe publishable key based on mode.
		$this->stripe_publishable_key = Stripe_Helper::get_stripe_publishable_key();

		$this->payment_type      = $attributes['paymentType'] ?? 'one-time';
		$this->subscription_plan = $attributes['subscriptionPlan'] ?? [];
		$this->amount_type       = $attributes['amountType'] ?? 'fixed';
		$this->fixed_amount      = $attributes['fixedAmount'] ?? 10;
		$this->minimum_amount    = $attributes['minimumAmount'] ?? 0;

		// Set customer field mappings.
		$this->customer_name_field  = $attributes['customerNameField'] ?? '';
		$this->customer_email_field = $attributes['customerEmailField'] ?? '';

		// Set variable amount field mapping.
		$this->variable_amount_field = $attributes['variableAmountField'] ?? '';

		// BACKWARD COMPATIBILITY: Migrate customer fields from subscriptionPlan.
		if ( empty( $this->customer_name_field ) && ! empty( $this->subscription_plan['customer_name'] ) ) {
			$this->customer_name_field = $this->subscription_plan['customer_name'];
		}

		if ( empty( $this->customer_email_field ) && ! empty( $this->subscription_plan['customer_email'] ) ) {
			$this->customer_email_field = $this->subscription_plan['customer_email'];
		}
	}

	/**
	 * Render the payment field markup.
	 *
	 * @return string|bool
	 * @since 2.0.0
	 */
	public function markup() {
		// Check if Stripe is connected.
		if ( ! $this->stripe_connected || empty( $this->stripe_publishable_key ) ) {
			return '';
		}

		// Validate payment field requirements only if Stripe is connected.
		$is_valid = $this->validate_payment_requirements();
		if ( ! $is_valid ) {
			return '';
		}

		$field_classes = $this->get_field_classes();

		$data_input_attributes = [
			'name'                      => $this->field_name,
			'class'                     => 'srfm-payment-input',
			'data-currency'             => strtolower( $this->currency ),
			'data-stripe-key'           => $this->stripe_publishable_key,
			'data-payment-mode'         => $this->payment_mode,
			'data-amount-type'          => $this->amount_type,
			'data-fixed-amount'         => $this->fixed_amount,
			'aria-describedby'          => trim( $this->aria_described_by ),
			'data-payment-type'         => $this->payment_type,
			'data-customer-name-field'  => $this->customer_name_field,
			'data-customer-email-field' => $this->customer_email_field,
		];

		if ( 'subscription' === $this->payment_type && ! empty( $this->subscription_plan ) ) {
			$data_input_attributes['data-subscription-plan-name']      = $this->subscription_plan['name'] ?? __( 'Subscription Plan', 'sureforms' );
			$data_input_attributes['data-subscription-interval']       = $this->subscription_plan['interval'] ?? 'month';
			$data_input_attributes['data-subscription-billing-cycles'] = $this->subscription_plan['billingCycles'] ?? 0;
		}

		if ( 'variable' === $this->amount_type ) {
			$data_input_attributes['data-variable-amount-field'] = $this->variable_amount_field;
		}

		// If minimum amount is greater than 0, add it to the data input attributes.
		if ( $this->minimum_amount > 0 ) {
			$data_input_attributes['data-minimum-amount'] = $this->minimum_amount;
		}

		ob_start();
		?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<div class="srfm-payment-field-wrapper">
				<?php if ( 'fixed' === $this->amount_type ) { ?>
					<!-- Fixed Payment Amount Display. -->
					<div class="srfm-payment-amount srfm-block-label">
						<span class="srfm-payment-value">
							<?php
							if ( 'subscription' === $this->payment_type && ! empty( $this->subscription_plan ) ) {
								$interval       = $this->subscription_plan['interval'] ?? 'month';
								$billing_cycles = $this->subscription_plan['billingCycles'] ?? 0;
								$interval_label = $this->get_interval_label( $interval );

								// Build subscription text.
								if ( 'ongoing' === $billing_cycles ) {
									echo esc_html(
										sprintf(
											/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year) */
											__( '%1$s per %2$s (until cancelled)', 'sureforms' ),
											$this->format_currency( $this->fixed_amount, $this->currency ),
											$interval_label
										)
									);
								} elseif ( $billing_cycles > 0 ) {
									echo esc_html(
										sprintf(
											/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year), 3: Number of billing cycles */
											__( '%1$s per %2$s (%3$s payments)', 'sureforms' ),
											$this->format_currency( $this->fixed_amount, $this->currency ),
											$interval_label,
											$billing_cycles
										)
									);
								} else {
									echo esc_html(
										sprintf(
											/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year) */
											__( '%1$s per %2$s', 'sureforms' ),
											$this->format_currency( $this->fixed_amount, $this->currency ),
											$interval_label
										)
									);
								}
							} else {
								echo esc_html( $this->format_currency( $this->fixed_amount, $this->currency ) );
							}
							?>
						</span>
					</div>
				<?php } else { ?>
					<!-- Variable Payment Amount Display. -->
					<div class="srfm-variable-amount-display srfm-block-label">
						<div class="srfm-payment-amount-wrapper">
							<?php
							// Generate message format for variable amounts.
							$message_format = '{amount}';
							if ( 'subscription' === $this->payment_type && ! empty( $this->subscription_plan ) ) {
								$interval       = $this->subscription_plan['interval'] ?? 'month';
								$billing_cycles = $this->subscription_plan['billingCycles'] ?? 0;
								$interval_label = $this->get_interval_label( $interval );

								// Build message format based on billing cycles.
								if ( 'ongoing' === $billing_cycles ) {
									/* translators: 1: Amount with currency placeholder, 2: Interval (day/week/month/quarter/year) */
									$message_format = sprintf( __( '{amount} per %s (until cancelled)', 'sureforms' ), $interval_label );
								} elseif ( $billing_cycles > 0 ) {
									/* translators: 1: Amount with currency placeholder, 2: Interval (day/week/month/quarter/year), 3: Number of billing cycles */
									$message_format = sprintf( __( '{amount} per %1$s (%2$s payments)', 'sureforms' ), $interval_label, $billing_cycles );
								} else {
									/* translators: 1: Amount with currency placeholder, 2: Interval (day/week/month/quarter/year) */
									$message_format = sprintf( __( '{amount} per %s', 'sureforms' ), $interval_label );
								}
							}
							?>
							<span class="srfm-payment-value" data-currency="<?php echo esc_attr( strtolower( $this->currency ) ); ?>" data-currency-symbol="<?php echo esc_attr( Stripe_Helper::get_currency_symbol( $this->currency ) ); ?>" data-message-format="<?php echo esc_attr( $message_format ); ?>">
							</span>
						</div>
						<?php if ( $this->minimum_amount > 0 ) { ?>
							<span class="srfm-description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: Minimum amount with currency */
										__( 'Minimum amount: %s', 'sureforms' ),
										$this->format_currency( $this->minimum_amount, $this->currency )
									)
								);
								?>
							</span>
						<?php } ?>
					</div>
				<?php } ?>

				<?php
				if ( 'test' === $this->payment_mode ) {
					echo $this->get_test_mode_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<!-- Stripe Elements Container -->
				<div id="srfm-payment-element-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-stripe-payment-element">
					<!-- Stripe Elements will be inserted here -->
				</div>

				<!-- Hidden fields for payment data -->
				<input type="hidden"
					<?php
					foreach ( $data_input_attributes as $attr_key => $attr_value ) {
						echo esc_attr( $attr_key ) . '="' . esc_attr( $attr_value ) . '" ';
					}
					?>
				/>

				<!-- Payment processing status -->
				<div id="srfm-payment-status-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-payment-status" style="display: none;">
					<div class="srfm-payment-processing">
						<span class="srfm-spinner"></span>
						<?php esc_html_e( 'Processing payment...', 'sureforms' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate payment field requirements.
	 *
	 * @return bool True if validation passes, false otherwise.
	 * @since 2.0.0
	 */
	private function validate_payment_requirements() {
		// Check customer email field requirement (highest priority).
		if ( empty( $this->customer_email_field ) ) {
			return false;
		}

		// Check subscription-specific requirements.
		if ( 'subscription' === $this->payment_type ) {
			if ( empty( $this->customer_name_field ) ) {
				return false;
			}

			if ( empty( $this->subscription_plan ) || empty( $this->subscription_plan['name'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Format currency for display.
	 *
	 * @param float  $amount   Amount to format.
	 * @param string $currency Currency code.
	 * @return string
	 * @since 2.0.0
	 */
	private function format_currency( $amount, $currency ) {
		$symbol = Stripe_Helper::get_currency_symbol( $currency );

		// Format based on currency.
		if ( in_array( $currency, [ 'JPY', 'KRW' ], true ) ) {
			// No decimal places for these currencies.
			return $symbol . number_format( $amount, 0 );
		}

		return $symbol . number_format( $amount, 2 );
	}

	/**
	 * Get the human-readable label for a payment interval slug.
	 *
	 * @param string $interval_slug The slug (e.g., 'day', 'week', 'month', 'quarter', 'yearly').
	 * @return string The translated interval label, or the slug itself if not found.
	 * @since 2.0.0
	 */
	private function get_interval_label( $interval_slug ) {
		$interval_labels = [
			'day'     => __( 'day', 'sureforms' ),
			'week'    => __( 'week', 'sureforms' ),
			'month'   => __( 'month', 'sureforms' ),
			'quarter' => __( 'quarter', 'sureforms' ),
			'yearly'  => __( 'year', 'sureforms' ),
		];

		return $interval_labels[ $interval_slug ] ?? $interval_slug;
	}

	/**
	 * Render test mode notice for admin users.
	 * Only shows if user has manage_options capability.
	 *
	 * @return string|bool Test mode notice markup or empty string.
	 * @since 2.0.0
	 */
	private function get_test_mode_notice() {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		// Build dynamic link to payment settings.
		$settings_url = admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings' );

		ob_start();
		?>
		<div class="srfm-test-mode-notice" style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin-bottom: 16px; color: #856404;">
			<strong><?php esc_html_e( 'Test mode is enabled:', 'sureforms' ); ?></strong>
			<a href="<?php echo esc_url( $settings_url ); ?>" style="color: #856404;" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Click here to enable live mode and accept payment', 'sureforms' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}
}
