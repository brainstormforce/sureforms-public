<?php
/**
 * Event RSVP Form pattern.
 *
 * @link       https://sureforms.com
 * @since      x.x.x
 * @package    SureForms/Templates/Forms
 * @author     SureForms <https://sureforms.com/>
 */

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Event RSVP Form', 'sureforms' ),
	'slug'             => __( 'event-rsvp-form', 'sureforms' ),
	'info'             => __( 'Form for RSVP', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'RSVP Forms', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"c7894ce2","required":true,"fieldWidth":50,"label":"First Name","formId":85} /--><!-- wp:srfm/input {"block_id":"44759383","required":true,"fieldWidth":50,"label":"Last Name","formId":85} /--><!-- wp:srfm/email {"block_id":"82ea2785","required":true,"formId":85} /--><!-- wp:srfm/multi-choice {"block_id":"3a7ef9dd","required":true,"options":[{"optionTitle":"Yes"},{"optionTitle":"No"}],"label":"Will you be attending the Event","formId":85} /--><!-- wp:srfm/textarea {"block_id":"7046569e","label":"Any Comments or Suggestions","formId":85} /-->',
	'postMetas'        => [
		'_srfm_submit_alignment'     => [ 'justify' ],
		'_srfm_submit_width'         => [ '100%' ],
		'_srfm_submit_width_backend' => [ 'auto' ],
	],
	'id'               => 'form-' . Helper::generate_random_id( 2 ),
	'isPro'            => false,
];
