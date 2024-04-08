<?php
/**
 * PHP render form Text Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Inlinebutton;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Helper;
use SRFM\Inc\Fields\Inlinebutton_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Inline Button Block.
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

		// $id            = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		// $is_page_break = Helper::get_meta_value( $id, '_srfm_is_page_break' );

		// if ( '1' === $is_page_break ) {
		// 	return '';
		// }

		if ( ! empty( $attributes ) ) {
			$markup_class = new Inlinebutton_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes );
		}
		return ob_get_clean();
	}
}
