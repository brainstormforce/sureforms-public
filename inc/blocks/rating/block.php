<?php
/**
 * PHP render form Rating Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Rating;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

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
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id           = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help         = isset( $attributes['ratingBoxHelpText'] ) ? $attributes['ratingBoxHelpText'] : '';
			$width        = isset( $attributes['width'] ) ? $attributes['width'] : '';
			$icon_color   = isset( $attributes['iconColor'] ) ? $sureforms_helper_instance->get_string_value( $attributes['iconColor'] ) : '';
			$show_numbers = isset( $attributes['showNumbers'] ) ? $attributes['showNumbers'] : '';
			$icon_shape   = isset( $attributes['iconShape'] ) ? $attributes['iconShape'] : '';
			$max_value    = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
			$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname    = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start();
			?>
			<div class="sureforms-rating-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<label class="text-primary">
					<?php echo esc_html( $label ); ?><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<input type="hidden" class="sureforms-rating-random-id" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $id ); ?>" />
				<input type="hidden" class="sureforms-rating-icon-color-<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $icon_color ); ?>" />
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
						<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" class="sureforms-rating-field" value="<?php echo esc_attr( $sureforms_helper_instance->get_string_value( $i + 1 ) ); ?>" id="sureforms-rating-<?php echo esc_attr( $id . '-' . $i ); ?>" 
						type="radio" area-required=<?php echo esc_attr( $required && 0 === $i ? 'true' : 'false' ); ?> />
						<div style="display:flex; flex-direction:column; align-items: center;">
							<label color-data="#ddd" style="color:#ddd; font-size:25px;" class="sureforms-rating-<?php echo esc_attr( $id ); ?>" for="sureforms-rating-<?php echo esc_attr( $id . '-' . $i ); ?>">
								<?php echo wp_kses_post( $icon ); ?>
							</label>
							<div><?php echo esc_html( $sureforms_helper_instance->get_string_value( $show_numbers ? $i + 1 : '' ) ); ?></div>
						</div>
					<?php } ?>
				</div>
				<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			</div>
			<?php
		}
		return ob_get_clean();
	}
}
