<?php
/**
 * PHP render form Sureforms_Form Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Sform;

use SureForms\Inc\Blocks\Base;

/**
 * Sureforms_Form Block.
 */
class Block extends Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( empty( $attributes['id'] ) ) {
			return '';
		}

		$form = get_post( absint( $attributes['id'] ) );

		if ( ! $form || SUREFORMS_FORMS_POST_TYPE !== $form->post_type ) {
			return '';
		}

		if ( 'publish' !== $form->post_status || ! empty( $form->post_password ) ) {
			return '';
		}

		$result = do_blocks( $form->post_content );

		return '<div>' . $result . '</div>';
	}

}
