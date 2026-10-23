<?php
/**
 * Form templates: Sign-ups & applications.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
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

return [
	[
		'id'            => 'newsletter-signup',
		'category'      => 'signup',
		'theme'         => 'sunset',
		'icon'          => 'newspaper',
		'title'         => __( 'Newsletter Signup', 'sureforms' ),
		'description'   => __( 'Grow your mailing list with a one-line signup form.', 'sureforms' ),
		'submit_text'   => __( 'Subscribe', 'sureforms' ),
		'fields'        => [
			// The notice sits above the box, so it is read before the button. On a
			// signup-only form, entering an email and pressing Subscribe is the consent.
			Catalog::width(
				array_merge(
					$c['email'],
					[
						'label'    => __( 'Email address', 'sureforms' ),
						'helpText' => __( 'News and updates only. Unsubscribe at any time.', 'sureforms' ),
					]
				),
				75
			),
			Catalog::attrs(
				Catalog::field( 'inline-button', 'subscribe', __( 'Subscribe', 'sureforms' ) ),
				[
					'fieldWidth' => 25,
					'buttonText' => __( 'Subscribe', 'sureforms' ),
				]
			),
		],
		'confirmation'  => [
			'heading' => __( 'You are on the list', 'sureforms' ),
			'text'    => __( 'Thanks for subscribing. We have sent a welcome email to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New subscriber: {form:email}', 'sureforms' ),
		'email_intro'   => __( '{form:email} signed up for your newsletter. Add them to your email list, or connect an email marketing integration to do this automatically.', 'sureforms' ),
		'autoresponder' => [
			'greeting' => __( 'Hi there,', 'sureforms' ),
			'subject'  => __( 'Welcome to {site_title}', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for subscribing. We will send our latest news and updates to this address.', 'sureforms' ),
				__( 'If you did not sign up, you can ignore this email or reply to let us know.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'volunteer-signup',
		'category'      => 'signup',
		'theme'         => 'mint',
		'icon'          => 'hand-heart',
		'title'         => __( 'Volunteer Signup', 'sureforms' ),
		'description'   => __( 'Recruit volunteers and see when they can help.', 'sureforms' ),
		'submit_text'   => __( 'Sign me up', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			// Many organisations need a guardian's consent for younger volunteers.
			Catalog::attrs( Catalog::field( 'multi-choice', 'over-18', __( 'Are you 18 or over?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Yes', 'sureforms' ), __( 'No', 'sureforms' ) ], true ) ), [ 'choiceWidth' => 50 ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'availability',
					__( 'When are you available?', 'sureforms' ),
					true,
					array_merge(
						Catalog::choice_options( [ __( 'Daytime', 'sureforms' ), __( 'Evenings', 'sureforms' ), __( 'Weekends', 'sureforms' ), __( 'Flexible', 'sureforms' ) ], false ),
						[ 'helpText' => __( 'Daytime and evenings are on weekdays. Select all that apply.', 'sureforms' ) ]
					)
				),
				[ 'choiceWidth' => 25 ]
			),
			Catalog::field(
				'multi-choice',
				'interests',
				__( 'How would you like to help?', 'sureforms' ),
				false,
				array_merge(
					Catalog::choice_options( [ __( 'Events', 'sureforms' ), __( 'Fundraising', 'sureforms' ), __( 'Outreach', 'sureforms' ), __( 'Admin and office', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Wherever needed', 'sureforms' ) ], false ),
					[ 'helpText' => __( 'Select all that apply.', 'sureforms' ) ]
				)
			),
			Catalog::field( 'textarea', 'skills', __( 'Relevant Skills or Experience', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. first aid, driving licence, languages, event planning', 'sureforms' ) ] ),
			// With SureForms Pro (Pro plan), a signed volunteer waiver.
			Catalog::field(
				'checkbox',
				'waiver-agreement',
				__( 'I will follow the volunteer guidelines and safety instructions I am given, and I understand that I take part at my own risk.', 'sureforms' ),
				true,
				[ 'pro_tier' => 'pro' ]
			),
			Catalog::field( 'signature', 'waiver-signature', __( 'Your Signature', 'sureforms' ), true, [ 'pro_tier' => 'pro' ] ),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my details so it can contact me about volunteering opportunities.', 'sureforms' ) ] ),
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for volunteering, {form:name}!', 'sureforms' ),
			'text'    => __( 'We will be in touch at {form:email} about opportunities that suit your availability.', 'sureforms' ),
		],
		'email_subject' => __( 'New volunteer: {form:name}', 'sureforms' ),
		'email_intro'   => __( '{form:name} signed up to volunteer. Available: {form:availability}.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thanks for volunteering with {site_title}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for offering your time. We have noted when you are available and what you would like to help with.', 'sureforms' ),
				__( 'We will contact you about upcoming opportunities that match.', 'sureforms' ),
			],
		],
	],
	[
		'id'             => 'job-application',
		'category'       => 'signup',
		'theme'          => 'lavender',
		'icon'           => 'briefcase',
		'title'          => __( 'Job Application', 'sureforms' ),
		'description'    => __( 'Accept applications and CVs.', 'sureforms' ),
		'submit_text'    => __( 'Submit application', 'sureforms' ),
		'steps'          => [ __( 'About you', 'sureforms' ), __( 'Your experience', 'sureforms' ) ],
		'fields'         => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'position', __( 'Position applied for', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Marketing Manager', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'experience-step', __( 'Your experience', 'sureforms' ) ),
			Catalog::field(
				'url',
				'cv',
				__( 'Link to your CV', 'sureforms' ),
				true,
				[
					'placeholder' => 'https://',
					'helpText'    => __( 'A Google Drive, Dropbox or OneDrive link that anyone with the link can view.', 'sureforms' ),
					'pro'         => [
						'fieldType'    => 'upload',
						'label'        => __( 'Your CV', 'sureforms' ),
						'placeholder'  => '',
						'helpText'     => __( 'PDF or Word document, up to 5 MB.', 'sureforms' ),
						'allowedTypes' => 'pdf,doc,docx',
						'uploadSize'   => 5,
					],
				]
			),
			Catalog::width( Catalog::field( 'url', 'linkedin', __( 'LinkedIn profile', 'sureforms' ), false, [ 'placeholder' => 'https://linkedin.com/in/' ] ), 50 ),
			Catalog::width( Catalog::field( 'url', 'portfolio', __( 'Portfolio or website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::number_field( 'years-experience', __( 'Years of relevant experience', 'sureforms' ), false, 0, 50, [ 'placeholder' => __( 'e.g. 3', 'sureforms' ) ] ), [ 'suffix' => __( 'years', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'start-date', __( 'When could you start?', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'Immediately', 'sureforms' ), __( 'Within a month', 'sureforms' ), __( 'In 1–3 months', 'sureforms' ), __( 'Later than that', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'cover-letter', __( 'Why are you a good fit for this role?', 'sureforms' ), true, [ 'helpText' => __( 'A few sentences about your relevant experience is plenty.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
			// Tells the employer which channels bring applicants.
			Catalog::attrs( Catalog::field( 'dropdown', 'how-heard', __( 'How did you hear about this role?', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'Job board', 'sureforms' ), __( 'LinkedIn', 'sureforms' ), __( 'Our website', 'sureforms' ), __( 'Referral', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] ) ), $no_search ),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my application details so they can be used to assess my application.', 'sureforms' ) ] ),
		],
		'confirmation'   => [
			'heading' => __( 'Your application for {form:position} is in', 'sureforms' ),
			'text'    => __( 'Thanks, {form:name}. We will review it and contact you at {form:email} if your experience is a good match.', 'sureforms' ),
		],
		// With SureForms Pro, the CV arrives with the notification, and (Pro plan)
		// applicants can save the form and finish it later.
		'attach_uploads' => [ 'cv' ],
		'save_resume'    => true,
		'email_subject'  => __( 'Application for {form:position} from {form:name}', 'sureforms' ),
		'email_intro'    => __( '{form:name} applied for {form:position}.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'We received your application for {form:position}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for applying for the {form:position} role at {site_title}.', 'sureforms' ),
				__( 'Our team reviews every application. If your experience is a good match, we will contact you about next steps.', 'sureforms' ),
			],
		],
	],
];
