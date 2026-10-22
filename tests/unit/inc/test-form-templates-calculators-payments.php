<?php
/**
 * Class Test_Form_Templates_Calculators_Payments
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Form_Templates\Formula;
use SRFM\Inc\Payments\Payment_Helper;

class Test_Form_Templates_Calculators_Payments extends TestCase {

	/**
	 * Field types a formula can read a number from.
	 *
	 * @var array<int, string>
	 */
	private $numeric_types = [ 'number', 'hidden', 'slider', 'dropdown', 'multi-choice' ];

	public function set_up() {
		parent::set_up();
		// Prices follow the payment currency, which other tests change.
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	private function symbol() {
		$symbol = Payment_Helper::get_currency_symbol( Payment_Helper::get_currency() );
		return '' === $symbol ? Payment_Helper::get_currency() . ' ' : $symbol;
	}

	private function templates_in( $categories ) {
		return array_filter( Form_Templates::get_templates(), static fn( $template ) => in_array( $template['category'], (array) $categories, true ) );
	}

	private function formula( $field ) {
		return $field['blockAttributes']['calculationFormula'] ?? '';
	}

	private function calculated_fields( $template ) {
		return array_filter( Form_Templates::resolve_fields( $template, 'business' ), fn( $field ) => '' !== $this->formula( $field ) );
	}

	private function answer( $template, $slug ) {
		$fields = Form_Templates::resolve_fields( $template, 'business' );
		foreach ( Form_Templates::get_answers( $fields ) as $answer ) {
			if ( $slug === $answer['slug'] ) {
				return $answer['sample'];
			}
		}
		$this->fail( "No answer {$slug} in {$template['id']}." );
	}

	public function test_ships_the_calculator_and_payment_templates() {
		$this->assertSame(
			[ 'instant-quote-calculator', 'loan-emi-calculator', 'bmi-calculator', 'roi-savings-calculator', 'shipping-cost-estimator' ],
			array_keys( $this->templates_in( 'calculators' ) )
		);
		$this->assertSame(
			[ 'donation-form', 'booking-deposit', 'membership-subscription', 'event-ticket-order', 'paid-class-registration' ],
			array_keys( $this->templates_in( 'payments' ) )
		);
	}

	public function test_calculators_need_business_and_list_calculation() {
		foreach ( $this->templates_in( 'calculators' ) as $id => $template ) {
			$this->assertSame( 'business', Form_Templates::get_required_tier( $template ), "Calculator {$id} must need Business." );
			$this->assertContains( 'calculation', $template['features'], "Calculator {$id} must list calculation." );
			$this->assertNotEmpty( $this->calculated_fields( $template ), "Calculator {$id} calculates nothing." );
		}
	}

	public function test_payment_templates_tiers_and_setup() {
		$free = [ 'donation-form', 'booking-deposit', 'membership-subscription' ];
		foreach ( $this->templates_in( 'payments' ) as $id => $template ) {
			$this->assertSame( [ 'stripe', 'paypal' ], $template['setup'], "Payment template {$id} must ask for Stripe (and PayPal)." );
			$calculated = ! empty( $this->calculated_fields( $template ) );
			if ( in_array( $id, $free, true ) ) {
				$this->assertSame( 'free', Form_Templates::get_required_tier( $template ), "{$id} works on the free plugin." );
				$this->assertFalse( $calculated, "{$id} is free, so it cannot calculate." );
			} else {
				$this->assertSame( 'business', Form_Templates::get_required_tier( $template ) );
				$this->assertTrue( $calculated, "{$id} charges a calculated total." );
				$this->assertContains( 'calculation', $template['features'] );
				// The total is checked on the server from Pro 2.11.1.
				$this->assertSame( '2.11.1', $template['min_pro_version'] );
			}
		}
	}

	public function test_setup_lists_stripe_and_skips_paypal_without_pro() {
		$setup = array_column( Form_Templates::get_setup( Form_Templates::get_template( 'donation-form' ) ), 'id' );
		$this->assertContains( 'stripe', $setup );
		if ( ! class_exists( '\SRFM_Pro\Inc\Business\Payments\PayPal\Helper' ) ) {
			$this->assertNotContains( 'paypal', $setup );
		}
	}

	public function test_every_formula_only_reads_numeric_fields_of_its_template() {
		foreach ( $this->templates_in( [ 'calculators', 'payments' ] ) as $id => $template ) {
			$fields = Form_Templates::resolve_fields( $template, 'business' );
			$types  = array_column( $fields, 'fieldType', 'slug' );
			foreach ( $this->calculated_fields( $template ) as $field ) {
				preg_match_all( '/\{form:([a-z0-9-]+)\}/', $this->formula( $field ), $tags );
				$this->assertNotEmpty( $tags[1], "{$id}: {$field['slug']} reads no answers." );
				foreach ( array_unique( $tags[1] ) as $slug ) {
					$this->assertArrayHasKey( $slug, $types, "{$id}: {$field['slug']} reads the unknown field {$slug}." );
					$this->assertContains( $types[ $slug ], $this->numeric_types, "{$id}: {$field['slug']} reads {$slug}, which has no number." );
					// A choice adds up its option values, which it only has with showValues.
					if ( in_array( $types[ $slug ], [ 'dropdown', 'multi-choice' ], true ) ) {
						$source = wp_list_filter( $fields, [ 'slug' => $slug ] );
						$source = reset( $source );
						$this->assertTrue( $source['showValues'], "{$id}: {$slug} must show values." );
						foreach ( $source['fieldOptions'] as $option ) {
							$this->assertIsNumeric( $option['value'], "{$id}: an option of {$slug} has no value." );
						}
					}
				}
				// Only the operators both the form's calculator and the server's understand.
				$expression = preg_replace( '/\{form:[a-z0-9-]+\}/', '1', $this->formula( $field ) );
				$this->assertMatchesRegularExpression( '/^[\d\s.+\-*\/^%()]+$/', $expression, "{$id}: {$field['slug']} uses an unsupported operator." );
				$this->assertNotNull( Formula::evaluate( $expression ), "{$id}: {$field['slug']} is not valid arithmetic." );
			}
		}
	}

	public function test_formulas_work_out_from_the_example_answers() {
		$expected = [
			'instant-quote-calculator' => [ 'estimate' => '2,300' ],
			// 25,000 at 6.5% over 5 years.
			'loan-emi-calculator'      => [
				'monthly-payment' => '489.15',
				'total-interest'  => '4,349.22',
			],
			// 68 kg, 170 cm.
			'bmi-calculator'           => [ 'bmi' => '23.5' ],
			// 10 people x 5 hours x 40 an hour x 48 weeks.
			'roi-savings-calculator'   => [ 'yearly-savings' => '96,000' ],
			// 8 base + 2 kg x 2.50 + standard.
			'shipping-cost-estimator'  => [ 'shipping-cost' => '13.00' ],
			'event-ticket-order'       => [ 'total' => '50.00' ],
			'paid-class-registration'  => [ 'total' => '40.00' ],
		];
		foreach ( $expected as $id => $results ) {
			$template = Form_Templates::get_template( $id );
			foreach ( $results as $slug => $value ) {
				$this->assertSame( $value, $this->answer( $template, $slug ), "{$id}: {$slug}" );
			}
		}
	}

	public function test_payment_amount_samples_follow_the_amount_source() {
		$samples = [
			'donation-form'           => [ '{form-payment:donation:amount}', $this->symbol() . '25.00' ],
			'booking-deposit'         => [ '{form-payment:deposit:amount}', $this->symbol() . '50.00' ],
			'membership-subscription' => [ '{form-payment:membership:amount}', $this->symbol() . '9.00' ],
			'event-ticket-order'      => [ '{form-payment:payment:amount}', $this->symbol() . '50.00' ],
			'paid-class-registration' => [ '{form-payment:payment:amount}', $this->symbol() . '40.00' ],
		];
		foreach ( $samples as $id => list( $tag, $amount ) ) {
			$template = Form_Templates::get_template( $id );
			$messages = Form_Templates::get_messages( $template, Form_Templates::resolve_fields( $template, 'business' ) );
			$this->assertSame( $amount, $messages['tag_samples'][ $tag ] ?? null, "{$id}: {$tag}" );
		}
	}

	public function test_messages_use_calculated_amounts_with_the_currency() {
		$template = Form_Templates::get_template( 'instant-quote-calculator' );
		// The answer is saved without the field's prefix, so the text adds it.
		$this->assertStringContainsString( $this->symbol() . '{form:estimate}', $template['confirmation']['text'] );
		$this->assertSame( '2,300', $this->answer( $template, 'estimate' ) );

		$content = Form_Templates::build_content( $template );
		preg_match( '/wp:srfm\/number (\{[^\n]*"slug":"estimate"[^\n]*\}) \/-->/', $content, $block );
		$attributes = json_decode( $block[1], true );
		$this->assertSame( $this->symbol(), $attributes['prefix'] );
		$this->assertTrue( $attributes['enableCalculation'] );
		$this->assertSame( '{form:service} + {form:pages} * 100 + {form:website-extras} + {form:store-extras} + {form:landing-extras}', $attributes['calculationFormula'] );
		$this->assertSame( 0, $attributes['calculationRound'] );
		$this->assertStringContainsString( '"showValues":true', $content );
	}

	public function test_payment_fields_build_with_their_amount_source() {
		$cases = [
			'donation-form'           => [ '"paymentType":"both"', '"oneTimeVariableAmountField":"amount"', '"subscriptionVariableAmountField":"amount"' ],
			'booking-deposit'         => [ '"paymentType":"one-time"', '"amountType":"fixed"', '"fixedAmount":50' ],
			'membership-subscription' => [ '"paymentType":"subscription"', '"variableAmountField":"plan"', '"interval":"month"' ],
			'event-ticket-order'      => [ '"amountType":"variable"', '"variableAmountField":"total"' ],
			'paid-class-registration' => [ '"amountType":"variable"', '"variableAmountField":"total"' ],
		];
		foreach ( $cases as $id => $needles ) {
			$fields  = Form_Templates::resolve_fields( Form_Templates::get_template( $id ), 'free' );
			$payment = wp_list_filter( $fields, [ 'fieldType' => 'payment' ] );
			$payment = reset( $payment );
			$this->assertSame( 'email', $payment['customerEmailField'], "{$id} needs the customer email to render." );
			$this->assertNotEmpty( $payment['customerNameField'], "{$id} needs the customer name." );
			$this->assertArrayNotHasKey( 'paymentMethods', $payment['blockAttributes'] ?? [], "{$id}: PayPal is Business only." );

			$business = wp_list_filter( Form_Templates::resolve_fields( Form_Templates::get_template( $id ), 'business' ), [ 'fieldType' => 'payment' ] );
			$business = reset( $business );
			$this->assertSame( [ 'stripe', 'paypal' ], $business['blockAttributes']['paymentMethods'] );

			if ( 'free' === Form_Templates::get_required_tier( Form_Templates::get_template( $id ) ) ) {
				$content = Form_Templates::build_content( Form_Templates::get_template( $id ) );
				$this->assertIsString( $content );
				$this->assertStringContainsString( 'wp:srfm/payment', $content );
				foreach ( $needles as $needle ) {
					$this->assertStringContainsString( $needle, $content, "{$id}: {$needle}" );
				}
			}
		}
	}

	public function test_variable_amounts_come_from_a_priced_choice_or_a_calculated_total() {
		foreach ( $this->templates_in( 'payments' ) as $id => $template ) {
			$fields  = Form_Templates::resolve_fields( $template, 'business' );
			$payment = wp_list_filter( $fields, [ 'fieldType' => 'payment' ] );
			$payment = reset( $payment );
			$sources = array_filter( [ $payment['variableAmountField'] ?? '', $payment['oneTimeVariableAmountField'] ?? '', $payment['subscriptionVariableAmountField'] ?? '' ] );
			foreach ( $sources as $slug ) {
				$source = wp_list_filter( $fields, [ 'slug' => $slug ] );
				$source = reset( $source );
				$this->assertNotFalse( $source, "{$id}: the amount comes from the unknown field {$slug}." );
				$this->assertTrue( ! empty( $source['required'] ), "{$id}: {$slug} must be required, or the amount can be empty." );
				if ( 'multi-choice' === $source['fieldType'] ) {
					$this->assertTrue( $source['showValues'] && $source['singleSelection'], "{$id}: {$slug} must be a single priced choice." );
				} else {
					$this->assertNotSame( '', $this->formula( $source ), "{$id}: {$slug} must be calculated." );
				}
			}
		}
	}

	public function test_instant_quote_add_ons_follow_the_service() {
		$template = Form_Templates::get_template( 'instant-quote-calculator' );
		$fields   = array_column( Form_Templates::resolve_fields( $template, 'business' ), null, 'slug' );
		$services = wp_list_pluck( $fields['service']['fieldOptions'], 'label' );
		foreach ( [ 'pages', 'website-extras', 'store-extras', 'landing-extras' ] as $slug ) {
			$this->assertArrayHasKey( $slug, $template['conditions'], "{$slug} should follow the service." );
			// The condition compares the dropdown's label, price included.
			$this->assertContains( $template['conditions'][ $slug ]['logic'][0][0][2], $services );
		}
		// The example picks the first service, so only its add-ons count in the sample estimate.
		$this->assertTrue( empty( $fields['website-extras']['no_sample'] ) );
		$this->assertTrue( $fields['store-extras']['no_sample'] );
		$this->assertTrue( $fields['landing-extras']['no_sample'] );
		$this->assertStringNotContainsString( 'no_sample', Form_Templates::build_content( $template ) );
	}

	public function test_calculated_inputs_are_required() {
		foreach ( $this->templates_in( [ 'calculators', 'payments' ] ) as $id => $template ) {
			$fields   = Form_Templates::resolve_fields( $template, 'business' );
			$required = wp_list_pluck( array_filter( $fields, static fn( $field ) => ! empty( $field['required'] ) ), 'slug' );
			foreach ( $this->calculated_fields( $template ) as $field ) {
				if ( 'calculators' === $template['category'] ) {
					// Nobody types in a result, so it shows no required mark.
					$this->assertNotContains( $field['slug'], $required, "{$id}: {$field['slug']} is worked out, so it should not look required." );
				} else {
					// A payment takes its amount from the total, which must not be empty.
					$this->assertContains( $field['slug'], $required );
				}
				preg_match_all( '/\{form:([a-z0-9-]+)\}/', $this->formula( $field ), $tags );
				foreach ( $tags[1] as $slug ) {
					// Optional add-ons simply add nothing.
					if ( ! preg_match( '/(^|-)extras$/', $slug ) ) {
						$this->assertContains( $slug, $required, "{$id}: {$field['slug']} reads the optional {$slug}." );
					}
				}
			}
		}
	}
}
