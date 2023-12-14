<?php
/**
 * Login Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'            => __( 'Login Form', 'sureforms' ),
	'info'             => __( 'User login form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => 'Registration Forms',
	'postTypes'        => SUREFORMS_FORMS_POST_TYPE,
	'content'          => '<!-- wp:sureforms/input {"block_id":"a9d3f6bc","label":"Email","formId":21} /--><!-- wp:sureforms/password {"block_id":"6cb53a65","formId":21} /-->',
	'id'               => 'form-7',
	'isPro'            => false,
];
