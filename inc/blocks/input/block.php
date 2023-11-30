<?php
/**
 * PHP render form Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Input;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Input_Markup;

/**
 * Address Block.
 */
class Block extends Base {

	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$markup_class = new Input_Markup();
			ob_start();
			$markup_class->classic_styling( $attributes );
		}
		return ob_get_clean();
	}
}
