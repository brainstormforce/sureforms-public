<?php
/**
 * PHP render form Number Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Number;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Number_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Address Block.
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
			$markup_class = new Number_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
