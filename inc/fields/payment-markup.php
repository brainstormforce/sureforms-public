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
	 * Payment type (one-time or subscription).
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
	protected $subscription_plans;

	/**
	 * Subscription selection type.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $subscription_selection_type;

	/**
	 * Subscription layout class.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $subscription_layout_class;

	/**
	 * Enable quantity for subscriptions.
	 *
	 * @var bool
	 * @since x.x.x
	 */
	protected $enable_quantity;

	/**
	 * Show price after labels.
	 *
	 * @var bool
	 * @since x.x.x
	 */
	protected $show_price_after_labels;

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
		$this->amount      = $attributes['amount'] ?? 10;
		$this->currency    = $attributes['currency'] ?? 'USD';
		$this->description = $attributes['description'] ?? 'Payment';

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

		// Set subscription-specific properties.
		$this->payment_type                 = $attributes['paymentType'] ?? 'one-time';
		$this->subscription_plans           = $attributes['subscriptionPlans'] ?? [];
		$this->subscription_selection_type  = $attributes['subscriptionSelectionType'] ?? 'radio';
		$this->subscription_layout_class    = $attributes['subscriptionLayoutClass'] ?? 'ff_list_buttons';
		$this->enable_quantity              = $attributes['enableQuantity'] ?? false;
		$this->show_price_after_labels      = $attributes['showPriceAfterLabels'] ?? true;
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
		$payment_config = $this->get_payment_config();

		ob_start();
		?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo $this->label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			
			<div class="srfm-payment-field-wrapper">
				<div class="srfm-payment-items-wrapper"></div>
				
				<?php if ( 'subscription' === $this->payment_type ) : ?>
					<?php echo $this->render_subscription_plans(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<!-- Payment Amount Display -->
					<div class="srfm-payment-amount">
						<span class="srfm-payment-label"><?php echo esc_html( $this->description ); ?>:</span>
						<span class="srfm-payment-value">
							<?php echo esc_html( $this->format_currency( $this->amount, $this->currency ) ); ?>
						</span>
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
					data-description="<?php echo esc_attr( $this->description ); ?>"
					data-required="<?php echo esc_attr( $this->data_require_attr ); ?>"
					data-stripe-key="<?php echo esc_attr( $this->stripe_publishable_key ); ?>"
					data-payment-mode="<?php echo esc_attr( $this->payment_mode ); ?>"
					data-payment-type="<?php echo esc_attr( $this->payment_type ); ?>"
					aria-describedby="<?php echo esc_attr( trim( $this->aria_described_by ) ); ?>"
					data-payment-config="<?php echo esc_attr( $payment_config ); ?>"
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

	/**
	 * Get payment configuration data.
	 *
	 * @return string
	 * @since x.x.x
	 */
	private function get_payment_config() {
		$config = [];
		
		if ( ! empty( $this->payment_items ) ) {
			$config['paymentItems'] = $this->payment_items;
		}

		if ( 'subscription' === $this->payment_type ) {
			$config['paymentType'] = 'subscription';
			$config['subscriptionPlans'] = $this->subscription_plans;
			$config['subscriptionSelectionType'] = $this->subscription_selection_type;
			$config['subscriptionLayoutClass'] = $this->subscription_layout_class;
			$config['enableQuantity'] = $this->enable_quantity;
			$config['showPriceAfterLabels'] = $this->show_price_after_labels;
		}

		return wp_json_encode( $config );
	}

	/**
	 * Render subscription plans selection.
	 *
	 * @return string
	 * @since x.x.x
	 */
	private function render_subscription_plans() {
		if ( empty( $this->subscription_plans ) ) {
			return '';
		}

		$selection_type = $this->subscription_selection_type;
		$layout_class = $this->subscription_layout_class;
		$group_name = $this->field_name . '_plan';

		ob_start();
		?>
		<div class="srfm-subscription-plans-wrapper <?php echo esc_attr( $layout_class ); ?>">
			<?php if ( 'select' === $selection_type ) : ?>
				<?php echo $this->render_subscription_select(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php echo $this->render_subscription_choices( $selection_type, $group_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render subscription plans as select dropdown.
	 *
	 * @return string
	 * @since x.x.x
	 */
	private function render_subscription_select() {
		$group_name = $this->field_name . '_plan';
		
		ob_start();
		?>
		<select name="<?php echo esc_attr( $group_name ); ?>" class="srfm-subscription-select">
			<option value=""><?php esc_html_e( '--Select Plan--', 'sureforms' ); ?></option>
			<?php foreach ( $this->subscription_plans as $index => $plan ) : ?>
				<option value="<?php echo esc_attr( $index ); ?>" 
					<?php echo ! empty( $plan['is_default'] ) ? 'selected' : ''; ?>
					<?php echo $this->get_plan_data_attributes( $plan ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo esc_html( $plan['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render subscription plans as radio buttons or checkboxes.
	 *
	 * @param string $selection_type Type of input (radio/checkbox).
	 * @param string $group_name     Name for the input group.
	 * @return string
	 * @since x.x.x
	 */
	private function render_subscription_choices( $selection_type, $group_name ) {
		$input_type = 'radio' === $selection_type ? 'radio' : 'checkbox';
		
		ob_start();
		?>
		<div class="srfm-subscription-choices srfm-subscription-<?php echo esc_attr( $selection_type ); ?>">
			<?php foreach ( $this->subscription_plans as $index => $plan ) : ?>
				<div class="srfm-subscription-choice-item">
					<label class="srfm-subscription-choice-label">
						<input type="<?php echo esc_attr( $input_type ); ?>" 
							name="<?php echo esc_attr( $group_name ); ?>"
							value="<?php echo esc_attr( $index ); ?>"
							<?php echo ! empty( $plan['is_default'] ) ? 'checked' : ''; ?>
							<?php echo $this->get_plan_data_attributes( $plan ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							class="srfm-subscription-choice-input" />
						
						<span class="srfm-subscription-plan-content">
							<span class="srfm-subscription-plan-name"><?php echo esc_html( $plan['name'] ); ?></span>
							<?php if ( $this->show_price_after_labels ) : ?>
								<span class="srfm-subscription-plan-price">
									<?php echo esc_html( $this->get_subscription_price_display( $plan ) ); ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $plan['plan_features'] ) ) : ?>
								<div class="srfm-subscription-plan-features">
									<?php foreach ( $plan['plan_features'] as $feature ) : ?>
										<span class="srfm-subscription-feature"><?php echo esc_html( $feature ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</span>
					</label>
					
					<?php if ( ! empty( $plan['user_input'] ) ) : ?>
						<?php echo $this->render_custom_amount_input( $plan, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get plan data attributes for Stripe integration.
	 *
	 * @param array $plan Subscription plan data.
	 * @return string
	 * @since x.x.x
	 */
	private function get_plan_data_attributes( $plan ) {
		$attributes = [
			'data-subscription-amount' => $plan['subscription_amount'] ?? 0,
			'data-billing-interval' => $plan['billing_interval'] ?? 'month',
			'data-bill-times' => $plan['bill_times'] ?? 0,
			'data-trial-days' => ! empty( $plan['has_trial_days'] ) ? ( $plan['trial_days'] ?? 0 ) : 0,
			'data-signup-fee' => ! empty( $plan['has_signup_fee'] ) ? ( $plan['signup_fee'] ?? 0 ) : 0,
			'data-plan-name' => esc_attr( $plan['name'] ?? '' ),
		];

		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			$attr_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		return $attr_string;
	}

	/**
	 * Get subscription price display text.
	 *
	 * @param array $plan Subscription plan data.
	 * @return string
	 * @since x.x.x
	 */
	private function get_subscription_price_display( $plan ) {
		$amount = $plan['subscription_amount'] ?? 0;
		$interval = $plan['billing_interval'] ?? 'month';
		$bill_times = $plan['bill_times'] ?? 0;

		$price_text = $this->format_currency( $amount, $this->currency );
		
		$interval_text = '';
		switch ( $interval ) {
			case 'day':
				$interval_text = __( 'per day', 'sureforms' );
				break;
			case 'week':
				$interval_text = __( 'per week', 'sureforms' );
				break;
			case 'month':
				$interval_text = __( 'per month', 'sureforms' );
				break;
			case 'year':
				$interval_text = __( 'per year', 'sureforms' );
				break;
		}

		$display_text = $price_text . ' ' . $interval_text;

		if ( $bill_times > 0 ) {
			$display_text .= sprintf( __( ' for %d payments', 'sureforms' ), $bill_times );
		}

		if ( ! empty( $plan['has_trial_days'] ) && ! empty( $plan['trial_days'] ) ) {
			$display_text .= sprintf( __( ' (with %d-day trial)', 'sureforms' ), $plan['trial_days'] );
		}

		if ( ! empty( $plan['has_signup_fee'] ) && ! empty( $plan['signup_fee'] ) ) {
			$setup_fee = $this->format_currency( $plan['signup_fee'], $this->currency );
			$display_text .= sprintf( __( ' + %s setup fee', 'sureforms' ), $setup_fee );
		}

		return $display_text;
	}

	/**
	 * Render custom amount input for user-defined plans.
	 *
	 * @param array $plan  Subscription plan data.
	 * @param int   $index Plan index.
	 * @return string
	 * @since x.x.x
	 */
	private function render_custom_amount_input( $plan, $index ) {
		if ( empty( $plan['user_input'] ) ) {
			return '';
		}

		$input_name = $this->field_name . '_custom_' . $index;
		$input_id = $this->block_id . '_custom_' . $index;
		$default_value = $plan['user_input_default_value'] ?? '';
		$min_value = $plan['user_input_min_value'] ?? 0;
		$label = $plan['user_input_label'] ?? __( 'Custom Amount', 'sureforms' );
		$is_default = ! empty( $plan['is_default'] );

		ob_start();
		?>
		<div class="srfm-subscription-custom-amount <?php echo $is_default ? '' : 'srfm-hidden'; ?>" 
			data-plan-index="<?php echo esc_attr( $index ); ?>">
			<label for="<?php echo esc_attr( $input_id ); ?>" class="srfm-custom-amount-label">
				<?php echo esc_html( $label ); ?>
			</label>
			<input type="number" 
				id="<?php echo esc_attr( $input_id ); ?>"
				name="<?php echo esc_attr( $input_name ); ?>"
				class="srfm-custom-amount-input"
				value="<?php echo esc_attr( $default_value ); ?>"
				min="<?php echo esc_attr( $min_value ); ?>"
				step="0.01"
				placeholder="<?php echo esc_attr( $label ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}
}
