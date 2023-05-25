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
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">IT Service Ticket</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"style":{"color":{"background":"#00d184b5"}},"className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background is-style-wide" style="background-color:#00d184b5;color:#00d184b5"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Full Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/email {"required":true} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Department"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Computer ID"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/textarea {"required":true,"label":"Explain your issue"} /-->
    
    <!-- wp:sureforms/upload {"label":"Attach Screenshots"} /-->
    
    <!-- wp:sureforms/submit {"text":"Send Request","buttonAlignment":"right"} /--></div>
    <!-- /wp:column -->',
];
