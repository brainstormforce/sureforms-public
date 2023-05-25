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
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Contact Form</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"ast-global-color-1","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-ast-global-color-1-color has-alpha-channel-opacity has-ast-global-color-1-background-color has-background is-style-wide"/>
    <!-- /wp:separator --></div>
    <!-- /wp:column -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:separator {"backgroundColor":"ast-global-color-1","className":"is-style-dots"} -->
    <hr class="wp-block-separator has-text-color has-ast-global-color-1-color has-alpha-channel-opacity has-ast-global-color-1-background-color has-background is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/phone {"required":true,"placeholder":"","help":""} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/email {"required":true} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:separator {"backgroundColor":"ast-global-color-1","className":"is-style-dots"} -->
    <hr class="wp-block-separator has-text-color has-ast-global-color-1-color has-alpha-channel-opacity has-ast-global-color-1-background-color has-background is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:sureforms/textarea {"required":true,"label":"Message"} /-->
    
    <!-- wp:sureforms/submit {"text":"Send","buttonAlignment":"right"} /-->',
];
