<?php
/**
 * PHP render form Date & Time Picker Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Datetimepicker;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Datetimepicker_Markup;

/**
 * Date Time Picker Block.
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
			$markup_class = new Datetimepicker_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->classic_styling( $attributes );
		}
			return ob_get_clean();
	}
}
