<?php
/**
 * Payment History Block.
 *
 * Server-side rendering for the Payment History Gutenberg block.
 * Delegates to the Payment_History_Shortcode class for output.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Blocks\Payment_History;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Payments\Payment_History_Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Payment History Block.
 *
 * @since x.x.x
 */
class Block extends Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since x.x.x
	 * @return string
	 */
	public function render( $attributes ) {
		$shortcode_atts = [
			'per_page'          => isset( $attributes['perPage'] ) && is_numeric( $attributes['perPage'] ) ? strval( absint( $attributes['perPage'] ) ) : '10',
			'show_subscription' => ! empty( $attributes['showSubscription'] ) ? 'true' : 'false',
			'show_renewal'      => ! empty( $attributes['showRenewal'] ) ? 'true' : 'false',
		];

		$shortcode_instance = Payment_History_Shortcode::get_instance();
		return $shortcode_instance->render( $shortcode_atts );
	}
}
