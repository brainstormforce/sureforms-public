<?php
/**
 * PHP render form Phone Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Phone;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Fields\Phone_Markup;

/**
 * Phone Block.
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
		$upload_dir = wp_upload_dir();

		if ( ! empty( $attributes ) ) {
			$form_id      = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$markup_class = new Phone_Markup();
			ob_start();
			// phpcs:ignore
			echo $markup_class->default( $attributes, $form_id  );
		}
		return ob_get_clean();
	}
}
