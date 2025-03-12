<?php
/**
 * PHP render form dropdown Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Dropdown;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Dropdown_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Dropdown Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|bool
	 */
	public function render( $attributes ) {
		if ( ! empty( $attributes ) ) {
			$markup_class = new Dropdown_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
