<?php
/**
 * Information Request Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'Information Request Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Information Request Form</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"cyan-bluish-gray","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-cyan-bluish-gray-color has-alpha-channel-opacity has-cyan-bluish-gray-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/email {"required":true} /-->
    
    <!-- wp:sureforms/phone {"required":false,"placeholder":"","help":""} /-->
    
    <!-- wp:sureforms/textarea {"label":"Request information regarding?"} /-->
    
    <!-- wp:sureforms/submit {"text":"Request Info!","full":true,"buttonAlignment":"left"} /--></div>
    <!-- /wp:column -->',
];
