<?php
/**
 * Newsletter Form pattern.
 *
 * @link       https://surecart.com
 * @since      0.0.1
 * @package    SRFM/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Newsletter Form', 'sureforms' ),
	'info'             => __( 'Creates a Newsletter Form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Newsletter Forms', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"3f513e23","fieldWidth":50,"label":"Name","formId":3697} /--><!-- wp:srfm/email {"block_id":"6ef07308","fieldWidth":50,"label":"Your Email","formId":3697} /-->',
	'id'               => 'form-3',
	'isPro'            => true,
];
