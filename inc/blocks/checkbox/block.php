<?php
/**
 * PHP render for Text Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Checkbox;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Checkbox_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render form checkbox block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|bool
	 */
	public function render( $attributes ) {
		if ( ! empty( $attributes ) ) {
			$markup_class = new Checkbox_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
