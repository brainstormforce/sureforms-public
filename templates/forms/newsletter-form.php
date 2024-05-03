<?php
/**
 * Newsletter Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     SureForms <https://sureforms.com/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Newsletter Form', 'sureforms' ),
	'slug'             => 'newsletter-form',
	'info'             => __( 'Creates a Newsletter Form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Newsletter', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/advanced-heading {"block_id":"2117a231","headingTitle":"Subscribe to Our Newsletter","headingDescToggle":true,"headingDesc":"Subscribe to our newsletter below and receive exclusive access to new content, including tips, articles, guides, and updates.","subHeadingColor":"#545454","headingTag":"h3","headSpace":12,"subHeadSpace":20,"subHeadFontSize":18} /--><!-- wp:srfm/input {"block_id":"3f513e23","fieldWidth":50,"label":"First Name","formId":80} /--><!-- wp:srfm/input {"block_id":"4f84bead","fieldWidth":50,"label":"Last Name","formId":80} /--><!-- wp:srfm/email {"block_id":"6ef07308","label":"Your Email","formId":80} /-->',
	'postMetas'        => [
		'_srfm_submit_alignment'     => [ 'justify' ],
		'_srfm_submit_width'         => [ '100%' ],
		'_srfm_submit_width_backend' => [ 'auto' ],
	],
	'isPro'            => false,
];
