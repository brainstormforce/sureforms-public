<?php
/**
 * Sureforms Hidden Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Hidden Markup Class.
 *
 * @since 0.0.1
 */
class Hidden_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms hidden default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$default  = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';

		return '<div class="srfm-main-container">
            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" value="' . esc_attr( $default ) . '" type="hidden" class="srfm-hidden-input">     
        </div>';

	}
}
