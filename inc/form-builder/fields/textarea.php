<?php
/**
 * Textarea field render
 *
 * @package WP_Travel_Engine
 */
class WP_Travel_Engine_Form_Field_Textarea {

	/**
	 * Field object
	 *
	 * @var $field
	 */
	protected $field;

	/**
	 * Initialize the field class
	 *
	 * @access public
	 */
	public function init( $field ) {
		$this->field = $field;
		return $this;
	}

	/**
	 * Render form template
	 *
	 * @access public
	 */
	public function render( $display = true ) {
		$validations = '';
		if ( isset( $this->field['validations'] ) ) {
			foreach ( $this->field['validations'] as $key => $attr ) {
				$validations .= sprintf( 'data-parsley-%s="%s"', $key, $attr );
			}
		}

		$attributes = '';
		if ( isset( $this->field['attributes'] ) ) {
			foreach ( $this->field['attributes'] as $attribute => $attribute_val ) {
				$attributes .= sprintf( ' %s="%s" ', $attribute, $attribute_val );
			}
		}

		$output  = sprintf( '<textarea id="%s" name="%s" %s %s>', $this->field['id'], $this->field['name'], $validations, $attributes );
		$output .= $this->field['default'];
		$output .= sprintf( '</textarea>' );

		if ( ! $display ) {
			return $output;
		}

		echo $output;
	}
}
