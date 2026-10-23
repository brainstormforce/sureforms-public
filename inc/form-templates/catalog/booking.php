<?php
/**
 * Form templates: Bookings & events.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * @package sureforms
 * @since 2.13.0
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
		'description'   => __( 'Let clients request appointments at their preferred times.', 'sureforms' ),
		'submit_text'   => __( 'Request appointment', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			// Beside the date on free forms. With SureForms Pro the date and time
			// pickers share a row, so the service takes the full row above them.
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'service',
						__( 'Service', 'sureforms' ),
						true,
						array_merge(
							Catalog::dropdown_options( [ __( 'First visit', 'sureforms' ), __( 'Follow-up visit', 'sureforms' ), __( 'Consultation', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ),
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
			Catalog::width( $appointment_date, 50 ),
			// With SureForms Pro, an exact time next to the date instead of a part of the day.
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
				// Icon beside the text, the three choices side by side.
				[
					'verticalLayout' => false,
					'choiceWidth'    => 33.33,
				]
			),
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'meeting-type', __( 'How would you like to meet?', 'sureforms' ), true, Catalog::choice_options( [ __( 'In person', 'sureforms' ), __( 'Phone call', 'sureforms' ), __( 'Video call', 'sureforms' ) ], true, [ 'handshake', 'phone', 'video' ] ) ),
				[
					'verticalLayout' => false,
					'choiceWidth'    => 33.33,
				]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'notes', __( 'Anything we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. the reason for your visit, access needs, or other times that work', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Appointment requested', 'sureforms' ),
			'text'    => __( 'We have noted {form:preferred-date} ({form:preferred-time}). This is not confirmed yet: we will check availability and confirm by email or phone.', 'sureforms' ),
		],
		'email_subject' => __( 'Appointment request from {form:name}: {form:preferred-date}, {form:preferred-time} ({form:meeting-type})', 'sureforms' ),
		'email_intro'   => __( '{form:name} would like an appointment ({form:service}, {form:meeting-type}) on {form:preferred-date}, {form:preferred-time}. Reply to this email to confirm, or call them on {form:phone}.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We received your appointment request', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for your request. We have noted {form:service} ({form:meeting-type}) on {form:preferred-date} ({form:preferred-time}).', 'sureforms' ),
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
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
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
			Catalog::width( Catalog::field( 'input', 'organization', __( 'Organization', 'sureforms' ) ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'questions', __( 'Anything else we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'Dietary or accessibility needs, or questions for the organizers', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
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
			'text'    => __( 'Thanks for registering. We will send the event details to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New registration: {form:name} ({form:attendees} attending)', 'sureforms' ),
		'email_intro'   => __( '{form:name} has registered for your event. Places requested: {form:attendees}. Reply to this email to reach them directly.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'You are registered', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
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
			Catalog::attrs( Catalog::field( 'multi-choice', 'attending', __( 'Will you attend?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Yes, I will be there', 'sureforms' ), __( 'Sorry, I can\'t make it', 'sureforms' ) ], true ) ), [ 'choiceWidth' => 50 ] ),
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
			'text'    => __( 'Your RSVP is saved. Thank you for letting us know.', 'sureforms' ),
		],
		// With SureForms Pro, guest questions only show for guests who are coming.
		'conditions'    => [
			'guests'  => [ 'logic' => [ [ [ 'attending', '==', __( 'Yes, I will be there', 'sureforms' ) ] ] ] ],
			'dietary' => [ 'logic' => [ [ [ 'attending', '==', __( 'Yes, I will be there', 'sureforms' ) ] ] ] ],
		],
		'email_subject' => __( 'RSVP: {form:first-name} {form:last-name} – {form:attending}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} replied to your invitation: {form:attending}. Reply to this email to get in touch with them.', 'sureforms' ),
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
			Catalog::field( 'multi-choice', 'experience', __( 'Experience level', 'sureforms' ), true, Catalog::choice_options( [ __( 'Beginner', 'sureforms' ), __( 'Mid-level', 'sureforms' ), __( 'Advanced', 'sureforms' ) ], true ) ),
			Catalog::field( 'multi-choice', 'schedule', __( 'Times That Suit You', 'sureforms' ), false, array_merge( Catalog::choice_options( [ __( 'Mornings', 'sureforms' ), __( 'Evenings', 'sureforms' ), __( 'Weekends', 'sureforms' ) ], false ), [ 'helpText' => __( 'Mornings and evenings are on weekdays. Select all that apply.', 'sureforms' ) ] ) ),
			Catalog::attrs( Catalog::field( 'textarea', 'goals', __( 'Goals or Anything We Should Know', 'sureforms' ), false, [ 'placeholder' => __( 'What you hope to learn, access needs, questions…', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have received your enrollment for {form:course}. We will be in touch at {form:email} with your place and the next steps.', 'sureforms' ),
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
