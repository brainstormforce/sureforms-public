<?php
/**
 * Form templates: Accounts & Submissions.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * Accounts and posts are created by SureForms Business: a registration form
 * makes a subscriber with the password the visitor chooses, and submission
 * forms turn each entry into a post waiting for review, never a published one.
 * The long applications and the bulk order form built with the same features
 * live here too, each under the library category it belongs to.
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

/*
 * An image upload, such as a featured image or a logo.
 */
$image_upload = static function ( $slug, $label, $help ) {
	return Catalog::field(
		'upload',
		$slug,
		$label,
		false,
		[
			'helpText'     => $help,
			'allowedTypes' => 'jpg,jpeg,png,webp',
			'uploadSize'   => 5,
		]
	);
};

/*
 * A document upload for an application: PDF or a photo of the document.
 */
$document_upload = static function ( $slug, $label, $required, $help, $files = 1 ) {
	return Catalog::field(
		'upload',
		$slug,
		$label,
		$required,
		array_merge(
			[
				'helpText'     => $help,
				'allowedTypes' => 'pdf,jpg,jpeg,png',
				'uploadSize'   => 5,
			],
			$files > 1 ? [
				'multiUpload'      => true,
				'multiFilesNumber' => $files,
			] : []
		)
	);
};

/*
 * A signature at the end of an application (SureForms Pro).
 */
$signature = static fn( $label ) => Catalog::field( 'signature', 'signature', $label, true, [ 'pro_tier' => 'pro' ] );

return [
	[
		'id'            => 'member-registration',
		'preview_image' => 'member-registration.webp',
		'category'      => 'accounts',
		'theme'         => 'ocean',
		'icon'          => 'user-plus',
		'requires'      => 'business',
		'title'         => __( 'Member Registration', 'sureforms' ),
		'description'   => __( 'Let visitors create their own account with a password.', 'sureforms' ),
		'submit_text'   => __( 'Create my account', 'sureforms' ),
		'fields'        => [
			// For people who already have an account; the link is this site's own login page.
			array_merge( $c['first_name'], [ 'label' => __( 'First name', 'sureforms' ) ] ),
			array_merge( $c['last_name'], [ 'label' => __( 'Last name', 'sureforms' ) ] ),
			Catalog::field(
				'input',
				'username',
				__( 'Username', 'sureforms' ),
				true,
				[
					'placeholder' => __( 'e.g. janesmith', 'sureforms' ),
					'helpText'    => __( 'You can sign in with your username or your email.', 'sureforms' ),
				]
			),
			$c['email'],
			Catalog::field(
				'password',
				'password',
				__( 'Password', 'sureforms' ),
				true,
				[
					// One box: the eye lets people check what they typed, so no confirm box.
					'isConfirmPassword'        => false,
					'checkPasswordStrength'    => true,
					'togglePasswordVisibility' => true,
				]
			),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my details to create and manage my account.', 'sureforms' ) ] ),
		],
		// Every account is a subscriber; the welcome email comes from the registration itself.
		'registration'  => [
			'email'           => 'email',
			'username'        => 'username',
			'password'        => 'password',
			'first_name'      => 'first-name',
			'last_name'       => 'last-name',
			'auto_login'      => true,
			'send_user_email' => true,
		],
		'confirmation'  => [
			'heading' => __( 'Welcome, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'Your account is ready and you are now signed in. We have sent a welcome email to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'New member: {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} created an account with the username {form:username}. They can sign in now; you can manage the account under Users.', 'sureforms' ),
	],
	[
		'id'            => 'guest-post-submission',
		'preview_image' => 'guest-post-submission.webp',
		'category'      => 'accounts',
		'theme'         => 'lavender',
		'icon'          => 'pen-line',
		'requires'      => 'business',
		'title'         => __( 'Guest Post Submission', 'sureforms' ),
		'description'   => __( 'Accept guest post submissions for your review.', 'sureforms' ),
		'submit_text'   => __( 'Submit my post', 'sureforms' ),
		// About the writer first, then the post on a screen of its own.
		'steps'         => [ __( 'About you', 'sureforms' ), __( 'Your post', 'sureforms' ) ],
		'fields'        => [
			// What you accept, before anyone writes a whole article. Edit it to match your site.
			Catalog::field(
				'html',
				'guidelines',
				'',
				false,
				[ 'htmlContent' => '<div style="padding: 12px 16px;border-radius: 8px;background: #f5f3ff"><p style="margin: 0 0 4px;font-size: 15px"><strong>' . esc_html__( 'Before you submit', 'sureforms' ) . '</strong></p><p style="margin: 0;font-size: 15px;line-height: 1.6">' . esc_html__( 'Original work only, around 800 to 2,000 words, with at most two links. We may edit for length and style.', 'sureforms' ) . '</p></div>' ]
			),
			Catalog::width( array_merge( $c['name'], [ 'label' => __( 'Your name', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'url', 'website', __( 'Your website or a link to your work (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'https://', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'author-bio', __( 'About you (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Jane writes about saving energy at home and runs the blog Small Green Steps.', 'sureforms' ) ] ),
				[ 'rows' => 3 ]
			),
			Catalog::step( 'post-step', __( 'Your post', 'sureforms' ) ),
			Catalog::field( 'input', 'post-title', __( 'Post title', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 5 Simple Ways to Cut Your Energy Bill', 'sureforms' ) ] ),
			// Filled with the site's own categories when the form loads (dynamic options). It tells
			// the editor where the post belongs; the post itself is filed when it is published.
			Catalog::attrs(
				Catalog::field( 'dropdown', 'category', __( 'Category', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'General', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a category', 'sureforms' ) ] ) ),
				[
					'searchable'        => false,
					'optionsSource'     => 'dynamic',
					'dynamicSourceType' => 'taxonomy',
					'dynamicSource'     => 'category',
				]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'post-content', __( 'Your post', 'sureforms' ), true, [ 'helpText' => __( 'Write or paste your article. You can add headings, links and lists.', 'sureforms' ) ] ),
				[
					'isRichText' => true,
					'rows'       => 12,
				]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'excerpt', __( 'Short summary (optional)', 'sureforms' ), false, [ 'helpText' => __( 'One or two sentences shown in post listings.', 'sureforms' ) ] ),
				[ 'rows' => 2 ]
			),
			$image_upload( 'featured-image', __( 'Featured image (optional)', 'sureforms' ), __( 'A landscape JPG, PNG or WebP image, up to 5 MB.', 'sureforms' ) ),
			Catalog::field( 'checkbox', 'original-work', __( 'This is my original work and has not been published elsewhere.', 'sureforms' ), true ),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my details so it can review my post and contact me about it.', 'sureforms' ) ] ),
		],
		// The post waits in Posts as Pending until it is reviewed and published.
		'cpt'           => [
			'name'           => __( 'Guest post', 'sureforms' ),
			'title'          => 'post-title',
			'content'        => 'post-content',
			'excerpt'        => 'excerpt',
			'featured_image' => 'featured-image',
			'post_type'      => 'post',
			'status'         => 'pending',
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:full-name}!', 'sureforms' ),
			'text'    => __( 'Your post “{form:post-title}” is waiting for review. We will email you at {form:email} once we have read it.', 'sureforms' ),
		],
		'email_subject' => __( 'New guest post for review: {form:post-title}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} submitted “{form:post-title}” for the category {form:category}. It is waiting for review under Posts, marked Pending.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'We received your post “{form:post-title}”', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for writing for {site_title}. Your post is now waiting for review.', 'sureforms' ),
				__( 'We read every submission and will let you know whether it is accepted or needs changes.', 'sureforms' ),
			],
		],
	],
	// Library category: Contact & Leads, with the other order and quote requests.
	[
		'id'            => 'bulk-order-form',
		'preview_image' => 'bulk-order-form.webp',
		'category'      => 'contact',
		'theme'         => 'sand',
		'icon'          => 'package',
		'requires'      => 'business',
		'title'         => __( 'Catering Orders', 'sureforms' ),
		'description'   => __( 'Capture catering orders of any size, available for delivery or pickup.', 'sureforms' ),
		'submit_text'   => __( 'Send my order', 'sureforms' ),
		'fields'        => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company or organization (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			// The event first: a caterer checks the date and size before the menu.
			Catalog::width( Catalog::date_field( 'delivery-date', __( 'Date needed', 'sureforms' ), true, true ), 50 ),
			Catalog::width(
				Catalog::field(
					'time-picker',
					'delivery-time',
					__( 'Time needed', 'sureforms' ),
					true,
					[
						'increment' => 30,
						'minTime'   => '07:00',
						'maxTime'   => '20:00',
					]
				),
				50
			),
			Catalog::number_field( 'guests', __( 'Number of guests', 'sureforms' ), true, 1, 5000, [ 'placeholder' => __( 'e.g. 40', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'fulfilment',
					__( 'Delivery or pickup?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Delivery', 'sureforms' ), __( 'Pickup', 'sureforms' ) ], true )
				),
				[ 'choiceWidth' => 50 ]
			),
			// Local delivery: no country list, and the region is optional.
			Catalog::field(
				'address',
				'delivery-address',
				__( 'Delivery address', 'sureforms' ),
				true,
				[
					'innerFields' => [
						Catalog::attrs( Catalog::field( 'input', 'street-address', __( 'Street address', 'sureforms' ), true ), [ 'addressComponent' => 'line1' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'city', __( 'City', 'sureforms' ), true ), 50 ), [ 'addressComponent' => 'city' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'state', __( 'State or region', 'sureforms' ), false ), 25 ), [ 'addressComponent' => 'state' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'postal-code', __( 'Postal code', 'sureforms' ), true ), 25 ), [ 'addressComponent' => 'postal_code' ] ),
					],
				]
			),
			Catalog::field(
				'repeater',
				'items',
				__( 'Your order', 'sureforms' ),
				false,
				[
					'minItems'    => 1,
					'maxItems'    => 30,
					'innerFields' => [
						Catalog::width( Catalog::field( 'input', 'item', __( 'Item', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Sandwich platter for 10', 'sureforms' ) ] ), 75 ),
						Catalog::width( Catalog::number_field( 'quantity', __( 'Quantity', 'sureforms' ), true, 1, 1000, [ 'placeholder' => __( 'e.g. 4', 'sureforms' ) ] ), 25 ),
					],
				]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'notes', __( 'Order notes (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'Allergies, dietary needs, packaging or delivery instructions', 'sureforms' ) ] ),
				[ 'rows' => 3 ]
			),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my details so it can confirm and deliver my order.', 'sureforms' ) ] ),
		],
		// The delivery address is only asked for deliveries.
		'conditions'    => [
			'delivery-address' => [
				'action' => 'show',
				'logic'  => [ [ [ 'fulfilment', '==', __( 'Delivery', 'sureforms' ) ] ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for your order, {form:name}!', 'sureforms' ),
			'text'    => __( 'We will check availability for {form:delivery-date} and email you at {form:email} to confirm your order and the total.', 'sureforms' ),
		],
		'email_subject' => __( 'Catering order for {form:delivery-date} from {form:name} ({form:guests} guests)', 'sureforms' ),
		'email_intro'   => __( '{form:name} placed a catering order for {form:guests} guests, for {form:fulfilment} on {form:delivery-date} at {form:delivery-time}. Reply to confirm the order and its total.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We received your order for {form:delivery-date}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for your catering order for {form:guests} guests, for {form:fulfilment} on {form:delivery-date} at {form:delivery-time}.', 'sureforms' ),
				__( 'This is not a confirmation yet: we will check availability and reply with the total before we confirm.', 'sureforms' ),
			],
		],
	],
	// Library category: Sign-ups & Applications, with the job application.
	[
		'id'              => 'rental-application',
		'preview_image'   => 'rental-application.webp',
		'category'        => 'signup',
		'theme'           => 'ocean',
		'icon'            => 'house',
		'requires'        => 'pro',
		'title'           => __( 'Rental Application', 'sureforms' ),
		'description'     => __( 'A step-by-step rental application with references, ID, and signature.', 'sureforms' ),
		'submit_text'     => __( 'Submit application', 'sureforms' ),
		'steps'           => [ __( 'About you', 'sureforms' ), __( 'Work and income', 'sureforms' ), __( 'References', 'sureforms' ), __( 'Documents and signature', 'sureforms' ) ],
		'fields'          => [
			Catalog::field( 'input', 'property', __( 'Property you are applying for', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 12 Oak Street, Apartment 4', 'sureforms' ) ] ),
			$c['first_name'],
			$c['last_name'],
			// Phone before email, so the save-for-later checkbox that follows the email sits under a full row.
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'address', 'current-address', __( 'Current address', 'sureforms' ), true ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'time-at-address',
					__( 'How long have you lived at your current address?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Less than 6 months', 'sureforms' ), __( '6–12 months', 'sureforms' ), __( '1–3 years', 'sureforms' ), __( 'More than 3 years', 'sureforms' ) ], true )
				),
				[ 'choiceWidth' => 50 ]
			),
			Catalog::width( Catalog::date_field( 'move-in-date', __( 'Preferred move-in date', 'sureforms' ), true, true ), 50 ),
			Catalog::width( Catalog::number_field( 'occupants', __( 'Number of occupants', 'sureforms' ), true, 1, 20, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ), 50 ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'pets',
					__( 'Pets', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'No pets', 'sureforms' ), __( 'Cat', 'sureforms' ), __( 'Dog', 'sureforms' ), __( 'Other pets', 'sureforms' ) ], true )
				),
				[ 'choiceWidth' => 25 ]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'pet-details', __( 'Tell us about your pets', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. one small dog, house-trained and vaccinated', 'sureforms' ) ] ),
				[ 'rows' => 2 ]
			),
			Catalog::step( 'work-step', __( 'Work and income', 'sureforms' ) ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'employment-status',
						__( 'Employment status', 'sureforms' ),
						true,
						array_merge( Catalog::dropdown_options( [ __( 'Employed full-time', 'sureforms' ), __( 'Employed part-time', 'sureforms' ), __( 'Self-employed', 'sureforms' ), __( 'Student', 'sureforms' ), __( 'Retired', 'sureforms' ), __( 'Not working', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] )
					),
					$no_search
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'employer', __( 'Employer', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'job-title', __( 'Job title', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Store Manager', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::number_field( 'monthly-income', __( 'Monthly income before tax', 'sureforms' ), true, 0, 1000000, [ 'placeholder' => __( 'e.g. 3,200', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'references-step', __( 'References', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'landlord-name', __( 'Current landlord or reference', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Sam Patel', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'     => 'landlord-phone',
						'label'    => __( 'Their phone', 'sureforms' ),
						'required' => true,
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'reference-name', __( 'Second reference', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. your employer or a previous landlord', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'  => 'reference-phone',
						'label' => __( 'Their phone', 'sureforms' ),
					]
				),
				50
			),
			Catalog::field( 'textarea', 'anything-else', __( 'Anything else we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. a guarantor, a parking space you need, or why you are moving', 'sureforms' ) ] ),
			Catalog::step( 'documents-step', __( 'Documents and signature', 'sureforms' ) ),
			$document_upload( 'id-proof', __( 'Photo ID', 'sureforms' ), true, __( 'Passport, driving licence or national ID card. PDF, JPG or PNG, up to 5 MB.', 'sureforms' ) ),
			$document_upload( 'proof-of-income', __( 'Proof of income', 'sureforms' ), false, __( 'Your last three payslips or a recent bank statement. Up to 3 files.', 'sureforms' ), 3 ),
			array_merge( $c['consent'], [ 'label' => __( 'I confirm these details are true, and agree to have this website store them and contact my references to assess my application.', 'sureforms' ) ] ),
			$signature( __( 'Signature', 'sureforms' ) ),
		],
		// Pet details are only asked once the applicant says they have a pet.
		'conditions'      => [
			'pet-details' => [
				'action' => 'show',
				'logic'  => [
					[ [ 'pets', '==', __( 'Cat', 'sureforms' ) ] ],
					[ [ 'pets', '==', __( 'Dog', 'sureforms' ) ] ],
					[ [ 'pets', '==', __( 'Other pets', 'sureforms' ) ] ],
				],
			],
		],
		'save_resume'     => true,
		// SureForms Business also keeps unfinished applications and reminds the applicant to finish.
		'partial_entries' => [
			'resume'   => true,
			'reminder' => true,
			'email'    => 'email',
		],
		'attach_uploads'  => [ 'id-proof', 'proof-of-income' ],
		'confirmation'    => [
			'heading' => __( 'Application received, {form:first-name}', 'sureforms' ),
			'text'    => __( 'Thanks for applying for {form:property}. We will check your details and references and contact you at {form:email}.', 'sureforms' ),
		],
		'email_subject'   => __( 'Rental application for {form:property} from {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'     => __( '{form:first-name} {form:last-name} applied for {form:property}, moving in from {form:move-in-date}. Their ID and proof of income are attached.', 'sureforms' ),
		'autoresponder'   => [
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'subject'  => __( 'We received your application for {form:property}', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for applying. We will review your application and contact your references.', 'sureforms' ),
				__( 'We aim to reply within a few working days. If anything is missing, we will ask you for it by email.', 'sureforms' ),
			],
			'summary'  => false,
		],
	],
];
