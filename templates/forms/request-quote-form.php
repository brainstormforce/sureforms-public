<?php
/**
 * Request a Quote Form pattern.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

return [
	'title'      => __( 'Request a Quote Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"placeholder":"First Name","label":"First Name","className":"wp-block-sureforms-input"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"placeholder":"Last Name","label":"Last Name","className":"wp-block-sureforms-input"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/input {"required":true,"placeholder":"Business / Organization","label":"Business / Organization","className":"wp-block-sureforms-input"} /-->
    
    <!-- wp:sureforms/email {"required":true,"placeholder":"Email","name":"input"} /-->
    
    <!-- wp:sureforms/phone {"required":true,"label":"Phone","help":""} /-->
    
    <!-- wp:sureforms/textarea {"required":true,"label":"Request","className":"wp-block-sureforms-textarea"} /-->
    
    <!-- wp:sureforms/submit /-->',
];
