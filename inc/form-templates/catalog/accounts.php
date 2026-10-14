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
 * @since x.x.x
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
		'description'   => __( 'Let visitors create their own account with a password and sign them in straight away.', 'sureforms' ),
		'submit_text'   => __( 'Create my account', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( Catalog::field( 'input', 'username', __( 'Username', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. janesmith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field(
				'password',
				'password',
				__( 'Password', 'sureforms' ),
				true,
				[
					'isConfirmPassword'        => true,
					'checkPasswordStrength'    => true,
					'togglePasswordVisibility' => true,
					'confirmLabel'             => __( 'Confirm Password', 'sureforms' ),
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
		'description'   => __( 'Accept guest articles that arrive as posts waiting for your review.', 'sureforms' ),
		'submit_text'   => __( 'Submit my post', 'sureforms' ),
		'fields'        => [
			Catalog::width( array_merge( $c['name'], [ 'label' => __( 'Your Name', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'input', 'post-title', __( 'Post Title', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 5 Simple Ways to Cut Your Energy Bill', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'post-content', __( 'Your Post', 'sureforms' ), true, [ 'helpText' => __( 'Write or paste your article. You can add headings, links and lists.', 'sureforms' ) ] ),
				[
					'isRichText' => true,
					'rows'       => 12,
				]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'excerpt', __( 'Short Summary', 'sureforms' ), false, [ 'helpText' => __( 'One or two sentences shown in post listings.', 'sureforms' ) ] ),
				[ 'rows' => 2 ]
			),
			$image_upload( 'featured-image', __( 'Featured Image', 'sureforms' ), __( 'A landscape JPG, PNG or WebP image, up to 5 MB.', 'sureforms' ) ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'author-bio', __( 'About You', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Jane writes about saving energy at home and runs the blog Small Green Steps.', 'sureforms' ) ] ),
				[ 'rows' => 3 ]
			),
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
		'email_intro'   => __( '{form:full-name} submitted “{form:post-title}”. It is waiting for review under Posts, marked Pending.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'We received your post “{form:post-title}”', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for writing for {site_title}. Your post is now waiting for review.', 'sureforms' ),
				__( 'We read every submission and will let you know whether it is accepted or needs changes.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'business-directory-listing',
		'preview_image' => 'business-directory-listing.webp',
		'category'      => 'accounts',
		'theme'         => 'mint',
		'icon'          => 'store',
		'requires'      => 'business',
		'title'         => __( 'Business Directory Listing', 'sureforms' ),
		'description'   => __( 'Let businesses add themselves to your directory, with their logo and address, for you to approve.', 'sureforms' ),
		'submit_text'   => __( 'Submit my listing', 'sureforms' ),
		'fields'        => [
			Catalog::width( Catalog::field( 'input', 'business-name', __( 'Business Name', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'category',
						__( 'Category', 'sureforms' ),
						true,
						array_merge(
							Catalog::dropdown_options( [ __( 'Food and drink', 'sureforms' ), __( 'Shops and retail', 'sureforms' ), __( 'Health and beauty', 'sureforms' ), __( 'Home and garden services', 'sureforms' ), __( 'Professional services', 'sureforms' ), __( 'Other', 'sureforms' ) ] ),
							[ 'placeholder' => __( 'Choose a category', 'sureforms' ) ]
						)
					),
					$no_search
				),
				50
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'description', __( 'About the Business', 'sureforms' ), true, [ 'helpText' => __( 'What you offer and what makes you different. This appears on your listing.', 'sureforms' ) ] ),
				[ 'rows' => 5 ]
			),
			// Named sub-fields, so the post can show the address: the address block itself has no single value.
			Catalog::field(
				'address',
				'address',
				__( 'Business Address', 'sureforms' ),
				true,
				[
					'innerFields' => [
						Catalog::attrs( Catalog::field( 'input', 'street-address', __( 'Street Address', 'sureforms' ), true ), [ 'addressComponent' => 'line1' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'city', __( 'City', 'sureforms' ), true ), 50 ), [ 'addressComponent' => 'city' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'state', __( 'State or Region', 'sureforms' ), false ), 25 ), [ 'addressComponent' => 'state' ] ),
						Catalog::attrs( Catalog::width( Catalog::field( 'input', 'postal-code', __( 'Postal Code', 'sureforms' ), true ), 25 ), [ 'addressComponent' => 'postal_code' ] ),
					],
				]
			),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'label'    => __( 'Business Phone', 'sureforms' ),
						'required' => true,
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'url', 'website', __( 'Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'opening-hours', __( 'Opening Hours', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Mon–Fri 8am–6pm, Sat 9am–2pm, closed Sunday', 'sureforms' ) ] ),
				[ 'rows' => 2 ]
			),
			$image_upload( 'logo', __( 'Logo', 'sureforms' ), __( 'A square JPG, PNG or WebP image, up to 5 MB.', 'sureforms' ) ),
			Catalog::width( array_merge( $c['name'], [ 'label' => __( 'Your Name', 'sureforms' ) ] ), 50 ),
			Catalog::width( array_merge( $c['email'], [ 'label' => __( 'Your Email', 'sureforms' ) ] ), 50 ),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store these details and publish the business details in its directory.', 'sureforms' ) ] ),
		],
		// The listing waits in Posts as Pending; the logo becomes its featured image.
		'cpt'           => [
			'name'           => __( 'Directory listing', 'sureforms' ),
			'title'          => 'business-name',
			'content'        => '<p>{form:description}</p><p>{form:street-address}, {form:city} {form:state} {form:postal-code}</p><p>{form:phone}<br>{form:website}</p><p>{form:opening-hours}</p>',
			'featured_image' => 'logo',
			'post_type'      => 'post',
			'status'         => 'pending',
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:full-name}!', 'sureforms' ),
			'text'    => __( 'We have received the listing for {form:business-name}. We will review it and email you at {form:email} once it is live.', 'sureforms' ),
		],
		'email_subject' => __( 'New directory listing for review: {form:business-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} submitted a listing for {form:business-name} ({form:category}). It is waiting for review under Posts, marked Pending.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'We received the listing for {form:business-name}', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for adding {form:business-name} to the {site_title} directory.', 'sureforms' ),
				__( 'We check every listing before it goes live, and will let you know when it is published.', 'sureforms' ),
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
		'title'         => __( 'Bulk / Catering Order', 'sureforms' ),
		'description'   => __( 'Take large or catering orders with as many items as the customer needs, for delivery or pickup.', 'sureforms' ),
		'submit_text'   => __( 'Send my order', 'sureforms' ),
		'fields'        => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company or Organization', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::field(
				'repeater',
				'items',
				__( 'Your Order', 'sureforms' ),
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
				Catalog::field(
					'multi-choice',
					'fulfilment',
					__( 'Delivery or Pickup?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Delivery', 'sureforms' ), __( 'Pickup', 'sureforms' ) ], true, [ 'truck', 'store' ] )
				),
				[ 'choiceWidth' => 50 ]
			),
			Catalog::width( Catalog::date_field( 'delivery-date', __( 'Date Needed', 'sureforms' ), true, true ), 50 ),
			Catalog::width(
				Catalog::field(
					'time-picker',
					'delivery-time',
					__( 'Time Needed', 'sureforms' ),
					true,
					[
						'increment' => 30,
						'minTime'   => '07:00',
						'maxTime'   => '20:00',
					]
				),
				50
			),
			Catalog::field( 'address', 'delivery-address', __( 'Delivery Address', 'sureforms' ), true ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'notes', __( 'Order Notes', 'sureforms' ), false, [ 'placeholder' => __( 'Allergies, dietary needs, packaging or delivery instructions', 'sureforms' ) ] ),
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
			'heading' => __( 'Thanks for your order, {form:full-name}!', 'sureforms' ),
			'text'    => __( 'We will check availability for {form:delivery-date} and email you at {form:email} to confirm your order and the total.', 'sureforms' ),
		],
		'email_subject' => __( 'Bulk order for {form:delivery-date} from {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} placed an order for {form:fulfilment} on {form:delivery-date} at {form:delivery-time}. Reply to confirm the order and its total.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'We received your order for {form:delivery-date}', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for your order. Your order details are below.', 'sureforms' ),
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
		'description'     => __( 'A step-by-step tenant application with income, references, ID documents and a signature. Applicants can save and finish later.', 'sureforms' ),
		'submit_text'     => __( 'Submit application', 'sureforms' ),
		'steps'           => [ __( 'About you', 'sureforms' ), __( 'Work and income', 'sureforms' ), __( 'References', 'sureforms' ), __( 'Documents and signature', 'sureforms' ) ],
		'fields'          => [
			Catalog::field( 'input', 'property', __( 'Property You Are Applying For', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. 12 Oak Street, Apartment 4', 'sureforms' ) ] ),
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::field( 'address', 'current-address', __( 'Current Address', 'sureforms' ), true ),
			Catalog::width( Catalog::date_field( 'move-in-date', __( 'Preferred Move-in Date', 'sureforms' ), true, true ), 50 ),
			Catalog::width( Catalog::number_field( 'occupants', __( 'Number of Occupants', 'sureforms' ), true, 1, 20, [ 'placeholder' => __( 'e.g. 2', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'pets',
						__( 'Pets', 'sureforms' ),
						true,
						array_merge( Catalog::dropdown_options( [ __( 'No pets', 'sureforms' ), __( 'Cat', 'sureforms' ), __( 'Dog', 'sureforms' ), __( 'Other pet', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] )
					),
					$no_search
				),
				100
			),
			Catalog::step( 'work-step', __( 'Work and income', 'sureforms' ) ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'employment-status',
						__( 'Employment Status', 'sureforms' ),
						true,
						array_merge( Catalog::dropdown_options( [ __( 'Employed full-time', 'sureforms' ), __( 'Employed part-time', 'sureforms' ), __( 'Self-employed', 'sureforms' ), __( 'Student', 'sureforms' ), __( 'Retired', 'sureforms' ), __( 'Not working', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] )
					),
					$no_search
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'employer', __( 'Employer', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'job-title', __( 'Job Title', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Store Manager', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::number_field( 'monthly-income', __( 'Monthly Income Before Tax', 'sureforms' ), true, 0, 1000000, [ 'placeholder' => __( 'e.g. 3,200', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'references-step', __( 'References', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'landlord-name', __( 'Current Landlord or Reference', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Sam Patel', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'     => 'landlord-phone',
						'label'    => __( 'Their Phone', 'sureforms' ),
						'required' => true,
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'reference-name', __( 'Second Reference', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. your employer or a previous landlord', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				array_merge(
					$c['phone'],
					[
						'slug'  => 'reference-phone',
						'label' => __(
							'Their Phone',
							'sureforms'
						),
					]
				),
				50
			),
			Catalog::field( 'textarea', 'anything-else', __( 'Anything Else We Should Know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. a guarantor, a parking space you need, or why you are moving', 'sureforms' ) ] ),
			Catalog::step( 'documents-step', __( 'Documents and signature', 'sureforms' ) ),
			$document_upload( 'id-proof', __( 'Photo ID', 'sureforms' ), true, __( 'Passport, driving licence or national ID card. PDF, JPG or PNG, up to 5 MB.', 'sureforms' ) ),
			$document_upload( 'proof-of-income', __( 'Proof of Income', 'sureforms' ), false, __( 'Your last three payslips or a recent bank statement. Up to 3 files.', 'sureforms' ), 3 ),
			array_merge( $c['consent'], [ 'label' => __( 'I confirm these details are true, and agree to have this website store them and contact my references to assess my application.', 'sureforms' ) ] ),
			$signature( __( 'Signature', 'sureforms' ) ),
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
	// Library category: Sign-ups & Applications.
	[
		'id'              => 'scholarship-application',
		'preview_image'   => 'scholarship-application.webp',
		'category'        => 'signup',
		'theme'           => 'lavender',
		'icon'            => 'graduation-cap',
		'requires'        => 'pro',
		'title'           => __( 'Scholarship Application', 'sureforms' ),
		'description'     => __( 'A step-by-step scholarship or grant application with an essay, transcript and signature. Applicants can save and finish later.', 'sureforms' ),
		'submit_text'     => __( 'Submit application', 'sureforms' ),
		'steps'           => [ __( 'About you', 'sureforms' ), __( 'Your studies', 'sureforms' ), __( 'Your essay', 'sureforms' ), __( 'Documents and signature', 'sureforms' ) ],
		'fields'          => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::field( 'address', 'home-address', __( 'Home Address', 'sureforms' ), true ),
			Catalog::step( 'studies-step', __( 'Your studies', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'school', __( 'School, College or University', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Riverside High School', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'program', __( 'Course or Program', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. BSc Computer Science', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::attrs(
					Catalog::field(
						'dropdown',
						'study-level',
						__( 'Level of Study', 'sureforms' ),
						true,
						array_merge( Catalog::dropdown_options( [ __( 'High school', 'sureforms' ), __( 'Undergraduate', 'sureforms' ), __( 'Postgraduate', 'sureforms' ), __( 'Vocational or trade', 'sureforms' ), __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] )
					),
					$no_search
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'grades', __( 'Current Grade Average', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. 3.6 GPA or 78%', 'sureforms' ) ] ), 50 ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'achievements', __( 'Achievements and Activities', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. awards, volunteering, sports, clubs or part-time work', 'sureforms' ) ] ),
				[ 'rows' => 4 ]
			),
			Catalog::step( 'essay-step', __( 'Your essay', 'sureforms' ) ),
			Catalog::attrs(
				Catalog::field( 'textarea', 'essay', __( 'Why should you receive this scholarship?', 'sureforms' ), true, [ 'helpText' => __( 'Tell us about your goals, what you have achieved and how this support would help. Around 300–500 words.', 'sureforms' ) ] ),
				[ 'rows' => 10 ]
			),
			Catalog::attrs(
				Catalog::field( 'textarea', 'funding-use', __( 'How would you use the funding?', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. tuition fees, books and a laptop for my course', 'sureforms' ) ] ),
				[ 'rows' => 3 ]
			),
			Catalog::step( 'documents-step', __( 'Documents and signature', 'sureforms' ) ),
			$document_upload( 'transcript', __( 'Transcript or Latest Grades', 'sureforms' ), true, __( 'PDF, JPG or PNG, up to 5 MB.', 'sureforms' ) ),
			$document_upload( 'recommendation-letter', __( 'Letter of Recommendation', 'sureforms' ), false, __( 'Optional. From a teacher, tutor or employer.', 'sureforms' ) ),
			array_merge( $c['consent'], [ 'label' => __( 'I confirm these details are true and the essay is my own work, and agree to have this website store them to assess my application.', 'sureforms' ) ] ),
			$signature( __( 'Signature', 'sureforms' ) ),
		],
		'save_resume'     => true,
		// SureForms Business also keeps unfinished applications and reminds the applicant to finish.
		'partial_entries' => [
			'resume'   => true,
			'reminder' => true,
			'email'    => 'email',
		],
		'attach_uploads'  => [ 'transcript', 'recommendation-letter' ],
		'confirmation'    => [
			'heading' => __( 'Application received, {form:first-name}', 'sureforms' ),
			'text'    => __( 'Thanks for applying. Our panel reviews every application after the deadline, and we will email you at {form:email} with the decision.', 'sureforms' ),
		],
		'email_subject'   => __( 'Scholarship application from {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'     => __( '{form:first-name} {form:last-name} ({form:program} at {form:school}) applied for the scholarship. Their transcript is attached.', 'sureforms' ),
		'autoresponder'   => [
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'subject'  => __( 'We received your scholarship application', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for applying for a scholarship with {site_title}.', 'sureforms' ),
				__( 'Our panel reviews every application after the deadline. We will email you with the decision, and may ask for more information first.', 'sureforms' ),
			],
			'summary'  => false,
		],
	],
];
