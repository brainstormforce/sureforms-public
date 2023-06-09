<?php
/**
 * Contact Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'Contact Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column {"layout":{"type":"default"}} -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">Contact Form</h2>
    <!-- /wp:heading -->
    
    <!-- wp:sureforms/input {"required":true,"label":"Full Name"} /-->
    
    <!-- wp:sureforms/input {"required":true,"label":"Email"} /-->
    
    <!-- wp:sureforms/phone {"placeholder":"","help":""} /-->
    
    <!-- wp:sureforms/textarea {"label":"Message:"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/submit /--></div>
    <!-- /wp:column -->',
];
