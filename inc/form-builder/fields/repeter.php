<?php
/**
 * Text info field type
 *
 * @package WP Travel Engine
 */
class WP_Travel_Engine_Form_Field_Repeater {

	/**
	 * Field.
	 *
	 * @var obj
	 */
	protected $field;

	/**
	 * Field type object.
	 *
	 * @var string
	 */
	protected $field_type = 'repeater';

	/**
	 * Initialize the field class.
	 *
	 * @param [type] $field
	 * @return void
	 */
	function init( $field ) {

		$this->field = $field;

		return $this;
	}

	/**
	 * Render field layout.
	 *
	 * @param boolean $display
	 * @return void
	 */
	function render( $display = true ) {

		$before_field = isset( $this->field['before_field'] ) ? $this->field['before_field'] : '';

		ob_start();

		if ( isset( $this->field['fields'] ) && is_array( $this->field['fields'] ) && ! empty( $this->field['fields'] ) ) :

			$form_fields = new WP_Travel_Engine_Form_Field();

			$form_fields->init( $this->field['fields'] )->render();

			?>

			<script type="text/html" id="tmpl-">


			
			</script>

			<script>


			
			</script>

			<?php

		endif;

		$output = ob_get_clean();

		if ( ! $display ) {

			return $output;

		}

		echo $output;
	}
}
