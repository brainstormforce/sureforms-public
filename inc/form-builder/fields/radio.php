<?php
/**
 * Input field class for radio.
 *
 * @since 2.2.6
 * @package WP Travel Engine
 */

class WP_Travel_Engine_Form_Field_Radio {

	/**
	 * field object.
	 *
	 * @var obj
	 */
	private $field;

	/**
	 * Initialize radio class
	 *
	 * @param obj $field
	 * @return void
	 */
	function init( $field ) {
		$this->field = $field;
		return $this;
	}

	/**
	 * radio field
	 *
	 * @param boolean $display
	 * @return void
	 */
	function render( $display = true ) {
		$validations = '';
		if ( isset( $this->field['validations'] ) ) :
			foreach ( $this->field['validations'] as $key => $attr ) :
				$validations .= sprintf( '%s="%s"', $key, $attr );
			endforeach;
		endif;

		$output = '';

		if ( ! empty( $this->field['options'] ) ) {
			$index       = 0;
			$options_arr = $this->field['options'];
			if ( ! is_array( $this->field['options'] ) ) {
				$options_arr = json_decode( $this->field['options'] );
			}
			foreach ( $options_arr as $key => $value ) {
				// Option Attributes.
				$option_attributes = '';
				if ( isset( $this->field['option_attributes'] ) && count( $this->field['option_attributes'] ) > 0 ) {
					foreach ( $this->field['option_attributes'] as $key1 => $attr ) {
						if ( ! is_array( $attr ) ) {
							$option_attributes .= sprintf( '%s="%s"', $key1, $attr );
						} else {
							foreach ( $attr as $att ) {
								$option_attributes .= sprintf( '%s="%s"', $key1, $att );
							}
						}
					}
				}
				if ( is_array( $this->field['default'] ) && count( $this->field['default'] ) > 0 ) {
					$checked = ( in_array( $key, $this->field['default'] ) ) ? 'checked' : '';
				} else {
					$checked = ( $key === $this->field['default'] ) ? 'checked' : '';
				}
					$error_coontainer_id = sprintf( 'error_container-%s', $this->field['id'] );
					$output             .= sprintf(
						'<div class="wpte-bf-radio-wrap">
					<input type="radio" name="%s[]" value="%s" id="%s" %s %s %s>
					<label for="%s">
						%s
					</label>
				</div>',
						$this->field['name'],
						$key,
						$this->field['id'] . '_' . $index,
						$option_attributes,
						$checked,
						$validations,
						$this->field['id'] . '_' . $index,
						$value
					);

				$index++;
			}

			$output .= sprintf( '<div id="%s"></div>', $error_coontainer_id );
		}

		if ( ! $display ) {
			return $output;
		}

		echo $output;
	}
}
