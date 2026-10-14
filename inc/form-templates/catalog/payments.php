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
 * is the amount the payment field or the calculation uses.
 */
$priced_options = static function ( $options, $icons = [] ) {
	$list = [];
	foreach ( $options as $index => $option ) {
		$list[] = [
			'optionTitle' => $option[0],
			'value'       => (string) $option[1],
			'icon'        => $icons[ $index ] ?? '',
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
		'description'   => __( 'Accept one-time or monthly donations by card, with a note for a dedication.', 'sureforms' ),
		'submit_text'   => __( 'Donate now', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			$c['email'],
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'amount',
					__( 'Choose an Amount', 'sureforms' ),
					true,
					$priced_options( [ [ $money( 10 ), 10 ], [ $money( 25 ), 25 ], [ $money( 50 ), 50 ], [ $money( 100 ), 100 ] ] )
				),
				[ 'choiceWidth' => 25 ]
			),
			$payment(
				'donation',
				__( 'Your Donation', 'sureforms' ),
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
			Catalog::attrs( Catalog::field( 'textarea', 'dedication', __( 'Dedicate Your Gift', 'sureforms' ), false, [ 'placeholder' => __( 'Optional: in honor or memory of someone, or a note for our team', 'sureforms' ) ] ), [ 'rows' => 2 ] ),
			Catalog::field( 'checkbox', 'anonymous', __( 'Keep my donation anonymous', 'sureforms' ) ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your gift of {form-payment:donation:amount} makes a real difference. A receipt is on its way to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New donation: {form-payment:donation:amount} from {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} donated {form-payment:donation:amount} (order {form-payment:donation:order-id}). Check below whether they asked to stay anonymous before thanking them publicly.', 'sureforms' ),
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
		'description'   => __( 'Take a deposit when clients book, so the slots you hold are paid for.', 'sureforms' ),
		'submit_text'   => __( 'Pay deposit and book', 'sureforms' ),
		'fields'        => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'service', __( 'Service', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Consultation', 'sureforms' ), __( 'Standard session', 'sureforms' ), __( 'Extended session', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a service', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::date_field( 'preferred-date', __( 'Preferred Date', 'sureforms' ), true, true ),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. preferred time of day, access needs', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$payment(
				'deposit',
				__( 'Booking Deposit', 'sureforms' ),
				[
					'paymentType'        => 'one-time',
					'amountType'         => 'fixed',
					'fixedAmount'        => 50,
					'customerNameField'  => 'full-name',
					'customerEmailField' => 'email',
					/* translators: %s: deposit amount. */
					'helpText'           => sprintf( __( 'A %s deposit holds your booking and comes off the final price.', 'sureforms' ), $money( 50 ) ),
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Deposit received', 'sureforms' ),
			'text'    => __( 'Thanks, {form:full-name}. Your deposit of {form-payment:deposit:amount} is paid. We will confirm your {form:service} on {form:preferred-date} by email shortly.', 'sureforms' ),
		],
		'email_subject' => __( 'Deposit paid: {form:service} for {form:full-name} on {form:preferred-date}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} paid a {form-payment:deposit:amount} deposit (order {form-payment:deposit:order-id}) for {form:service} on {form:preferred-date}. Reply to this email to confirm the booking, or call them on {form:phone}.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your deposit is paid', 'sureforms' ),
			'lines'   => [
				__( 'Thanks for your booking. We have received your deposit of {form-payment:deposit:amount} for {form:service} on {form:preferred-date}.', 'sureforms' ),
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
		'description'   => __( 'Sign members up to a monthly plan and bill them automatically until they cancel.', 'sureforms' ),
		'submit_text'   => __( 'Start my membership', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			$c['email'],
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'plan',
					__( 'Choose Your Plan', 'sureforms' ),
					true,
					$priced_options(
						[
							[ $label_with_price( __( 'Basic', 'sureforms' ), $per_month( $money( 9 ) ) ), 9 ],
							[ $label_with_price( __( 'Plus', 'sureforms' ), $per_month( $money( 19 ) ) ), 19 ],
							[ $label_with_price( __( 'Premium', 'sureforms' ), $per_month( $money( 39 ) ) ), 39 ],
						],
						[ 'seedling', 'star', 'crown' ]
					)
				),
				[ 'choiceWidth' => 33.33 ]
			),
			$payment(
				'membership',
				__( 'Payment', 'sureforms' ),
				[
					'paymentType'         => 'subscription',
					'amountType'          => 'variable',
					'variableAmountField' => 'plan',
					'subscriptionPlan'    => [
						'name'          => __( 'Membership', 'sureforms' ),
						'interval'      => 'month',
						'billingCycles' => 'ongoing',
					],
					'customerNameField'   => 'first-name',
					'customerEmailField'  => 'email',
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Welcome aboard, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your {form:plan} membership is active. We have sent the details to {form:email}. You can cancel any time by replying to that email.', 'sureforms' ),
		],
		'email_subject' => __( 'New member: {form:first-name} {form:last-name} ({form:plan})', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} joined on the {form:plan} plan. First payment: {form-payment:membership:amount}, order {form-payment:membership:order-id}.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Welcome to your membership', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for joining. Your {form:plan} membership is active, and your first payment of {form-payment:membership:amount} has gone through.', 'sureforms' ),
				__( 'You will be billed monthly until you cancel. To change or cancel your plan, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'              => 'event-ticket-order',
		'category'        => 'payments',
		'theme'           => 'sunset',
		'icon'            => 'ticket',
		'requires'        => 'business',
		// Recomputes the calculated total on the server before charging.
		'min_pro_version' => '2.11.1',
		'features'        => [ 'calculation' ],
		'setup'           => $setup,
		'title'           => __( 'Event Ticket Order', 'sureforms' ),
		'description'     => __( 'Sell tickets: buyers pick a ticket type and quantity, see the total and pay.', 'sureforms' ),
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
						],
						[ 'ticket', 'crown' ]
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
		'category'        => 'payments',
		'theme'           => 'ocean',
		'icon'            => 'graduation-cap',
		'requires'        => 'business',
		// Recomputes the calculated total on the server before charging.
		'min_pro_version' => '2.11.1',
		'features'        => [ 'calculation' ],
		'setup'           => $setup,
		'title'           => __( 'Paid Class Registration', 'sureforms' ),
		'description'     => __( 'Register people for a paid class or workshop and take payment for every seat.', 'sureforms' ),
		'submit_text'     => __( 'Register and pay', 'sureforms' ),
		'fields'          => [
			$c['first_name'],
			$c['last_name'],
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
						],
						[ 'sun', 'moon', 'calendar-day' ]
					)
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::width( Catalog::number_field( 'seats', __( 'Seats', 'sureforms' ), true, 1, 10, [ 'placeholder' => __( 'e.g. 1', 'sureforms' ) ] ), 50 ),
			Catalog::width( $total( '{form:session} * {form:seats}' ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything the instructor should know?', 'sureforms' ), false, [ 'placeholder' => __( 'Experience level, access needs, names of the other attendees', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
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
			'heading' => __( 'See you in class, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your {form:seats} seat(s) for the {form:session} session are booked and {form-payment:payment:amount} is paid. The details are on their way to {form:email}.', 'sureforms' ),
		],
		'email_subject'   => __( 'Class registration: {form:first-name} {form:last-name}, {form:seats} seat(s), {form:session}', 'sureforms' ),
		'email_intro'     => __( '{form:first-name} {form:last-name} booked {form:seats} seat(s) for the {form:session} session and paid {form-payment:payment:amount} (order {form-payment:payment:order-id}). Reply to this email to reach them.', 'sureforms' ),
		'autoresponder'   => [
			'subject'  => __( 'You are booked in', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for registering. Your {form:seats} seat(s) for the {form:session} session are confirmed and {form-payment:payment:amount} is paid.', 'sureforms' ),
				__( 'We will send a reminder with the details before the class. Questions? Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
