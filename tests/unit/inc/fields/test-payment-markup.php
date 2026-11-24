<?php
/**
 * Tests for the Payment_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Payment_Markup;

class Test_Payment_Markup extends TestCase {

	/**
	 * Test validate_payment_requirements returns false when customer email field is empty.
	 */
	public function test_validate_payment_requirements_fails_without_email() {
		$attributes = [
			'required'           => false,
			'fieldWidth'         => '',
			'label'              => 'Payment',
			'help'               => '',
			'block_id'           => 'pay001',
			'formId'             => '1',
			'slug'               => 'payment',
			'placeholder'        => '',
			'defaultValue'       => '',
			'checked'            => '',
			'isUnique'           => false,
			'amount'             => 25,
			'currency'           => 'USD',
			'paymentType'        => 'one-time',
			'subscriptionPlan'   => [],
			'amountType'         => 'fixed',
			'fixedAmount'        => 25,
			'minimumAmount'      => 0,
			'customerNameField'  => 'name-field',
			'customerEmailField' => '',
			'variableAmountField' => '',
			'paymentMethods'     => [ 'stripe' ],
			'errorMsg'           => '',
		];

		$payment = new Payment_Markup( $attributes );

		$result = $this->call_private_method( $payment, 'validate_payment_requirements' );
		$this->assertFalse( $result );
	}

	/**
	 * Test validate_payment_requirements returns false for subscription without customer name.
	 */
	public function test_validate_payment_requirements_fails_subscription_without_name() {
		$attributes = [
			'required'           => false,
			'fieldWidth'         => '',
			'label'              => 'Payment',
			'help'               => '',
			'block_id'           => 'pay002',
			'formId'             => '1',
			'slug'               => 'payment',
			'placeholder'        => '',
			'defaultValue'       => '',
			'checked'            => '',
			'isUnique'           => false,
			'amount'             => 50,
			'currency'           => 'USD',
			'paymentType'        => 'subscription',
			'subscriptionPlan'   => [
				'name'          => 'Monthly Plan',
				'interval'      => 'month',
				'billingCycles' => 12,
			],
			'amountType'         => 'fixed',
			'fixedAmount'        => 50,
			'minimumAmount'      => 0,
			'customerNameField'  => '',
			'customerEmailField' => 'email-field',
			'variableAmountField' => '',
			'paymentMethods'     => [ 'stripe' ],
			'errorMsg'           => '',
		];

		$payment = new Payment_Markup( $attributes );

		$result = $this->call_private_method( $payment, 'validate_payment_requirements' );
		$this->assertFalse( $result );
	}

	/**
	 * Test markup returns empty string when no payment methods are registered (Stripe not connected).
	 */
	public function test_markup() {
		$attributes = [
			'required'            => true,
			'fieldWidth'          => '',
			'label'               => 'Payment',
			'help'                => '',
			'block_id'            => 'pay003',
			'formId'              => '1',
			'slug'                => 'payment',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'amount'              => 10,
			'currency'            => 'USD',
			'paymentType'         => 'one-time',
			'subscriptionPlan'    => [],
			'amountType'          => 'fixed',
			'fixedAmount'         => 10,
			'minimumAmount'       => 0,
			'customerNameField'   => '',
			'customerEmailField'  => 'email-field',
			'variableAmountField' => '',
			'paymentMethods'      => [ 'stripe' ],
			'paymentDescription'  => 'Annual Membership',
			'errorMsg'            => '',
		];

		$payment = new Payment_Markup( $attributes );
		$markup  = $payment->markup();

		// Stripe is not connected in unit test environment, so no payment methods
		// are registered and markup() returns an empty string early.
		$this->assertSame( '', $markup );
	}

	/**
	 * Test paymentDescription attribute is stored on the payment_description property.
	 */
	public function test_markup_payment_description_stored() {
		$attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Payment',
			'help'                => '',
			'block_id'            => 'pay004',
			'formId'              => '1',
			'slug'                => 'payment',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'amount'              => 25,
			'currency'            => 'USD',
			'paymentType'         => 'one-time',
			'subscriptionPlan'    => [],
			'amountType'          => 'fixed',
			'fixedAmount'         => 25,
			'minimumAmount'       => 0,
			'customerNameField'   => '',
			'customerEmailField'  => 'email-field',
			'variableAmountField' => '',
			'paymentMethods'      => [ 'stripe' ],
			'paymentDescription'  => 'Annual Membership',
			'errorMsg'            => '',
		];

		$payment    = new Payment_Markup( $attributes );
		$reflection = new \ReflectionClass( $payment );
		$prop       = $reflection->getProperty( 'payment_description' );
		$prop->setAccessible( true );

		$this->assertSame( 'Annual Membership', $prop->getValue( $payment ) );
	}

	/**
	 * Test payment_description defaults to empty string when attribute is absent.
	 */
	public function test_markup_payment_description_defaults_to_empty() {
		$attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Payment',
			'help'                => '',
			'block_id'            => 'pay005',
			'formId'              => '1',
			'slug'                => 'payment',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'amount'              => 10,
			'currency'            => 'USD',
			'paymentType'         => 'one-time',
			'subscriptionPlan'    => [],
			'amountType'          => 'fixed',
			'fixedAmount'         => 10,
			'minimumAmount'       => 0,
			'customerNameField'   => '',
			'customerEmailField'  => 'email-field',
			'variableAmountField' => '',
			'paymentMethods'      => [ 'stripe' ],
			'errorMsg'            => '',
		];

		$payment    = new Payment_Markup( $attributes );
		$reflection = new \ReflectionClass( $payment );
		$prop       = $reflection->getProperty( 'payment_description' );
		$prop->setAccessible( true );

		$this->assertSame( '', $prop->getValue( $payment ) );
	}

	/**
	 * A block saved with an amount type the validators refuse renders as
	 * 'fixed', the type the stored config is rebuilt to, so the browser does
	 * not compute an amount the server will not accept.
	 */
	public function test_markup_normalizes_unsupported_amount_type() {
		$property = static function ( $object, $name ) {
			$reflection = new \ReflectionProperty( $object, $name );
			$reflection->setAccessible( true );
			return $reflection->getValue( $object );
		};

		$one_time = new Payment_Markup(
			[
				'block_id'    => 'pay004',
				'slug'        => 'payment',
				'paymentType' => 'one-time',
				'amountType'  => 'user-choice',
				'fixedAmount' => 25,
			]
		);
		$this->assertSame( 'fixed', $property( $one_time, 'amount_type' ), 'data-amount-type is rendered from this property.' );

		$both = new Payment_Markup(
			[
				'block_id'               => 'pay005',
				'slug'                   => 'payment',
				'paymentType'            => 'both',
				'oneTimeAmountType'      => 'user-choice',
				'subscriptionAmountType' => 'variable',
				'defaultPaymentChoice'   => 'one-time',
			]
		);
		$this->assertSame( 'fixed', $property( $both, 'one_time_config' )['amount_type'] );
		$this->assertSame( 'variable', $property( $both, 'subscription_config' )['amount_type'], 'A supported type is kept.' );
		$this->assertSame( 'fixed', $property( $both, 'amount_type' ), 'The active (default) choice renders as fixed.' );
	}

	/**
	 * The variable-amount message template only ever carries a known interval
	 * label and an integer cycle count, whatever the block attributes hold.
	 *
	 * @dataProvider provide_subscription_plans_for_message_format
	 *
	 * @param array  $plan     subscriptionPlan block attribute.
	 * @param string $expected Expected data-message-format value (decoded).
	 */
	public function test_render_amount_display_message_format_is_built_from_known_values( $plan, $expected ) {
		$payment = new Payment_Markup(
			[
				'block_id'         => 'pay006',
				'slug'             => 'payment',
				'paymentType'      => 'subscription',
				'amountType'       => 'variable',
				'subscriptionPlan' => $plan,
			]
		);

		$html = $this->call_private_method( $payment, 'render_amount_display', [ 'subscription', 'variable', 0, 0 ] );

		$this->assertSame( 1, preg_match( '/data-message-format="([^"]*)"/', $html, $match ) );
		$this->assertSame( $expected, html_entity_decode( $match[1], ENT_QUOTES ) );
	}

	/**
	 * Subscription plans for the message-format test.
	 *
	 * @return array<string, array{0: array, 1: string}>
	 */
	public function provide_subscription_plans_for_message_format() {
		return [
			'known interval, ongoing'           => [ [ 'interval' => 'week', 'billingCycles' => 'ongoing' ], '{amount} per week (until cancelled)' ],
			'editor yearly slug'                => [ [ 'interval' => 'year', 'billingCycles' => 'ongoing' ], '{amount} per year (until cancelled)' ],
			'legacy yearly slug'                => [ [ 'interval' => 'yearly', 'billingCycles' => 3 ], '{amount} per year (3 payments)' ],
			'numeric string cycles'             => [ [ 'interval' => 'month', 'billingCycles' => '12' ], '{amount} per month (12 payments)' ],
			'unknown interval, entity markup'   => [ [ 'interval' => '&lt;img src=x onerror=alert(1)&gt;', 'billingCycles' => 'ongoing' ], '{amount} per month (until cancelled)' ],
			'unknown interval, raw markup'      => [ [ 'interval' => '<b>x</b>', 'billingCycles' => 0 ], '{amount} per month' ],
			'non-string interval'               => [ [ 'interval' => [ 'month' ], 'billingCycles' => 'ongoing' ], '{amount} per month (until cancelled)' ],
			'non-numeric cycles with markup'    => [ [ 'interval' => 'day', 'billingCycles' => '2 &lt;img src=x onerror=alert(1)&gt;' ], '{amount} per day' ],
			'array cycles'                      => [ [ 'interval' => 'day', 'billingCycles' => [ 5 ] ], '{amount} per day' ],
			'negative cycles'                   => [ [ 'interval' => 'day', 'billingCycles' => '-3' ], '{amount} per day' ],
			'empty custom cycles'               => [ [ 'interval' => 'day', 'billingCycles' => '' ], '{amount} per day' ],
			'absent plan keys'                  => [ [ 'name' => 'Plan' ], '{amount} per month' ],
		];
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
