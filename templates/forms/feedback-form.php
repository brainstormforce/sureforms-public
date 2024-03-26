<?php
/**
 * Feedback Form / Survey Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     SureForms <https://sureforms.com/>
 */

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Feedback Form / Survey Form', 'sureforms' ),
	'info'             => __( 'Form for conducting surveys', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Survey Forms', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"c7894ce2","required":true,"label":"Name","formId":63} /--><!-- wp:srfm/email {"block_id":"82ea2785","required":true,"formId":63} /--><!-- wp:srfm/multi-choice {"block_id":"3a7ef9dd","required":true,"options":[{"optionTitle":"Food was great"},{"optionTitle":"Staff service"},{"optionTitle":"Location was great"},{"optionTitle":"Somthing else"}],"label":"What did you like about our lodge?","formId":63} /--><!-- wp:srfm/textarea {"block_id":"7046569e","label":"Any Comment","formId":63} /-->',
	'id'               => 'form-' . Helper::generate_random_id( 2 ),
	'isPro'            => false,
];
