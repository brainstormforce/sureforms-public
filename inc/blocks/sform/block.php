<?php
/**
 * PHP render form Sureforms_Form Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Sform;

use WP_REST_Response;
use SRFM\Inc\Blocks\SRFM_Base;
use SRFM\Inc\SRFM_Generate_Form_Markup;

/**
 * Sureforms_Form Block.
 */
class SRFM_Block extends SRFM_Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|false
	 */
	public function render( $attributes, $content = '' ) {
		$id           = isset( $attributes['id'] ) ? $attributes['id'] : '';
		$sf_classname = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$hide_title_current_page = isset( $attributes['hideTitle'] ) ? $attributes['hideTitle'] : '';

		if ( empty( $id ) ) {
			return '';
		}

		$form = get_post( absint( $id ) );

		if ( ! $form || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
			return '';
		}

		if ( 'publish' !== $form->post_status || ! empty( $form->post_password ) ) {
			return '';
		}

		$content = SRFM_Generate_Form_Markup::get_form_markup( absint( $id ), $hide_title_current_page, $sf_classname );

		return $content;
	}

}
