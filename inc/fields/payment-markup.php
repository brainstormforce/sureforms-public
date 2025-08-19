<?php
/**
 * SureForms Payment Markup Class file.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Fields;

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
	protected $description;

	/**
	 * Application fee percentage.
	 *
	 * @var float
	 * @since x.x.x
	 */
	protected $application_fee;

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
	 * Payment items.
	 *
	 * @var array
	 * @since x.x.x
	 */
	protected $payment_items;

	/**
	 * Constructor for the Payment Markup class.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since x.x.x
	 */
	public function __construct( $attributes ) {
		$this->slug = 'payment';
		$this->set_properties( $attributes );
		$this->set_input_label( 'Payment' );
		$this->set_error_msg( $attributes, 'srfm_payment_block_required_text' );
		$this->set_unique_slug();
		$this->set_markup_properties();
		$this->set_aria_described_by();

		$this->set_field_name( $this->unique_slug );

		// Set payment-specific properties.
		$this->amount          = $attributes['amount'] ?? 10;
		$this->currency        = $attributes['currency'] ?? 'USD';
		$this->description     = $attributes['description'] ?? 'Payment';
		$this->application_fee = $attributes['applicationFee'] ?? 3;

		// Get payment settings from SureForms settings.
		$payment_settings = get_option( 'srfm_payments_settings', [] );

		$this->stripe_connected = $payment_settings['stripe_connected'] ?? false;
		$this->payment_mode     = $payment_settings['payment_mode'] ?? 'test';

		// Use currency from settings if not specified in block.
		if ( empty( $this->currency ) || 'USD' === $this->currency ) {
			$this->currency = $payment_settings['currency'] ?? 'USD';
		}

		// Get appropriate Stripe keys based on mode.
		if ( 'live' === $this->payment_mode ) {
			$this->stripe_publishable_key = $payment_settings['stripe_live_publishable_key'] ?? '';
		} else {
			$this->stripe_publishable_key = $payment_settings['stripe_test_publishable_key'] ?? '';
		}

		$this->payment_items = $attributes['paymentItems'] ?? [];
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

		$field_classes  = $this->get_field_classes();
		$payment_config = [];
		if ( ! empty( $this->payment_items ) ) {
			$payment_config['paymentItems'] = $this->payment_items;
		}
		$payment_config = json_encode( $payment_config );

		ob_start();
		?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo $this->label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			
			<div class="srfm-payment-field-wrapper">
				<div class="srfm-payment-items-wrapper"></div>
				<!-- Payment Amount Display -->
				<div class="srfm-payment-amount">
					<span class="srfm-payment-label"><?php echo esc_html( $this->description ); ?>:</span>
					<span class="srfm-payment-value">
						<?php echo esc_html( $this->format_currency( $this->amount, $this->currency ) ); ?>
					</span>
				</div>

				<!-- Stripe Elements Container -->
				<div id="srfm-payment-element-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-stripe-payment-element">
					<!-- Stripe Elements will be inserted here -->
				</div>

				<!-- Hidden fields for payment data -->
				<input type="hidden" 
					name="<?php echo esc_attr( $this->field_name ); ?>" 
					class="srfm-payment-input"
					data-currency="<?php echo esc_attr( strtolower( $this->currency ) ); ?>"
					data-description="<?php echo esc_attr( $this->description ); ?>"
					data-required="<?php echo esc_attr( $this->data_require_attr ); ?>"
					data-stripe-key="<?php echo esc_attr( $this->stripe_publishable_key ); ?>"
					data-payment-mode="<?php echo esc_attr( $this->payment_mode ); ?>"
					aria-describedby="<?php echo esc_attr( trim( $this->aria_described_by ) ); ?>"
					data-payment-items="<?php echo esc_attr( $payment_config ); ?>"
					<?php echo $this->required ? 'required' : ''; ?>
				/>

				<!-- Payment processing status -->
				<div id="srfm-payment-status-<?php echo esc_attr( $this->block_id ); ?>" class="srfm-payment-status" style="display: none;">
					<div class="srfm-payment-processing">
						<span class="srfm-spinner"></span>
						<?php esc_html_e( 'Processing payment...', 'sureforms' ); ?>
					</div>
				</div>
			</div>

			<?php echo $this->help_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		return ob_get_clean();
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
			'CAD' => 'C$',
			'AUD' => 'A$',
			'CHF' => 'CHF',
			'CNY' => '¥',
			'SEK' => 'kr',
			'NZD' => 'NZ$',
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
