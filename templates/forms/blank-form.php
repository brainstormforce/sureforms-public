<?php
/**
 * Blank Form pattern.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     SureCart <https://surecart.com/>
 */

return [
	'title'      => __( 'Blank Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:column -->
		<div class="wp-block-column"></div>
	<!-- /wp:column -->',

];
