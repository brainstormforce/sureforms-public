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
 * @since 2.13.0
 */

use SRFM\Inc\Form_Templates\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];
$symbol    = Catalog::currency_symbol();
$money     = [ Catalog::class, 'money' ];
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
	// The block shows the prefix and suffix; the saved answer has neither. Not
	// required: nobody types in it, so a required mark would only confuse.
	return Catalog::attrs(
		Catalog::field( 'number', $slug, $label, false, $extra ),
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

// The instant quote's services. Its conditions match these labels.
$quote_services = [
	'website' => $label_with_price( __( 'Website', 'sureforms' ), $money( 1500 ) ),
	'store'   => $label_with_price( __( 'Online store', 'sureforms' ), $money( 3000 ) ),
	'landing' => $label_with_price( __( 'Landing page', 'sureforms' ), $money( 600 ) ),
];

/*
 * One service's add-ons in the instant quote, stacked with an icon each. A
 * condition shows them only for that service; $no_sample keeps them out of the
 * library's example, which picks the first service.
 */
$quote_extras = static function ( $slug, $options, $no_sample = false ) use ( $priced_options ) {
	return Catalog::attrs(
		Catalog::field(
			'multi-choice',
			$slug,
			__( 'Add-ons', 'sureforms' ),
			false,
			array_merge(
				$priced_options( $options, 'multi-choice', false ),
				[ 'helpText' => __( 'Optional. Select all that apply.', 'sureforms' ) ],
				$no_sample ? [ 'no_sample' => true ] : []
			)
		),
		[
			'choiceWidth'    => 100,
			'verticalLayout' => false,
		]
	);
};

// Monthly repayment of a loan: P × r × (1 + r)^n / ((1 + r)^n − 1), r the monthly rate, n the months.
$rate       = '({form:interest-rate} / 1200)';
$growth     = '(1 + ' . $rate . ') ^ ({form:term-years} * 12)';
$repayment  = '{form:loan-amount} * ' . $rate . ' * ' . $growth . ' / (' . $growth . ' - 1)';
$disclaimer = __( 'An estimate for guidance only, not financial advice.', 'sureforms' );

// BMI in either unit system. Fields hidden by the unit choice count as empty
// (0), so each half is 0 for the system not chosen; the tiny 0.0001 keeps it
// from dividing by zero and changes a real BMI by far less than its rounding.
$bmi_units   = [
	'metric'   => __( 'Metric (cm, kg)', 'sureforms' ),
	'imperial' => __( 'Imperial (ft, in, lbs)', 'sureforms' ),
];
$bmi_formula = '{form:weight-kg} / (({form:height-cm} / 100) * ({form:height-cm} / 100) + 0.0001) + 703 * {form:weight-lbs} / (({form:height-ft} * 12 + {form:height-in}) * ({form:height-ft} * 12 + {form:height-in}) + 0.0001)';

/*
 * What the BMI visitor gets for sending the form, under a heading that fits
 * their result. A condition shows the one for their BMI; each range has its
 * own colours (background, then the left border), so the change stands out.
 */
$plan_pitch = static function ( $slug, $heading, $colors ) {
	$points = [
		__( 'Built around your goal and your BMI', 'sureforms' ),
		__( 'Simple steps you can start this week', 'sureforms' ),
		__( 'Sent to your inbox by a coach, free', 'sureforms' ),
	];
	// Sizes on each paragraph: the HTML block sets its own smaller text size.
	$html  = '<div style="padding: 16px 20px;border-radius: 8px;background: ' . $colors[0] . ';border-left: 4px solid ' . $colors[1] . '">';
	$html .= '<p style="margin: 0 0 8px;font-size: 17px;line-height: 1.4"><strong>' . esc_html( $heading ) . '</strong></p>';
	foreach ( $points as $point ) {
		$html .= '<p style="margin: 0;font-size: 15px;line-height: 1.7">&#10003; ' . esc_html( $point ) . '</p>';
	}
	$html .= '</div>';
	return Catalog::field( 'html', $slug, '', false, [ 'htmlContent' => $html ] );
};
// The shipping estimator's destinations. Its conditions match these labels.
$zones = [
	/* translators: %s: base shipping price. */
	'domestic'      => sprintf( __( 'Domestic (%s base)', 'sureforms' ), $money( 8 ) ),
	/* translators: %s: base shipping price. */
	'neighbouring'  => sprintf( __( 'Nearby countries (%s base)', 'sureforms' ), $money( 18 ) ),
	/* translators: %s: base shipping price. */
	'international' => sprintf( __( 'International (%s base)', 'sureforms' ), $money( 35 ) ),
];

/*
 * One destination's delivery speeds: standard at no extra cost, express with
 * a surcharge. $no_sample keeps it out of the library's example, which picks
 * the first destination.
 */
$speed = static function ( $slug, $standard, $express, $surcharge, $no_sample = false ) use ( $priced_options, $money ) {
	return Catalog::attrs(
		Catalog::field(
			'multi-choice',
			$slug,
			__( 'Delivery speed', 'sureforms' ),
			true,
			array_merge(
				$priced_options(
					[
						[ $standard, 0 ],
						/* translators: %1$s: express delivery time, %2$s: its surcharge. */
						[ sprintf( __( '%1$s (+%2$s)', 'sureforms' ), $express, $money( $surcharge ) ), $surcharge ],
					]
				),
				$no_sample ? [ 'no_sample' => true ] : []
			)
		),
		[ 'choiceWidth' => 50 ]
	);
};

// The ROI calculator's two results.
$roi_hours   = '{form:team-size} * {form:hours-saved} * 48';
$roi_savings = $roi_hours . ' * {form:hourly-cost}';

/*
 * One ROI result as a card that updates as the visitor types (an HTML block
 * showing a calculation), half the row wide. In $format, %s stands for the result.
 */
$roi_card = static function ( $slug, $label, $formula, $round, $format ) {
	$html  = '<div style="padding: 14px 18px;border-radius: 8px;background: #f5f3ff;border: 1px solid #ddd6fe">';
	$html .= '<p style="margin: 0;font-size: 14px;line-height: 1.4">' . esc_html( $label ) . '</p>';
	$html .= '<p style="margin: 4px 0 0;font-size: 28px;line-height: 1.2;font-weight: 700;color: #5b21b6">' . str_replace( '%s', '{calculation-result}', esc_html( $format ) ) . '</p>';
	$html .= '</div>';
	return Catalog::width(
		Catalog::attrs(
			Catalog::field( 'html', $slug, '', false, [ 'htmlContent' => $html ] ),
			[
				'enableCalculation'  => true,
				'calculationFormula' => $formula,
				'calculationRound'   => $round,
			]
		),
		50
	);
};

// A calculated value kept out of sight, for the messages and the entry.
$roi_hidden = static fn( $slug, $label, $formula ) => Catalog::attrs(
	Catalog::field( 'hidden', $slug, $label, false ),
	[
		'enableCalculation'  => true,
		'calculationFormula' => $formula,
		'calculationRound'   => 0,
	]
);

// The ROI calculator's walkthrough answer that its thank-you rule matches.
$walkthrough_yes = __( 'Yes, book me in', 'sureforms' );

// A body measurement with its unit after the input.
$measure = static fn( $slug, $label, $unit, $min, $max, $placeholder ) => Catalog::attrs(
	Catalog::field( 'number', $slug, $label, true, [ 'placeholder' => $placeholder ] ),
	[
		'minValue' => $min,
		'maxValue' => $max,
		'suffix'   => $unit,
	]
);

return [
	[
		'id'            => 'instant-quote-calculator',
		'preview_image' => 'instant-quote-calculator.webp',
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
								[ $quote_services['website'], 1500 ],
								[ $quote_services['store'], 3000 ],
								[ $quote_services['landing'], 600 ],
							],
							'dropdown'
						),
						[ 'placeholder' => __( 'Select a project type', 'sureforms' ) ]
					)
				),
				$no_search
			),
			// Full width: it only shows once a website or store is chosen.
			Catalog::number_field(
				'pages',
				__( 'Number of pages', 'sureforms' ),
				true,
				1,
				50,
				[ 'placeholder' => __( 'e.g. 5', 'sureforms' ) ]
			),
			$quote_extras(
				'website-extras',
				[
					[ $label_with_price( __( 'Copywriting', 'sureforms' ), '+' . $money( 300 ) ), 300, 'pen-nib' ],
					[ $label_with_price( __( 'SEO setup', 'sureforms' ), '+' . $money( 400 ) ), 400, 'magnifying-glass' ],
					[ $label_with_price( __( 'Blog setup', 'sureforms' ), '+' . $money( 250 ) ), 250, 'newspaper' ],
					[ $label_with_price( __( 'Rush delivery', 'sureforms' ), '+' . $money( 500 ) ), 500, 'bolt' ],
				]
			),
			$quote_extras(
				'store-extras',
				[
					[ $label_with_price( __( 'Product upload, up to 50', 'sureforms' ), '+' . $money( 400 ) ), 400, 'boxes-stacked' ],
					[ $label_with_price( __( 'Payments and shipping setup', 'sureforms' ), '+' . $money( 300 ) ), 300, 'credit-card' ],
					[ $label_with_price( __( 'SEO setup', 'sureforms' ), '+' . $money( 400 ) ), 400, 'magnifying-glass' ],
					[ $label_with_price( __( 'Rush delivery', 'sureforms' ), '+' . $money( 500 ) ), 500, 'bolt' ],
				],
				true
			),
			$quote_extras(
				'landing-extras',
				[
					[ $label_with_price( __( 'Copywriting', 'sureforms' ), '+' . $money( 200 ) ), 200, 'pen-nib' ],
					[ $label_with_price( __( 'A/B test version', 'sureforms' ), '+' . $money( 250 ) ), 250, 'code-compare' ],
					[ $label_with_price( __( 'Rush delivery', 'sureforms' ), '+' . $money( 300 ) ), 300, 'bolt' ],
				],
				true
			),
			// Hidden fields count as empty, so only the chosen service's pages and add-ons add up.
			$result(
				'estimate',
				__( 'Estimated price', 'sureforms' ),
				'{form:service} + {form:pages} * 100 + {form:website-extras} + {form:store-extras} + {form:landing-extras}',
				0,
				$symbol,
				'',
				[ 'helpText' => __( 'A rough guide. Your detailed quote may differ.', 'sureforms' ) ]
			),
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Tell us about your project', 'sureforms' ), false, [ 'placeholder' => __( 'Deadlines, examples you like, anything that affects the price', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		// Each service has its own add-ons, and a landing page is always one page.
		'conditions'    => [
			'pages'          => [ 'logic' => [ [ [ 'service', '!=', $quote_services['landing'] ] ] ] ],
			'website-extras' => [ 'logic' => [ [ [ 'service', '==', $quote_services['website'] ] ] ] ],
			'store-extras'   => [ 'logic' => [ [ [ 'service', '==', $quote_services['store'] ] ] ] ],
			'landing-extras' => [ 'logic' => [ [ [ 'service', '==', $quote_services['landing'] ] ] ] ],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => sprintf(
				/* translators: %s: the estimated price. */
				__( 'Your estimate is %s. We will review your project and send a detailed quote to {form:email} within one business day.', 'sureforms' ),
				$amount( 'estimate' )
			),
		],
		'email_subject' => sprintf(
			/* translators: %s: the estimated price. */
			__( 'Quote estimate %s: {form:service} for {form:name}', 'sureforms' ),
			$amount( 'estimate' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: the estimated price. */
			__( '{form:name} priced {form:service} and got an estimate of %s. Reply to this email to send them a detailed quote.', 'sureforms' ),
			$amount( 'estimate' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: the estimated price. */
				__( 'Your estimate: %s', 'sureforms' ),
				$amount( 'estimate' )
			),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: the estimated price. */
					__( 'Thanks for using our quote calculator. Your estimate for {form:service} is %s.', 'sureforms' ),
					$amount( 'estimate' )
				),
				__( 'We will review your details and send a detailed quote within one business day. Questions in the meantime? Just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'loan-emi-calculator',
		'preview_image' => 'loan-emi-calculator.webp',
		'category'      => 'calculators',
		'theme'         => 'ocean',
		'icon'          => 'landmark',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'Loan Repayment Calculator', 'sureforms' ),
		'description'   => __( 'Calculate the monthly repayment and total interest on a loan.', 'sureforms' ),
		'submit_text'   => __( 'Email me the breakdown', 'sureforms' ),
		'fields'        => [
			Catalog::width(
				Catalog::attrs(
					Catalog::number_field( 'loan-amount', __( 'Loan amount', 'sureforms' ), true, 100, 10000000, [ 'placeholder' => __( 'e.g. 25,000', 'sureforms' ) ] ),
					[ 'prefix' => $symbol ]
				),
				100
			),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'number',
						'interest-rate',
						__( 'Interest rate (% a year)', 'sureforms' ),
						true,
						[ 'placeholder' => __( 'e.g. 6.5', 'sureforms' ) ]
					),
					[
						'minValue' => 0.1,
						'maxValue' => 50,
						'suffix'   => '%',
					]
				),
				50
			),
			Catalog::width(
				Catalog::number_field( 'term-years', __( 'Term (years)', 'sureforms' ), true, 1, 40, [ 'placeholder' => __( 'e.g. 5', 'sureforms' ) ] ),
				50
			),
			$result( 'monthly-payment', __( 'Monthly repayment', 'sureforms' ), $repayment, 2, $symbol, '', [ 'helpText' => $disclaimer ] ),
			Catalog::width( $result( 'total-interest', __( 'Total interest', 'sureforms' ), '(' . $repayment . ') * {form:term-years} * 12 - {form:loan-amount}', 2, $symbol ), 50 ),
			Catalog::width( $result( 'total-repayment', __( 'Total to repay', 'sureforms' ), '(' . $repayment . ') * {form:term-years} * 12', 2, $symbol ), 50 ),
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
				/* translators: %1$s: monthly repayment, %2$s: total interest, %3$s: total to repay. */
				__( 'Your estimated monthly repayment is %1$s over {form:term-years} years, with %2$s interest in total (%3$s to repay). We have sent the details to {form:email}.', 'sureforms' ),
				$amount( 'monthly-payment' ),
				$amount( 'total-interest' ),
				$amount( 'total-repayment' )
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
					/* translators: %1$s: monthly repayment, %2$s: total interest, %3$s: total to repay. */
					__( 'Monthly repayment: %1$s. Total interest: %2$s. Total to repay: %3$s.', 'sureforms' ),
					$amount( 'monthly-payment' ),
					$amount( 'total-interest' ),
					$amount( 'total-repayment' )
				),
				$disclaimer . ' ' . __( 'Reply to this email if you would like to talk it through.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'bmi-calculator',
		'preview_image' => 'bmi-calculator.webp',
		'category'      => 'calculators',
		'theme'         => 'mint',
		'icon'          => 'activity',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'BMI Calculator', 'sureforms' ),
		'description'   => __( 'Calculate BMI and request a personalized plan instantly.', 'sureforms' ),
		'submit_text'   => __( 'Send me my free plan', 'sureforms' ),
		'fields'        => [
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'units', __( 'Units', 'sureforms' ), true, Catalog::choice_options( [ $bmi_units['metric'], $bmi_units['imperial'] ], true ) ),
				[ 'choiceWidth' => 50 ]
			),
			Catalog::width( $measure( 'height-cm', __( 'Height', 'sureforms' ), 'cm', 50, 250, __( 'e.g. 170', 'sureforms' ) ), 50 ),
			Catalog::width( $measure( 'weight-kg', __( 'Weight', 'sureforms' ), 'kg', 20, 300, __( 'e.g. 68', 'sureforms' ) ), 50 ),
			// The example answers are metric, so the imperial fields stay out of the sample BMI.
			Catalog::width( $measure( 'height-ft', __( 'Height', 'sureforms' ), 'ft', 1, 8, __( 'e.g. 5', 'sureforms' ) ) + [ 'no_sample' => true ], 25 ),
			// Optional: empty counts as 0, so 5 ft needs no "0".
			Catalog::width(
				array_merge(
					$measure( 'height-in', __( 'Inches', 'sureforms' ), 'in', 0, 11, __( 'e.g. 7', 'sureforms' ) ),
					[
						'required'  => false,
						'no_sample' => true,
					]
				),
				25
			),
			Catalog::width( $measure( 'weight-lbs', __( 'Weight', 'sureforms' ), 'lbs', 40, 660, __( 'e.g. 150', 'sureforms' ) ) + [ 'no_sample' => true ], 50 ),
			$result(
				'bmi',
				__( 'Your BMI', 'sureforms' ),
				$bmi_formula,
				1,
				'',
				'',
				[ 'helpText' => __( 'Healthy range for adults: 18.5 to 24.9. An estimate, not medical advice.', 'sureforms' ) ]
			),
			// Why it's worth sending the form, in words that fit the result.
			$plan_pitch( 'plan-under', __( 'Build up healthily, with a plan made for you', 'sureforms' ), [ '#eff6ff', '#3b82f6' ] ),
			$plan_pitch( 'plan-healthy', __( 'You are in the healthy range. Get a plan to keep it that way', 'sureforms' ), [ '#ecfdf5', '#10b981' ] ),
			// Amber, not red: an encouraging nudge, not a warning.
			$plan_pitch( 'plan-over', __( 'Work towards a healthier range, one step at a time', 'sureforms' ), [ '#fffbeb', '#f59e0b' ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'goal',
					__( 'What\'s your goal?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Lose weight', 'sureforms' ), __( 'Maintain weight', 'sureforms' ), __( 'Gain weight', 'sureforms' ) ], true, [ 'arrow-trend-down', 'scale-balanced', 'arrow-trend-up' ] )
				),
				// Icon above the text, so "Maintain weight" fits a third of the row.
				[
					'choiceWidth'    => 33.33,
					'verticalLayout' => true,
				]
			),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			$c['consent'],
		],
		// Only the chosen unit system's fields show.
		'conditions'    => [
			'height-cm'    => [ 'logic' => [ [ [ 'units', '==', $bmi_units['metric'] ] ] ] ],
			'weight-kg'    => [ 'logic' => [ [ [ 'units', '==', $bmi_units['metric'] ] ] ] ],
			'height-ft'    => [ 'logic' => [ [ [ 'units', '==', $bmi_units['imperial'] ] ] ] ],
			'height-in'    => [ 'logic' => [ [ [ 'units', '==', $bmi_units['imperial'] ] ] ] ],
			'weight-lbs'   => [ 'logic' => [ [ [ 'units', '==', $bmi_units['imperial'] ] ] ] ],
			// The BMI shows 0 until height and weight are in, so no message shows before then.
			'plan-under'   => [ 'logic' => [ [ [ 'bmi', '>', '0' ], [ 'bmi', '<', '18.5' ] ] ] ],
			'plan-healthy' => [ 'logic' => [ [ [ 'bmi', '>=', '18.5' ], [ 'bmi', '<', '25' ] ] ] ],
			'plan-over'    => [ 'logic' => [ [ [ 'bmi', '>=', '25' ] ] ] ],
		],
		'confirmations' => [
			[
				'name'    => __( 'BMI under 18.5', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'Your BMI is {form:bmi}, below the usual healthy range. A coach will email you a plan for your goal ({form:goal}), with a focus on healthy, steady gains.', 'sureforms' ) ),
				'rules'   => [ [ 'bmi', '_LESSER_', '18.5' ] ],
			],
			[
				'name'    => __( 'BMI 18.5 to 24.9', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'Your BMI is {form:bmi}, within the usual healthy range. A coach will email you a plan for your goal ({form:goal}).', 'sureforms' ) ),
				'rules'   => [ [ 'bmi', '_GREATER_OR_EQUAL_', '18.5' ], [ 'bmi', '_LESSER_', '25' ] ],
			],
			[
				'name'    => __( 'BMI 25 and over', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'Your BMI is {form:bmi}, above the usual healthy range. A coach will email you a realistic plan for your goal ({form:goal}).', 'sureforms' ) ),
				'rules'   => [ [ 'bmi', '_GREATER_OR_EQUAL_', '25' ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => __( 'Your BMI is {form:bmi}. A coach will email you a plan for your goal ({form:goal}). BMI is an estimate, not medical advice.', 'sureforms' ),
		],
		'email_subject' => __( 'BMI check: {form:name} ({form:bmi}), goal: {form:goal}', 'sureforms' ),
		'email_intro'   => __( '{form:name} checked their BMI ({form:bmi}) and would like a plan for their goal: {form:goal}. Reply to this email to send it.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your BMI result', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for checking your BMI with us. Your result: {form:bmi}.', 'sureforms' ),
				__( 'A coach will be in touch with a plan for your goal ({form:goal}). BMI is a rough guide and doesn\'t account for muscle, age or build, so it is not medical advice.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'roi-savings-calculator',
		'preview_image' => 'roi-savings-calculator.webp',
		'category'      => 'calculators',
		'theme'         => 'lavender',
		'icon'          => 'trending-up',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'ROI / Savings Calculator', 'sureforms' ),
		'description'   => __( 'Show prospects how much time and money your product could save them a year.', 'sureforms' ),
		'submit_text'   => __( 'Send me the breakdown', 'sureforms' ),
		// Premium shows the numbers and the result first, then the contact details.
		'steps'         => [ __( 'Your numbers', 'sureforms' ), __( 'Get your breakdown', 'sureforms' ) ],
		'fields'        => [
			Catalog::width( Catalog::number_field( 'team-size', __( 'Team size', 'sureforms' ), true, 1, 10000, [ 'placeholder' => __( 'e.g. 10', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::attrs(
					Catalog::number_field( 'hourly-cost', __( 'Hourly cost per person', 'sureforms' ), true, 1, 1000, [ 'placeholder' => __( 'e.g. 40', 'sureforms' ) ] ),
					[ 'prefix' => $symbol ]
				),
				50
			),
			Catalog::attrs(
				Catalog::field(
					'slider',
					'hours-saved',
					__( 'Hours saved per person each week', 'sureforms' ),
					true,
					[
						'min'           => 1,
						'max'           => 20,
						'step'          => 1,
						// The preview's example answer: the slider's starting value.
						'placeholder'   => '5',
						'suffixTooltip' => __( 'hrs', 'sureforms' ),
						'helpText'      => __( 'We count 48 working weeks a year.', 'sureforms' ),
					]
				),
				[ 'numberDefaultValue' => '5' ]
			),
			// The results show as cards; hidden fields keep the numbers for the messages and the entry.
			$roi_card( 'savings-card', __( 'Saved a year', 'sureforms' ), $roi_savings, 0, $symbol . '%s' ),
			/* translators: %s: number of hours. */
			$roi_card( 'hours-card', __( 'Hours back a year', 'sureforms' ), $roi_hours, 0, __( '%s hrs', 'sureforms' ) ),
			Catalog::attrs(
				Catalog::field(
					'html',
					'staff-line',
					'',
					false,
					/* translators: %s: number of full-time staff, worked out as the visitor types. */
					[ 'htmlContent' => '<p style="margin: 0;font-size: 15px;line-height: 1.6">' . sprintf( esc_html__( 'That is about %s full-time staff worth of time, freed up without hiring.', 'sureforms' ), '<strong>{calculation-result}</strong>' ) . '</p>' ]
				),
				// 1,920 hours: 40 hours a week over 48 weeks.
				[
					'enableCalculation'  => true,
					'calculationFormula' => '{form:team-size} * {form:hours-saved} * 48 / 1920',
					'calculationRound'   => 1,
				]
			),
			$roi_hidden( 'hours-saved-year', __( 'Hours saved a year', 'sureforms' ), $roi_hours ),
			$roi_hidden( 'yearly-savings', __( 'Yearly savings', 'sureforms' ), $roi_savings ),
			Catalog::step( 'contact-step', __( 'Get your breakdown', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'email', 'email', __( 'Work email', 'sureforms' ), true, [ 'placeholder' => __( 'jane@company.com', 'sureforms' ) ] ), 50 ),
			Catalog::field( 'input', 'company', __( 'Company (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ),
			// Tells sales which leads want a demo now.
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'walkthrough', __( 'Would you like a short walkthrough?', 'sureforms' ), true, Catalog::choice_options( [ $walkthrough_yes, __( 'Not right now', 'sureforms' ) ], true ) ),
				[ 'choiceWidth' => 50 ]
			),
			// Only asked of people who want a walkthrough.
			Catalog::field( 'phone', 'phone', __( 'Phone (optional)', 'sureforms' ), false ),
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'best-time', __( 'Best time to call', 'sureforms' ), true, Catalog::choice_options( [ __( 'Morning', 'sureforms' ), __( 'Afternoon', 'sureforms' ), __( 'Evening', 'sureforms' ) ], true, [ 'sun', 'cloud-sun', 'moon' ] ) ),
				[
					'choiceWidth'    => 33.33,
					'verticalLayout' => false,
				]
			),
			$c['consent'],
		],
		'conditions'    => [
			// The staff line waits for the numbers it needs.
			'staff-line' => [ 'logic' => [ [ [ 'team-size', '>', '0' ] ] ] ],
			'phone'      => [ 'logic' => [ [ [ 'walkthrough', '==', $walkthrough_yes ] ] ] ],
			'best-time'  => [ 'logic' => [ [ [ 'walkthrough', '==', $walkthrough_yes ] ] ] ],
		],
		// The visitor has just seen the numbers on the form, so the thank-you message doesn't repeat them.
		'confirmation'  => [
			'heading' => __( 'Your breakdown is on its way', 'sureforms' ),
			'text'    => __( 'We have sent your savings estimate to {form:email}.', 'sureforms' ),
		],
		// With Premium, people who asked for a walkthrough are told it is coming.
		'confirmations' => [
			[
				'name'    => __( 'Walkthrough requested', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'Your breakdown is on its way', 'sureforms' ),
					__( 'We have sent your savings estimate to {form:email} and will be in touch shortly to book your walkthrough.', 'sureforms' )
				),
				'rules'   => [ [ 'walkthrough', '_EQUAL_', $walkthrough_yes ] ],
			],
		],
		'email_subject' => sprintf(
			/* translators: %s: yearly savings. */
			__( 'New ROI lead: {form:name}, %s a year (walkthrough: {form:walkthrough})', 'sureforms' ),
			$amount( 'yearly-savings' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: yearly savings. */
			__( '{form:name} worked out savings of %s and {form:hours-saved-year} hours a year for a team of {form:team-size} ({form:hours-saved} hours a week each). Walkthrough: {form:walkthrough}. Reply to this email to follow up.', 'sureforms' ),
			$amount( 'yearly-savings' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: yearly savings. */
				__( 'Your savings estimate: %s a year', 'sureforms' ),
				$amount( 'yearly-savings' )
			),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: yearly savings. */
					__( 'Thanks for running the numbers. With {form:team-size} people each saving {form:hours-saved} hours a week, you could save about {form:hours-saved-year} hours and %s a year.', 'sureforms' ),
					$amount( 'yearly-savings' )
				),
				__( 'Want to see how? Reply to this email and we will set up a short walkthrough.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'shipping-cost-estimator',
		'preview_image' => 'shipping-cost-estimator.webp',
		'category'      => 'calculators',
		'theme'         => 'sand',
		'icon'          => 'truck',
		'requires'      => 'business',
		'features'      => [ 'calculation' ],
		'title'         => __( 'Shipping Cost Estimator', 'sureforms' ),
		'description'   => __( 'Estimate shipping by destination, weight, and speed, then follow up with a booking.', 'sureforms' ),
		'submit_text'   => __( 'Request a booking', 'sureforms' ),
		// Premium shows the parcel and the estimate first, then the pickup details.
		'steps'         => [ __( 'Your parcel', 'sureforms' ), __( 'Book a pickup', 'sureforms' ) ],
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
									[ $zones['domestic'], 8 ],
									[ $zones['neighbouring'], 18 ],
									[ $zones['international'], 35 ],
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
					Catalog::field( 'number', 'weight-kg', __( 'Parcel weight', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ),
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
					'size',
					__( 'Parcel size', 'sureforms' ),
					true,
					array_merge(
						$priced_options(
							[
								[ _x( 'Small', 'parcel size', 'sureforms' ), 0 ],
								/* translators: %s: size surcharge. */
								[ sprintf( _x( 'Medium (+%s)', 'parcel size', 'sureforms' ), $money( 5 ) ), 5 ],
								/* translators: %s: size surcharge. */
								[ sprintf( _x( 'Large (+%s)', 'parcel size', 'sureforms' ), $money( 12 ) ), 12 ],
							]
						),
						// Carriers charge more for bulky parcels; this stands in for measuring the box.
						[ 'helpText' => __( 'Small fits a shoebox, medium a moving box. Large is anything bigger.', 'sureforms' ) ]
					)
				),
				[
					'choiceWidth'    => 33.33,
					'verticalLayout' => false,
				]
			),
			// Each destination has its own delivery times; a condition shows the one for the chosen destination.
			$speed( 'speed-domestic', __( 'Standard (3 to 5 days)', 'sureforms' ), __( 'Express, next day', 'sureforms' ), 15 ),
			$speed( 'speed-neighbouring', __( 'Standard (5 to 7 days)', 'sureforms' ), __( 'Express, 2 to 3 days', 'sureforms' ), 25, true ),
			$speed( 'speed-international', __( 'Standard (7 to 14 days)', 'sureforms' ), __( 'Express, 3 to 5 days', 'sureforms' ), 45, true ),
			$result(
				'shipping-cost',
				__( 'Estimated shipping', 'sureforms' ),
				'{form:zone} + {form:weight-kg} * 2.5 + {form:size} + {form:speed-domestic} + {form:speed-neighbouring} + {form:speed-international}',
				2,
				$symbol,
				'',
				/* translators: %s: price per kilogram. */
				[ 'helpText' => sprintf( __( 'Base price, plus %s per kg, size and speed.', 'sureforms' ), $symbol . number_format_i18n( 2.5, 2 ) ) ]
			),
			Catalog::step( 'pickup-step', __( 'Book a pickup', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( Catalog::date_field( 'pickup-date', __( 'Preferred pickup date', 'sureforms' ), true, true ), 50 ),
			Catalog::width( Catalog::field( 'input', 'from-postcode', __( 'Collection postcode', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. SW1A 1AA', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'to-postcode', __( 'Delivery postcode or country', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 10115 Berlin', 'sureforms' ) ] ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything we should know? (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. fragile, or collect from reception', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		// Hidden speeds count as empty, so only the chosen destination's speed adds to the price.
		'conditions'    => [
			'speed-domestic'      => [ 'logic' => [ [ [ 'zone', '==', $zones['domestic'] ] ] ] ],
			'speed-neighbouring'  => [ 'logic' => [ [ [ 'zone', '==', $zones['neighbouring'] ] ] ] ],
			'speed-international' => [ 'logic' => [ [ [ 'zone', '==', $zones['international'] ] ] ] ],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => sprintf(
				/* translators: %s: estimated shipping cost. */
				__( 'Your estimated shipping is %s. Delivery: {form:speed-domestic}{form:speed-neighbouring}{form:speed-international}. We will email {form:email} to confirm the pickup on {form:pickup-date} and the final price.', 'sureforms' ),
				$amount( 'shipping-cost' )
			),
		],
		'email_subject' => sprintf(
			/* translators: %s: estimated shipping cost. */
			__( 'Pickup request from {form:name}: %s, {form:weight-kg} kg on {form:pickup-date}', 'sureforms' ),
			$amount( 'shipping-cost' )
		),
		'email_intro'   => sprintf(
			/* translators: %s: estimated shipping cost. */
			__( '{form:name} wants to ship a {form:weight-kg} kg parcel ({form:size}) from {form:from-postcode} to {form:to-postcode}, collected on {form:pickup-date}. Destination: {form:zone}. Speed: {form:speed-domestic}{form:speed-neighbouring}{form:speed-international}. Their estimate: %s. Call them on {form:phone} or reply to this email to confirm.', 'sureforms' ),
			$amount( 'shipping-cost' )
		),
		'autoresponder' => [
			'subject'  => sprintf(
				/* translators: %s: estimated shipping cost. */
				__( 'Your shipping estimate: %s', 'sureforms' ),
				$amount( 'shipping-cost' )
			),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				sprintf(
					/* translators: %s: estimated shipping cost. */
					__( 'Thanks for your request. Shipping a {form:weight-kg} kg parcel comes to about %s. Delivery: {form:speed-domestic}{form:speed-neighbouring}{form:speed-international}.', 'sureforms' ),
					$amount( 'shipping-cost' )
				),
				__( 'We will confirm the pickup on {form:pickup-date} and the final price shortly. Questions? Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
