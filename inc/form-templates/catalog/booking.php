<?php
/**
 * Form templates: Bookings & events.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * @package sureforms
 * @since x.x.x
 */

use SRFM\Inc\Form_Templates\Catalog;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];

// With SureForms Pro, the appointment date shares its row with a time picker.
$appointment_date        = Catalog::date_field( 'preferred-date', __( 'Preferred Date', 'sureforms' ), true, true );
$appointment_date['pro'] = Catalog::attrs( Helper::get_array_value( $appointment_date['pro'] ), [ 'fieldWidth' => 50 ] );

return [
	[
		'id'            => 'appointment-request',
		'category'      => 'booking',
		'theme'         => 'lavender',
		'icon'          => 'calendar-clock',
		'title'         => __( 'Appointment Request', 'sureforms' ),
		'description'   => __( 'Let clients request an appointment and preferred time.', 'sureforms' ),
		'submit_text'   => __( 'Request appointment', 'sureforms' ),
		'fields'        => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'service', __( 'Service', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'First visit', 'sureforms' ), __( 'Follow-up visit', 'sureforms' ), __( 'Consultation', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a service', 'sureforms' ) ] ) ), $no_search ), 50 ),
			$appointment_date,
			// With SureForms Pro, an exact time next to the date instead of a part of the day.
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'preferred-time',
					__( 'Preferred Time', 'sureforms' ),
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
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. the reason for your visit, access needs, or other times that work', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Appointment requested', 'sureforms' ),
			'text'    => __( 'We have noted {form:preferred-date} ({form:preferred-time}). This is not confirmed yet: we will check availability and confirm by email or phone.', 'sureforms' ),
		],
		'email_subject' => __( 'Appointment request from {form:full-name}: {form:preferred-date}, {form:preferred-time}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} would like an appointment ({form:service}) on {form:preferred-date}, {form:preferred-time}. Reply to this email to confirm, or call them on {form:phone}.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'We received your appointment request', 'sureforms' ),
			'lines'   => [
				__( 'Thanks for your request. We have noted {form:service} on {form:preferred-date} ({form:preferred-time}).', 'sureforms' ),
				__( 'This is not a confirmation yet: we will check availability and get back to you to confirm.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'event-registration',
		'category'      => 'booking',
		'theme'         => 'sunset',
		'icon'          => 'ticket',
		'title'         => __( 'Event Registration', 'sureforms' ),
		'description'   => __( 'Register attendees for a workshop, webinar or meetup.', 'sureforms' ),
		'submit_text'   => __( 'Register now', 'sureforms' ),
		'fields'        => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::width(
				Catalog::number_field(
					'attendees',
					__( 'Attendees', 'sureforms' ),
					true,
					1,
					20,
					[
						'placeholder' => __( 'Including yourself', 'sureforms' ),
					]
				),
				50
			),
			Catalog::field( 'input', 'organization', __( 'Organization', 'sureforms' ) ),
			Catalog::field( 'multi-choice', 'dietary', __( 'Dietary Requirements', 'sureforms' ), false, array_merge( Catalog::choice_options( [ __( 'Vegetarian', 'sureforms' ), __( 'Vegan', 'sureforms' ), __( 'Gluten-free', 'sureforms' ), __( 'Dairy-free', 'sureforms' ), __( 'Halal', 'sureforms' ), __( 'Other', 'sureforms' ) ], false ), [ 'helpText' => __( 'For in-person events. Leave blank if none.', 'sureforms' ) ] ) ),
			Catalog::attrs( Catalog::field( 'textarea', 'questions', __( 'Anything else we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'Accessibility needs, dietary details or questions for the organizers', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::field( 'checkbox', 'event-updates', __( 'Email me about future events', 'sureforms' ) ),
			$c['consent'],
			// With SureForms Pro, the campaign that brought each registration.
			Catalog::field(
				'hidden',
				'utm-source',
				__( 'UTM Source', 'sureforms' ),
				false,
				[
					'defaultValue' => '{get_input:utm_source}',
					'pro_only'     => true,
				]
			),
			Catalog::field(
				'hidden',
				'utm-medium',
				__( 'UTM Medium', 'sureforms' ),
				false,
				[
					'defaultValue' => '{get_input:utm_medium}',
					'pro_only'     => true,
				]
			),
			Catalog::field(
				'hidden',
				'utm-campaign',
				__( 'UTM Campaign', 'sureforms' ),
				false,
				[
					'defaultValue' => '{get_input:utm_campaign}',
					'pro_only'     => true,
				]
			),
		],
		'confirmation'  => [
			'heading' => __( 'You are registered', 'sureforms' ),
			'text'    => __( 'Thanks for registering. A confirmation is on its way to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New registration: {form:full-name} ({form:attendees} attending)', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} has registered for your event. Places requested: {form:attendees}. Reply to this email to reach them directly.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'You are registered', 'sureforms' ),
			'lines'   => [
				__( 'Thanks for registering. Places requested: {form:attendees}.', 'sureforms' ),
				__( 'We will send you the event details before the day. If your plans change, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'rsvp',
		'category'      => 'booking',
		'theme'         => 'sunset',
		'icon'          => 'party-popper',
		'title'         => __( 'RSVP', 'sureforms' ),
		'description'   => __( 'Find out who is coming to your party, wedding or gathering.', 'sureforms' ),
		'submit_text'   => __( 'Send my RSVP', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			$c['email'],
			Catalog::attrs( Catalog::field( 'multi-choice', 'attending', __( 'Will you attend?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Yes, I will be there', 'sureforms' ), __( 'Sorry, I can\'t make it', 'sureforms' ) ], true, [ 'circle-check', 'circle-xmark' ] ) ), [ 'choiceWidth' => 50 ] ),
			Catalog::width(
				Catalog::number_field(
					'guests',
					__( 'Number of Guests', 'sureforms' ),
					false,
					1,
					10,
					[
						'placeholder' => __( 'Including you', 'sureforms' ),
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'dietary', __( 'Dietary Requirements', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. vegetarian, nut allergy', 'sureforms' ) ] ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'message', __( 'Message for the Host', 'sureforms' ), false, [ 'placeholder' => __( 'Anything you would like to share?', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your RSVP is saved. We have sent a copy to {form:email}.', 'sureforms' ),
		],
		// With SureForms Pro, guest questions only show for guests who are coming.
		'conditions'    => [
			'guests'  => [ 'logic' => [ [ [ 'attending', '==', __( 'Yes, I will be there', 'sureforms' ) ] ] ] ],
			'dietary' => [ 'logic' => [ [ [ 'attending', '==', __( 'Yes, I will be there', 'sureforms' ) ] ] ] ],
		],
		'email_subject' => __( 'RSVP: {form:first-name} {form:last-name} – {form:attending}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} replied to your invitation: {form:attending}. Their full RSVP is below. Reply to this email to get in touch with them.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We got your RSVP', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for letting us know. Your reply: {form:attending}.', 'sureforms' ),
				__( 'If your plans change, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'class-enrollment',
		'category'      => 'booking',
		'theme'         => 'ocean',
		'icon'          => 'graduation-cap',
		'title'         => __( 'Class Enrollment', 'sureforms' ),
		'description'   => __( 'Enroll students in a class or course.', 'sureforms' ),
		'submit_text'   => __( 'Enroll now', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'course', __( 'Course', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Beginner workshop', 'sureforms' ), __( 'Weekly class', 'sureforms' ), __( 'Weekend intensive', 'sureforms' ), __( 'Private lesson', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a course', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::width( Catalog::date_field( 'start-date', __( 'Preferred Start Date', 'sureforms' ), false, true ), 50 ),
			Catalog::field( 'multi-choice', 'experience', __( 'Experience Level', 'sureforms' ), true, Catalog::choice_options( [ __( 'New to this', 'sureforms' ), __( 'Some experience', 'sureforms' ), __( 'Experienced', 'sureforms' ) ], true, [ 'seedling', 'arrow-trend-up', 'award' ] ) ),
			Catalog::field( 'multi-choice', 'schedule', __( 'Times That Suit You', 'sureforms' ), false, array_merge( Catalog::choice_options( [ __( 'Weekday mornings', 'sureforms' ), __( 'Weekday evenings', 'sureforms' ), __( 'Weekends', 'sureforms' ) ], false, [ 'sun', 'moon', 'calendar-week' ] ), [ 'helpText' => __( 'Select all that apply.', 'sureforms' ) ] ) ),
			Catalog::attrs( Catalog::field( 'textarea', 'goals', __( 'Goals or Anything We Should Know', 'sureforms' ), false, [ 'placeholder' => __( 'What you hope to learn, access needs, questions…', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have received your enrollment for {form:course} and sent a copy to {form:email}. We will be in touch with your place and the next steps.', 'sureforms' ),
		],
		'email_subject' => __( 'New enrollment: {form:first-name} {form:last-name} – {form:course}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} wants to enroll in {form:course} (level: {form:experience}). Reply to this email to confirm their place.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your enrollment for {form:course}', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for enrolling in {form:course}. We have your details and will be in touch shortly to confirm your place and share the schedule.', 'sureforms' ),
				__( 'Questions in the meantime? Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
