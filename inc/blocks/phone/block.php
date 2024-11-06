<?php
/**
 * PHP render form Phone Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Phone;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Phone_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Phone Block.
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
			$markup_class = new Phone_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
