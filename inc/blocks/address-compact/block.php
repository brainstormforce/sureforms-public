<?php
/**
 * PHP render form Address Compact Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Address_Compact;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Address_Compact_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Address Compact Block.
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

		if ( ! empty( $attributes ) ) {
			$markup_class = new Address_Compact_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
