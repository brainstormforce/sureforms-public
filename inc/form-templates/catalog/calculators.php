<?php
/**
 * Form templates: Calculators.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * Every calculator needs SureForms Business, whose calculations work the
 * result out while the visitor types. Prices use the currency set in the
 * SureForms payment settings.
 *
 * @package sureforms
 * @since x.x.x
 */

use SRFM\Inc\Form_Templates\Catalog;
use SRFM\Inc\Payments\Payment_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];
$currency  = Payment_Helper::get_currency();
$symbol    = Payment_Helper::get_currency_symbol( $currency );
$symbol    = '' === $symbol ? $currency . ' ' : $symbol;
$money     = static fn( $amount ) => $symbol . number_format_i18n( $amount );
// A calculated amount in a message. The answer is saved without the field's prefix, so the message adds the currency.
$amount = static fn( $slug ) => $symbol . '{form:' . $slug . '}';

/*
 * A choice whose options add a price to the calculation: the label shows the
 * price, the option value is what the formula adds up.
 */
$priced_options = static function ( $options, $field_type = 'multi-choice', $single = true ) {
	$list = [];
	foreach ( $options as $option ) {
		$list[] = 'dropdown' === $field_type ? [
			'label' => $option[0],
			'value' => (string) $option[1],
		] : [
			'optionTitle' => $option[0],
			'value'       => (string) $option[1],
			'icon'        => $option[2] ?? '',
		];
	}
	return array_merge(
		[
			'fieldOptions' => $list,
			'showValues'   => true,
		],
		'dropdown' === $field_type ? [] : [ 'singleSelection' => $single ]
	);
};

/*
 * A read-only number field showing a formula's result. `{form:slug}` tags in
 * the formula are the answers it uses.
 */
$result = static function ( $slug, $label, $formula, $round, $prefix = '', $suffix = '', $extra = [] ) {
	return Catalog::attrs(
		// The block shows the prefix and suffix; the empty schema keys keep them out
		// of the library's example answer, as the saved answer has neither.
		Catalog::field(
			'number',
			$slug,
			$label,
			true,
			array_merge(
				[
					'prefix' => '',
					'suffix' => '',
				],
				$extra
			)
		),
		[
			'enableCalculation'  => true,
			'calculationFormula' => $formula,
			'calculationRound'   => $round,
			'prefix'             => $prefix,
			'suffix'             => $suffix,
		]
	);
};

/* translators: %1$s: option name, %2$s: its price, e.g. "Website (+$1,500)". */
$label_with_price = static fn( $name, $price ) => sprintf( __( '%1$s (%2$s)', 'sureforms' ), $name, $price );

// Monthly repayment of a loan: P × r × (1 + r)^n / ((1 + r)^n − 1), r the monthly rate, n the months.
$rate       = '({form:interest-rate} / 1200)';
$growth     = '(1 + ' . $rate . ') ^ ({form:term-years} * 12)';
$repayment  = '{form:loan-amount} * ' . $rate . ' * ' . $growth . ' / (' . $growth . ' - 1)';
$disclaimer = __( 'An estimate for guidance only, not financial advice.', 'sureforms' );

return [
	[
		'id'            => 'instant-quote-calculator',
		'category'      => 'calculators',
		'theme'         => 'midnight',
		'icon'          => 'calculator',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'Instant Quote Calculator', 'sureforms' ),
		'description'   => __( 'Show visitors a price estimate as they pick options, then capture the lead.', 'sureforms' ),
		'submit_text'   => __( 'Get my detailed quote', 'sureforms' ),
		'fields'        => [
			Catalog::attrs(
				Catalog::field(
					'dropdown',
					'service',
					__( 'What do you need?', 'sureforms' ),
					true,
					array_merge(
						$priced_options(
							[
								[ $label_with_price( __( 'Website', 'sureforms' ), $money( 1500 ) ), 1500 ],
								[ $label_with_price( __( 'Online store', 'sureforms' ), $money( 3000 ) ), 3000 ],
								[ $label_with_price( __( 'Landing page', 'sureforms' ), $money( 600 ) ), 600 ],
							],
							'dropdown'
						),
						[ 'placeholder' => __( 'Select a project type', 'sureforms' ) ]
					)
				),
				$no_search
			),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'extras',
					__( 'Add-ons', 'sureforms' ),
					false,
					array_merge(
						$priced_options(
							[
								[ $label_with_price( __( 'Copywriting', 'sureforms' ), '+' . $money( 300 ) ), 300, 'pen-nib' ],
								[ $label_with_price( __( 'SEO setup', 'sureforms' ), '+' . $money( 400 ) ), 400, 'magnifying-glass' ],
								[ $label_with_price( __( 'Rush delivery', 'sureforms' ), '+' . $money( 500 ) ), 500, 'bolt' ],
							],
							'multi-choice',
							false
						),
						[ 'helpText' => __( 'Optional. Select all that apply.', 'sureforms' ) ]
					)
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::width(
				Catalog::number_field(
					'pages',
					__( 'Number of Pages', 'sureforms' ),
					true,
					1,
					50,
					[ 'placeholder' => __( 'e.g. 5', 'sureforms' ) ]
				),
				50
			),
			Catalog::width( $result( 'estimate', __( 'Estimated Price', 'sureforms' ), '{form:service} + {form:pages} * 100 + {form:extras}', 0, $symbol ), 50 ),
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Tell us about your project', 'sureforms' ), false, [ 'placeholder' => __( 'Deadlines, examples you like, anything that affects the price', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => sprintf(
				/* translators: %s: the estimated price. */
				__( 'Your estimate is %s. We will review your project and send a detailed quote to {form:email} within one business day.', 'sureforms' ),
				$amount( 'estimate' )
			),
		],
		'email_subject' => sprintf(
			/* translators: %s: the estimated price. */
			__( 'Quote estimate %s: {form:service} for {form:first-name} {form:last-name}', 'sureforms' ),
			$amount( 'estimate' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: the estimated price. */
			__( '{form:first-name} {form:last-name} priced {form:service} with {form:pages} pages and got an estimate of %s. Reply to this email to send them a detailed quote.', 'sureforms' ),
			$amount( 'estimate' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: the estimated price. */
				__( 'Your estimate: %s', 'sureforms' ),
				$amount( 'estimate' )
			),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: the estimated price. */
					__( 'Thanks for using our quote calculator. Your estimate for {form:service} with {form:pages} pages is %s.', 'sureforms' ),
					$amount( 'estimate' )
				),
				__( 'We will review your details and send a detailed quote within one business day. Questions in the meantime? Just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'loan-emi-calculator',
		'category'      => 'calculators',
		'theme'         => 'ocean',
		'icon'          => 'landmark',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'Loan Repayment Calculator', 'sureforms' ),
		'description'   => __( 'Work out the monthly repayment and total interest of a loan, and email the breakdown.', 'sureforms' ),
		'submit_text'   => __( 'Email me the breakdown', 'sureforms' ),
		'fields'        => [
			Catalog::width(
				Catalog::attrs(
					Catalog::number_field( 'loan-amount', __( 'Loan Amount', 'sureforms' ), true, 100, 10000000, [ 'placeholder' => __( 'e.g. 25,000', 'sureforms' ) ] ),
					[ 'prefix' => $symbol ]
				),
				50
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'number',
						'interest-rate',
						__( 'Interest Rate', 'sureforms' ),
						true,
						[ 'placeholder' => __( 'e.g. 6.5', 'sureforms' ) ]
					),
					[
						'minValue' => 0.1,
						'maxValue' => 50,
						'suffix'   => __( '% a year', 'sureforms' ),
					]
				),
				25
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::number_field( 'term-years', __( 'Term', 'sureforms' ), true, 1, 40, [ 'placeholder' => __( 'e.g. 5', 'sureforms' ) ] ),
					[ 'suffix' => __( 'years', 'sureforms' ) ]
				),
				25
			),
			Catalog::width( $result( 'monthly-payment', __( 'Monthly Repayment', 'sureforms' ), $repayment, 2, $symbol ), 50 ),
			Catalog::width( $result( 'total-interest', __( 'Total Interest', 'sureforms' ), '(' . $repayment . ') * {form:term-years} * 12 - {form:loan-amount}', 2, $symbol ), 50 ),
			Catalog::field(
				'email',
				'email',
				__( 'Email', 'sureforms' ),
				true,
				[
					'placeholder' => __( 'jane@example.com', 'sureforms' ),
					'helpText'    => __( 'We will email you this breakdown so you can compare offers.', 'sureforms' ),
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Your breakdown is on its way', 'sureforms' ),
			'text'    => sprintf(
				/* translators: %1$s: monthly repayment, %2$s: total interest. */
				__( 'Your estimated monthly repayment is %1$s over {form:term-years} years, with %2$s interest in total. We have sent the details to {form:email}.', 'sureforms' ),
				$amount( 'monthly-payment' ),
				$amount( 'total-interest' )
			) . ' ' . $disclaimer,
		],
		'email_subject' => sprintf(
			/* translators: %s: loan amount. */
			__( 'Loan estimate: %s over {form:term-years} years', 'sureforms' ),
			$amount( 'loan-amount' )
		),
		'email_intro'   => sprintf(
			/* translators: %1$s: loan amount, %2$s: monthly repayment. */
			__( '{form:email} estimated a loan of %1$s at {form:interest-rate}%% over {form:term-years} years: %2$s a month. Reply to this email to follow up.', 'sureforms' ),
			$amount( 'loan-amount' ),
			$amount( 'monthly-payment' )
		),
		'autoresponder' => [
			'subject'  => __( 'Your loan repayment estimate', 'sureforms' ),
			'greeting' => __( 'Hi,', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: loan amount. */
					__( 'Here is your estimate for a loan of %s at {form:interest-rate}%% a year over {form:term-years} years.', 'sureforms' ),
					$amount( 'loan-amount' )
				),
				sprintf(
					/* translators: %1$s: monthly repayment, %2$s: total interest. */
					__( 'Monthly repayment: %1$s. Total interest: %2$s.', 'sureforms' ),
					$amount( 'monthly-payment' ),
					$amount( 'total-interest' )
				),
				$disclaimer . ' ' . __( 'Reply to this email if you would like to talk it through.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'bmi-calculator',
		'category'      => 'calculators',
		'theme'         => 'mint',
		'icon'          => 'activity',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'BMI Calculator', 'sureforms' ),
		'description'   => __( 'Let visitors check their body mass index and ask for a plan toward their goal.', 'sureforms' ),
		'submit_text'   => __( 'Send me a plan', 'sureforms' ),
		'fields'        => [
			Catalog::width(
				Catalog::attrs(
					Catalog::field( 'number', 'height-cm', __( 'Height', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 170', 'sureforms' ) ] ),
					[
						'minValue' => 50,
						'maxValue' => 250,
						'suffix'   => 'cm',
					]
				),
				50
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::field( 'number', 'weight-kg', __( 'Weight', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 68', 'sureforms' ) ] ),
					[
						'minValue' => 20,
						'maxValue' => 300,
						'suffix'   => 'kg',
					]
				),
				50
			),
			$result(
				'bmi',
				__( 'Your BMI', 'sureforms' ),
				'{form:weight-kg} / (({form:height-cm} / 100) * ({form:height-cm} / 100))',
				1,
				'',
				'',
				[ 'helpText' => __( 'Healthy range for adults: 18.5 to 24.9. An estimate, not medical advice.', 'sureforms' ) ]
			),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'goal',
					__( 'Your Goal', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Lose weight', 'sureforms' ), __( 'Maintain my weight', 'sureforms' ), __( 'Gain weight', 'sureforms' ) ], true, [ 'arrow-trend-down', 'scale-balanced', 'arrow-trend-up' ] )
				),
				[ 'choiceWidth' => 33.33 ]
			),
			$c['first_name'],
			Catalog::width( $c['email'], 50 ),
			$c['consent'],
		],
		'confirmations' => [
			[
				'name'    => __( 'BMI under 18.5', 'sureforms' ),
				'message' => '<h2>' . __( 'Thanks, {form:first-name}!', 'sureforms' ) . '</h2><p>' . __( 'Your BMI is {form:bmi}, below the usual healthy range. A coach will email you a plan for your goal ({form:goal}), with a focus on healthy, steady gains.', 'sureforms' ) . '</p>',
				'rules'   => [ [ 'bmi', '_LESSER_', '18.5' ] ],
			],
			[
				'name'    => __( 'BMI 18.5 to 24.9', 'sureforms' ),
				'message' => '<h2>' . __( 'Thanks, {form:first-name}!', 'sureforms' ) . '</h2><p>' . __( 'Your BMI is {form:bmi}, within the usual healthy range. A coach will email you a plan for your goal ({form:goal}).', 'sureforms' ) . '</p>',
				'rules'   => [ [ 'bmi', '_GREATER_OR_EQUAL_', '18.5' ], [ 'bmi', '_LESSER_', '25' ] ],
			],
			[
				'name'    => __( 'BMI 25 and over', 'sureforms' ),
				'message' => '<h2>' . __( 'Thanks, {form:first-name}!', 'sureforms' ) . '</h2><p>' . __( 'Your BMI is {form:bmi}, above the usual healthy range. A coach will email you a realistic plan for your goal ({form:goal}).', 'sureforms' ) . '</p>',
				'rules'   => [ [ 'bmi', '_GREATER_OR_EQUAL_', '25' ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your BMI is {form:bmi}. A coach will email you a plan for your goal ({form:goal}). BMI is an estimate, not medical advice.', 'sureforms' ),
		],
		'email_subject' => __( 'BMI check: {form:first-name} ({form:bmi}), goal: {form:goal}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} checked their BMI ({form:bmi}) and would like a plan for their goal: {form:goal}. Reply to this email to send it.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your BMI result', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for checking your BMI with us. Your result: {form:bmi}.', 'sureforms' ),
				__( 'A coach will be in touch with a plan for your goal ({form:goal}). BMI is a rough guide and doesn\'t account for muscle, age or build, so it is not medical advice.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'roi-savings-calculator',
		'category'      => 'calculators',
		'theme'         => 'lavender',
		'icon'          => 'trending-up',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'ROI / Savings Calculator', 'sureforms' ),
		'description'   => __( 'Show prospects how much time and money your product could save them a year.', 'sureforms' ),
		'submit_text'   => __( 'Send me the breakdown', 'sureforms' ),
		'fields'        => [
			Catalog::width( Catalog::number_field( 'team-size', __( 'Team Size', 'sureforms' ), true, 1, 10000, [ 'placeholder' => __( 'e.g. 10', 'sureforms' ) ] ), 33.33 ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'slider',
						'hours-saved',
						__( 'Hours Saved per Person Each Week', 'sureforms' ),
						true,
						[
							'min'           => 1,
							'max'           => 20,
							'step'          => 1,
							// The preview's example answer: the slider's starting value.
							'placeholder'   => '5',
							'suffixTooltip' => __( 'hrs', 'sureforms' ),
						]
					),
					[ 'numberDefaultValue' => '5' ]
				),
				33.33
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::number_field( 'hourly-cost', __( 'Hourly Cost per Person', 'sureforms' ), true, 1, 1000, [ 'placeholder' => __( 'e.g. 40', 'sureforms' ) ] ),
					[ 'prefix' => $symbol ]
				),
				33.33
			),
			$result(
				'yearly-savings',
				__( 'Estimated Yearly Savings', 'sureforms' ),
				'{form:team-size} * {form:hours-saved} * {form:hourly-cost} * 48',
				0,
				$symbol,
				'',
				[ 'helpText' => __( 'Based on 48 working weeks a year.', 'sureforms' ) ]
			),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'email', 'email', __( 'Work Email', 'sureforms' ), true, [ 'placeholder' => __( 'jane@company.com', 'sureforms' ) ] ), 50 ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => sprintf(
				/* translators: %s: yearly savings. */
				__( 'You could save %s a year', 'sureforms' ),
				$amount( 'yearly-savings' )
			),
			'text'    => __( 'That is {form:hours-saved} hours a week for each of your {form:team-size} people. We have sent the breakdown to {form:email}.', 'sureforms' ),
		],
		'email_subject' => sprintf(
			/* translators: %s: yearly savings. */
			__( 'New ROI lead: %s a year for a team of {form:team-size}', 'sureforms' ),
			$amount( 'yearly-savings' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: yearly savings. */
			__( '{form:email} worked out savings of %s a year for a team of {form:team-size} ({form:hours-saved} hours a week each). Reply to this email to follow up.', 'sureforms' ),
			$amount( 'yearly-savings' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: yearly savings. */
				__( 'Your savings estimate: %s a year', 'sureforms' ),
				$amount( 'yearly-savings' )
			),
			'greeting' => __( 'Hi,', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: yearly savings. */
					__( 'Thanks for running the numbers. With {form:team-size} people each saving {form:hours-saved} hours a week, you could save about %s a year.', 'sureforms' ),
					$amount( 'yearly-savings' )
				),
				__( 'Want to see how? Reply to this email and we will set up a short walkthrough.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'shipping-cost-estimator',
		'category'      => 'calculators',
		'theme'         => 'sand',
		'icon'          => 'truck',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'Shipping Cost Estimator', 'sureforms' ),
		'description'   => __( 'Estimate shipping from destination, weight and speed, and follow up with a booking.', 'sureforms' ),
		'submit_text'   => __( 'Request a booking', 'sureforms' ),
		'fields'        => [
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'zone',
						__( 'Destination', 'sureforms' ),
						true,
						array_merge(
							$priced_options(
								[
									/* translators: %s: base shipping price. */
									[ sprintf( __( 'Domestic (%s base)', 'sureforms' ), $money( 8 ) ), 8 ],
									/* translators: %s: base shipping price. */
									[ sprintf( __( 'Neighbouring countries (%s base)', 'sureforms' ), $money( 18 ) ), 18 ],
									/* translators: %s: base shipping price. */
									[ sprintf( __( 'International (%s base)', 'sureforms' ), $money( 35 ) ), 35 ],
								],
								'dropdown'
							),
							[ 'placeholder' => __( 'Select a destination', 'sureforms' ) ]
						)
					),
					$no_search
				),
				50
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::field( 'number', 'weight-kg', __( 'Parcel Weight', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ),
					[
						'minValue' => 0.1,
						'maxValue' => 30,
						'suffix'   => 'kg',
					]
				),
				50
			),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'speed',
					__( 'Delivery Speed', 'sureforms' ),
					true,
					$priced_options(
						[
							[ __( 'Standard (3 to 5 days)', 'sureforms' ), 0, 'truck' ],
							/* translators: %s: express surcharge. */
							[ sprintf( __( 'Express, next day (+%s)', 'sureforms' ), $money( 15 ) ), 15, 'truck-fast' ],
						]
					)
				),
				[ 'choiceWidth' => 50 ]
			),
			$result(
				'shipping-cost',
				__( 'Estimated Shipping', 'sureforms' ),
				'{form:zone} + {form:weight-kg} * 2.5 + {form:speed}',
				2,
				$symbol,
				'',
				/* translators: %s: price per kilogram. */
				[ 'helpText' => sprintf( __( 'Base price plus %s per kg.', 'sureforms' ), $symbol . number_format_i18n( 2.5, 2 ) ) ]
			),
			$c['first_name'],
			Catalog::width( $c['email'], 50 ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => sprintf(
				/* translators: %s: estimated shipping cost. */
				__( 'Your estimated shipping is %s. Delivery: {form:speed}. We will email {form:email} to confirm the booking and the final price.', 'sureforms' ),
				$amount( 'shipping-cost' )
			),
		],
		'email_subject' => sprintf(
			/* translators: %s: estimated shipping cost. */
			__( 'Shipping request from {form:first-name}: %s, {form:weight-kg} kg', 'sureforms' ),
			$amount( 'shipping-cost' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: estimated shipping cost. */
			__( '{form:first-name} wants to ship a {form:weight-kg} kg parcel. Destination: {form:zone}. Speed: {form:speed}. Their estimate: %s. Reply to this email to confirm the booking.', 'sureforms' ),
			$amount( 'shipping-cost' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: estimated shipping cost. */
				__( 'Your shipping estimate: %s', 'sureforms' ),
				$amount( 'shipping-cost' )
			),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: estimated shipping cost. */
					__( 'Thanks for your request. Shipping a {form:weight-kg} kg parcel comes to about %s. Delivery: {form:speed}.', 'sureforms' ),
					$amount( 'shipping-cost' )
				),
				__( 'We will confirm the booking and the final price shortly. Questions? Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
