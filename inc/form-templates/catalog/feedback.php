<?php
/**
 * Form templates: Feedback & surveys.
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

$c            = Catalog::common_fields();
$no_search    = [ 'searchable' => false ];
$satisfaction = [ __( 'Very satisfied', 'sureforms' ), __( 'Satisfied', 'sureforms' ), __( 'Neutral', 'sureforms' ), __( 'Dissatisfied', 'sureforms' ), __( 'Very dissatisfied', 'sureforms' ) ];
$nps_scale    = array_map( 'strval', range( 0, 10 ) );

return [
	[
		'id'            => 'customer-feedback',
		'category'      => 'feedback',
		'theme'         => 'mint',
		'icon'          => 'message-circle-heart',
		'title'         => __( 'Customer Feedback', 'sureforms' ),
		'description'   => __( 'Ask customers what they liked and what could be improved.', 'sureforms' ),
		'submit_text'   => __( 'Send feedback', 'sureforms' ),
		'fields'        => [
			Catalog::field(
				'multi-choice',
				'satisfaction',
				__( 'How satisfied are you overall?', 'sureforms' ),
				true,
				array_merge(
					Catalog::choice_options( $satisfaction, true ),
					[
						// One option per row, so the five answers read as a scale.
						'blockAttributes' => [
							'verticalLayout' => false,
							'choiceWidth'    => 100,
						],
						'pro'             => [
							'fieldType'     => 'rating',
							'label'         => __( 'How would you rate your overall experience?', 'sureforms' ),
							'showTooltip'   => true,
							'tooltipValues' => [ [ __( 'Very poor', 'sureforms' ), __( 'Poor', 'sureforms' ), __( 'Okay', 'sureforms' ), __( 'Good', 'sureforms' ), __( 'Excellent', 'sureforms' ) ] ],
						],
					]
				)
			),
			Catalog::attrs( Catalog::field( 'textarea', 'liked', __( 'What did you like most?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. fast delivery, friendly staff, easy to use', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::attrs( Catalog::field( 'textarea', 'improve', __( 'What could we do better?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. clearer pricing, quicker replies', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::field(
					'email',
					'email',
					__( 'Email (if you would like a reply)', 'sureforms' ),
					false,
					[
						'placeholder' => __( 'jane@example.com', 'sureforms' ),
					]
				),
				50
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for your feedback', 'sureforms' ),
			'text'    => __( 'Your answers help us do better.', 'sureforms' ),
		],
		// With SureForms Pro, the thank-you message follows the rating (1 to 5 stars).
		'confirmations' => [
			[
				'name'    => __( 'Happy customer', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'Thank you, we are glad you are happy', 'sureforms' ),
					__( 'If you have a minute, a short online review would help other people find us.', 'sureforms' )
				),
				'rules'   => [ [ 'satisfaction', '_GREATER_OR_EQUAL_', '4' ] ],
			],
			[
				'name'    => __( 'Unhappy customer', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'We are sorry we let you down', 'sureforms' ),
					__( 'Thank you for telling us. Your feedback goes straight to our team, and if you left your email address, someone will be in touch to put things right.', 'sureforms' )
				),
				'rules'   => [ [ 'satisfaction', '_LESSER_OR_EQUAL_', '2' ] ],
			],
		],
		'email_subject' => __( 'New customer feedback – overall: {form:satisfaction}', 'sureforms' ),
		'email_intro'   => __( 'A customer rated their overall experience: {form:satisfaction}. If they left an email address, you can follow up there.', 'sureforms' ),
	],
	[
		'id'            => 'satisfaction-survey',
		'category'      => 'feedback',
		'theme'         => 'lavender',
		'icon'          => 'gauge',
		'title'         => __( 'Satisfaction Survey', 'sureforms' ),
		'description'   => __( 'A short survey: how likely people are to recommend you (0–10), and why.', 'sureforms' ),
		'submit_text'   => __( 'Submit survey', 'sureforms' ),
		'fields'        => [
			Catalog::field(
				'dropdown',
				'recommend-score',
				__( 'How likely are you to recommend us to a friend or colleague?', 'sureforms' ),
				true,
				array_merge(
					Catalog::dropdown_options( $nps_scale ),
					[
						'placeholder'     => __( 'Select a score', 'sureforms' ),
						'helpText'        => __( '0 = not at all likely, 10 = extremely likely', 'sureforms' ),
						'blockAttributes' => $no_search,
						'pro'             => [
							'fieldType'   => 'nps',
							'helpText'    => '',
							'placeholder' => '',
						],
					]
				)
			),
			Catalog::attrs( Catalog::field( 'textarea', 'reason', __( 'What is the main reason for your score?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. great support, but setup took a while', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::attrs( Catalog::field( 'multi-choice', 'frequency', __( 'How often do you use our product or service?', 'sureforms' ), false, Catalog::choice_options( [ __( 'Daily', 'sureforms' ), __( 'Weekly', 'sureforms' ), __( 'Monthly', 'sureforms' ), __( 'Rarely', 'sureforms' ) ], true ) ), [ 'choiceWidth' => 25 ] ),
			Catalog::field(
				'email',
				'email',
				__( 'Email (optional)', 'sureforms' ),
				false,
				[
					'placeholder' => __( 'jane@example.com', 'sureforms' ),
					'helpText'    => __(
						'Only if you would like us to follow up.',
						'sureforms'
					),
				]
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thank you', 'sureforms' ),
			'text'    => __( 'We appreciate you taking the time to answer.', 'sureforms' ),
		],
		// With SureForms Pro, the thank-you message follows the score, and the
		// answers are charted in the form's survey report.
		'confirmations' => [
			[
				'name'    => __( 'Promoter (9–10)', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'Thank you, that means a lot', 'sureforms' ),
					__( 'If you have a minute, a short online review would help other people find us.', 'sureforms' )
				),
				'rules'   => [ [ 'recommend-score', '_GREATER_OR_EQUAL_', '9' ] ],
			],
			[
				'name'    => __( 'Detractor (0–6)', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'Thank you for being honest', 'sureforms' ),
					__( 'We are sorry we fell short. We read every response and will use yours to work out what to fix.', 'sureforms' )
				),
				'rules'   => [ [ 'recommend-score', '_LESSER_OR_EQUAL_', '6' ] ],
			],
		],
		'survey'        => [ 'show_live_results' => false ],
		'email_subject' => __( 'New survey response: {form:recommend-score}/10', 'sureforms' ),
		'email_intro'   => __( 'New recommend score: {form:recommend-score}/10. As a guide, 9–10 are promoters, 7–8 passives and 0–6 detractors.', 'sureforms' ),
	],
	[
		'id'            => 'website-feedback',
		'category'      => 'feedback',
		'theme'         => 'ocean',
		'icon'          => 'monitor',
		'title'         => __( 'Website Feedback', 'sureforms' ),
		'description'   => __( 'Learn whether visitors found what they came for.', 'sureforms' ),
		'submit_text'   => __( 'Send feedback', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'multi-choice', 'found-it', __( 'Did you find what you were looking for?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Yes', 'sureforms' ), __( 'Partly', 'sureforms' ), __( 'No', 'sureforms' ) ], true ) ),
			Catalog::attrs( Catalog::field( 'multi-choice', 'ease', __( 'How easy was the site to use?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Very easy', 'sureforms' ), __( 'Easy', 'sureforms' ), __( 'Difficult', 'sureforms' ), __( 'Very difficult', 'sureforms' ) ], true, [ 'face-grin-beam', 'face-smile', 'face-frown', 'face-tired' ] ) ), [ 'choiceWidth' => 25 ] ),
			Catalog::field( 'textarea', 'improve', __( 'What could we do better?', 'sureforms' ), false, [ 'placeholder' => __( 'Tell us what was missing, confusing or not working.', 'sureforms' ) ] ),
			Catalog::field(
				'upload',
				'screenshot',
				__( 'Screenshot (optional)', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'helpText'     => __( 'Helpful if something looked broken.', 'sureforms' ),
					'allowedTypes' => 'jpg,jpeg,png,gif,webp',
					'uploadSize'   => 5,
				]
			),
			Catalog::width( Catalog::field( 'input', 'page', __( 'Which page is this about? (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Pricing, or paste a link', 'sureforms' ) ] ), 50 ),
			Catalog::width(
				Catalog::field(
					'email',
					'email',
					__( 'Email (if you would like a reply)', 'sureforms' ),
					false,
					[
						'placeholder' => __( 'jane@example.com', 'sureforms' ),
					]
				),
				50
			),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for helping us improve', 'sureforms' ),
			'text'    => __( 'We read every response and use it to make the site better.', 'sureforms' ),
		],
		// With SureForms Pro, the screenshot is only asked for when something went wrong.
		'conditions'    => [
			'screenshot' => [
				'logic' => [
					[ [ 'found-it', 'in', [ __( 'Partly', 'sureforms' ), __( 'No', 'sureforms' ) ] ] ],
					[ [ 'ease', 'in', [ __( 'Difficult', 'sureforms' ), __( 'Very difficult', 'sureforms' ) ] ] ],
				],
			],
		],
		'email_subject' => __( 'Website feedback: found what they needed? {form:found-it}', 'sureforms' ),
		'email_intro'   => __( 'A visitor left feedback from {current_page_url}. If they left an email address, you will find it in the entry.', 'sureforms' ),
	],
	[
		'id'            => 'testimonial',
		'category'      => 'feedback',
		'theme'         => 'sand',
		'icon'          => 'quote',
		'title'         => __( 'Testimonial Submission', 'sureforms' ),
		'description'   => __( 'Collect reviews to mention on your website.', 'sureforms' ),
		'submit_text'   => __( 'Share my testimonial', 'sureforms' ),
		'fields'        => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name (as you\'d like it shown)', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( array_merge( $c['email'], [ 'label' => __( 'Email (never published)', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'role', __( 'Job title', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Owner', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::field( 'input', 'product', __( 'Product or service (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Website redesign', 'sureforms' ) ] ),
			Catalog::field(
				'rating',
				'rating',
				__( 'How would you rate us?', 'sureforms' ),
				false,
				[
					'pro_only'  => true,
					'iconShape' => 'star',
				]
			),
			Catalog::attrs(
				Catalog::field(
					'textarea',
					'testimonial',
					__( 'Your Testimonial', 'sureforms' ),
					true,
					[
						'placeholder' => __( 'What did we help you with, and what difference did it make?', 'sureforms' ),
						'helpText'    => __( 'A few honest sentences are perfect.', 'sureforms' ),
					]
				),
				[ 'rows' => 5 ]
			),
			Catalog::field(
				'upload',
				'photo',
				__( 'Your Photo (optional)', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'helpText'     => __( 'A clear headshot shown next to your testimonial.', 'sureforms' ),
					'allowedTypes' => 'jpg,jpeg,png',
					'uploadSize'   => 5,
				]
			),
			Catalog::field( 'gdpr', 'publish-permission', __( 'You may publish my testimonial with my name and job title on this website. It may be lightly edited for length.', 'sureforms' ), true ),
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:name}', 'sureforms' ),
			'text'    => __( 'We really appreciate you sharing your experience. We will review it before it appears on our site.', 'sureforms' ),
		],
		'email_subject' => __( 'New testimonial from {form:name}', 'sureforms' ),
		'email_intro'   => __( '{form:name} shared a testimonial and gave permission to publish it. Reply to this email to thank them or ask a question.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thank you for your testimonial', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thank you for taking the time to share your experience. It means a lot to us.', 'sureforms' ),
				__( 'We review every testimonial before publishing it. If you would like to change anything, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'             => 'complaint',
		'category'       => 'feedback',
		'theme'          => 'mint',
		'icon'           => 'circle-alert',
		'title'          => __( 'Complaint Form', 'sureforms' ),
		'description'    => __( 'Give customers a clear way to report issues.', 'sureforms' ),
		'submit_text'    => __( 'Submit complaint', 'sureforms' ),
		'steps'          => [ __( 'Your details', 'sureforms' ), __( 'Your complaint', 'sureforms' ) ],
		'fields'         => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::width( Catalog::field( 'input', 'reference', __( 'Order or Reference Number', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. #10234', 'sureforms' ) ] ), 50 ),
			Catalog::step( 'complaint-step', __( 'Your complaint', 'sureforms' ) ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'complaint-type', __( 'What is your complaint about?', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Product quality', 'sureforms' ), __( 'Service or staff', 'sureforms' ), __( 'Billing or payment', 'sureforms' ), __( 'Delivery or shipping', 'sureforms' ), __( 'Website or account', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a topic', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::width( Catalog::date_field( 'incident-date', __( 'When did it happen?', 'sureforms' ) ), 50 ),
			Catalog::attrs( Catalog::field( 'textarea', 'details', __( 'What happened?', 'sureforms' ), true, [ 'helpText' => __( 'Please include names, dates and anything you have already tried.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
			Catalog::field(
				'url',
				'evidence',
				__( 'Link to a photo or document', 'sureforms' ),
				false,
				[
					'placeholder' => 'https://',
					'helpText'    => __( 'For example a shared Google Drive or Dropbox link.', 'sureforms' ),
					'pro'         => [
						'fieldType'        => 'upload',
						'label'            => __( 'Photos or documents (optional)', 'sureforms' ),
						'placeholder'      => '',
						'helpText'         => __( 'Up to 3 files: JPG, PNG or PDF.', 'sureforms' ),
						'allowedTypes'     => 'jpg,jpeg,png,pdf',
						'uploadSize'       => 10,
						'multiUpload'      => true,
						'multiFilesNumber' => 3,
					],
				]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'resolution', __( 'How would you like us to put it right?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. a refund, a replacement or a call back', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		// With SureForms Pro, the evidence arrives with the notification.
		'attach_uploads' => [ 'evidence' ],
		'confirmation'   => [
			'heading' => __( 'We have received your complaint', 'sureforms' ),
			'text'    => __( 'We are sorry for the trouble, {form:name}. We will look into it and contact you at {form:email}.', 'sureforms' ),
		],
		'email_subject'  => __( 'Complaint from {form:name}: {form:complaint-type}', 'sureforms' ),
		'email_intro'    => __( '{form:name} raised a complaint about: {form:complaint-type}. Reply to this email to respond to them directly.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'We have received your complaint', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'We are sorry for the trouble, and thank you for letting us know.', 'sureforms' ),
				__( 'Your complaint has been logged and someone from our team will look into it and contact you. If you have anything to add, just reply to this email.', 'sureforms' ),
			],
		],
	],
];
