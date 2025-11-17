<?php
/**
 * PHP render form Payment Block.
 *
 * @package SureForms.
 * @since 2.0.0
 */

namespace SRFM\Inc\Blocks\Payment;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Fields\Payment_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Payment Block.
 *
 * @since 2.0.0
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|bool
	 * @since 2.0.0
	 */
	public function render( $attributes ) {
		if ( ! empty( $attributes ) ) {
			$markup_class = new Payment_Markup( $attributes );
			ob_start();
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $markup_class->markup();
		}
		return ob_get_clean();
	}
}
