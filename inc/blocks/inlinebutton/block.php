<?php
/**
 * PHP render form Inline Button Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Inlinebutton;

use SRFM\Inc\Blocks\Base;
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
	 * @since 0.0.2
	 */
	public function render( $attributes, $content = '' ) {

		if ( ! empty( $attributes ) ) {
			$markup_class = new Inlinebutton_Markup( $attributes );
			ob_start();
			// phpcs:ignore.
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
