<?php
/**
 * PHP render form Address Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Address;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Address_Markup;

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
	 * @param string       $content Post content.
	 *
	 * @return string|bool
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$markup_class = new Address_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $content );
		}
		return ob_get_clean();
	}
}
