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
		$random_id = wp_rand( 1, 100 );
		if ( ! empty( $attributes ) ) {
			$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help         = isset( $attributes['ratingBoxHelpText'] ) ? $attributes['ratingBoxHelpText'] : '';
			$width        = isset( $attributes['width'] ) ? $attributes['width'] : '';
			$icon_color   = isset( $attributes['iconColor'] ) ? $attributes['iconColor'] : '';
			$show_numbers = isset( $attributes['showNumbers'] ) ? $attributes['showNumbers'] : '';
			$icon_shape   = isset( $attributes['iconShape'] ) ? $attributes['iconShape'] : '';
			$max_value    = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';

			ob_start();
			?>
			<div class="sureform-rating-container" style="display:flex; flex-direction:column; gap:0.5rem;">
				<label for="sureform-rating">
					<?php echo esc_html( $label ); ?><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<?php echo '' !== $help ? '<label for="sureform-rating" style="color:#ddd;">' . esc_html( $help ) . '</label>' : ''; ?>
				<div style="justify-content: <?php echo 'fullWidth' === $width ? 'space-between' : 'space-evenly'; ?>; display: flex; align-items: center;">
					<?php
					$icon = '';
					switch ( $icon_shape ) {
						case 'star':
							$icon .= '<i class="fa-regular fa-star"></i>';
							break;
						case 'heart':
							$icon .= '<i class="fa-regular fa-heart"></i>';
							break;
						case 'smiley':
							$icon .= '<i class="fa-regular fa-smile"></i>';
							break;
						default:
							$icon .= '<i class="fa-regular fa-star"></i>';
							break;
					}
					for ( $i = 0; $i < $max_value; $i++ ) {
						?>
						<input id="sureform-rating-<?php echo esc_attr( $random_id . '-' . $i ); ?>" type="radio" required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" />
						<label for="sureform-rating-<?php echo esc_attr( $random_id . '-' . $i ); ?>">
							<?php echo wp_kses_post( $icon ); ?>
						</label>
					<?php } ?>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}
}
