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
			$markup_class = new Number_Slider_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->classic_styling( $attributes, $form_id );
		}
			return ob_get_clean();
	}
}
