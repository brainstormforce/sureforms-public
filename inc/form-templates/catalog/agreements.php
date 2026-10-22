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
 * @since x.x.x
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

// The service agreement's terms, shown on the form and in the signed copy.
$service_terms = [
	__( 'This agreement is between you (the client) and us (the provider).', 'sureforms' ),
	__( 'Services: We will provide the services described in our proposal or quote, which forms part of this agreement.', 'sureforms' ),
	__( 'Fees and payment: You agree to pay the fees in our proposal. Invoices are due within 14 days unless agreed otherwise in writing.', 'sureforms' ),
	__( 'Changes: Work outside the agreed scope will be quoted separately and only starts once you approve it.', 'sureforms' ),
	__( 'Cancellation: Either party may end this agreement with 14 days’ written notice. Work completed up to that date will be invoiced.', 'sureforms' ),
	__( 'Confidentiality: Both parties will keep each other’s confidential information private.', 'sureforms' ),
];

// The photo release's terms, shown on the form and in the signed copy.
$release_terms = [
	__( 'Permission: I allow the organiser to take and use photos, video and recordings of me in the places I choose below.', 'sureforms' ),
	__( 'Use: The media may be edited, cropped or captioned. It will not be sold to third parties or used in a way that misrepresents me.', 'sureforms' ),
	__( 'Payment: I will not be paid for this use.', 'sureforms' ),
	__( 'Withdrawing: I can withdraw my permission at any time by contacting the organiser. Media already printed or published cannot always be recalled.', 'sureforms' ),
];

/**
 * The signed copy of an agreement as PDF content, laid out like a contract: a
 * header, the two parties, numbered terms, the signature block and a footer
 * recording when and where it was signed. Tables and inline styles only, the
 * layout mPDF renders reliably.
 *
 * @param string                          $heading    Document title.
 * @param array<int, string>              $sections   Terms: plain paragraphs (an introduction) or `Title: text`.
 * @param array<int, string>              $parties    Labels of the two parties: the site's side, then the signer's.
 * @param array<string, string>           $details    The signer's details, label => smart tag; empty answers leave an empty row.
 * @param array<int, array<int, string>> $signatures Signature lines: [ signature tag, name, caption, optional detail under the name ].
 * @return string HTML.
 */
$signed_copy = static function ( $heading, $sections, $parties, $details, $signatures ) {
	$muted = 'color: #64748b;font-size: 9pt;';
	$label = 'color: #64748b;font-size: 8pt;letter-spacing: 1px;text-transform: uppercase;';

	// Header: who it is from, what it is and when it was signed.
	$html  = '<table width="100%" style="border-collapse: collapse;border-bottom: 2px solid #0f172a"><tr>';
	$html .= '<td style="padding: 0 0 10px;font-size: 11pt;font-weight: bold;color: #0f172a">{site_title}</td>';
	/* translators: %s: date the agreement was signed. */
	$html .= '<td style="padding: 0 0 10px;text-align: right;' . $muted . '">' . sprintf( esc_html__( 'Signed on %s', 'sureforms' ), '{date_mdy}' ) . '</td>';
	$html .= '</tr></table>';
	$html .= '<h1 style="margin: 24px 0 4px;font-size: 22pt;color: #0f172a">' . esc_html( $heading ) . '</h1>';
	$html .= '<p style="margin: 0 0 20px;' . $muted . '">' . esc_html__( 'Signed copy for the records of both parties.', 'sureforms' ) . '</p>';

	// The parties.
	$html .= '<table width="100%" style="border-collapse: collapse;margin-bottom: 20px"><tr>';
	$html .= '<td width="50%" style="vertical-align: top;padding: 12px 14px;background: #f8fafc;border: 1px solid #e2e8f0">';
	$html .= '<div style="' . $label . '">' . esc_html( $parties[0] ) . '</div>';
	$html .= '<div style="margin-top: 6px;font-size: 10.5pt;font-weight: bold">{site_title}</div><div style="' . $muted . '">{site_url}</div></td>';
	$html .= '<td width="50%" style="vertical-align: top;padding: 12px 14px;background: #f8fafc;border: 1px solid #e2e8f0">';
	$html .= '<div style="' . $label . '">' . esc_html( $parties[1] ) . '</div>';
	$html .= '<table style="margin-top: 6px;border-collapse: collapse;font-size: 10pt">';
	foreach ( $details as $name => $tag ) {
		$html .= '<tr><td style="vertical-align: top;padding: 1px 10px 1px 0;' . $muted . '">' . esc_html( $name ) . '</td><td style="vertical-align: top;padding: 1px 0">' . $tag . '</td></tr>';
	}
	$html .= '</table></td></tr></table>';

	// The terms, numbered.
	$html  .= '<div style="' . $label . 'margin-bottom: 8px">' . esc_html__( 'Terms', 'sureforms' ) . '</div>';
	$number = 0;
	foreach ( $sections as $section ) {
		$parts = explode( ':', $section, 2 );
		if ( ! isset( $parts[1] ) ) {
			$html .= '<p style="margin: 0 0 10px;font-size: 10.5pt;line-height: 1.5">' . esc_html( $section ) . '</p>';
			continue;
		}
		++$number;
		$html .= '<table width="100%" style="border-collapse: collapse;margin-bottom: 8px"><tr>';
		$html .= '<td width="24" style="vertical-align: top;font-weight: bold;color: #0f172a;font-size: 10.5pt">' . $number . '.</td>';
		$html .= '<td style="vertical-align: top;font-size: 10.5pt;line-height: 1.5"><strong>' . esc_html( trim( $parts[0] ) ) . '.</strong> ' . esc_html( trim( $parts[1] ) ) . '</td>';
		$html .= '</tr></table>';
	}

	// Signature block.
	// The lines sit on the cells: mPDF draws a div's border only as wide as its text.
	$line = 'vertical-align: top;border-top: 1px solid #0f172a;padding-top: 6px;font-size: 10pt';
	foreach ( $signatures as $signature ) {
		$detail = isset( $signature[3] ) ? '<br>' . $signature[3] : '';
		$html  .= '<table width="100%" style="border-collapse: collapse;margin-top: 28px">';
		$html  .= '<tr><td width="55%" style="vertical-align: bottom;height: 70px">' . $signature[0] . '</td><td width="8%"></td><td width="37%"></td></tr>';
		$html  .= '<tr><td style="' . $line . '"><strong>' . $signature[1] . '</strong>' . $detail . '<br><span style="' . $muted . '">' . esc_html( $signature[2] ) . '</span></td>';
		$html  .= '<td></td><td style="' . $line . '"><strong>{date_mdy}</strong><br><span style="' . $muted . '">' . esc_html__( 'Date', 'sureforms' ) . '</span></td></tr>';
		$html  .= '</table>';
	}

	// Footer: how and where it was signed.
	/* translators: %1$s: date, %2$s: website address, %3$s: entry number. */
	$html .= '<p style="margin-top: 36px;padding-top: 10px;border-top: 1px solid #e2e8f0;' . $muted . '">' . sprintf( esc_html__( 'Signed electronically on %1$s through %2$s. Entry #%3$s.', 'sureforms' ), '{date_mdy}', '{site_url}', '{entry_id}' ) . '</p>';
	return $html;
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
					[ __( 'Starter wording, not legal advice: replace it with your own terms, here and in the signed copy (the form’s PDF settings), before you publish this form.', 'sureforms' ) ],
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
		// A signed copy with the terms, the client's details and signature (Premium PDF).
		'pdf'           => [
			[
				'slug'    => 'agreement',
				'name'    => __( 'Download your signed agreement', 'sureforms' ),
				'content' => $signed_copy(
					__( 'Service agreement', 'sureforms' ),
					$service_terms,
					[ __( 'Provider', 'sureforms' ), __( 'Client', 'sureforms' ) ],
					[
						__( 'Name', 'sureforms' )    => '{form:full-name}',
						__( 'Company', 'sureforms' ) => '{form:company}',
						__( 'Email', 'sureforms' )   => '{form:email}',
						__( 'Proposal or quote', 'sureforms' ) => '{form:reference}',
					],
					[ [ '{form:signature}', '{form:full-name}', __( 'Client signature', 'sureforms' ) ] ]
				),
			],
		],
		'confirmation'  => [
			'heading' => __( 'Signed – thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'A copy has been sent to {form:email}. We will be in touch about next steps.', 'sureforms' ) . "\n\n{pdf:agreement}",
		],
		'email_subject' => __( 'Service agreement signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the service agreement on {date_mdy}. Their details and signature are below. Reply to this email to contact them.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your signed service agreement', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our service agreement on {date_mdy}.', 'sureforms' ),
				// On its own line: without the PDF library, SureForms Pro drops the whole line.
				'{pdf:agreement}',
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
					[ __( 'Starter wording, not legal advice: replace it with your own release, here and in the signed copy (the form’s PDF settings), before you publish this form.', 'sureforms' ) ],
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
		// A signed copy with the release, what was allowed and who signed (Premium PDF).
		'pdf'           => [
			[
				'slug'    => 'release',
				'name'    => __( 'Download your signed release', 'sureforms' ),
				'content' => $signed_copy(
					__( 'Photo and media release', 'sureforms' ),
					$release_terms,
					[ __( 'Organiser', 'sureforms' ), __( 'Person in the photos', 'sureforms' ) ],
					[
						__( 'Name', 'sureforms' )         => '{form:full-name}',
						__( 'Email', 'sureforms' )        => '{form:email}',
						__( 'Event', 'sureforms' )        => '{form:event}',
						__( 'Allowed uses', 'sureforms' ) => '{form:usage}',
						__( 'Naming', 'sureforms' )       => '{form:naming}',
					],
					// One line: only one of the two signatures is ever given.
					[ [ '{form:signature}{form:guardian-signature}', '{form:full-name}', __( 'Signature (a parent or guardian signs for under-18s)', 'sureforms' ), '{form:guardian-relationship} {form:guardian-name}' ] ]
				),
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thank you, {form:full-name}', 'sureforms' ),
			'text'    => __( 'Your release is signed. A copy has been sent to {form:email}.', 'sureforms' ) . "\n\n{pdf:release}",
		],
		'email_subject' => __( 'Media release signed by {form:full-name}', 'sureforms' ),
		'email_intro'   => __( '{form:full-name} signed the photo and media release. Allowed uses: {form:usage}. Naming: {form:naming}. Their details and signature are below.', 'sureforms' ),
		'autoresponder' => [
			'subject' => __( 'Your signed media release', 'sureforms' ),
			'lines'   => [
				__( 'Thank you for signing our photo and media release. You allowed us to use your photos and video on: {form:usage}.', 'sureforms' ),
				// On its own line: without the PDF library, SureForms Pro drops the whole line.
				'{pdf:release}',
				__( 'You can change your mind at any time. Just reply to this email.', 'sureforms' ),
			],
		],
	],
];
