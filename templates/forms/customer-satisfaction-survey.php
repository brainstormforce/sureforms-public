<?php
/**
 * Customer Satisfaction Survey.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'Customer Satisfaction Survey', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading -->
    <h2 class="wp-block-heading">Customer Expectations Survey</h2>
    <!-- /wp:heading -->
    
    <!-- wp:sureforms/rating {"id":"blocke32bc06a14314517ad2b7046c9c92776","label":"Please indicate overall satisfaction with the product.","width":"fullWidth","iconColor":"#ffed20"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block1d76a092a223451c9c6ff9248754ebb7","singleSelection":true,"options":["Better than expected","As expected","Did not meet expectations"],"label":"How does the product compare with your expectations overall?","style":"buttons"} /-->
    
    <!-- wp:sureforms/textarea {"id":"blockbfe30f2e3c864d7196cb341f460f0cd5","label":"What do you like best about the product?"} /-->
    
    <!-- wp:sureforms/textarea {"id":"blockf78bd49855d34155a607d4322b06da29","label":"What changes would you make in the product?"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block0ca794f4ba2d482eb37ec4d251619c72","required":false,"singleSelection":true,"options":["Definitely we reccomend.","Probably will reccomend.","Definitely will not reccomend.","Probably will not reccomend."],"label":"How likely will you recomment the product to your friend or colleague?","style":"buttons"} /-->
    
    <!-- wp:heading -->
    <h2 class="wp-block-heading">How does the product compare with your expectations?</h2>
    <!-- /wp:heading -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"id":"blockac914fd77d4840449cf497b5daaa475a","singleSelection":true,"options":["Worse than expected","As expected","Better than expected"],"label":"Easy to use?","style":"buttons"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"id":"block62a1cac8afbb4960b81455403b2df44c","singleSelection":true,"options":["Worse than expected","As expected","Better than expected"],"label":"Quality of the product?","style":"buttons"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"id":"blockebcd91ef15c64d629bd6c8c2e2608a8c","singleSelection":true,"options":["Worse than expected","As expected","Better than expected"],"label":"Comes with clear instructions","style":"buttons"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"id":"blocked2f8cffa2c340d888858001e51e5cf9","singleSelection":true,"options":["Worse than expected","As expected","Better than expected"],"label":"Performs well","style":"buttons"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/submit /--></div>
    <!-- /wp:column -->',
];
