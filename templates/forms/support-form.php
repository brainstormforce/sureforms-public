<?php
/**
 * Support Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'            => __( 'Support Form', 'sureforms' ),
	'info'             => __( 'Form for submitting support query', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => 'Support Forms',
	'postTypes'        => SUREFORMS_FORMS_POST_TYPE,
	'content'          => '<!-- wp:sureforms/input {"block_id":"fd4ef0f9","required":true,"fieldWidth":50,"label":"First Name","formId":25} /--><!-- wp:sureforms/input {"block_id":"667678ef","required":true,"fieldWidth":50,"label":"Last Name","formId":25} /--><!-- wp:sureforms/email {"block_id":"eae1ae54","required":true,"formId":25} /--><!-- wp:sureforms/input {"block_id":"5e4d1e8f","required":true,"label":"Subject","formId":25} /--><!-- wp:sureforms/textarea {"block_id":"f0076110","required":true,"label":"Please describe your question in detail. Explain the exact steps to replicate the problem.","formId":25} /--><!-- wp:sureforms/url {"block_id":"0e087bac","required":true,"label":"URL where we can see more details.","formId":25} /--><!-- wp:sureforms/upload {"block_id":"b000e5a4","label":"Attach screenshots to help us understand your question accurately.","allowedFormats":[{"value":"jpg","label":"jpg"},{"value":"jpeg","label":"jpeg"},{"value":"gif","label":"gif"},{"value":"png","label":"png"},{"value":"pdf","label":"pdf"},{"value":"mp4","label":"mp4"}],"formId":25} /-->',
	'id'               => 'form-4',
	'isPro'            => false,
];
