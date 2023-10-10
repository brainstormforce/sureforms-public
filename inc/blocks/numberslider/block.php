<?php
/**
 * PHP render form Number Slider Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Numberslider;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Number_Slider_Markup;

/**
 * Number Slider Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 * @since 0.0.1
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$styling      = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ) : '';
			$markup_class = new Number_Slider_Markup();
			ob_start();
			switch ( 'classic' ) {
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
