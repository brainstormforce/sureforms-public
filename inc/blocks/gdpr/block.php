<?php
/**
 * GDPR Block.
 *
 * @package SureForms.
 * @since x.x.x
 */

namespace SRFM\Inc\Blocks\GDPR;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Helper;
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
	 * @since x.x.x
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? Helper::get_integer_value( $attributes['formId'] ) : '';
			$markup_class = new GDPR_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes, $form_id );
		}
		return ob_get_clean();
	}
}
