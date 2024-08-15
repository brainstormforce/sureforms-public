<?php
/**
 * Feedback Form / Survey Form pattern.
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
	'title'            => __( 'Feedback Form / Survey Form', 'sureforms' ),
	'slug'             => 'survey-form',
	'info'             => __( 'Form for conducting surveys', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => __( 'Survey', 'sureforms' ),
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/advanced-heading {"block_id":"a9e4f8ad","headingTitle":"Customer Survey","headingDescToggle":true,"headingDesc":"Please take a moment to complete a short survey and let us know how much you like our service.","subHeadingColor":"#575757","headingTag":"h3","headSpace":12,"subHeadSpace":20,"subHeadFontSize":18} /--><!-- wp:srfm/input {"block_id":"c7894ce2","required":true,"label":"Name","formId":82} /--><!-- wp:srfm/email {"block_id":"82ea2785","required":true,"formId":82} /--><!-- wp:srfm/multi-choice {"block_id":"3a7ef9dd","required":true,"options":[{"optionTitle":"Food was great"},{"optionTitle":"Staff service"},{"optionTitle":"Location was great"},{"optionTitle":"Something else"}],"label":"What did you like about our lodge?","formId":82} /--><!-- wp:srfm/textarea {"block_id":"7046569e","label":"Any Comment","formId":82} /-->',
	'isPro'            => false,
];
