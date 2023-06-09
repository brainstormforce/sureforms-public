<?php
/**
 * IT Service Ticket Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'IT Service Ticket Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading -->
    <h2 class="wp-block-heading">IT Service Ticket</h2>
    <!-- /wp:heading -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/email {"required":true} /-->
    
    <!-- wp:sureforms/input {"required":true,"label":"Computer ID"} /-->
    
    <!-- wp:sureforms/textarea {"label":"Probelm description"} /-->
    
    <!-- wp:sureforms/upload {"label":"Upload Screenshots"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/submit /--></div>
    <!-- /wp:column -->',
];
