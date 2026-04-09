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

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
