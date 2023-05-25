<?php
/**
 * SEO Request Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'SEO Request Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">SEO Content Request</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"luminous-vivid-orange","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-luminous-vivid-orange-color has-alpha-channel-opacity has-luminous-vivid-orange-background-color has-background is-style-wide"/>
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
    
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Define Your Audience</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"luminous-vivid-orange","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-luminous-vivid-orange-color has-alpha-channel-opacity has-luminous-vivid-orange-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["0-17","18-24","25-34","35-44","45-54","55-64","65 or above"],"label":"Age"} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Male","Female","Both"],"label":"Gender"} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["Not important","High school","Some college","Bachelors degree","Graduate degree"],"label":"Education"} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["$0-$24,999","$25,000-$49,999","$5,000-$74,999","$75,000-$99,999","$100,000-$149,999","$150,000 or above"],"label":"Household income"} /-->
    
    <!-- wp:sureforms/dropdown {"options":["Please Select","Arts \u0026 Entertainment","Autos \u0026 Vehicles","Beauty \u0026 Fitness","Books \u0026 Literature","Business \u0026 Industrial","Computer \u0026 Electronics","Finance","Food \u0026 Drinks","Games","Hobbies \u0026 Leisure","Home \u0026 Garden","Internet \u0026 Telecom","Jobs \u0026 Education","Law \u0026 Government","News","Online Communities","People \u0026 Society","Pets \u0026 Animals","Real Estate","Refernce","Science","Shopping","Sports","Travel"],"label":"Audience interested?"} /-->
    
    <!-- wp:sureforms/input {"required":true,"label":"Website URL"} /-->
    
    <!-- wp:sureforms/textarea {"label":"Website description"} /-->
    
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Content Length &amp; Delivery Time</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"luminous-vivid-orange","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-luminous-vivid-orange-color has-alpha-channel-opacity has-luminous-vivid-orange-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"required":true,"label":"Day"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"required":true,"label":"Month"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"required":true,"label":"Year"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["500 words - $24.99","1000 words - $44.99","1500 words - $74.99"],"label":"Length of content"} /-->
    
    <!-- wp:sureforms/submit {"text":"Submit \u0026  Pay","full":true} /--></div>
    <!-- /wp:column -->',
];
