<?php
/**
 * Contact Form pattern.
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
	'title'            => __( 'Contact Form', 'sureforms' ),
	'info'             => __( 'A basic Contact Form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Basic Forms', 'sureforms' ),
	'postTypes'        => SUREFORMS_FORMS_POST_TYPE,
	'content'          => '<!-- wp:sureforms/input {"block_id":"e8a489f7","required":true,"label":"Name","formId":17} /--><!-- wp:sureforms/email {"block_id":"a5728450","required":true,"formId":17} /--><!-- wp:sureforms/input {"block_id":"9ec2463e","required":true,"label":"Subject","formId":17} /--><!-- wp:sureforms/textarea {"block_id":"4afb9556","required":true,"label":"Message","formId":17} /-->',
	'id'               => 'form-2',
	'isPro'            => false,
];
