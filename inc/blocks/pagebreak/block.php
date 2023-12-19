<?php
/**
 * PHP render form Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Pagebreak;

use SureForms\Inc\Blocks\Base;

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
			$block_id = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			ob_start(); ?>
			<div class="srfm-page-break">
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
