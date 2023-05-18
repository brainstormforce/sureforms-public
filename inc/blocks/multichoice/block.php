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
		<div class="sureforms-multi-choice-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input type="hidden" value="<?php echo esc_attr( $single_selection ); ?>" id="sureforms-multi-choice-selection" />
			<input type="hidden" value="<?php echo esc_attr( $style ); ?>" id="sureforms-multi-choice-style" />
			<input class="sureforms-multi-choice" name="<?php echo esc_attr( $single_selection ? str_replace( ' ', '_', $label ) : str_replace( ' ', '_', $label ) ); ?>" type="hidden" value="">
				<?php foreach ( $options as $i => $option ) : ?>
					<div style="display: flex; align-items: center;">
						<input
							style="display: <?php echo esc_attr( 'buttons' === $style ? 'none' : 'inherit' ); ?>"
							class="sureforms-multi-choice"
							id="sureforms-multi-choice-<?php echo esc_attr( $i ); ?>"
							type="<?php echo esc_attr( $single_selection ? 'radio' : 'checkbox' ); ?>"
							<?php echo esc_attr( $single_selection ? 'name="radio"' : '' ); ?>
						/>
						<label
							class="sureforms-multi-choice-label"
							for="sureforms-multi-choice-<?php echo esc_attr( $i ); ?>"
							style="
							<?php
							echo esc_attr( 'buttons' === $style ? 'cursor: pointer; border: 1px solid black; border-radius: 10px; padding: .5rem 1rem .5rem 1rem; width: 100%; ' : '' );

							?>
							"
						>
							<span
								class="multi-choice-option"
								id="multi-choice-option"
							>
								<?php echo esc_html( $option ); ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
				<?php echo '' !== $help ? '<label for="sureforms-multi-choice" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
