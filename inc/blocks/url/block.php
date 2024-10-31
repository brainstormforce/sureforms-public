<?php
/**
 * PHP render form URL Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Url;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Url_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * URL Block.
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
			$markup_class = new Url_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
			return ob_get_clean();
	}
}
