<?php
/**
 * PHP render for Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Checkbox;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render form checkbox block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id        = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help      = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
			$label_url = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
			$checked   = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-checkbox-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<div>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> >
				<span class="sf-text-primary">
					<label for="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" class="sf-text-primary">
						<?php
							echo $label_url
							? '<a target="_blank" href="' . esc_url( $label_url ) . '" style="text-decoration:none;">' . esc_html( $label ) . '</a>'
							: esc_html( $label );
						?>
						<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
					</label>
				</span>
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-checkbox" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
		<!-- classic layout -->
		<div class="sureforms-checkbox-container main-container sf-classic-inputs-holder">
			<div class="!relative !flex !items-start !flex-row !gap-2">
				<div class="!flex !h-6 !items-center">
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> class="!h-4 !w-4 !rounded !border-[#d1d5db] sureforms-classic-checkbox-input">
				</div>
				<div class="text-sm leading-6">
					<label for="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" class="sf-classic-label-text">
						<?php
							echo $label_url
							? '<a target="_blank" href="' . esc_url( $label_url ) . '" style="text-decoration:none;" class="!underline">' . esc_html( $label ) . '</a>'
							: esc_html( $label );
						?>
						<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?></label>
				</div>
			</div>
			<p for="sureforms-checkbox" class="text-sm <?php echo '' !== $help ? '!mt-2' : ''; ?> text-gray-500"><?php echo '' !== $help ? esc_html( $help ) : ''; ?></p>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
