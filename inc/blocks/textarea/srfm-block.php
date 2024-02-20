<?php
/**
 * PHP render form Textarea Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Textarea;

use SRFM\Inc\Blocks\SRFM_Base;
use SRFM\Inc\SRFM_Helper;
use SRFM\Inc\Fields\SRFM_Textarea_Markup;

/**
 * Address Block.
 */
class SRFM_Block extends SRFM_Base {
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
			$form_id      = isset( $attributes['formId'] ) ? SRFM_Helper::get_integer_value( $attributes['formId'] ) : '';
			$markup_class = new SRFM_Textarea_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->default( $attributes, $form_id );
		}
		return ob_get_clean();
	}
}
