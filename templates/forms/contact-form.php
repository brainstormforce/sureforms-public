<?php
/**
 * Contact Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'            => __( 'Contact Form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Basic Forms', 'sureforms' ),
	'postTypes'        => SUREFORMS_FORMS_POST_TYPE,
	'content'          => '<!-- wp:sureforms/input {"id":"block1e339f805adb42acadb2ab77ddd7c5b3","required":true,"label":"Full Name"} /--><!-- wp:sureforms/email {"id":"blockdbc94679f6ea4491a174ed78666f3ddc","required":true} /--><!-- wp:sureforms/phone {"id":"block36db0d7999b44bd788aaed4cab09cf80","placeholder":"","help":""} /--><!-- wp:sureforms/textarea {"id":"block0c2968c28c1e4e08bed54209feb757b4","label":"Message:"} /-->',
	'id'               => 'form-2',
];
