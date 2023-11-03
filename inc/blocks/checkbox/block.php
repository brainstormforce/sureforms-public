<?php
/**
 * PHP render for Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Checkbox;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Checkbox_Markup;


/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render form checkbox block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$styling      = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_form_styling', true ) ) : '';
			$markup_class = new Checkbox_Markup();
			ob_start();
			switch ( $styling ) {
				case 'inherit':
					// phpcs:ignore
					echo $markup_class->default_styling( $attributes );
					break;
				case 'classic':
					// phpcs:ignore
					echo $markup_class->classic_styling( $attributes );
					break;
				default:
					// phpcs:ignore
					echo $markup_class->default_styling( $attributes );
					break;
			}
		}
		return ob_get_clean();
	}
}
