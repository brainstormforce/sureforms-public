<?php
/**
 * PHP render form Multichoice Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Multichoice;

use SureForms\Inc\Blocks\Base;

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
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : array();
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			ob_start();
			?>
		<div class="sureform-multi-choice-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label for="sureform-multi-choice"><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
				<?php foreach ( $options as $i => $option ) : ?>
					<div style="display: flex; align-items: center;">
						<input
							name="<?php echo esc_attr( str_replace( ' ', '_', $label ) ); ?>"
							style="display: <?php echo esc_attr( 'buttons' === $style ? 'none' : 'inherit' ); ?>"
							id="sureform-multi-choice"
							type="<?php echo esc_attr( $single_selection ? 'radio' : 'checkbox' ); ?>"
							name="<?php echo esc_attr( $single_selection ? 'radio' : '' ); ?>"
							onclick="<?php echo esc_attr( 'handleClick(' . $i . ');' ); ?>"
						/>
						<label
							for="sureform-multi-choice"
							style="
							<?php
							echo esc_attr( 'buttons' === $style ? 'border: 1px solid black; border-radius: 10px; padding: .5rem 1rem .5rem 1rem; width: 100%; background-color: ' : '' );
							?>
							"
						>
							<span
								class="multi-choice-option"
								id="multi-choice-option'"
							>
								<?php echo esc_html( $option ); ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
				<?php echo '' !== $help ? '<label for="sureform-multi-choice" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
