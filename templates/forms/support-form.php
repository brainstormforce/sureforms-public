<?php
/**
 * Support Form pattern.
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
	'title'            => __( 'Support Form', 'sureforms' ),
	'slug'             => 'support-form',
	'info'             => __( 'Form for submitting support query', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Support', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"fd4ef0f9","required":true,"fieldWidth":50,"label":"First Name","formId":81} /--><!-- wp:srfm/input {"block_id":"667678ef","required":true,"fieldWidth":50,"label":"Last Name","formId":81} /--><!-- wp:srfm/email {"block_id":"eae1ae54","required":true,"formId":81} /--><!-- wp:srfm/input {"block_id":"5e4d1e8f","required":true,"label":"Subject","formId":81} /--><!-- wp:srfm/textarea {"block_id":"f0076110","required":true,"label":"Please describe your question in detail. Explain the exact steps to replicate the problem.","formId":81} /--><!-- wp:srfm/url {"block_id":"0e087bac","required":true,"label":"URL where we can see more details.","formId":81} /-->',
	'isPro'            => false,
];
