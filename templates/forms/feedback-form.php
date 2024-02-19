<?php
/**
 * Feedback Form / Survey Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Feedback Form / Survey Form', 'sureforms' ),
	'info'             => __( 'Form for conducting surveys', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => 'Survey Forms',
	'postTypes'        => SUREFORMS_FORMS_POST_TYPE,
	'content'          => '<!-- wp:sureforms/input {"block_id":"c7894ce2","required":true,"label":"Name","formId":23} /--><!-- wp:sureforms/email {"block_id":"82ea2785","required":true,"formId":23} /--><!-- wp:sureforms/multi-choice {"block_id":"3a7ef9dd","options":[{"optionTitle":"Food was great"},{"optionTitle":"Staff service"},{"optionTitle":"Location was great"},{"optionTitle":"Somthing else"}],"label":"What did you like about our lodge?","formId":23} /--><!-- wp:sureforms/textarea {"block_id":"7046569e","label":"Any Comment","formId":23} /-->',
	'id'               => 'form-6',
	'isPro'            => false,
];
