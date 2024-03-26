<?php
/**
 * Login Form pattern.
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
	'title'            => __( 'Login Form', 'sureforms' ),
	'info'             => __( 'User login form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Registration Forms', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"a9d3f6bc","label":"Email","formId":21} /--><!-- wp:srfm/password {"block_id":"6cb53a65","formId":21} /-->',
	'id'               => 'form-' . Helper::generate_random_id( 2 ),
	'isPro'            => false,
];
