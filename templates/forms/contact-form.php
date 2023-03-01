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
	'content'    => '<!-- wp:sureforms/input {"label":"Name"} -->
	<sc-input label="Name" name="input" placeholder="" size="medium" value="" class="wp-block-sureforms-input"></sc-input>
	<!-- /wp:sureforms/input -->
	
	<!-- wp:sureforms/email {"name":"input"} /-->
	
	<!-- wp:sureforms/textarea {"required":true,"label":"Message"} -->
	<sc-textarea label="Message" maxlength="500" name="textarea" placeholder="" size="medium" value="" required class="wp-block-sureforms-textarea"></sc-textarea>
	<!-- /wp:sureforms/textarea -->
	
	<!-- wp:sureforms/textarea {"label":"Additional Notes","name":"input"} -->
	<sc-textarea label="Additional Notes" maxlength="500" name="input" placeholder="" size="medium" value="" class="wp-block-sureforms-textarea"></sc-textarea>
	<!-- /wp:sureforms/textarea -->',
];
