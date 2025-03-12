<?php
/**
 * Subscription Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.2
 * @package    SureForms/Templates/Forms
 * @author     SureForms <https://sureforms.com/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Subscription Form', 'sureforms' ),
	'slug'             => 'subscription-form',
	'info'             => __( 'Form for Subscription', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Subscription', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/advanced-heading {"block_id":"6b9decba","headingTitle":"Subscribe to our Blog Post","headingTag":"h3","blockTopPadding":0,"blockRightPadding":0,"blockLeftPadding":0,"blockBottomPadding":0,"blockTopMargin":0,"blockRightMargin":0,"blockLeftMargin":0,"blockBottomMargin":20} /--><!-- wp:srfm/email {"block_id":"6ef07308","label":"Your Email","formId":83} /-->',
	'postMetas'        => [
		'_srfm_submit_alignment'     => [ 'justify' ],
		'_srfm_submit_width'         => [ '100%' ],
		'_srfm_submit_width_backend' => [ 'auto' ],
	],
	'isPro'            => false,
];
