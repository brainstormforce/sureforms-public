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
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">SEO Content Request</h2>
    <!-- /wp:heading -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"id":"blockefef40681ca745ef89fcb13cf0a93171","required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"id":"block8b842d439c5e4101aea5a8dd39e27908","required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/email {"id":"block379e711b0f294e33835ab8f3cd1841c3","required":true} /-->
    
    <!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">Define Your Audience</h2>
    <!-- /wp:heading -->
    
    <!-- wp:sureforms/multi-choice {"id":"blockece7ab6678df4eb0a4c7baa6558c7b27","required":true,"options":["0-17","18-24","25-34","35-44","45-54","55-64","65 or above"],"label":"Age","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block23d3a2b77b1649d795e88d005e7ccfa7","required":true,"singleSelection":true,"options":["Male","Female","Both"],"label":"Gender","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"blockcb3be6530ab046e59bce59d935ad610a","required":true,"options":["Not important","High school","Some college","Bachelors degree","Graduate degree"],"label":"Education","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block71bd1fdd48164d5987c03d9dfae935a6","required":true,"options":["$0-$24,999","$25,000-$49,999","$5,000-$74,999","$75,000-$99,999","$100,000-$149,999","$150,000 or above"],"label":"Household income","style":"buttons"} /-->
    
    <!-- wp:sureforms/dropdown {"id":"block4dfae418a13c4885bc9b52140e9034f6","options":["Please Select","Arts \u0026 Entertainment","Autos \u0026 Vehicles","Beauty \u0026 Fitness","Books \u0026 Literature","Business \u0026 Industrial","Computer \u0026 Electronics","Finance","Food \u0026 Drinks","Games","Hobbies \u0026 Leisure","Home \u0026 Garden","Internet \u0026 Telecom","Jobs \u0026 Education","Law \u0026 Government","News","Online Communities","People \u0026 Society","Pets \u0026 Animals","Real Estate","Refernce","Science","Shopping","Sports","Travel"],"label":"Audience interested?"} /-->
    
    <!-- wp:sureforms/input {"id":"block145d19b496f64e89ba523f4b6db8a750","required":true,"label":"Website URL"} /-->
    
    <!-- wp:sureforms/textarea {"id":"block83b63b8b301c4d3e826881d53a60d255","label":"Website description"} /-->
    
    <!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">Content Length &amp; Delivery Time</h2>
    <!-- /wp:heading -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"id":"blocka2423f120c9645a78a012ed6f32adddf","required":true,"label":"Day"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"id":"block8f13495b51064a71b7e04a5bdc111988","required":true,"label":"Month"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"id":"blocke1ee868d1836478d948d913625854143","required":true,"label":"Year"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/multi-choice {"id":"block0b432a784b2c4ff8a70e8dffd47fd93d","required":true,"options":["500 words - $24.99","1000 words - $44.99","1500 words - $74.99"],"label":"Length of content","style":"buttons"} /-->
    
    <!-- wp:sureforms/submit {"text":"Submit \u0026  Pay","full":true} /--></div>
    <!-- /wp:column -->',
];
