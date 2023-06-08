<?php
/**
 * PHP render form Rating Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Rating;

use SureForms\Inc\Blocks\Base;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content    Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$id           = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help         = isset( $attributes['ratingBoxHelpText'] ) ? $attributes['ratingBoxHelpText'] : '';
			$width        = isset( $attributes['width'] ) ? $attributes['width'] : '';
			$icon_color   = isset( $attributes['iconColor'] ) ? strval( $attributes['iconColor'] ) : '';
			$show_numbers = isset( $attributes['showNumbers'] ) ? $attributes['showNumbers'] : '';
			$icon_shape   = isset( $attributes['iconShape'] ) ? $attributes['iconShape'] : '';
			$max_value    = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';

			ob_start();
			?>
			<div class="sureforms-rating-container" style="display:flex; flex-direction:column; gap:0.5rem;">
				<label for="sureforms-rating">
					<?php echo esc_html( $label ); ?><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<input type="hidden" class="sureforms-rating-random-id" value="<?php echo esc_attr( $id ); ?>" />
				<input type="hidden" class="sureforms-rating-icon-color-<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $icon_color ); ?>" />
				<?php echo '' !== $help ? '<label for="sureforms-rating" style="color:#ddd;">' . esc_html( $help ) . '</label>' : ''; ?>
				<div style="justify-content: <?php echo 'fullWidth' === $width ? 'space-between' : 'space-evenly'; ?>; display: flex; align-items: center;">
					<?php
					$icon = '';
					switch ( $icon_shape ) {
						case 'star':
							$icon .= '<i class="fa fa-star"></i>';
							break;
						case 'heart':
							$icon .= '<i class="fa fa-heart"></i>';
							break;
						case 'smiley':
							$icon .= '<i class="fa fa-smile"></i>';
							break;
						default:
							$icon .= '<i class="fa fa-star"></i>';

							break;
					}
					?>
					<?php
					for ( $i = 0; $i < $max_value; $i++ ) {
						?>
						<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" class="sureforms-rating-field" value="<?php echo esc_attr( strval( $i + 1 ) ); ?>" id="sureforms-rating-<?php echo esc_attr( $id . '-' . $i ); ?>" 
						type="radio" <?php echo esc_attr( $required && 0 === $i ? 'required' : '' ); ?> />
						<div style="display:flex; flex-direction:column; align-items: center;">
							<label style="color:#ddd; font-size:25px;" class="sureforms-rating-<?php echo esc_attr( $id ); ?>" for="sureforms-rating-<?php echo esc_attr( $id . '-' . $i ); ?>">
								<?php echo wp_kses_post( $icon ); ?>
							</label>
							<div><?php echo esc_html( strval( $show_numbers ? $i : '' ) ); ?></div>
						</div>
					<?php } ?>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}
}
