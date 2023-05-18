<?php
/**
 * PHP render form Submit Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Submit;

use SureForms\Inc\Blocks\Base;

/**
 * Submit Block.
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

		if ( ! empty( $attributes ) ) {
			$text             = isset( $attributes['text'] ) ? strval( $attributes['text'] ) : 'Submit';
			$show_total       = isset( $attributes['showTotal'] ) ? $attributes['showTotal'] : '';
			$show_icon        = isset( $attributes['showIcon'] ) ? $attributes['showIcon'] : '';
			$full             = isset( $attributes['full'] ) ? $attributes['full'] : '';
			$button_alignment = isset( $attributes['buttonAlignment'] ) ? $attributes['buttonAlignment'] : '';
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';

			ob_start();
			?>
			<div id="sureforms-submit-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
				<div style="text-align: <?php echo esc_attr( $button_alignment ); ?>">
				<button style="display: flex; gap: 0.4rem; align-items: center; justify-content: center; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" type="submit" value="" id="sureforms-submit-btn">
					<?php if ( $show_icon ) : ?>
						<i class="fa-solid fa-lock"></i>
					<?php endif; ?>
					<?php echo esc_html( $text ); ?>
					<?php if ( $show_total ) : ?>
						<span>$100</span>
					<?php endif; ?>
					<div style="display: none" class="sureforms-loader"></div>
				</button>
				</div>
			</div>
			<?php
		}

		return ob_get_clean();
	}

}
