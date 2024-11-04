<?php
/**
 * PHP render form Sureforms_Form Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Sform;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms_Form Block.
 */
class Block extends Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|false
	 */
	public function render( $attributes ) {
		$id = isset( $attributes['id'] ) ? Helper::get_integer_value( $attributes['id'] ) : '';

		if ( empty( $id ) ) {
			return '';
		}

		$sf_classname            = $attributes['className'] ?? '';
		$show_title_current_page = $attributes['showTitle'] ?? true;

		$form = get_post( $id );

		if ( ! $form || SRFM_FORMS_POST_TYPE !== $form->post_type || 'publish' !== $form->post_status || ! empty( $form->post_password ) ) {
			return '';
		}

		return Generate_Form_Markup::get_form_markup( $id, $show_title_current_page, $sf_classname );
	}

}
