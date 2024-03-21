<?php
/**
 * PHP render form URL Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Url;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Helper;
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
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {

		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? Helper::get_integer_value( $attributes['formId'] ) : '';
			$markup_class = new Url_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes, $form_id  );
		}
			return ob_get_clean();
	}
}
