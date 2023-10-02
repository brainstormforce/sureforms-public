<?php
/**
 * PHP render form Multichoice Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Multichoice;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

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
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id               = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : array();
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start();
			?>
		<!-- <div class="sureforms-multi-choice-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-multi-choice-container-<?php echo esc_attr( $id ); ?>">
			<label class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input type="hidden" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $single_selection ); ?>" id="sureforms-multi-choice-selection-<?php echo esc_attr( $id ); ?>" />
			<input type="hidden" value="<?php echo esc_attr( $style ); ?>" id="sureforms-multi-choice-style-<?php echo esc_attr( $id ); ?>" />
			<input class="sureforms-multi-choice-<?php echo esc_attr( $id ); ?>" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" name="<?php echo esc_attr( $single_selection ? str_replace( ' ', '_', $label . 'SF-divider' . $id ) : str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" value="">
				<?php foreach ( $options as $i => $option ) : ?>
					<div style="display: flex; align-items: center;">
						<input
							style="display: <?php echo esc_attr( 'buttons' === $style ? 'none' : 'inherit' ); ?>"
							class="sureforms-multi-choice"
							id="sureforms-multi-choice-<?php echo esc_attr( $id . '-' . $i ); ?>"
							type="<?php echo esc_attr( $single_selection ? 'radio' : 'checkbox' ); ?>"
							<?php echo esc_attr( $single_selection ? 'name="' . esc_attr( "sf-radio-$id" ) . '"' : '' ); ?>
						/>
						<label
							class="sureforms-multi-choice-label-<?php echo esc_attr( $id ); ?> sureforms-multi-choice-label-button"
							for="sureforms-multi-choice-<?php echo esc_attr( $id . '-' . $i ); ?>"
							style="
							<?php
							echo esc_attr( 'buttons' === $style ? 'background-color: white; cursor: pointer; border: 2px solid black; border-radius: 10px; padding: .5rem 1rem .5rem 1rem; width: 100%; color: black;' : '' );
							?>
							"
						>
							<span
								class="multi-choice-option"
								id="multi-choice-option-<?php echo esc_attr( $id . '-' . $i ); ?>"
							>
								<?php echo esc_html( $option ); ?>
							</span>
						</label>
						<span></span>
					</div>
				<?php endforeach; ?>
				<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
			<div class="sureforms-multi-choice-container main-container sf-classic-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-multi-choice-container-<?php echo esc_attr( $id ); ?>">
			<input type="hidden" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $single_selection ); ?>" id="sureforms-multi-choice-selection-<?php echo esc_attr( $id ); ?>" />
			<input type="hidden" value="<?php echo esc_attr( $style ); ?>" id="sureforms-multi-choice-style-<?php echo esc_attr( $id ); ?>" />
			<input class="sureforms-multi-choice-<?php echo esc_attr( $id ); ?>" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" name="<?php echo esc_attr( $single_selection ? str_replace( ' ', '_', $label . 'SF-divider' . $id ) : str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" value="">
			<label for="text" class="block text-sm font-medium leading-6 text-primary_color"><?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="text-red-500"> *</span>' : ''; ?></label>
				<div class="radio-buttons flex flex-wrap mt-2">
				<?php foreach ( $options as $i => $option ) : ?>
					<label class="classic-sf-radio">
					<input type="<?php echo esc_attr( $single_selection ? 'radio' : 'checkbox' ); ?>" <?php echo esc_attr( $single_selection ? 'name="' . esc_attr( "sf-radio-$id" ) . '"' : '' ); ?> id="sureforms-multi-choice-<?php echo esc_attr( $id . '-' . $i ); ?>" class="sureforms-multi-choice">
					<div class="flex items-center classic-radio-btn cursor-pointer rounded-lg !ring-1 !ring-gray-300 bg-white p-2 shadow-sm focus:outline-none focus:border-0 mb-2">
						<div class="pr-[5px] relative flex">
							<i class="fa fa-check-circle text-base" aria-hidden="true"></i>
							<i class="fa-regular fa-circle text-sm absolute text-gray-300" aria-hidden="true"></i>
						</div>
						<div>
							<article id="multi-choice-option-<?php echo esc_attr( $id . '-' . $i ); ?>" class="text-sm font-medium leading-6 text-primary_color"><?php echo esc_html( $option ); ?></article>
						</div>
					</div>
					</label>
				<?php endforeach; ?>
				</div>
				<?php echo '' !== $help ? '<p class="text-sm text-gray-500">' . esc_html( $help ) . '</p>' : ''; ?>
				<p style="display:none" class="error-message mt-2 text-sm text-red-600"><?php echo esc_html( $error_msg ); ?></p>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
