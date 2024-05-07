<?php
/**
 * GDPR Block.
 *
 * @package SureForms.
 * @since 0.0.2
 */

namespace SRFM\Inc\Blocks\GDPR;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\GDPR_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GDPR Block.
 */
class Block extends Base {
	/**
	 * Render form GDPR block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 * @since 0.0.2
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$markup_class = new GDPR_Markup( $attributes );
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
