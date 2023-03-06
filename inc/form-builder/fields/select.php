<?php
/**
 * Select field render
 *
 * @package WP Travel Engine
 */
class WP_Travel_Engine_Form_Field_Select {

	/**
	 * Field object
	 *
	 * @var [type]
	 */
	protected $field;

	/**
	 * Initialize select class
	 *
	 * @param obj $field
	 * @return void
	 */
	function init( $field ) {

		$this->field = $field;

		return $this;
	}

	/**
	 * Render template for select dropdown
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

		$attributes = '';

		if ( isset( $this->field['attributes'] ) ) :

			foreach ( $this->field['attributes'] as $attribute => $attribute_val ) :

				if ( 'placeholder' !== $attribute ) :

					$attributes .= sprintf( ' %s="%s" ', $attribute, $attribute_val );

				endif;

			endforeach;

		endif;

		$output = sprintf( '<select id="%s" name="%s" class="%s" %s %s>', $this->field['id'], $this->field['name'], $this->field['class'], $validations, $attributes );

		if ( ! empty( $this->field['attributes']['placeholder'] ) ) :

			$this->field['options'] = wp_parse_args(
				$this->field['options'],
				array(
					'' => $this->field['attributes']['placeholder'],
				)
			);

		endif;

		if ( ! empty( $this->field['options'] ) ) :
			$options_arr = $this->field['options'];
			if ( ! is_array( $this->field['options'] ) ) {
				$options_arr = json_decode( $this->field['options'], true );
			}
			foreach ( $options_arr as $key => $value ) {

				// Option Attributes.
				$option_attributes = '';

				if ( isset( $this->field['option_attributes'] ) && count( $this->field['option_attributes'] ) > 0 ) :

					foreach ( $this->field['option_attributes'] as $key1 => $attr ) :

						if ( ! is_array( $attr ) ) :

							$option_attributes .= sprintf( '%s="%s"', $key1, $attr );

						else :

							foreach ( $attr as $att ) :

								$option_attributes .= sprintf( '%s="%s"', $key1, $att );

							endforeach;

						endif;

					endforeach;

				endif;

				$selected = ( $key == $this->field['default'] ) ? 'selected' : '';

				$output .= sprintf( '<option %s value="%s" %s>%s</option>', $option_attributes, $key, $selected, $value );
			}
		endif;

		$output .= sprintf( '</select>' );

		if ( ! $display ) :

			return $output;

		endif;

		echo $output;
	}
}
