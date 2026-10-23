<?php
/**
 * Form templates: Contact & leads.
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
$services  = [ __( 'Consultation', 'sureforms' ), __( 'Installation', 'sureforms' ), __( 'Maintenance', 'sureforms' ), __( 'Repair', 'sureforms' ), __( 'Something else', 'sureforms' ) ];

// With SureForms Pro, lead forms record the page the visitor sent them from.
$source_page = Catalog::field(
	'hidden',
	'source-page',
	__( 'Sent From Page', 'sureforms' ),
	false,
	[
		'defaultValue' => '{current_page_url}',
		'pro_only'     => true,
	]
);

return [
	[
		'id'            => 'contact-us',
		'category'      => 'contact',
		'theme'         => 'ocean',
		'icon'          => 'mail',
		'title'         => __( 'Contact Us', 'sureforms' ),
		'description'   => __( 'A simple form for visitors to send you a message.', 'sureforms' ),
		'submit_text'   => __( 'Send message', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::field( 'input', 'subject', __( 'Subject', 'sureforms' ), false, [ 'placeholder' => __( 'What is this about?', 'sureforms' ) ] ),
			Catalog::attrs( Catalog::field( 'textarea', 'message', __( 'Message', 'sureforms' ), true, [ 'placeholder' => __( 'How can we help?', 'sureforms' ) ] ), [ 'rows' => 5 ] ),
			$c['consent'],
			$source_page,
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => __( 'Your message is with us. We will reply to {form:email} as soon as we can.', 'sureforms' ),
		],
		'email_subject' => __( 'New message from {form:name}', 'sureforms' ),
		'email_intro'   => __( '{form:name} sent a message through your contact form. Reply to this email to answer them directly.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thanks for contacting {site_title}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [ __( 'Thanks for reaching out. Your message has reached us and we will get back to you as soon as we can.', 'sureforms' ) ],
		],
	],
	[
		'id'             => 'quote-request',
		'category'       => 'contact',
		'theme'          => 'midnight',
		'icon'           => 'receipt',
		'title'          => __( 'Quote Request', 'sureforms' ),
		'description'    => __( 'Collect project details so you can send an estimate.', 'sureforms' ),
		'submit_text'    => __( 'Request my quote', 'sureforms' ),
		'steps'          => [ __( 'Your details', 'sureforms' ), __( 'Your project', 'sureforms' ) ],
		'fields'         => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::field( 'input', 'company', __( 'Company', 'sureforms' ), false, [ 'placeholder' => __( 'If you are asking for a business', 'sureforms' ) ] ),
			Catalog::step( 'project-step', __( 'Your project', 'sureforms' ) ),
			Catalog::attrs( Catalog::field( 'dropdown', 'service', __( 'Service Needed', 'sureforms' ), true, array_merge( Catalog::dropdown_options( $services ), [ 'placeholder' => __( 'Select a service', 'sureforms' ) ] ) ), $no_search ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'budget', __( 'Estimated Budget', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'Under 1,000', 'sureforms' ), __( '1,000 – 5,000', 'sureforms' ), __( '5,000 – 20,000', 'sureforms' ), __( 'Over 20,000', 'sureforms' ), __( 'Not sure yet', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a range', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'timeline', __( 'Timeline', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'As soon as possible', 'sureforms' ), __( 'Within a month', 'sureforms' ), __( '1–3 months', 'sureforms' ), __( 'More than 3 months', 'sureforms' ), __( 'Flexible', 'sureforms' ) ] ), [ 'placeholder' => __( 'When do you need it?', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::attrs(
				Catalog::field(
					'textarea',
					'project-details',
					__( 'Project Details', 'sureforms' ),
					true,
					[
						'placeholder' => __( 'What do you need, how big is the job, and is there anything we should know?', 'sureforms' ),
						'helpText'    => __( 'The more detail you share, the more accurate your quote will be.', 'sureforms' ),
					]
				),
				[ 'rows' => 5 ]
			),
			Catalog::field(
				'upload',
				'attachments',
				__( 'Attachments', 'sureforms' ),
				false,
				[
					'pro_only'         => true,
					'helpText'         => __( 'Plans, photos or specs help us quote accurately. Up to 5 files.', 'sureforms' ),
					'allowedTypes'     => 'jpg,jpeg,png,pdf,doc,docx',
					'uploadSize'       => 10,
					'multiUpload'      => true,
					'multiFilesNumber' => 5,
				]
			),
			$c['consent'],
			$source_page,
		],
		// With SureForms Pro, the files arrive with the notification.
		'attach_uploads' => [ 'attachments' ],
		'confirmation'   => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => __( 'Your quote request is in. We will review your project details and send your estimate to {form:email}.', 'sureforms' ),
		],
		'email_subject'  => __( 'Quote request: {form:service} from {form:name}', 'sureforms' ),
		'email_intro'    => __( '{form:name} asked for a quote for {form:service}. Reply to this email to send your estimate.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'We received your quote request', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [ __( 'Thanks for telling us about your project. We will review the details and get back to you with an estimate.', 'sureforms' ) ],
		],
	],
	[
		'id'            => 'callback-request',
		'category'      => 'contact',
		'theme'         => 'mint',
		'icon'          => 'phone-call',
		'title'         => __( 'Callback Request', 'sureforms' ),
		'description'   => __( 'Let visitors request a phone call at their preferred time.', 'sureforms' ),
		'submit_text'   => __( 'Request a call', 'sureforms' ),
		'fields'        => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'best-time',
					__( 'Best time to call', 'sureforms' ),
					true,
					array_merge(
						Catalog::choice_options( [ __( 'Morning', 'sureforms' ), __( 'Afternoon', 'sureforms' ), __( 'Evening', 'sureforms' ), __( 'Any time', 'sureforms' ) ], true, [ 'sun', 'cloud-sun', 'moon', 'clock' ] ),
						[ 'helpText' => __( 'In your local time.', 'sureforms' ) ]
					)
				),
				[ 'choiceWidth' => 25 ]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'topic', __( 'What would you like to talk about?', 'sureforms' ), false, [ 'placeholder' => __( 'A quick note helps us prepare for the call.', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			$c['consent'],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, we will call you back', 'sureforms' ),
			'text'    => __( 'We will call you on {form:phone}. Best time noted: {form:best-time}.', 'sureforms' ),
		],
		'email_subject' => __( 'Call {form:full-name} back ({form:best-time})', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} would like a call on {form:phone}. Best time: {form:best-time}.', 'sureforms' ),
	],
	[
		'id'            => 'consultation-request',
		'category'      => 'contact',
		'theme'         => 'lavender',
		'icon'          => 'messages-square',
		'title'         => __( 'Consultation Request', 'sureforms' ),
		'description'   => __( 'Qualify leads who want a free consultation.', 'sureforms' ),
		'submit_text'   => __( 'Request a consultation', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company', 'sureforms' ) ), 50 ),
			Catalog::width( Catalog::field( 'url', 'website', __( 'Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
			Catalog::attrs(
				Catalog::field(
					'textarea',
					'goals',
					__( 'What are your goals?', 'sureforms' ),
					true,
					[
						'placeholder' => __( 'What would you like to achieve, and what is getting in the way?', 'sureforms' ),
						'helpText'    => __( 'A few sentences is plenty.', 'sureforms' ),
					]
				),
				[ 'rows' => 4 ]
			),
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'meeting-type', __( 'How would you like to meet?', 'sureforms' ), false, Catalog::choice_options( [ __( 'Video call', 'sureforms' ), __( 'Phone call', 'sureforms' ), __( 'In person', 'sureforms' ) ], true, [ 'video', 'phone', 'handshake' ] ) ),
				// Icon beside the text, the three choices side by side.
				[
					'verticalLayout' => false,
					'choiceWidth'    => 33.33,
				]
			),
			Catalog::width( Catalog::date_field( 'preferred-date', __( 'Preferred Date', 'sureforms' ), false, true ) + [ 'pro_only' => true ], 50 ),
			Catalog::width(
				Catalog::field(
					'time-picker',
					'preferred-time',
					__( 'Preferred time', 'sureforms' ),
					false,
					[
						'pro_only'  => true,
						'minTime'   => '09:00',
						'maxTime'   => '18:00',
						'increment' => 30,
					]
				),
				50
			),
			Catalog::attrs( Catalog::field( 'dropdown', 'how-heard', __( 'How did you hear about us?', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'Search engine', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Referral', 'sureforms' ), __( 'Advertisement', 'sureforms' ), __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select an option', 'sureforms' ) ] ) ), $no_search ),
			$c['consent'],
			$source_page,
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have your request and will email {form:email} to arrange a time for your consultation.', 'sureforms' ),
		],
		'email_subject' => __( 'Consultation request from {form:first-name} {form:last-name}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} would like a consultation. Reply to this email to arrange a time.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your consultation request with {site_title}', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [ __( 'Thanks for your interest. We will review your goals and get in touch to find a time that works for you.', 'sureforms' ) ],
		],
	],
	[
		'id'            => 'product-inquiry',
		'category'      => 'contact',
		'theme'         => 'sand',
		'icon'          => 'package',
		'title'         => __( 'Product Inquiry', 'sureforms' ),
		'description'   => __( 'Answer questions about a product before someone buys.', 'sureforms' ),
		'submit_text'   => __( 'Send my question', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'topic', __( 'What would you like to know?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Price & bulk orders', 'sureforms' ), __( 'Stock & delivery', 'sureforms' ), __( 'Product details', 'sureforms' ), __( 'Something else', 'sureforms' ) ], true, [ 'tags', 'truck', 'circle-info', 'circle-question' ] ) ),
				// Icon beside the text, two choices per row.
				[
					'verticalLayout' => false,
					'choiceWidth'    => 50,
				]
			),
			Catalog::width( Catalog::field( 'input', 'product', __( 'Product', 'sureforms' ), true, [ 'placeholder' => __( 'Product name or code', 'sureforms' ) ] ), 66.66 ),
			Catalog::width( Catalog::number_field( 'quantity', __( 'Quantity', 'sureforms' ), false, 1, 10000, [ 'placeholder' => '1' ] ), 33.33 ),
			Catalog::attrs( Catalog::field( 'textarea', 'question', __( 'Your Question', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Is it in stock? Does it come in other sizes? Do you offer bulk pricing?', 'sureforms' ) ] ), [ 'rows' => 4 ] ),
			$c['consent'],
			$source_page,
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for your question', 'sureforms' ),
			'text'    => __( 'We will reply to {form:email} with an answer about {form:product} as soon as we can.', 'sureforms' ),
		],
		// With SureForms Pro, a bulk order gets its own thank-you message.
		'confirmations' => [
			[
				'name'    => __( 'Bulk order', 'sureforms' ),
				'message' => Catalog::thank_you(
					__( 'Thanks for your question', 'sureforms' ),
					__( 'Asking about a larger order? We will reply to {form:email} with volume pricing for {form:product} as soon as we can.', 'sureforms' )
				),
				// 50 or more. A pattern, not _GREATER_OR_EQUAL_: the number field
				// stores 1000 as "1,000" (or "1.000"), which compares as text.
				'rules'   => [ [ 'quantity', '_REGEX_', '^([5-9][0-9]|[1-9][0-9]{2,}|[1-9][0-9]{0,2}[.,][0-9]{3})$' ] ],
			],
		],
		'email_subject' => __( 'Product question ({form:topic}): {form:product} from {form:name}', 'sureforms' ),
		'email_intro'   => __( '{form:name} has a question about {form:product} ({form:topic}). Reply to this email to answer them directly.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'We got your question about {form:product}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [ __( 'Thanks for your interest in {form:product}. We have received your question and will get back to you with an answer soon.', 'sureforms' ) ],
		],
	],
	[
		'id'             => 'partnership-inquiry',
		'category'       => 'contact',
		'theme'          => 'midnight',
		'icon'           => 'handshake',
		'title'          => __( 'Partnership Inquiry', 'sureforms' ),
		'description'    => __( 'Hear from businesses that want to work with you.', 'sureforms' ),
		'submit_text'    => __( 'Send proposal', 'sureforms' ),
		'fields'         => [
			Catalog::width( $c['name'], 50 ),
			Catalog::width(
				array_merge(
					$c['email'],
					[
						'label'       => __( 'Work Email', 'sureforms' ),
						'placeholder' => __( 'jane@company.com', 'sureforms' ),
					]
				),
				50
			),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company', 'sureforms' ), true ), 50 ),
			Catalog::width( Catalog::field( 'input', 'job-title', __( 'Your Role', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Head of Partnerships', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'url', 'website', __( 'Company Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'partnership-type', __( 'Type of Partnership', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Reseller or distributor', 'sureforms' ), __( 'Affiliate or referral', 'sureforms' ), __( 'Technology integration', 'sureforms' ), __( 'Co-marketing', 'sureforms' ), __( 'Sponsorship', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a type', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::field(
				'textarea',
				'proposal',
				__( 'Tell us about your proposal', 'sureforms' ),
				true,
				[
					'placeholder' => __( 'What do you have in mind, and how would it benefit both sides?', 'sureforms' ),
					'helpText'    => __( 'Include your audience or customer base if it is relevant.', 'sureforms' ),
				]
			),
			Catalog::field(
				'upload',
				'media-kit',
				__( 'Pitch Deck or Media Kit', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'allowedTypes' => 'pdf,ppt,pptx,key',
					'uploadSize'   => 10,
				]
			),
			$c['consent'],
		],
		// With SureForms Pro, the deck arrives with the notification.
		'attach_uploads' => [ 'media-kit' ],
		'confirmation'   => [
			'heading' => __( 'Thanks for your interest', 'sureforms' ),
			'text'    => __( 'We have received the proposal from {form:company}. We review every one and will be in touch if it is a good fit.', 'sureforms' ),
		],
		'email_subject'  => __( 'Partnership inquiry: {form:partnership-type} from {form:company}', 'sureforms' ),
		'email_intro'    => __( '{form:full-name} from {form:company} sent a partnership proposal ({form:partnership-type}). Reply to this email to continue the conversation.', 'sureforms' ),
		'autoresponder'  => [
			'subject' => __( 'Thanks for your partnership proposal', 'sureforms' ),
			'lines'   => [ __( 'Thanks for thinking of us. We have received the proposal from {form:company} and will get back to you if it is a good fit.', 'sureforms' ) ],
		],
	],
];
