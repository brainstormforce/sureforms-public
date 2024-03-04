<?php
/**
 * PHP render form URL Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Url;

use SRFM\Inc\Blocks\SRFM_Base;
use SRFM\Inc\SRFM_Helper;
use SRFM\Inc\Fields\SRFM_Url_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * URL Block.
 */
class SRFM_Block extends SRFM_Base {
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
			$markup_class = new SRFM_Url_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->markup( $attributes );
		}
			return ob_get_clean();
	}
}
