<?php
/**
 * Input field class for checkbox.
 *
 * @since 2.2.6
 * @package SureForms
 */

class Checkbox {

	/**
	 * field object.
	 *
	 * @var obj
	 */
	private $field;

	/**
	 * Initialize checkbox class
	 *
	 * @param obj $field
	 * @return void
	 */
	function init( $field ) {
		$this->field = $field;
		return $this;
	}

	/**
	 * Checkbox field
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
				$options_arr = json_decode( $this->field['options'], true );
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
				if ( count( $options_arr ) > 1 ) {
					$validations .= 'data-parsley-multiple="checkbox" data-parsley-mincheck="1" data-parsley-required';
						$output  .= sprintf(
							'<div class="wpte-bf-checkbox-wrap wpte-checkbox-wrap">
							<input type="checkbox" name="%s[]" value="%s" id="%s" %s %s %s>
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
				} else {
					$output .= sprintf(
						'<div class="wpte-bf-checkbox-wrap wpte-checkbox-wrap">
							<input type="checkbox" name="%s[]" value="%s" id="%s" %s %s %s>
							<label for="%s">
								%s
							</label>
						</div>',
						$this->field['name'],
						$key,
						$this->field['id'],
						$option_attributes,
						$checked,
						$validations,
						$this->field['id'],
						$value
					);
				}

				$index++;
			}

			$output .= sprintf( '<div id="%s"></div>', $error_coontainer_id );
		} else {
			$output .= sprintf(
				'<div class="wpte-checkbox-wrap">
				<input type="checkbox" name="%s" value="%s" id="%s" %s %s %s>
				<label for="%s"></label>
			</div>',
				$this->field['name'],
				'1',
				$this->field['id'],
				'',
				$this->field['default'] === '1' ? 'checked' : '',
				$validations,
				$this->field['id'],
				''
			);
		}

		if ( ! $display ) {
			return $output;
		}

		echo $output;
	}
}
