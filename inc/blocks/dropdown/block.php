<?php
/**
 * PHP render form dropdown Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Dropdown;

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
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id        = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options   = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help      = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-dropdown-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<select 
			name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
			area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
			class="sureforms-input-field"
			>
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
			</select>
			<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
		<!-- classic layout -->
		<div class="sureforms-classic-dropdown-container main-container sf-classic-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label id="listbox-label" class="!block !text-sm !font-medium !leading-6 !text-primary_color">						
				<?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span class="!text-required_icon_color"> *</span>' : ''; ?>
			</label>
			<div class="relative mt-2">
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> type="hidden" class="sf-classic-dropdown-result" value="<?php echo esc_attr( 0 < count( $options ) ? $options[0] : '' ); ?>" />
				<button type="button" class="sureforms-classic-dropdown-button !border-solid !border-0 !border-[#D1D5DB] placeholder:!text-gray-500 !relative w-full !cursor-pointer !rounded-md !bg-white !py-1.5 !pl-3 !pr-10 !text-left !text-gray-900 !shadow-sm !ring-1 !ring-gray-300 focus:!outline-none focus:!ring-2 focus:!ring-primary_color sm:!text-sm sm:!leading-6" id="sureforms-classic-dropdown-button-<?php echo esc_attr( $id ); ?>">
					<span class="sf-dropdown-value !block !truncate">
						<?php echo esc_attr( 0 < count( $options ) ? $options[0] : '&nbsp;' ); ?>
					</span>
					<span class="sf-classic-select-icon !pointer-events-none !absolute !inset-y-0 !right-0 !flex !items-center !pr-2 !duration-300 transition-all">
						<i class="fa-solid fa-angle-down !h-5 !w-5 !text-gray-400 !mt-[10px]"></i>
					</span>
				</button>
				<ul class="sf-classic-dropdown-box !opacity-0 !absolute !mt-1 !max-h-60 !w-full !overflow-auto !rounded-md !bg-white !py-1 !text-base !shadow-lg !ring-1 !ring-black !ring-opacity-5 focus:!outline-none sm:!text-sm !mx-0 !list-none !pl-0" tabindex="-1" value="value" style="display: none;">
					<?php echo 0 === count( $options ) ? '<div class="text-gray-500 !relative !select-none !py-2 !pl-3 !pr-9">' . esc_html__( 'No Options Found', 'sureforms' ) . '</div>' : ''; ?>
					<?php foreach ( $options as $option ) : ?>
						<li class="sf-classic-dropdown-option !text-gray-900 !relative !select-none !py-2 !pl-3 !pr-9 hover:!bg-primary_color hover:!text-white !cursor-pointer" id="listbox-option-0" role="option">
							<span class="font-normal block truncate"><?php echo esc_html( $option ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<p for="sureforms-checkbox" class="text-sm mt-2 text-gray-500"><?php echo '' !== $help ? esc_html( $help ) : ''; ?></p>
			<span style="display:none" class="error-message mt-2"><?php echo esc_html( $error_msg ); ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
