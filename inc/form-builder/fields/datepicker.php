<?php
/**
 * Datepicker field.
 *
 * @package WP_Travel_Engine
 */
class WP_Travel_Engine_Form_Field_Date extends WP_Travel_Engine_Form_Field_Text {
	protected $field;
	protected $field_type = 'text';
	function init( $field ) {
		$this->field                               = $field;
		$this->field['attributes']['autocomplete'] = 'off';
		return $this;
	}

	function render( $display = true ) {
		$output = parent::render( false );
		wp_enqueue_script( 'wte-fpickr' );
		wp_enqueue_style( 'wte-fpickr' );

		$max_today = isset( $this->field['attributes'] ) && isset( $this->field['attributes']['data-max-today'] ) ? $this->field['attributes']['data-max-today'] : '';
		$output   .= sprintf(
			'<script>;(function() {
				window.addEventListener("load",function() {
					var fpArgs = {
						dateFormat: "Y-m-d"
					}
					if("%2$s" == "false") {
						fpArgs["minDate"] = new Date()
					}
					if("%2$s" == "true") {
						fpArgs["maxDate"] = new Date()
					}
					window.flatpickr && window.flatpickr("#%1$s", fpArgs)
				})
			})();</script>',
			$this->field['id'],
			$max_today
		);

		if ( ! $display ) {
			return $output;
		}

		echo $output;
	}

}
