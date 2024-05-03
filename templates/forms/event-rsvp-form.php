<?php
/**
 * Event RSVP Form pattern.
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
	'title'            => __( 'Event RSVP Form', 'sureforms' ),
	'slug'             => 'event-rsvp-form',
	'info'             => __( 'Form for RSVP', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'RSVP', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"c7894ce2","required":true,"fieldWidth":50,"label":"First Name","formId":85} /--><!-- wp:srfm/input {"block_id":"44759383","required":true,"fieldWidth":50,"label":"Last Name","formId":85} /--><!-- wp:srfm/email {"block_id":"82ea2785","required":true,"formId":85} /--><!-- wp:srfm/multi-choice {"block_id":"3a7ef9dd","required":true,"singleSelection":true,"options":[{"optionTitle":"Yes"},{"optionTitle":"No"}],"label":"Will you be attending the Event","formId":85} /--><!-- wp:srfm/textarea {"block_id":"7046569e","label":"Any Comments or Suggestions","formId":85} /-->',
	'postMetas'        => [
		'_srfm_submit_alignment'     => [ 'justify' ],
		'_srfm_submit_width'         => [ '100%' ],
		'_srfm_submit_width_backend' => [ 'auto' ],
	],
	'isPro'            => false,
];
