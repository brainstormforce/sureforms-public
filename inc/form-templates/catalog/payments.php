<?php
/**
 * Form templates: Payments.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * Payments work with the free plugin through Stripe. SureForms Business adds
 * PayPal to every payment field, and the order templates need its
 * calculations for their total. Prices use the currency set in the SureForms
 * payment settings.
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
$setup     = [ 'stripe', 'paypal' ];

/*
 * A payment field. Stripe takes the payment on every plan; with SureForms
 * Business the visitor can also pay with PayPal.
 */
$payment = static function ( $slug, $label, $schema ) {
	// The block charges in the currency set in the payment settings.
	return Catalog::field(
		'payment',
		$slug,
		$label,
		true,
		array_merge(
			$schema,
			[
				'pro'      => [ 'blockAttributes' => [ 'paymentMethods' => [ 'stripe', 'paypal' ] ] ],
				'pro_tier' => 'business',
			]
		)
	);
};

/*
 * A choice whose options carry a price: the label shows it, the option value
 * is the amount the payment field or the calculation uses. An optional third
 * item is the option's icon.
 */
$priced_options = static function ( $options ) {
	$list = [];
	foreach ( $options as $option ) {
		$list[] = [
			'optionTitle' => $option[0],
			'value'       => (string) $option[1],
			'icon'        => $option[2] ?? '',
		];
	}
	return [
		'fieldOptions'    => $list,
		'showValues'      => true,
		'singleSelection' => true,
	];
};

/*
 * A read-only order total worked out by a formula (SureForms Business). The
 * block shows the currency; the saved answer has none.
 */
$total = static function ( $formula ) use ( $symbol ) {
	return Catalog::attrs(
		Catalog::field( 'number', 'total', __( 'Total', 'sureforms' ), true ),
		[
			'enableCalculation'  => true,
			'calculationFormula' => $formula,
			'calculationRound'   => 2,
			'prefix'             => $symbol,
		]
	);
};

/* translators: %1$s: option name, %2$s: its price, e.g. "VIP ($60)". */
$label_with_price = static fn( $name, $price ) => sprintf( __( '%1$s (%2$s)', 'sureforms' ), $name, $price );
/* translators: %s: monthly price, e.g. "$19". */
$per_month = static fn( $price ) => sprintf( __( '%s a month', 'sureforms' ), $price );

return [
	[
		'id'            => 'donation-form',
		'category'      => 'payments',
		'theme'         => 'sunset',
		'icon'          => 'hand-heart',
		'setup'         => $setup,
		'title'         => __( 'Donation Form', 'sureforms' ),
		'description'   => __( 'Accept one-time or monthly donations from supporters.', 'sureforms' ),
		'submit_text'   => __( 'Donate now', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			$c['email'],
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'amount',
					__( 'Choose an amount', 'sureforms' ),
					true,
					$priced_options( [ [ $money( 25 ), 25 ], [ $money( 50 ), 50 ], [ $money( 100 ), 100 ], [ $money( 250 ), 250 ] ] )
				),
				[ 'choiceWidth' => 25 ]
			),
			$payment(
				'donation',
				__( 'Your donation', 'sureforms' ),
				[
					'paymentType'                     => 'both',
					'oneTimeLabel'                    => __( 'Give once', 'sureforms' ),
					'subscriptionLabel'               => __( 'Give monthly', 'sureforms' ),
					'defaultPaymentChoice'            => 'one-time',
					'amountType'                      => 'variable',
					'variableAmountField'             => 'amount',
					'oneTimeAmountType'               => 'variable',
					'oneTimeVariableAmountField'      => 'amount',
					'subscriptionAmountType'          => 'variable',
					'subscriptionVariableAmountField' => 'amount',
					'subscriptionPlan'                => [
						'name'          => __( 'Monthly donation', 'sureforms' ),
						'interval'      => 'month',
						'billingCycles' => 'ongoing',
					],
					'customerNameField'               => 'first-name',
					'customerEmailField'              => 'email',
				]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'dedication', __( 'Dedicate your gift', 'sureforms' ), false, [ 'placeholder' => __( 'Optional: in honor or memory of someone, or a note for our team', 'sureforms' ) ] ), [ 'rows' => 2 ] ),
			Catalog::field( 'checkbox', 'anonymous', __( 'Keep my donation anonymous', 'sureforms' ) ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your gift of {form-payment:donation:amount} makes a real difference. A receipt is on its way to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New donation: {form-payment:donation:amount} from {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} donated {form-payment:donation:amount} (order {form-payment:donation:order-id}). Check whether they asked to stay anonymous before thanking them publicly.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thank you for your donation', 'sureforms' ),
			'greeting' => __( 'Dear {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for your generous gift of {form-payment:donation:amount}. It makes a real difference.', 'sureforms' ),
				__( 'Please keep this email as your receipt. Order reference: {form-payment:donation:order-id}.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'booking-deposit',
		'category'      => 'payments',
		'theme'         => 'lavender',
		'icon'          => 'calendar-check',
		'setup'         => $setup,
		'title'         => __( 'Booking Deposit', 'sureforms' ),
		'description'   => __( 'Take a deposit when clients book so you get paid for the slots you hold.', 'sureforms' ),
		'submit_text'   => __( 'Pay deposit and book', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			// With Premium the date and time pickers share a row, so the
			// service takes the full row above them.
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'service',
						__( 'Service', 'sureforms' ),
						true,
						array_merge(
							Catalog::dropdown_options( [ __( 'Consultation', 'sureforms' ), __( 'Standard session', 'sureforms' ), __( 'Extended session', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ),
							[
								'placeholder' => __( 'Select a service', 'sureforms' ),
								'pro'         => [ 'blockAttributes' => [ 'fieldWidth' => 100 ] ],
							]
						)
					),
					$no_search
				),
				50
			),
			Catalog::width( Catalog::date_field( 'preferred-date', __( 'Preferred date', 'sureforms' ), true, true ), 50 ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'preferred-time',
					__( 'Preferred time', 'sureforms' ),
					true,
					array_merge(
						Catalog::choice_options( [ __( 'Morning', 'sureforms' ), __( 'Afternoon', 'sureforms' ), __( 'Evening', 'sureforms' ) ], true, [ 'sun', 'cloud-sun', 'moon' ] ),
						[
							'pro' => [
								'fieldType'       => 'time-picker',
								'minTime'         => '09:00',
								'maxTime'         => '18:00',
								'increment'       => 30,
								'blockAttributes' => [ 'fieldWidth' => 50 ],
							],
						]
					)
				),
				[
					'choiceWidth'    => 33.33,
					'verticalLayout' => false,
				]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. access needs, or questions before your visit', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$payment(
				'deposit',
				__( 'Booking deposit', 'sureforms' ),
				[
					'paymentType'        => 'one-time',
					'amountType'         => 'fixed',
					'fixedAmount'        => 50,
					'customerNameField'  => 'name',
					'customerEmailField' => 'email',
					/* translators: %s: deposit amount. */
					'helpText'           => sprintf( __( 'A %s deposit holds your booking and comes off the final price. If we can\'t offer your date, we\'ll refund it in full.', 'sureforms' ), $money( 50 ) ),
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Deposit received', 'sureforms' ),
			'text'    => __( 'Thanks, {form:name}. Your deposit of {form-payment:deposit:amount} is paid. We will confirm your {form:service} on {form:preferred-date}, {form:preferred-time} by email shortly.', 'sureforms' ),
		],
		'email_subject' => __( 'Deposit paid: {form:service} for {form:name} on {form:preferred-date}, {form:preferred-time}', 'sureforms' ),
		'email_intro'   => __( '{form:name} paid a {form-payment:deposit:amount} deposit (order {form-payment:deposit:order-id}) for {form:service} on {form:preferred-date}, {form:preferred-time}. Reply to this email to confirm the booking, or call them on {form:phone}.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your deposit is paid', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for your booking. We have received your deposit of {form-payment:deposit:amount} for {form:service} on {form:preferred-date}, {form:preferred-time}.', 'sureforms' ),
				__( 'We will confirm the exact time shortly. The deposit comes off your final price. Order reference: {form-payment:deposit:order-id}.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'membership-subscription',
		'category'      => 'payments',
		'theme'         => 'midnight',
		'icon'          => 'crown',
		'setup'         => $setup,
		'title'         => __( 'Membership Signup', 'sureforms' ),
		'description'   => __( 'Sign members up for a monthly recurring plan until they cancel.', 'sureforms' ),
		'submit_text'   => __( 'Start my membership', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			$c['email'],
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'plan',
					__( 'Choose your plan', 'sureforms' ),
					true,
					array_merge(
						$priced_options(
							[
								[ $label_with_price( __( 'Basic', 'sureforms' ), $per_month( $money( 9 ) ) ), 9, 'leaf' ],
								[ $label_with_price( __( 'Plus', 'sureforms' ), $per_month( $money( 19 ) ) ), 19, 'star' ],
								[ $label_with_price( __( 'Premium', 'sureforms' ), $per_month( $money( 39 ) ) ), 39, 'crown' ],
							]
						),
						[ 'helpText' => __( 'Billed monthly. Cancel any time.', 'sureforms' ) ]
					)
				),
				[
					'choiceWidth'    => 100,
					'verticalLayout' => false,
				]
			),
			$payment(
				'membership',
				__( 'Payment details', 'sureforms' ),
				[
					'paymentType'         => 'subscription',
					'amountType'          => 'variable',
					'variableAmountField' => 'plan',
					'subscriptionPlan'    => [
						'name'          => __( 'Membership', 'sureforms' ),
						'interval'      => 'month',
						'billingCycles' => 'ongoing',
					],
					'customerNameField'   => 'name',
					'customerEmailField'  => 'email',
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Welcome aboard, {form:name}!', 'sureforms' ),
			'text'    => __( 'Your {form:plan} membership is active. We have sent the details to {form:email}. You can cancel any time by replying to that email.', 'sureforms' ),
		],
		'email_subject' => __( 'New member: {form:name} ({form:plan})', 'sureforms' ),
		'email_intro'   => __( '{form:name} joined on the {form:plan} plan. First payment: {form-payment:membership:amount}, order {form-payment:membership:order-id}.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Welcome to your membership', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for joining. Your {form:plan} membership is active, and your first payment of {form-payment:membership:amount} has gone through.', 'sureforms' ),
				__( 'You will be billed monthly until you cancel. To change or cancel your plan, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'              => 'event-ticket-order',
		'preview_image'   => 'event-ticket-order.webp',
		'category'        => 'payments',
		'theme'           => 'sunset',
		'icon'            => 'ticket',
		'requires'        => 'business',
		// Recomputes the calculated total on the server before charging.
		'min_pro_version' => '2.11.1',
		'features'        => [ 'calculation' ],
		'setup'           => $setup,
		'title'           => __( 'Event Ticket Order', 'sureforms' ),
		'description'     => __( 'Buyers select a ticket type and quantity before paying.', 'sureforms' ),
		'submit_text'     => __( 'Buy tickets', 'sureforms' ),
		'fields'          => [
			$c['first_name'],
			$c['last_name'],
			$c['email'],
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'ticket-type',
					__( 'Ticket Type', 'sureforms' ),
					true,
					$priced_options(
						[
							[ $label_with_price( __( 'General admission', 'sureforms' ), $money( 25 ) ), 25 ],
							[ $label_with_price( __( 'VIP', 'sureforms' ), $money( 60 ) ), 60 ],
						]
					)
				),
				[ 'choiceWidth' => 50 ]
			),
			Catalog::width( Catalog::number_field( 'quantity', __( 'Number of Tickets', 'sureforms' ), true, 1, 10, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ), 50 ),
			Catalog::width( $total( '{form:ticket-type} * {form:quantity}' ), 50 ),
			$payment(
				'payment',
				__( 'Payment', 'sureforms' ),
				[
					'paymentType'         => 'one-time',
					'amountType'          => 'variable',
					'variableAmountField' => 'total',
					'customerNameField'   => 'first-name',
					'customerEmailField'  => 'email',
				]
			),
			$c['consent'],
		],
		'confirmation'    => [
			'heading' => __( 'You\'re in, {form:first-name}!', 'sureforms' ),
			'text'    => __( '{form:quantity} x {form:ticket-type}, paid {form-payment:payment:amount}. Order {form-payment:payment:order-id}. Your tickets are on their way to {form:email}.', 'sureforms' ),
		],
		'email_subject'   => __( 'Ticket order: {form:quantity} x {form:ticket-type} for {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'     => __( '{form:first-name} {form:last-name} bought {form:quantity} x {form:ticket-type} and paid {form-payment:payment:amount} (order {form-payment:payment:order-id}).', 'sureforms' ),
		'autoresponder'   => [
			'subject'  => __( 'Your tickets: order {form-payment:payment:order-id}', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for your order: {form:quantity} x {form:ticket-type}, {form-payment:payment:amount} paid.', 'sureforms' ),
				__( 'Show this email at the door. Questions about the event? Just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'              => 'paid-class-registration',
		'preview_image'   => 'paid-class-registration.webp',
		'category'        => 'payments',
		'theme'           => 'ocean',
		'icon'            => 'graduation-cap',
		'requires'        => 'business',
		// Recomputes the calculated total on the server before charging.
		'min_pro_version' => '2.11.1',
		'features'        => [ 'calculation' ],
		'setup'           => $setup,
		'title'           => __( 'Paid Class Registration', 'sureforms' ),
		'description'     => __( 'Register people for a paid class or a workshop.', 'sureforms' ),
		'submit_text'     => __( 'Register and pay', 'sureforms' ),
		'fields'          => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'session',
					__( 'Session', 'sureforms' ),
					true,
					$priced_options(
						[
							[ $label_with_price( __( 'Morning', 'sureforms' ), $money( 40 ) ), 40 ],
							[ $label_with_price( __( 'Evening', 'sureforms' ), $money( 40 ) ), 40 ],
							[ $label_with_price( __( 'Full day', 'sureforms' ), $money( 70 ) ), 70 ],
						]
					)
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::width( Catalog::number_field( 'seats', __( 'Seats', 'sureforms' ), true, 1, 10, [ 'placeholder' => __( 'e.g. 1', 'sureforms' ) ] ), 50 ),
			Catalog::width( $total( '{form:session} * {form:seats}' ), 50 ),
			// One row per other person, only when more than one seat is booked. Seats sets the price.
			Catalog::field(
				'repeater',
				'other-attendees',
				__( 'Other attendees', 'sureforms' ),
				false,
				[
					'minItems'    => 1,
					'maxItems'    => 9,
					'innerFields' => [
						Catalog::width( Catalog::field( 'input', 'attendee-name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Alex Kim', 'sureforms' ) ] ), 50 ),
						Catalog::width( Catalog::field( 'email', 'attendee-email', __( 'Email (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'alex@example.com', 'sureforms' ) ] ), 50 ),
					],
				]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything the instructor should know?', 'sureforms' ), false, [ 'placeholder' => __( 'Experience level or access needs', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$payment(
				'payment',
				__( 'Payment', 'sureforms' ),
				[
					'paymentType'         => 'one-time',
					'amountType'          => 'variable',
					'variableAmountField' => 'total',
					'customerNameField'   => 'name',
					'customerEmailField'  => 'email',
				]
			),
			$c['consent'],
		],
		'conditions'      => [
			'other-attendees' => [ 'logic' => [ [ [ 'seats', '>=', '2' ] ] ] ],
		],
		'confirmation'    => [
			'heading' => __( 'See you in class, {form:name}!', 'sureforms' ),
			'text'    => __( 'Your {form:seats} seat(s) for the {form:session} session are booked and {form-payment:payment:amount} is paid. The details are on their way to {form:email}.', 'sureforms' ),
		],
		'email_subject'   => __( 'Class registration: {form:name}, {form:seats} seat(s), {form:session}', 'sureforms' ),
		'email_intro'     => __( '{form:name} booked {form:seats} seat(s) for the {form:session} session and paid {form-payment:payment:amount} (order {form-payment:payment:order-id}). Reply to this email to reach them.', 'sureforms' ),
		'autoresponder'   => [
			'subject'  => __( 'You are booked in', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for registering. Your {form:seats} seat(s) for the {form:session} session are confirmed and {form-payment:payment:amount} is paid.', 'sureforms' ),
				__( 'We will send a reminder with the details before the class. Questions? Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
