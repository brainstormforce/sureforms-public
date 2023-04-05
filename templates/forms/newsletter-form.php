<?php
/**
 * Newsletter Form pattern.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

return [
	'title'      => __( 'Newsletter Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:sureforms/form -->
    <!-- wp:sureforms/form -->
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"label":"First Name","name":"First Name"} -->
    <sc-input label="First Name" name="First Name" placeholder="" size="medium" value="" class="wp-block-sureforms-input"></sc-input>
    <!-- /wp:sureforms/input --></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"label":"Last Name","name":"Last Name"} -->
    <sc-input label="Last Name" name="Last Name" placeholder="" size="medium" value="" class="wp-block-sureforms-input"></sc-input>
    <!-- /wp:sureforms/input --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/email {"name":"input"} /-->
    <!-- /wp:sureforms/form -->
    <!-- /wp:sureforms/form -->',

];
