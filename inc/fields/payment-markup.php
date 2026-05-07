<?php
/**
 * SureForms Payment Markup Class file.
 *
 * @package sureforms.
 * @since 2.0.0
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Helper;
use SRFM\Inc\Payments\Payment_Helper;
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
	 * Payment methods enabled for this form.
	 *
	 * @var array
	 * @since 2.4.0
	 */
	protected $payment_methods;

	/**
	 * Payment description shown on receipts and in the payment dashboard.
	 *
	 * @var string
	 * @since 2.7.1
	 */
	protected $payment_description;

	// BOTH MODE: start — admin-configurable dual-mode attributes.
	/**
	 * Original payment type as configured in the editor (one-time / subscription / both).
	 * While rendering we may rewrite $this->payment_type to the resolved default-choice,
	 * so we keep the original intent here for conditionals.
	 *
	 * @var string
	 * @since 2.8.2
	 */
	protected $original_payment_type;

	/**
	 * Label shown next to the "one-time" radio choice in both mode.
	 *
	 * @var string
	 * @since 2.8.2
	 */
	protected $one_time_label;

	/**
	 * Label shown next to the "subscription" radio choice in both mode.
	 *
	 * @var string
	 * @since 2.8.2
	 */
	protected $subscription_label;

	/**
	 * Default radio selection in both mode ("one-time" or "subscription").
	 *
	 * @var string
	 * @since 2.8.2
	 */
	protected $default_payment_choice;

	/**
	 * One-time amount configuration (used only in both mode).
	 *
	 * @var array<mixed>
	 * @since 2.8.2
	 */
	protected $one_time_config = [];

	/**
	 * Subscription amount configuration (used only in both mode).
	 *
	 * @var array<mixed>
	 * @since 2.8.2
	 */
	protected $subscription_config = [];
	// BOTH MODE: end.

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

		// BOTH MODE: start — capture original intent and dual-mode configuration.
		$this->original_payment_type = $this->payment_type;

		$this->one_time_label         = $attributes['oneTimeLabel'] ?? __( 'One-Time Payment', 'sureforms' );
		$this->subscription_label     = $attributes['subscriptionLabel'] ?? __( 'Subscription', 'sureforms' );
		$this->default_payment_choice = $attributes['defaultPaymentChoice'] ?? 'one-time';
		if ( ! in_array( $this->default_payment_choice, [ 'one-time', 'subscription' ], true ) ) {
			$this->default_payment_choice = 'one-time';
		}

		$this->one_time_config = [
			'amount_type'    => $attributes['oneTimeAmountType'] ?? 'fixed',
			'fixed_amount'   => isset( $attributes['oneTimeFixedAmount'] ) ? (float) $attributes['oneTimeFixedAmount'] : 10.0,
			'minimum_amount' => isset( $attributes['oneTimeMinimumAmount'] ) ? (float) $attributes['oneTimeMinimumAmount'] : 0.0,
			'variable_field' => $attributes['oneTimeVariableAmountField'] ?? '',
		];

		$this->subscription_config = [
			'amount_type'    => $attributes['subscriptionAmountType'] ?? 'fixed',
			'fixed_amount'   => isset( $attributes['subscriptionFixedAmount'] ) ? (float) $attributes['subscriptionFixedAmount'] : 10.0,
			'minimum_amount' => isset( $attributes['subscriptionMinimumAmount'] ) ? (float) $attributes['subscriptionMinimumAmount'] : 0.0,
			'variable_field' => $attributes['subscriptionVariableAmountField'] ?? '',
		];

		// When rendering the "both" block, the visible amount + Stripe mode is driven by
		// the default choice. Rewrite the scalar properties so the existing rendering
		// logic below continues to work with zero changes.
		if ( 'both' === $this->original_payment_type ) {
			$active_config               = 'subscription' === $this->default_payment_choice ? $this->subscription_config : $this->one_time_config;
			$this->amount_type           = $active_config['amount_type'];
			$this->fixed_amount          = $active_config['fixed_amount'];
			$this->minimum_amount        = $active_config['minimum_amount'];
			$this->variable_amount_field = $active_config['variable_field'];
			// $this->payment_type now represents the *active* rendering type, not the admin-set value.
			$this->payment_type = $this->default_payment_choice;
		}
		// BOTH MODE: end.

		// Set payment methods from block attributes, default to 'stripe' for backward compatibility.
		$this->payment_methods = $attributes['paymentMethods'] ?? [ 'stripe' ];

		// Set custom payment description (empty means JS/gateway will use its own default).
		$this->payment_description = $attributes['paymentDescription'] ?? '';

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
		// Get registered payment methods.
		$registered_methods = $this->get_registered_payment_methods();

		// Check if any payment methods are available.
		if ( empty( $registered_methods ) ) {
			return '';
		}

		// Validate payment field requirements.
		$is_valid = $this->validate_payment_requirements();
		if ( ! $is_valid ) {
			return '';
		}

		$field_classes = $this->get_field_classes();

		// Get first payment method as default.
		$first_method_id = array_key_first( $registered_methods );

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
			'data-payment-methods'      => wp_json_encode( array_keys( $registered_methods ) ),
			'data-selected-method'      => $first_method_id,
		];

		// Subscription plan attributes are emitted when subscription is the active type
		// OR when "both" mode allows it as a possible end-user choice.
		$has_subscription_path = 'subscription' === $this->payment_type || 'both' === $this->original_payment_type;
		if ( $has_subscription_path && ! empty( $this->subscription_plan ) ) {
			// Defense-in-depth: coerce to scalar before output. A malformed REST
			// save could store these as arrays, which would otherwise emit notices
			// or empty strings through esc_attr() downstream.
			$data_input_attributes['data-subscription-plan-name']      = (string) ( $this->subscription_plan['name'] ?? __( 'Subscription Plan', 'sureforms' ) );
			$data_input_attributes['data-subscription-interval']       = (string) ( $this->subscription_plan['interval'] ?? 'month' );
			$data_input_attributes['data-subscription-billing-cycles'] = $this->subscription_plan['billingCycles'] ?? 0;
		}

		if ( ! empty( $this->payment_description ) ) {
			$data_input_attributes['data-description'] = $this->payment_description;
		}

		if ( 'variable' === $this->amount_type ) {
			$data_input_attributes['data-variable-amount-field'] = $this->variable_amount_field;
		}

		// BOTH MODE: ensure data-variable-amount-field is emitted at page load if
		// EITHER type uses variable amount. listenAmountChanges() runs once at init
		// and queries [data-variable-amount-field] — without this, the listener is
		// never wired when the default choice is fixed but the other type is variable.
		if ( 'both' === $this->original_payment_type && 'variable' !== $this->amount_type ) {
			$other_config = 'subscription' === $this->default_payment_choice
				? $this->one_time_config
				: $this->subscription_config;
			if ( 'variable' === $other_config['amount_type'] && ! empty( $other_config['variable_field'] ) ) {
				$data_input_attributes['data-variable-amount-field'] = $other_config['variable_field'];
			}
		}

		// If minimum amount is greater than 0, add it to the data input attributes.
		if ( $this->minimum_amount > 0 ) {
			$data_input_attributes['data-minimum-amount'] = $this->minimum_amount;
		}

		// BOTH MODE: start — emit per-type configuration for the JS chooser to read.
		if ( 'both' === $this->original_payment_type ) {
			$data_input_attributes['data-original-payment-type']  = 'both';
			$data_input_attributes['data-default-payment-choice'] = $this->default_payment_choice;

			$data_input_attributes['data-one-time-amount-type']    = $this->one_time_config['amount_type'];
			$data_input_attributes['data-one-time-fixed-amount']   = $this->one_time_config['fixed_amount'];
			$data_input_attributes['data-one-time-minimum-amount'] = $this->one_time_config['minimum_amount'];
			if ( 'variable' === $this->one_time_config['amount_type'] ) {
				$data_input_attributes['data-one-time-variable-amount-field'] = $this->one_time_config['variable_field'];
			}

			$data_input_attributes['data-subscription-amount-type']    = $this->subscription_config['amount_type'];
			$data_input_attributes['data-subscription-fixed-amount']   = $this->subscription_config['fixed_amount'];
			$data_input_attributes['data-subscription-minimum-amount'] = $this->subscription_config['minimum_amount'];
			if ( 'variable' === $this->subscription_config['amount_type'] ) {
				$data_input_attributes['data-subscription-variable-amount-field'] = $this->subscription_config['variable_field'];
			}
		}
		// BOTH MODE: end.

		ob_start();
		?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $field_classes ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<div class="srfm-payment-field-wrapper">
				<?php
				// BOTH MODE: start — render the payment-type chooser.
				if ( 'both' === $this->original_payment_type ) {
					echo $this->render_payment_type_chooser(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				// BOTH MODE: end.

				if ( 'both' === $this->original_payment_type ) {
					// BOTH MODE: render two amount displays, one per choice. JS toggles visibility.
					$is_one_time_default = 'one-time' === $this->default_payment_choice;
					?>
					<div
						id="srfm-payment-amount-<?php echo esc_attr( $this->block_id ); ?>-one-time"
						class="srfm-payment-amount-block srfm-payment-amount-block-one-time"
						data-payment-type="one-time"
						aria-labelledby="srfm-payment-type-<?php echo esc_attr( $this->block_id ); ?>-one-time"
						<?php echo esc_attr( $is_one_time_default ? '' : 'hidden' ); ?>
					>
						<?php
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_amount_display handles its own escaping.
						echo $this->render_amount_display(
							'one-time',
							Helper::get_string_value( $this->one_time_config['amount_type'] ?? 'fixed' ),
							is_numeric( $this->one_time_config['fixed_amount'] ?? null ) ? (float) $this->one_time_config['fixed_amount'] : 0.0,
							is_numeric( $this->one_time_config['minimum_amount'] ?? null ) ? (float) $this->one_time_config['minimum_amount'] : 0.0
						);
						// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
					<div
						id="srfm-payment-amount-<?php echo esc_attr( $this->block_id ); ?>-subscription"
						class="srfm-payment-amount-block srfm-payment-amount-block-subscription"
						data-payment-type="subscription"
						aria-labelledby="srfm-payment-type-<?php echo esc_attr( $this->block_id ); ?>-subscription"
						<?php echo esc_attr( $is_one_time_default ? 'hidden' : '' ); ?>
					>
						<?php
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_amount_display handles its own escaping.
						echo $this->render_amount_display(
							'subscription',
							Helper::get_string_value( $this->subscription_config['amount_type'] ?? 'fixed' ),
							is_numeric( $this->subscription_config['fixed_amount'] ?? null ) ? (float) $this->subscription_config['fixed_amount'] : 0.0,
							is_numeric( $this->subscription_config['minimum_amount'] ?? null ) ? (float) $this->subscription_config['minimum_amount'] : 0.0
						);
						// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
					<?php
				} else {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_amount_display handles its own escaping.
					echo $this->render_amount_display(
						$this->payment_type,
						$this->amount_type,
						$this->fixed_amount,
						$this->minimum_amount
					);
					// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<?php
				if ( 'test' === $this->payment_mode ) {
					echo $this->get_test_mode_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<!-- Payment Methods Accordion -->
				<?php echo $this->render_payment_methods_accordion( $registered_methods ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

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
	 * Get registered payment methods for display.
	 *
	 * @return array Array of payment method configurations.
	 * @since 2.4.0
	 */
	private function get_registered_payment_methods() {
		$methods = [];

		// Get enabled payment methods from block attributes.
		$enabled_methods = $this->payment_methods;

		// Filter to get method configurations - start with Stripe as default.
		$available_methods = apply_filters(
			'srfm_payment_methods_registry',
			[
				'stripe' => [
					'id'              => 'stripe',
					'label'           => __( 'Stripe', 'sureforms' ),
					'description'     => __( 'Pay with credit or debit card', 'sureforms' ),
					'icon'            => 'credit-card',
					'enabled'         => $this->stripe_connected,
					'container_class' => 'srfm-stripe-payment-element',
				],
			]
		);

		// Filter enabled methods.
		foreach ( $enabled_methods as $method_id ) {
			if ( isset( $available_methods[ $method_id ] ) && $available_methods[ $method_id ]['enabled'] ) {
				$methods[ $method_id ] = $available_methods[ $method_id ];
			}
		}

		return $methods;
	}

	// BOTH MODE: start — helper renderers for the dual-mode chooser and amount displays.

	/**
	 * Render the one-time / subscription radio chooser shown only in "both" mode.
	 *
	 * @return string Chooser markup.
	 * @since 2.8.2
	 */
	private function render_payment_type_chooser() {
		$chooser_name = 'srfm-payment-type-choice-' . $this->block_id;
		$is_one_time  = 'one-time' === $this->default_payment_choice;

		$options = [
			[
				'value'   => 'one-time',
				'label'   => $this->one_time_label,
				'checked' => $is_one_time,
			],
			[
				'value'   => 'subscription',
				'label'   => $this->subscription_label,
				'checked' => ! $is_one_time,
			],
		];

		ob_start();
		?>
		<div
			class="srfm-payment-type-chooser srfm-block-wrap"
			role="radiogroup"
			aria-label="<?php esc_attr_e( 'Choose payment type', 'sureforms' ); ?>"
		>
			<?php
			foreach ( $options as $opt ) {
				$radio_id = 'srfm-payment-type-' . $this->block_id . '-' . $opt['value'];
				$panel_id = 'srfm-payment-amount-' . $this->block_id . '-' . $opt['value'];
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_radio_pill handles its own escaping.
				echo $this->render_radio_pill(
					[
						'wrapper_class' => 'srfm-payment-type-choice srfm-payment-type-choice--' . $opt['value'],
						'radio_id'      => $radio_id,
						'name'          => $chooser_name,
						'value'         => $opt['value'],
						'label'         => $opt['label'],
						'checked'       => $opt['checked'],
						'radio_class'   => 'srfm-payment-type-choice-radio',
						'aria_controls' => $panel_id,
						'data_attrs'    => [ 'data-payment-type' => $opt['value'] ],
					]
				);
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
		$markup = ob_get_clean();
		return is_string( $markup ) ? $markup : '';
	}

	/**
	 * Render a single radio "pill" using the same DOM shape as the multi-choice
	 * block (`.srfm-block-content-wrap` + `.srfm-option-container` + circle SVGs).
	 * This lets us inherit the existing visual language: bordered box, hover/focus
	 * states, primary-tinted background when selected, animated check icon.
	 *
	 * @param array{wrapper_class:string,radio_id:string,name:string,value:string,label:string,checked:bool,radio_class:string,aria_controls?:string,data_attrs?:array<string,string>} $args Pill config.
	 * @return string Pill markup.
	 * @since 2.8.2
	 */
	private function render_radio_pill( $args ) {
		$check_svg     = Helper::fetch_svg( 'circle-checked', 'srfm-payment-icon', 'aria-hidden="true"' );
		$unchecked_svg = Helper::fetch_svg( 'circle-unchecked', 'srfm-payment-icon-unchecked', 'aria-hidden="true"' );

		$data_attrs_html = '';
		if ( ! empty( $args['data_attrs'] ) && is_array( $args['data_attrs'] ) ) {
			foreach ( $args['data_attrs'] as $key => $val ) {
				$data_attrs_html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
			}
		}

		ob_start();
		?>
		<label class="<?php echo esc_attr( $args['wrapper_class'] ); ?>">
			<input
				type="radio"
				id="<?php echo esc_attr( $args['radio_id'] ); ?>"
				name="<?php echo esc_attr( $args['name'] ); ?>"
				value="<?php echo esc_attr( $args['value'] ); ?>"
				class="<?php echo esc_attr( $args['radio_class'] ); ?>"
				<?php if ( ! empty( $args['aria_controls'] ) ) { ?>
					aria-controls="<?php echo esc_attr( $args['aria_controls'] ); ?>"
				<?php } ?>
				<?php echo $data_attrs_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php checked( ! empty( $args['checked'] ) ); ?>
			/>
			<div class="srfm-block-content-wrap">
				<div class="srfm-option-container">
					<span class="srfm-payment-option-label"><?php echo esc_html( $args['label'] ); ?></span>
				</div>
				<div class="srfm-icon-container">
					<?php
					echo wp_kses( $check_svg, Helper::$allowed_tags_svg );
					echo wp_kses( $unchecked_svg, Helper::$allowed_tags_svg );
					?>
				</div>
			</div>
		</label>
		<?php
		$markup = ob_get_clean();
		return is_string( $markup ) ? $markup : '';
	}

	/**
	 * Render the amount display block (fixed or variable, one-time or subscription).
	 * Extracted so the "both" mode can render two of these — one per choice.
	 *
	 * @param string $payment_type   The payment type for this block ("one-time" or "subscription").
	 * @param string $amount_type    Either "fixed" or "variable".
	 * @param float  $fixed_amount   The configured fixed amount.
	 * @param float  $minimum_amount The configured minimum amount.
	 * @return string Amount display markup.
	 * @since 2.8.2
	 */
	private function render_amount_display( $payment_type, $amount_type, $fixed_amount, $minimum_amount ) {
		ob_start();
		if ( 'fixed' === $amount_type ) {
			?>
			<!-- Fixed Payment Amount Display. -->
			<div class="srfm-payment-amount srfm-block-label">
				<span class="srfm-payment-value">
					<?php
					if ( 'subscription' === $payment_type && ! empty( $this->subscription_plan ) ) {
						$interval       = $this->subscription_plan['interval'] ?? 'month';
						$billing_cycles = $this->subscription_plan['billingCycles'] ?? 0;
						$interval_label = $this->get_interval_label( $interval );

						if ( 'ongoing' === $billing_cycles ) {
							echo esc_html(
								sprintf(
									/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year) */
									__( '%1$s per %2$s (until cancelled)', 'sureforms' ),
									$this->format_currency( $fixed_amount, $this->currency ),
									$interval_label
								)
							);
						} elseif ( $billing_cycles > 0 ) {
							echo esc_html(
								sprintf(
									/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year), 3: Number of billing cycles */
									__( '%1$s per %2$s (%3$s payments)', 'sureforms' ),
									$this->format_currency( $fixed_amount, $this->currency ),
									$interval_label,
									$billing_cycles
								)
							);
						} else {
							echo esc_html(
								sprintf(
									/* translators: 1: Amount with currency, 2: Interval (day/week/month/quarter/year) */
									__( '%1$s per %2$s', 'sureforms' ),
									$this->format_currency( $fixed_amount, $this->currency ),
									$interval_label
								)
							);
						}
					} else {
						echo esc_html( $this->format_currency( $fixed_amount, $this->currency ) );
					}
					?>
				</span>
			</div>
			<?php
		} else {
			// Variable amount display.
			$message_format = '{amount}';
			if ( 'subscription' === $payment_type && ! empty( $this->subscription_plan ) ) {
				$interval       = $this->subscription_plan['interval'] ?? 'month';
				$billing_cycles = $this->subscription_plan['billingCycles'] ?? 0;
				$interval_label = $this->get_interval_label( $interval );

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
			<!-- Variable Payment Amount Display. -->
			<div class="srfm-variable-amount-display srfm-block-label">
				<div class="srfm-payment-amount-wrapper">
					<span
						class="srfm-payment-value"
						data-currency="<?php echo esc_attr( strtolower( $this->currency ) ); ?>"
						data-currency-symbol="<?php echo esc_attr( Stripe_Helper::get_currency_symbol( $this->currency ) ); ?>"
						data-message-format="<?php echo esc_attr( $message_format ); ?>"
					></span>
				</div>
				<?php if ( $minimum_amount > 0 ) { ?>
					<span class="srfm-description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: Minimum amount with currency */
								__( 'Minimum amount: %s', 'sureforms' ),
								$this->format_currency( $minimum_amount, $this->currency )
							)
						);
						?>
					</span>
				<?php } ?>
			</div>
			<?php
		}

		$markup = ob_get_clean();
		return is_string( $markup ) ? $markup : '';
	}

	// BOTH MODE: end.

	/**
	 * Render payment methods as accordion.
	 * Each payment method is an accordion item with header and collapsible content.
	 *
	 * @param array<mixed> $methods Array of payment methods.
	 * @return string Payment methods accordion markup.
	 * @since 2.4.0
	 */
	private function render_payment_methods_accordion( $methods ) {
		if ( empty( $methods ) || ! is_array( $methods ) ) {
			return '';
		}

		$is_single_method = count( $methods ) === 1;
		$is_first         = true; // Track the first payment method.

		ob_start();
		?>
		<div class="srfm-payment-methods-accordion <?php echo $is_single_method ? 'srfm-single-payment-method' : ''; ?>">
			<?php foreach ( $methods as $method ) { ?>
				<div
					class="srfm-accordion-item"
					data-method="<?php echo esc_attr( $method['id'] ); ?>"
				>
					<div
						class="srfm-accordion-header"
						role="button"
						tabindex="0"
						aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>"
						aria-controls="srfm-accordion-content-<?php echo esc_attr( $method['id'] ); ?>-<?php echo esc_attr( $this->block_id ); ?>"
					>
						<div class="srfm-payment-input-wrapper">
							<input
								type="radio"
								name="payment-method-<?php echo esc_attr( $this->block_id ); ?>"
								value="<?php echo esc_attr( $method['id'] ); ?>"
								class="srfm-payment-method-radio"
								data-method="<?php echo esc_attr( $method['id'] ); ?>"
								<?php checked( $is_first ); ?>
								aria-label="<?php echo esc_attr( $method['label'] ); ?>"
							/>
							<span class="srfm-accordion-title srfm-block-label">
								<?php echo esc_html( $method['label'] ); ?>
							</span>
						</div>
					</div>
					<div
						id="srfm-accordion-content-<?php echo esc_attr( $method['id'] ); ?>-<?php echo esc_attr( $this->block_id ); ?>"
						class="srfm-accordion-content"
						role="region"
						aria-labelledby="srfm-accordion-header-<?php echo esc_attr( $method['id'] ); ?>"
					>
						<div
							class="srfm-payment-method-content"
							data-method="<?php echo esc_attr( $method['id'] ); ?>"
						>
							<div
								id="srfm-<?php echo esc_attr( $method['id'] ); ?>-<?php echo esc_attr( $this->block_id ); ?>"
								class="<?php echo esc_attr( $method['container_class'] ); ?>"
							>
								<?php
								// Output provider's placeholder content if available, otherwise show a generic hint for JS rendering.
								if ( ! empty( $method['place_holder_content'] ) ) {
									echo $method['place_holder_content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									?>
									<!-- Provider JS will render content here -->
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<?php $is_first = false; // Set to false after first iteration. ?>
			<?php } ?>
		</div>
		<?php
		$template = ob_get_clean();

		return is_string( $template ) ? $template : '';
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
		// BOTH MODE: subscription is a possible end-user choice, so name field is also required.
		if ( 'subscription' === $this->original_payment_type || 'both' === $this->original_payment_type ) {
			if ( empty( $this->customer_name_field ) ) {
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
		$symbol   = Stripe_Helper::get_currency_symbol( $currency );
		$position = Payment_Helper::get_currency_sign_position();

		// Format based on currency.
		if ( in_array( $currency, [ 'JPY', 'KRW' ], true ) ) {
			// No decimal places for these currencies.
			$formatted_amount = number_format( $amount, 0 );
		} else {
			$formatted_amount = number_format( $amount, 2 );
		}

		// Apply currency sign position.
		switch ( $position ) {
			case 'right':
				return $formatted_amount . $symbol;
			case 'left_space':
				return $symbol . ' ' . $formatted_amount;
			case 'right_space':
				return $formatted_amount . ' ' . $symbol;
			case 'left':
			default:
				return $symbol . $formatted_amount;
		}
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
		$settings_url = admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings&subpage=general' );

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
