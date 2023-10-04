<?php
/**
 * PHP render form Number Slider Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Numberslider;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Number Slider Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 * @since 0.0.1
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id                 = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
			$max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
			$step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
			$value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
			$error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-number-slider-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label class="text-primary" for="sureforms-number-slider-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-number-slider-<?php echo esc_attr( $id ); ?>" type="range" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> 
			min="<?php echo ( intval( $min ) ); ?>" max="<?php echo ( intval( $max ) ); ?>" step="<?php echo ( intval( $step ) ); ?>" value="0"
			class="sureforms-number-slider-input"
			>
			<div style="font-size:14px; font-weight:600;"><?php echo esc_html( $value_display_text ); ?><span id="sureforms-number-slider-value-<?php echo esc_attr( $id ); ?>">0</span></div>
			<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt" for="sureforms-number-slider-' . esc_attr( $id ) . '">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
		<div class="sureforms-number-slider-container sf-classic-number-slider sf-classic-inputs-holder">
			<div class="range-slider-container">
				<div class="range-slider-block">
					<div id="range-sliders" class="range-sliders w-full">
						<div class="range-slider-group range-slider-group-sf">
						<label for="range-slider-sf" class="sf-classic-label-text"><?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="text-red-500"> *</span>' : ''; ?></label>
							<div class="flex justify-between items-center">
							<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="range" min="<?php echo ( intval( $min ) ); ?>" max="<?php echo ( intval( $max ) ); ?>" value="<?php echo ( intval( $min ) ); ?>" data-color="#0284c7"
							step="<?php echo ( intval( $step ) ); ?>" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> class="range-slider range-slider-sf !border-solid !border !border-[#d1d5db]" id="range-slider-sf" />
							<input type="number" min="<?php echo ( intval( $min ) ); ?>" max="<?php echo ( intval( $max ) ); ?>" value="<?php echo ( intval( $min ) ); ?>" class="input-slider number-input-slider-sf !w-[60px] !border-solid !border-[1px] !border-[#D1D5DB] !rounded-md !px-2 !py-1 !text-center !bg-white focus:!border-primary_color focus:!ring-primary_color focus:!outline-0 focus:!bg-white sm:text-sm sm:leading-6" id="input-slider-sf" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php echo '' !== $help ? '<p class="text-sm text-gray-500" id="text-description">' . esc_html( $help ) . '</p>' : ''; ?>
			<p style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></p>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
