<?php
/**
 * Form templates: Support.
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

$c           = Catalog::common_fields();
$no_search   = [ 'searchable' => false ];
$screenshots = [
	'pro' => [
		'fieldType'        => 'upload',
		'label'            => __( 'Screenshots (optional)', 'sureforms' ),
		'placeholder'      => '',
		'helpText'         => __( 'Add up to 3 images or PDFs.', 'sureforms' ),
		'allowedTypes'     => 'jpg,jpeg,png,gif,webp,pdf',
		'multiUpload'      => true,
		'multiFilesNumber' => 3,
	],
];

return [
	[
		'id'             => 'support-request',
		'category'       => 'support',
		'theme'          => 'ocean',
		'icon'           => 'life-buoy',
		'title'          => __( 'Support Request', 'sureforms' ),
		'description'    => __( 'Let customers report issues by urgency.', 'sureforms' ),
		'submit_text'    => __( 'Send request', 'sureforms' ),
		'fields'         => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'topic', __( 'Topic', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Technical issue', 'sureforms' ), __( 'Billing or payment', 'sureforms' ), __( 'Account or login', 'sureforms' ), __( 'Orders and delivery', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a topic', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::width( Catalog::field( 'input', 'order-number', __( 'Order or Account Number', 'sureforms' ), false, [ 'placeholder' => __( 'If you have one', 'sureforms' ) ] ), 50 ),
			Catalog::field( 'input', 'subject', __( 'Subject', 'sureforms' ), true, [ 'placeholder' => __( 'A short summary of the issue', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'priority',
					__( 'How urgent is this?', 'sureforms' ),
					true,
					array_merge(
						Catalog::choice_options( [ __( 'Low', 'sureforms' ), __( 'Normal', 'sureforms' ), __( 'High', 'sureforms' ), __( 'Urgent', 'sureforms' ) ], true ),
						[ 'helpText' => __( 'Choose Urgent only if you cannot use the product or service at all.', 'sureforms' ) ]
					)
				),
				[ 'choiceWidth' => 25 ]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'description', __( 'Describe the issue', 'sureforms' ), true, [ 'helpText' => __( 'Include what you were doing, what happened, any error messages and anything you have already tried.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
			Catalog::field( 'url', 'link', __( 'Link to a screenshot or page', 'sureforms' ), false, array_merge( [ 'placeholder' => 'https://' ], $screenshots ) ),
			$c['consent'],
		],
		'confirmation'   => [
			'heading' => __( 'Request received', 'sureforms' ),
			'text'    => __( 'Thanks, {form:name}. Your reference number is #{entry_id}. We will reply to {form:email} as soon as we can.', 'sureforms' ),
		],
		// With SureForms Pro, the screenshots arrive with the notification.
		'attach_uploads' => [ 'link' ],
		'email_subject'  => __( 'Support: {form:subject} ({form:priority}) from {form:name}', 'sureforms' ),
		'email_intro'    => __( '{form:name} needs help ({form:priority} priority, request #{entry_id}). Reply to this email to answer them directly.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'We received your request: {form:subject}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for getting in touch. We have logged your request about "{form:subject}" with reference number #{entry_id}.', 'sureforms' ),
				__( 'Our team will reply as soon as we can. If you have anything to add, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'             => 'bug-report',
		'category'       => 'support',
		'theme'          => 'midnight',
		'icon'           => 'bug',
		'title'          => __( 'Bug Report', 'sureforms' ),
		'description'    => __( 'Collect clear, reproducible bug reports.', 'sureforms' ),
		'submit_text'    => __( 'Report bug', 'sureforms' ),
		'fields'         => [
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'input', 'summary', __( 'Bug Summary', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. The checkout button does nothing on mobile', 'sureforms' ) ] ),
			Catalog::field(
				'multi-choice',
				'severity',
				__( 'Severity', 'sureforms' ),
				true,
				array_merge(
					Catalog::choice_options( [ __( 'Minor', 'sureforms' ), __( 'Major', 'sureforms' ), __( 'Blocking', 'sureforms' ) ], true ),
					[ 'helpText' => __( 'Minor: looks wrong or is annoying. Major: something does not work. Blocking: you cannot continue.', 'sureforms' ) ]
				)
			),
			Catalog::attrs( Catalog::field( 'textarea', 'what-happened', __( 'What went wrong?', 'sureforms' ), true, [ 'helpText' => __( 'Tell us what you saw, including any error message, and what you expected to happen instead.', 'sureforms' ) ] ), [ 'rows' => 5 ] ),
			Catalog::field(
				'textarea',
				'steps',
				__( 'Steps to Reproduce', 'sureforms' ),
				false,
				[
					'placeholder' => __( '1. Go to… 2. Click… 3. See the error', 'sureforms' ),
					'helpText'    => __( 'Optional, but it helps us fix the problem much faster.', 'sureforms' ),
				]
			),
			Catalog::field( 'url', 'page-url', __( 'Page Where It Happened', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'browser', __( 'Browser', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ 'Chrome', 'Safari', 'Firefox', 'Edge', __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a browser', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'device', __( 'Device', 'sureforms' ), false, array_merge( Catalog::dropdown_options( [ __( 'Desktop or laptop', 'sureforms' ), __( 'Phone', 'sureforms' ), __( 'Tablet', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a device', 'sureforms' ) ] ) ), $no_search ), 50 ),
			Catalog::field( 'url', 'screenshot', __( 'Link to a screenshot', 'sureforms' ), false, array_merge( [ 'placeholder' => 'https://' ], $screenshots ) ),
			$c['consent'],
		],
		'confirmation'   => [
			'heading' => __( 'Thanks for the report', 'sureforms' ),
			'text'    => __( 'We have logged "{form:summary}" as report #{entry_id}. We will contact you at {form:email} if we need more details.', 'sureforms' ),
		],
		// With SureForms Pro, the screenshots arrive with the notification.
		'attach_uploads' => [ 'screenshot' ],
		'email_subject'  => __( 'Bug: {form:summary} ({form:severity})', 'sureforms' ),
		'email_intro'    => __( '{form:name} reported a {form:severity} bug (report #{entry_id}). Reply to this email to ask them for more details.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'We received your bug report: {form:summary}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for helping us improve. We have logged your report "{form:summary}" with reference number #{entry_id}.', 'sureforms' ),
				__( 'We will look into it and get back to you if we need more details.', 'sureforms' ),
			],
		],
	],
];
