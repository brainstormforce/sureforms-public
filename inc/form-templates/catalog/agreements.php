<?php
/**
 * Form templates: Agreements.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * The terms are starting-point wording for the site owner to replace with
 * their own, not legal advice.
 *
 * @package sureforms
 * @since 2.13.0
 */

use SRFM\Inc\Form_Templates\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c = Catalog::common_fields();

/**
 * A block of terms text shown above the signature (SureForms Pro HTML block).
 * The first paragraph is a highlighted note to the site owner to replace the
 * starter wording; each other paragraph is `Title: text`.
 *
 * @param string             $slug     Field slug.
 * @param string             $label    Block name in the editor.
 * @param string             $heading  Heading.
 * @param array<int, string|array<int, string>> $sections Owner note, then paragraphs or [ title, text ] terms.
 * @return array<string, mixed>
 */
$terms = static function ( $slug, $label, $heading, $sections ) {
	// Styles as wp_kses_post() writes them. No max-height: kses drops `overflow`, so a fixed height would cut the text off.
	$html  = '<div style="padding: 16px 20px;border: 1px solid #e2e8f0;border-radius: 8px;background: #ffffff;font-size: 14px;line-height: 1.6">';
	$html .= '<h3 style="margin-top: 0">' . esc_html( $heading ) . '</h3>';
	$html .= '<p style="padding: 8px 12px;background: #fef3c7;border-radius: 6px"><em>' . esc_html( (string) array_shift( $sections ) ) . '</em></p>';
	foreach ( $sections as $section ) {
		/* translators: 1: a term's title, 2: its text. */
		$html .= '<p>' . ( is_array( $section ) ? sprintf( esc_html__( '%1$s: %2$s', 'sureforms' ), '<strong>' . esc_html( $section[0] ) . '</strong>', esc_html( $section[1] ) ) : esc_html( $section ) ) . '</p>';
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

// The service agreement's terms, shown on the form.
$service_terms = [
	__( 'This agreement is between you (the client) and us (the provider).', 'sureforms' ),
	[ __( 'Services', 'sureforms' ), __( 'We will provide the services described in our proposal or quote, which forms part of this agreement.', 'sureforms' ) ],
	[ __( 'Fees and payment', 'sureforms' ), __( 'You agree to pay the fees in our proposal. Invoices are due within 14 days unless agreed otherwise in writing.', 'sureforms' ) ],
	[ __( 'Changes', 'sureforms' ), __( 'Work outside the agreed scope will be quoted separately and only starts once you approve it.', 'sureforms' ) ],
	[ __( 'Cancellation', 'sureforms' ), __( 'Either party may end this agreement with 14 days’ written notice. Work completed up to that date will be invoiced.', 'sureforms' ) ],
	[ __( 'Confidentiality', 'sureforms' ), __( 'Both parties will keep each other’s confidential information private.', 'sureforms' ) ],
];

// The photo release's terms, shown on the form.
$release_terms = [
	[ __( 'Permission', 'sureforms' ), __( 'I allow the organiser to take and use photos, video and recordings of me in the places I choose below.', 'sureforms' ) ],
	[ __( 'Use', 'sureforms' ), __( 'The media may be edited, cropped or captioned. It will not be sold to third parties or used in a way that misrepresents me.', 'sureforms' ) ],
	[ __( 'Payment', 'sureforms' ), __( 'I will not be paid for this use.', 'sureforms' ) ],
	[ __( 'Withdrawing', 'sureforms' ), __( 'I can withdraw my permission at any time by contacting the organiser. Media already printed or published cannot always be recalled.', 'sureforms' ) ],
];

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
		'requires'      => 'business',
		'theme'         => 'ocean',
		'icon'          => 'file-signature',
		'title'         => __( 'Service Agreement', 'sureforms' ),
		'description'   => __( 'Your terms plus the client’s details and signature.', 'sureforms' ),
		'submit_text'   => __( 'Sign agreement', 'sureforms' ),
		'fields'        => [
			$terms(
				'terms',
				__( 'Agreement terms', 'sureforms' ),
				__( 'Service agreement', 'sureforms' ),
				array_merge(
					[ __( 'Starter wording, not legal advice: replace it with your own terms before you publish this form.', 'sureforms' ) ],
					$service_terms
				)
			),
			Catalog::width( $c['name'], 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company (if any)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( Catalog::field( 'input', 'reference', __( 'Proposal or quote number', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. #10234', 'sureforms' ) ] ), 50 ),
			Catalog::field( 'gdpr', 'agree-terms', __( 'I have read and agree to the terms above, and I am authorised to sign on behalf of the client.', 'sureforms' ), true ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'confirmation'  => [
			'heading' => __( 'Signed – thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'We will be in touch at {form:email} about next steps.', 'sureforms' ),
		],
		'email_subject' => __( 'Service agreement signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the service agreement on {date_mdy}. Reply to this email to contact them.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'You signed our service agreement', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our service agreement on {date_mdy}.', 'sureforms' ),
				__( 'If anything looks wrong, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'photo-release-consent',
		'preview_image' => 'photo-release-consent.webp',
		'category'      => 'agreements',
		'requires'      => 'business',
		'theme'         => 'lavender',
		'icon'          => 'camera',
		'title'         => __( 'Photo & Media Release', 'sureforms' ),
		'description'   => __( 'Signed permission to use someone’s photos and videos.', 'sureforms' ),
		'submit_text'   => __( 'Sign release', 'sureforms' ),
		'fields'        => [
			$terms(
				'release-terms',
				__( 'Release terms', 'sureforms' ),
				__( 'Photo and media release', 'sureforms' ),
				array_merge(
					[ __( 'Starter wording, not legal advice: replace it with your own release before you publish this form.', 'sureforms' ) ],
					$release_terms
				)
			),
			Catalog::width( array_merge( $c['name'], [ 'label' => __( 'Full name', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			Catalog::field( 'input', 'event', __( 'Event or photo session (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Summer open day 2026', 'sureforms' ) ] ),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'usage',
					__( 'Where may we use your photos and video?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Our website', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Print and ads', 'sureforms' ) ], false )
				),
				[ 'choiceWidth' => 33.33 ]
			),
			// People often agree to photos but not to being named or tagged.
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'naming', __( 'May we name you with your photos?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Yes, use my name', 'sureforms' ), __( 'No, without my name', 'sureforms' ) ], true ) ),
				[ 'choiceWidth' => 50 ]
			),
			// For children, a parent or guardian gives permission and signs instead.
			Catalog::field( 'checkbox', 'under-18', __( 'The person in the photos is under 18', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'guardian-name', __( 'Parent or guardian’s full name', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Alex Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::attrs( Catalog::field( 'dropdown', 'guardian-relationship', __( 'Relationship to the child', 'sureforms' ), true, array_merge( Catalog::dropdown_options( [ __( 'Parent', 'sureforms' ), __( 'Legal guardian', 'sureforms' ), __( 'Other authorised adult', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select one', 'sureforms' ) ] ) ), [ 'searchable' => false ] ), 50 ),
			Catalog::field( 'gdpr', 'release-consent', __( 'I have read the release above and give my permission for the uses I have chosen.', 'sureforms' ), true ),
			$signature( 'guardian-signature', __( 'Parent or guardian’s signature', 'sureforms' ) ),
			$signature( 'signature', __( 'Signature', 'sureforms' ) ),
		],
		'conditions'    => [
			'guardian-name'         => [ 'logic' => [ [ [ 'under-18', 'isChecked' ] ] ] ],
			'guardian-relationship' => [ 'logic' => [ [ [ 'under-18', 'isChecked' ] ] ] ],
			'guardian-signature'    => [ 'logic' => [ [ [ 'under-18', 'isChecked' ] ] ] ],
			'signature'             => [
				'action' => 'hide',
				'logic'  => [ [ [ 'under-18', 'isChecked' ] ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'Your release is signed. We will only use your photos and videos in the ways you chose.', 'sureforms' ),
		],
		'email_subject' => __( 'Media release signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the photo and media release. Allowed uses: {form:usage}. Naming: {form:naming}.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'You signed our media release', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our photo and media release. You allowed us to use your photos and video on: {form:usage}.', 'sureforms' ),
				__( 'You can change your mind at any time. Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
