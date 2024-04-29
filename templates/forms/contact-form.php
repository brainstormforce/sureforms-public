<?php
/**
 * Contact Form pattern.
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
	'title'            => __( 'Contact Form', 'sureforms' ),
	'slug'             => 'contact-form',
	'info'             => __( 'A basic Contact Form', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Basic', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/advanced-heading {"block_id":"88614146","headingTitle":"Contact us","headingDescToggle":true,"headingDesc":"Use this form to contact our team. We usually respond within 24 hours but it can take longer on weekends and around public holidays.","headSpace":16,"subHeadSpace":24,"subHeadFontWeight":"400","subHeadFontSize":18} /--><!-- wp:srfm/input {"block_id":"e8a489f7","required":true,"label":"Name","formId":79} /--><!-- wp:srfm/email {"block_id":"a5728450","required":true,"formId":79} /--><!-- wp:srfm/input {"block_id":"9ec2463e","required":true,"label":"Subject","formId":79} /--><!-- wp:srfm/textarea {"block_id":"4afb9556","required":true,"label":"Message","formId":79} /-->',
	'isPro'            => false,
];
