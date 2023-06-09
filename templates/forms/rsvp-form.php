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
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">RSVP Form</h2>
    <!-- /wp:heading -->
    
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
    
    <!-- wp:sureforms/submit {"text":"Register","buttonAlignment":"left"} /--></div>
    <!-- /wp:column -->',
];
