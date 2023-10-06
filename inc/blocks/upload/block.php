<?php
/**
 * PHP render form upload Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Upload;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\SureForms_Upload_Markup;

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
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$form_id = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$styling = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ) : '';
			ob_start();
			switch ( 'classic' ) {
				case 'inherit':
					// phpcs:ignore
					echo SureForms_Upload_Markup::upload_default_styling( $attributes );
					break;
				case 'classic':
					// phpcs:ignore
					echo SureForms_Upload_Markup::upload_classic_styling( $attributes );
					break;
				default:
					// phpcs:ignore
					echo SureForms_Upload_Markup::upload_default_styling( $attributes );
					break;
			}
		}
			return ob_get_clean();
	}
}
