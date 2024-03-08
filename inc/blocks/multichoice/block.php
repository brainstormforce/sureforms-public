<?php
/**
 * PHP render form Multichoice Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Multichoice;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\SRFM_Helper;
use SRFM\Inc\Fields\SRFM_Multichoice_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Multichoice Block.
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
			$form_id      = isset( $attributes['formId'] ) ? SRFM_Helper::get_integer_value( $attributes['formId'] ) : '';
			$markup_class = new SRFM_Multichoice_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes, $form_id );
		}
			return ob_get_clean();
	}
}
