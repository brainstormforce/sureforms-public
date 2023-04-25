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
	'content'    => '<!-- wp:column -->
	<div class="wp-block-column"><!-- wp:sureforms/email /--></div>
	<!-- /wp:column -->
	
	<!-- wp:sureforms/rating {"width":"fullWidth","iconColor":"#ffdd19"} /-->',

];
