<?php
/**
 * Job Application Form pattern.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SRFM/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'title'            => __( 'Job Application Form', 'sureforms' ),
	'info'             => __( 'Form for hiring a Developer', 'sureforms' ),
	'categories'       => [ 'sureforms_form' ],
	'templateCategory' => 'Job Application Forms',
	'postTypes'        => SRFM_FORMS_POST_TYPE,
	'content'          => '<!-- wp:srfm/input {"block_id":"2e456e8d","required":true,"fieldWidth":50,"label":"First Name","formId":24} /--><!-- wp:srfm/input {"block_id":"e0707ee6","required":true,"fieldWidth":50,"label":"Last Name","formId":24} /--><!-- wp:srfm/email {"block_id":"3abce5b5","required":true,"placeholder":"Email Address","fieldWidth":50,"formId":24} /--><!-- wp:srfm/input {"block_id":"3d69c8a5","placeholder":"Ex: 2 years, 3 months","fieldWidth":50,"label":"Relevant Experience","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"2b991181","required":true,"options":[{"optionTitle":"Yes"},{"optionTitle":"No"}],"label":"Have you ever applied before with us","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"76145d91","required":true,"options":[{"optionTitle":" I don’t have knowledge of this (1)"},{"optionTitle":" I know basic concepts only (2)"},{"optionTitle":" I have good knowledge to get the job done (3)"},{"optionTitle":" I have solid experience (4)"},{"optionTitle":" I can do anything anyone asks! (5)"}],"label":"Web Development ","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"93dae67d","required":true,"options":[{"optionTitle":" I don’t have knowledge of this (1)"},{"optionTitle":" I know basic concepts only (2)"},{"optionTitle":" I have good knowledge to get the job done (3)"},{"optionTitle":" I have solid experience (4)"},{"optionTitle":" I can do anything anyone asks! (5)"}],"label":"Servers \u0026amp; Hosting","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"dc58a393","required":true,"options":[{"optionTitle":" I don’t have knowledge of this (1)"},{"optionTitle":" I know basic concepts only (2)"},{"optionTitle":" I have good knowledge to get the job done (3)"},{"optionTitle":" I have solid experience (4)"},{"optionTitle":" I can do anything anyone asks! (5)"}],"label":"JavaScript","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"369764bf","required":true,"options":[{"optionTitle":" I don’t have knowledge of this (1)"},{"optionTitle":" I know basic concepts only (2)"},{"optionTitle":" I have good knowledge to get the job done (3)"},{"optionTitle":" I have solid experience (4)"},{"optionTitle":" I can do anything anyone asks! (5)"}],"label":"PHP","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"934ef92a","required":true,"options":[{"optionTitle":" I don’t have knowledge of this (1)"},{"optionTitle":" I know basic concepts only (2)"},{"optionTitle":" I have good knowledge to get the job done (3)"},{"optionTitle":" I have solid experience (4)"},{"optionTitle":" I can do anything anyone asks! (5)"}],"label":"CSS","formId":24} /--><!-- wp:srfm/multi-choice {"block_id":"3fa5d015","required":true,"options":[{"optionTitle":"Friend"},{"optionTitle":"Linkdin"},{"optionTitle":"Colleague"},{"optionTitle":"Search"}],"label":"Where did you hear about this job opening?","formId":24} /--><!-- wp:srfm/textarea {"block_id":"d3a0fd5e","required":true,"label":"Are you on social media? Please share your (at least 3) profile links.","formId":24} /-->',
	'id'               => 'form-5',
	'isPro'            => false,
];
