<?php
/**
 * PHP render form Address Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Address;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Address_Markup;


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
			$form_id      = isset( $attributes['formId'] ) ? Sureforms_Helper::get_integer_value( $attributes['formId'] ) : '';
			$markup_class = new Address_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes, $form_id );
		}
		return ob_get_clean();
	}
}
