<?php
/**
 * PHP render form Textarea Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Textarea;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Textarea_Markup;

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
			$markup_class = new Textarea_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
