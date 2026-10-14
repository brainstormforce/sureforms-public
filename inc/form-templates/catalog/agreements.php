<?php
/**
 * Form templates: Agreements.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * The terms are starting-point wording for the site owner to replace with
 * their own, not legal advice. The rental and scholarship applications at the
 * end belong to Sign-ups & Applications: a template's category is its
 * `category` key, not the file it lives in, and they sit here because, like
 * the agreements, they end with a signed declaration.
 *
 * @package sureforms
 * @since x.x.x
 */

use SRFM\Inc\Form_Templates\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];

/**
 * A block of terms text shown above the signature (SureForms Pro HTML block).
 * The first paragraph is a highlighted note to the site owner to replace the
 * starter wording; each other paragraph is `Title: text`.
 *
 * @param string             $slug     Field slug.
 * @param string             $label    Block name in the editor.
 * @param string             $heading  Heading.
 * @param array<int, string> $sections Owner note, then the paragraphs.
 * @return array<string, mixed>
 */
$terms = static function ( $slug, $label, $heading, $sections ) {
	// Styles as wp_kses_post() writes them. No max-height: kses drops `overflow`, so a fixed height would cut the text off.
	$html  = '<div style="padding: 16px 20px;border: 1px solid #e2e8f0;border-radius: 8px;background: #ffffff;font-size: 14px;line-height: 1.6">';
	$html .= '<h3 style="margin-top: 0">' . esc_html( $heading ) . '</h3>';
	$html .= '<p style="padding: 8px 12px;background: #fef3c7;border-radius: 6px"><em>' . esc_html( (string) array_shift( $sections ) ) . '</em></p>';
	foreach ( $sections as $section ) {
		$parts = explode( ':', $section, 2 );
		$html .= isset( $parts[1] ) ? '<p><strong>' . esc_html( $parts[0] ) . ':</strong>' . esc_html( $parts[1] ) . '</p>' : '<p>' . esc_html( $section ) . '</p>';
	}
	$html .= '</div>';

	return Catalog::field(
		'html',
		$slug,
		$label,
		false,
		[
			'pro_only'    => true,
			'htmlContent' => $html,
		]
	);
};

/**
 * A required signature: SureForms Pro and Business, not Starter.
 *
 * @param string $slug     Field slug.
 * @param string $label    Label.
 * @param bool   $required Required.
 * @return array<string, mixed>
 */
$signature = static function ( $slug, $label, $required = true ) {
	return Catalog::field( 'signature', $slug, $label, $required, [ 'pro_tier' => 'pro' ] );
};

return [
	[
		'id'            => 'service-agreement',
		'preview_image' => 'service-agreement.webp',
		'category'      => 'agreements',
		'requires'      => 'pro',
		'theme'         => 'ocean',
		'icon'          => 'file-signature',
		'title'         => __( 'Service Agreement', 'sureforms' ),
		'description'   => __( 'Show your terms, then collect the client’s details and signature. Starter wording to replace with your own.', 'sureforms' ),
		'submit_text'   => __( 'Sign agreement', 'sureforms' ),
		'fields'        => [
			$terms(
				'terms',
				__( 'Agreement terms', 'sureforms' ),
				__( 'Service agreement', 'sureforms' ),
				[
					__( 'Starter wording, not legal advice: replace it with your own terms before you publish this form.', 'sureforms' ),
					__( 'This agreement is between you (the client) and us (the provider).', 'sureforms' ),
					__( 'Services: We will provide the services described in our proposal or quote, which forms part of this agreement.', 'sureforms' ),
					__( 'Fees and payment: You agree to pay the fees in our proposal. Invoices are due within 14 days unless agreed otherwise in writing.', 'sureforms' ),
					__( 'Changes: Work outside the agreed scope will be quoted separately and only starts once you approve it.', 'sureforms' ),
					__( 'Cancellation: Either party may end this agreement with 14 days’ written notice. Work completed up to that date will be invoiced.', 'sureforms' ),
					__( 'Confidentiality: Both parties will keep each other’s confidential information private.', 'sureforms' ),
				]
			),
			Catalog::width( $c['name'], 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company (if any)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( Catalog::field( 'input', 'reference', __( 'Proposal or Quote Number', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. #10234', 'sureforms' ) ] ), 50 ),
			Catalog::field( 'gdpr', 'agree-terms', __( 'I have read and agree to the terms above, and I am authorised to sign on behalf of the client.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'confirmation'  => [
			'heading' => __( 'Signed – thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'A copy of the agreement has been sent to {form:email}. We will be in touch about next steps.', 'sureforms' ),
		],
		'email_subject' => __( 'Service agreement signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the service agreement on {date_mdy}. Their details and signature are below. Reply to this email to contact them.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your signed service agreement', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our service agreement on {date_mdy}. A copy of what you submitted is below for your records.', 'sureforms' ),
				__( 'If anything looks wrong, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'liability-waiver',
		'preview_image' => 'liability-waiver.webp',
		'category'      => 'agreements',
		'requires'      => 'pro',
		'theme'         => 'sand',
		'icon'          => 'shield-check',
		'title'         => __( 'Activity Liability Waiver', 'sureforms' ),
		'description'   => __( 'A signed waiver with emergency contacts. A parent or guardian signs too when the participant is under 18.', 'sureforms' ),
		'submit_text'   => __( 'Sign waiver', 'sureforms' ),
		'fields'        => [
			$terms(
				'waiver-terms',
				__( 'Waiver terms', 'sureforms' ),
				__( 'Assumption of risk and release', 'sureforms' ),
				[
					__( 'Starter wording, not legal advice: replace it with your own waiver, checked for where you operate, before you publish this form.', 'sureforms' ),
					__( 'Risks: I understand that taking part in this activity involves risks, including injury, and I choose to take part voluntarily.', 'sureforms' ),
					__( 'Health: I confirm the participant is fit to take part and I have told the organiser about any medical condition that matters.', 'sureforms' ),
					__( 'Rules: I agree that the participant will follow the instructions of the staff and the safety rules.', 'sureforms' ),
					__( 'Release: To the extent the law allows, I release the organiser and its staff from claims arising from taking part, except where caused by their negligence.', 'sureforms' ),
					__( 'Medical care: In an emergency, I allow the organiser to arrange medical care for the participant.', 'sureforms' ),
				]
			),
			Catalog::width( array_merge( $c['name'], [ 'label' => __( 'Participant’s Full Name', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( Catalog::date_field( 'activity-date', __( 'Activity Date', 'sureforms' ), true, true ), 50 ),
			Catalog::width( Catalog::field( 'input', 'emergency-contact', __( 'Emergency Contact Name', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. John Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'     => 'emergency-phone',
						'label'    => __( 'Emergency Contact Phone', 'sureforms' ),
						'required' => true,
					]
				),
				50
			),
			Catalog::attrs( Catalog::field( 'textarea', 'medical-notes', __( 'Medical conditions or allergies we should know about', 'sureforms' ), false, [ 'placeholder' => __( 'Leave blank if none', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::field( 'checkbox', 'under-18', __( 'The participant is under 18', 'sureforms' ) ),
			// Shown only when the participant is under 18.
			Catalog::field( 'input', 'guardian-name', __( 'Parent or Guardian’s Full Name', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Alex Smith', 'sureforms' ) ] ),
			$signature( 'guardian-signature', __( 'Parent or Guardian’s Signature', 'sureforms' ) ),
			Catalog::field( 'gdpr', 'risk-acknowledgement', __( 'I have read and understood this waiver, and I agree to it.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Participant’s Signature', 'sureforms' ) ),
		],
		'conditions'    => [
			'guardian-name'      => [ 'logic' => [ [ [ 'under-18', 'isChecked' ] ] ] ],
			'guardian-signature' => [ 'logic' => [ [ [ 'under-18', 'isChecked' ] ] ] ],
		],
		'confirmation'  => [
			'heading' => __( 'Waiver signed', 'sureforms' ),
			'text'    => __( 'Thank you, {form:full-name}. A copy has been sent to {form:email}. See you on the day!', 'sureforms' ),
		],
		'email_subject' => __( 'Waiver signed: {form:full-name} ({form:activity-date})', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the liability waiver for {form:activity-date}. Emergency contact: {form:emergency-contact}, {form:emergency-phone}. The full waiver details are below.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your signed waiver', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing the waiver for {form:activity-date}. A copy of what you submitted is below for your records.', 'sureforms' ),
				__( 'If anything changes, such as your emergency contact or a medical condition, just reply to this email before the day.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'photo-release-consent',
		'preview_image' => 'photo-release-consent.webp',
		'category'      => 'agreements',
		'requires'      => 'pro',
		'theme'         => 'lavender',
		'icon'          => 'camera',
		'title'         => __( 'Photo & Media Release', 'sureforms' ),
		'description'   => __( 'Get signed permission to use someone’s photos and video, and where you may use them.', 'sureforms' ),
		'submit_text'   => __( 'Sign release', 'sureforms' ),
		'fields'        => [
			$terms(
				'release-terms',
				__( 'Release terms', 'sureforms' ),
				__( 'Photo and media release', 'sureforms' ),
				[
					__( 'Starter wording, not legal advice: replace it with your own release before you publish this form.', 'sureforms' ),
					__( 'Permission: I allow the organiser to take and use photos, video and recordings of me in the places I choose below.', 'sureforms' ),
					__( 'Use: The media may be edited, cropped or captioned. It will not be sold to third parties or used in a way that misrepresents me.', 'sureforms' ),
					__( 'Payment: I will not be paid for this use.', 'sureforms' ),
					__( 'Withdrawing: I can withdraw my permission at any time by contacting the organiser. Media already printed or published cannot always be recalled.', 'sureforms' ),
				]
			),
			Catalog::width( $c['name'], 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'input', 'event', __( 'Event or Photo Session (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Summer open day 2026', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'usage',
					__( 'Where may we use your photos and video?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Our website', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Print and ads', 'sureforms' ) ], false, [ 'globe', 'share-nodes', 'newspaper' ] )
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::field( 'gdpr', 'release-consent', __( 'I have read the release above and give my permission for the uses I have chosen.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'Your release is signed. A copy has been sent to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'Media release signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the photo and media release. Allowed uses: {form:usage}. Their details and signature are below.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your signed media release', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our photo and media release. You allowed us to use your photos and video on: {form:usage}.', 'sureforms' ),
				__( 'You can change your mind at any time. Just reply to this email.', 'sureforms' ),
			],
		],
	],

	// Sign-ups & Applications.
	[
		'id'            => 'rental-application',
		'preview_image' => 'rental-application.webp',
		'category'      => 'signup',
		'requires'      => 'pro',
		'theme'         => 'mint',
		'icon'          => 'house',
		'title'         => __( 'Rental Application', 'sureforms' ),
		'description'   => __( 'A step-by-step tenant application with income, references, documents and a signature. Applicants can save and finish later.', 'sureforms' ),
		'submit_text'   => __( 'Submit application', 'sureforms' ),
		'steps'         => [ __( 'About you', 'sureforms' ), __( 'Employment', 'sureforms' ), __( 'References', 'sureforms' ), __( 'Documents and signature', 'sureforms' ) ],
		'save_resume'   => true,
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'current-address', __( 'Current Address', 'sureforms' ), true, [ 'placeholder' => __( 'Street, city and postcode', 'sureforms' ) ] ), [ 'rows' => 2 ] ),
			Catalog::width( Catalog::date_field( 'move-in', __( 'Preferred Move-in Date', 'sureforms' ), true, true ), 50 ),
			Catalog::width( Catalog::number_field( 'occupants', __( 'Number of Occupants', 'sureforms' ), true, 1, 20, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'employment-step', __( 'Employment', 'sureforms' ) ),
			Catalog::width(
				Catalog::field(
					'dropdown',
					'employment-status',
					__( 'Employment Status', 'sureforms' ),
					true,
					array_merge(
						Catalog::dropdown_options( [ __( 'Employed full-time', 'sureforms' ), __( 'Employed part-time', 'sureforms' ), __( 'Self-employed', 'sureforms' ), __( 'Student', 'sureforms' ), __( 'Retired', 'sureforms' ), __( 'Not working', 'sureforms' ) ] ),
						[
							'placeholder'     => __( 'Select one', 'sureforms' ),
							'blockAttributes' => $no_search,
						]
					)
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'employer', __( 'Employer', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::number_field( 'monthly-income', __( 'Monthly Income (before tax)', 'sureforms' ), true, 0, 1000000, [ 'placeholder' => __( 'e.g. 4,500', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'time-employed', __( 'Time with Current Employer', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. 2 years', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'references-step', __( 'References', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'landlord-name', __( 'Current Landlord or Agent', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Park Lane Lettings', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'  => 'landlord-phone',
						'label' => __(
							'Landlord’s Phone',
							'sureforms'
						),
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'reference-name', __( 'Personal Reference', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. John Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'     => 'reference-phone',
						'label'    => __( 'Reference’s Phone', 'sureforms' ),
						'required' => true,
					]
				),
				50
			),
			Catalog::step( 'documents-step', __( 'Documents and signature', 'sureforms' ) ),
			Catalog::field(
				'upload',
				'id-proof',
				__( 'Photo ID', 'sureforms' ),
				true,
				[
					'pro_only'     => true,
					'helpText'     => __( 'A passport or driving licence. JPG, PNG or PDF.', 'sureforms' ),
					'allowedTypes' => 'jpg,jpeg,png,pdf',
					'uploadSize'   => 10,
				]
			),
			Catalog::field(
				'upload',
				'proof-of-income',
				__( 'Proof of Income (optional)', 'sureforms' ),
				false,
				[
					'pro_only'         => true,
					'helpText'         => __( 'Up to 3 recent payslips or bank statements.', 'sureforms' ),
					'allowedTypes'     => 'jpg,jpeg,png,pdf',
					'uploadSize'       => 10,
					'multiUpload'      => true,
					'multiFilesNumber' => 3,
				]
			),
			Catalog::field( 'gdpr', 'declaration', __( 'The information I have given is true and complete. I agree that you may check it, including by contacting my references.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'confirmation'  => [
			'heading' => __( 'Application received', 'sureforms' ),
			'text'    => __( 'Thank you, {form:first-name}. We will review your application and contact you at {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'Rental application: {form:first-name} {form:last-name}, move-in {form:move-in}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} applied to rent, for {form:occupants} occupant(s) moving in on {form:move-in}. Their application, documents and signature are below.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We have received your rental application', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for applying. We will review your application, check your references and let you know the outcome.', 'sureforms' ),
				__( 'If you need to update anything in the meantime, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'scholarship-application',
		'preview_image' => 'scholarship-application.webp',
		'category'      => 'signup',
		'requires'      => 'pro',
		'theme'         => 'lavender',
		'icon'          => 'graduation-cap',
		'title'         => __( 'Scholarship Application', 'sureforms' ),
		'description'   => __( 'A step-by-step application with an essay, transcript upload and a signed declaration. Applicants can save and finish later.', 'sureforms' ),
		'submit_text'   => __( 'Submit application', 'sureforms' ),
		'steps'         => [ __( 'About you', 'sureforms' ), __( 'Education', 'sureforms' ), __( 'Your essay', 'sureforms' ), __( 'Documents and signature', 'sureforms' ) ],
		'save_resume'   => true,
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::date_field( 'date-of-birth', __( 'Date of Birth', 'sureforms' ), true ),
			Catalog::step( 'education-step', __( 'Education', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'school', __( 'School or University', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Riverside College', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'field-of-study', __( 'Field of Study', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Biology', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::field(
					'dropdown',
					'level',
					__( 'Current Level', 'sureforms' ),
					true,
					array_merge(
						Catalog::dropdown_options( [ __( 'High school', 'sureforms' ), __( 'Undergraduate', 'sureforms' ), __( 'Postgraduate', 'sureforms' ), __( 'Other', 'sureforms' ) ] ),
						[
							'placeholder'     => __( 'Select one', 'sureforms' ),
							'blockAttributes' => $no_search,
						]
					)
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'grades', __( 'Grade Average', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. 3.6 GPA', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'essay-step', __( 'Your essay', 'sureforms' ) ),
			Catalog::attrs( Catalog::field( 'textarea', 'essay', __( 'Why should you receive this scholarship?', 'sureforms' ), true, [ 'helpText' => __( 'Tell us about your goals and what this support would make possible. About 300–500 words.', 'sureforms' ) ] ), [ 'rows' => 10 ] ),
			Catalog::attrs( Catalog::field( 'textarea', 'achievements', __( 'Achievements and activities (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'Awards, volunteering, clubs, part-time work…', 'sureforms' ) ] ), [ 'rows' => 4 ] ),
			Catalog::step( 'documents-step', __( 'Documents and signature', 'sureforms' ) ),
			Catalog::field(
				'upload',
				'transcript',
				__( 'Academic Transcript', 'sureforms' ),
				true,
				[
					'pro_only'     => true,
					'helpText'     => __( 'Your most recent transcript or report card, as a PDF or photo.', 'sureforms' ),
					'allowedTypes' => 'pdf,jpg,jpeg,png',
					'uploadSize'   => 10,
				]
			),
			Catalog::field(
				'upload',
				'recommendation',
				__( 'Letter of Recommendation (optional)', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'allowedTypes' => 'pdf,doc,docx',
					'uploadSize'   => 10,
				]
			),
			Catalog::field( 'gdpr', 'declaration', __( 'The information in my application is true and my own work, and I agree that it may be checked.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'confirmation'  => [
			'heading' => __( 'Application submitted', 'sureforms' ),
			'text'    => __( 'Thank you, {form:first-name}. We have sent a confirmation to {form:email} and will contact you once the review is complete.', 'sureforms' ),
		],
		'email_subject' => __( 'Scholarship application: {form:first-name} {form:last-name} ({form:field-of-study})', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} ({form:level}, {form:field-of-study} at {form:school}) applied for the scholarship. Their essay, documents and signature are below.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We have received your scholarship application', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for applying. Your application is complete and will be reviewed with all the others once applications close.', 'sureforms' ),
				__( 'We will let you know the outcome by email. If you have a question in the meantime, just reply to this email.', 'sureforms' ),
			],
		],
	],
];
