<?php
/**
 * Blank Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms/Templates/Forms
 * @author     SureForms <https://sureforms.com/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Blank Form', 'sureforms' ),
	'slug'             => 'blank-form',
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Basic', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '',
	'isPro'            => false,
];
