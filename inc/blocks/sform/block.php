<?php
/**
 * PHP render form Sureforms_Form Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Sform;

use WP_REST_Response;
use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Generate_Form_Markup;

/**
 * Sureforms_Form Block.
 */
class Block extends Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|false
	 */
	public function render( $attributes, $content = '' ) {
		$id = isset( $attributes['id'] ) ? $attributes['id'] : '';

		if ( empty( $id ) ) {
			return '';
		}

		$form = get_post( absint( $id ) );

		if ( ! $form || SUREFORMS_FORMS_POST_TYPE !== $form->post_type ) {
			return '';
		}

		if ( 'publish' !== $form->post_status || ! empty( $form->post_password ) ) {
			return '';
		}

		$content = Generate_Form_Markup::get_form_markup( absint( $id ) );

		return $content;
	}

}
