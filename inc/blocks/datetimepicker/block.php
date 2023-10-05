<?php
/**
 * PHP render form Date & Time Picker Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\datetimepicker;

use SureForms\Inc\Blocks\Base;

/**
 * Date Time Picker Block.
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
			$id         = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required   = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label      = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help       = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min        = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max        = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg  = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname  = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-input-date-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-input-date-container-<?php echo esc_attr( $id ); ?>">
			<label class="sf-text-primary" for="sureforms-input-date-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input type="hidden" id="sureforms-full-date-time-<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" value="">
			<div class="sureforms-date-time-picker-holder" >
			<?php
			switch ( $field_type ) {
				case 'dateTime':
					echo '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
					echo '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
					break;
				case 'date':
					echo '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
					break;
				case 'time':
					echo '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
					break;
				default:
					echo '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
					echo '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
					break;
			}
			?>
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-input-date" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
		<div class="sf-classic-inputs-holder main-container sf-classic-date-time-container <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-email-<?php echo esc_attr( $id ); ?>" class="sf-classic-label-text">
				<?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="text-red-500"> *</span>' : ''; ?>
			</label>
			<input type="hidden" class="sf-min-max-holder" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" >
			<input type="hidden" field-type="<?php echo esc_attr( $field_type ); ?>" class="sf-classic-date-time-result" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" value="">
			<div class="sf-classic-date-time-picker relative mt-2 rounded-md shadow-sm datepicker-with-limits" data-te-input-wrapper-init
			<?php
			switch ( $field_type ) {
				case 'dateTime':
					echo esc_attr( 'data-te-date-timepicker-init' );
					break;
				case 'date':
					echo esc_attr( 'data-te-datepicker-init' );
					break;
				case 'time':
					echo esc_attr( 'data-te-timepicker-init' );
					break;
				default:
					echo esc_attr( 'data-te-date-timepicker-init' );
					break;
			}
			?>
				>
				<input type="text" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" class="sureforms-input-data-time sf-classic-datetime-picker" id="sureforms-input-time-'<?php esc_attr( $id ); ?>'" />
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-input-password" class="sforms-helper-txt">' . esc_attr( $help ) . '</label>' : ''; ?>
			<p style="display:none" class="error-message " id="email-error"><?php echo esc_html( $error_msg ); ?></p>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
