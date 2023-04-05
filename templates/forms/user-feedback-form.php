<?php
/**
 * User Feedback Form pattern.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

return [
	'title'      => __( 'User Feedback Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:sureforms/form -->
	<!-- wp:sureforms/form -->
	<!-- wp:column -->
	<div class="wp-block-column"><!-- wp:sureforms/email /--></div>
	<!-- /wp:column -->
	
	<!-- wp:sureforms/rating {"ratingBoxLabel":"Rating","ratingBoxMessage":"Please leave a rating for our team","width":"fullWidth","iconColor":"#ffdd19"} /-->
	<!-- /wp:sureforms/form -->
	<!-- /wp:sureforms/form -->
    ',

];
