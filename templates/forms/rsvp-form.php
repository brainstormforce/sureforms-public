<?php
/**
 * RSVP Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'RSVP Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">RSVP Forms</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"vivid-cyan-blue","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-vivid-cyan-blue-color has-alpha-channel-opacity has-vivid-cyan-blue-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/phone {"required":true,"placeholder":"","help":""} /-->
    
    <!-- wp:sureforms/dropdown {"options":["Please Select","1","2"],"label":"Number of people accompanying you."} /-->
    
    <!-- wp:sureforms/textarea {"label":"List their names"} /-->
    
    <!-- wp:sureforms/submit {"text":"Register","buttonAlignment":"center"} /--></div>
    <!-- /wp:column -->',
];
