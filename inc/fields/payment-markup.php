<?php
/**
 * SureForms Payment Markup Class file.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Payments\Stripe\Stripe_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payment Markup Class.
 *
 * @since x.x.x
 */
class Payment_Markup extends Base {
	/**
	 * Payment amount.
	 *
	 * @var float
	 * @since x.x.x
	 */
	protected $amount;

	/**
	 * Payment currency.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $currency;

	/**
	 * Payment description.
	 *
	 * @var string
	 * @since x.x.x
	 */
	// protected $description;

	/**
	 * Stripe publishable key.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $stripe_publishable_key;

	/**
	 * Whether Stripe is connected.
	 *
	 * @var bool
	 * @since x.x.x
	 */
	protected $stripe_connected;

	/**
	 * Payment mode (live or test).
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $payment_mode;

	/**
	 * Payment type.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $payment_type;

	/**
	 * Subscription plans.
	 *
	 * @var array
	 * @since x.x.x
	 */
	protected $subscription_plan;

	/**
	 * Amount type.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $amount_type;

	/**
	 * Fixed amount.
	 *
	 * @var float
	 * @since x.x.x
	 */
	protected $fixed_amount;

	/**
	 * Fixed amount label.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $fixed_amount_label;

	/**
	 * User-defined amount label.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $user_defined_amount_label;

	/**
	 * Customer name field slug.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $customer_name_field;

	/**
	 * Customer email field slug.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $customer_email_field;

	/**
	 * Constructor for the Payment Markup class.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since x.x.x
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
		$this->amount      = $attributes['amount'] ?? 10;
		$this->currency    = $attributes['currency'] ?? 'USD';
		// $this->description = $attributes['description'] ?? 'Payment';

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

		// Set default labels
		$fixed_label_default = __( 'Payment Amount', 'sureforms' );
		$user_label_default  = __( 'Enter Amount', 'sureforms' );

		// Apply filters to allow customization
		$this->fixed_amount_label = apply_filters(
			'srfm_payment_fixed_amount_label',
			$fixed_label_default,
			$this->block_id,
			$attributes
		);

		$this->user_defined_amount_label = apply_filters(
			'srfm_payment_user_defined_amount_label',
			$user_label_default,
			$this->block_id,
			$attributes
		);

		// Set customer field mappings
		$this->customer_name_field  = $attributes['customerNameField'] ?? '';
		$this->customer_email_field = $attributes['customerEmailField'] ?? '';

		// BACKWARD COMPATIBILITY: Migrate customer fields from subscriptionPlan
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
	 * @since x.x.x
	 */
	public function markup() {
		if ( ! $this->stripe_connected || empty( $this->stripe_publishable_key ) ) {
			return $this->render_not_connected_message();
		}

		$field_classes = $this->get_field_classes();

		ob_start();
		?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>"
			data-payment-type="<?php echo esc_attr( $this->payment_type ); ?>"
			data-customer-name-field="<?php echo esc_attr( $this->customer_name_field ); ?>"
			data-customer-email-field="<?php echo esc_attr( $this->customer_email_field ); ?>"
			<?php if ( 'subscription' === $this->payment_type && ! empty( $this->subscription_plan ) ) { ?>
			data-subscription-plan-name="<?php echo esc_attr( $this->subscription_plan['name'] ?? 'Subscription Plan' ); ?>"
			data-subscription-interval="<?php echo esc_attr( $this->subscription_plan['interval'] ?? 'month' ); ?>"
			<?php } ?>
			class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo $this->label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $this->help_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="srfm-payment-field-wrapper">
				<?php if ( 'fixed' === $this->amount_type ) : ?>
					<!-- Fixed Payment Amount Display -->
					<div class="srfm-payment-amount srfm-block-label">
						<span class="srfm-payment-label">
							<?php echo esc_html( $this->fixed_amount_label ); ?>
						</span>
						<span class="srfm-payment-value">
							<?php echo esc_html( $this->format_currency( $this->fixed_amount, $this->currency ) ); ?>
						</span>
					</div>
				<?php else : ?>
					<!-- User-Defined Payment Amount Input -->
					<div class="srfm-user-amount-input">
						<label for="srfm-amount-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-amount-label srfm-block-label">
							<?php echo esc_html( $this->user_defined_amount_label ); ?>
						</label>
						<input
							type="number"
							id="srfm-amount-<?php echo esc_attr( $this->block_id ); ?>"
							name="srfm_user_amount_<?php echo esc_attr( $this->block_id ); ?>"
							class="srfm-user-amount-field srfm-input-common srfm-input-number"
							placeholder="0.00"
							step="0.01"
							min="0"
							data-currency="<?php echo esc_attr( strtolower( $this->currency ) ); ?>"						
						/>
					</div>
				<?php endif; ?>

				<!-- Stripe Elements Container -->
				<div id="srfm-payment-element-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-stripe-payment-element">
					<!-- Stripe Elements will be inserted here -->
				</div>

				<!-- Hidden fields for payment data -->
				<input type="hidden"
					name="<?php echo esc_attr( $this->field_name ); ?>"
					class="srfm-payment-input"
					data-currency="<?php echo esc_attr( strtolower( $this->currency ) ); ?>"
					data-stripe-key="<?php echo esc_attr( $this->stripe_publishable_key ); ?>"
					data-payment-mode="<?php echo esc_attr( $this->payment_mode ); ?>"
					data-amount-type="<?php echo esc_attr( $this->amount_type ); ?>"
					data-fixed-amount="<?php echo esc_attr( $this->fixed_amount ); ?>"
					aria-describedby="<?php echo esc_attr( trim( $this->aria_described_by ) ); ?>"
				/>

				<!-- Payment processing status -->
				<div id="srfm-payment-status-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-payment-status" style="display: none;">
					<div class="srfm-payment-processing">
						<span class="srfm-spinner"></span>
						<?php esc_html_e( 'Processing payment...', 'sureforms' ); ?>
					</div>
				</div>
			</div>

			<?php echo $this->error_msg_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render message when Stripe is not connected.
	 *
	 * @return string
	 * @since x.x.x
	 */
	private function render_not_connected_message() {
		if ( current_user_can( 'manage_options' ) ) {
			$settings_url = admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings' );
			$message      = sprintf(
				/* translators: %s: Link to payment settings */
				__( 'Payment field is not configured. Please <a href="%s">connect your Stripe account</a> in the settings.', 'sureforms' ),
				esc_url( $settings_url )
			);
		} else {
			$message = __( 'Payment is currently unavailable.', 'sureforms' );
		}

		$field_classes = $this->get_field_classes( [ 'srfm-payment-not-configured' ] );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo $this->label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="srfm-payment-notice">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
		</div>
		<?php
		$result = ob_get_clean();
		return is_string( $result ) ? $result : '';
	}

	/**
	 * Format currency for display.
	 *
	 * @param float  $amount   Amount to format.
	 * @param string $currency Currency code.
	 * @return string
	 * @since x.x.x
	 */
	private function format_currency( $amount, $currency ) {
		$currency_symbols = [
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'AUD' => 'A$',
			'CAD' => 'C$',
			'CHF' => 'CHF',
			'CNY' => '¥',
			'SEK' => 'kr',
			'NZD' => 'NZ$',
			'MXN' => 'MX$',
			'SGD' => 'S$',
			'HKD' => 'HK$',
			'NOK' => 'kr',
			'KRW' => '₩',
			'TRY' => '₺',
			'RUB' => '₽',
			'INR' => '₹',
			'BRL' => 'R$',
			'ZAR' => 'R',
			'AED' => 'د.إ',
		];

		$symbol = $currency_symbols[ $currency ] ?? $currency . ' ';

		// Format based on currency.
		if ( in_array( $currency, [ 'JPY', 'KRW' ], true ) ) {
			// No decimal places for these currencies.
			return $symbol . number_format( $amount, 0 );
		}

		return $symbol . number_format( $amount, 2 );
	}
}
