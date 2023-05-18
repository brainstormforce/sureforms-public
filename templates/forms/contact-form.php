<?php
/**
 * Contact Form pattern.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

return [
	'title'      => __( 'Contact Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:sureforms/input {"label":"Name","className":"wp-block-sureforms-input"} /-->
	
	<!-- wp:sureforms/email {"name":"input"} /-->
	
	<!-- wp:sureforms/textarea {"required":true,"label":"Message","className":"wp-block-sureforms-textarea"} /-->
	
	<!-- wp:sureforms/textarea {"label":"Additional Notes","className":"wp-block-sureforms-textarea"} /-->

	<!-- wp:sureforms/submit /-->
	',
];
