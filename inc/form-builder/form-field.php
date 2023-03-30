<?php
/**
 * Form Field Class file.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc\Form_Builder;

/**
 * Form Field Class.
 *
 * @since X.X.X
 */
class Form_Field {

	/**
	 * Fields for registration.
	 *
	 * @var array $fields
	 * @since X.X.X
	 */
	private $fields;

	/**
	 * Field types array
	 *
	 * @var array
	 * @since X.X.X
	 */
	private $field_types;

	/**
	 * Initialize class
	 *
	 * @param array $fields Form fields.
	 * @param array $args Arguments Array.
	 * @return Form_Field Form Field instance.
	 * @since X.X.X
	 */
	public function init( $fields = array(), $args = array() ) {
		$this->includes();
		$this->fields = $fields;
		if ( ! empty( $args['single'] ) && true === $args['single'] ) {
			$this->fields = array( $fields );
		}

		$this->field_types = $this->field_types();
		// Return Field object.
		return $this;
	}

	/**
	 * Include required form field types.
	 *
	 * @return void
	 * @since X.X.X
	 */
	private function includes() {
		$field_types = $this->register_field_types();
		foreach ( $field_types as $type => $field ) :
			$file_path_incl = Sureforms_ABSPATH . 'includes/lib/wte-form-framework/field-types/class-field-' . $type . '.php';
			if ( file_exists( $file_path_incl ) ) :
				include_once $file_path_incl;
			endif;
		endforeach;
	}

	/**
	 * Register form field types.
	 *
	 * @return array Field types array.
	 * @since X.X.X
	 */
	public function register_field_types() {

		$field_types = array(
			'text'             => array(
				'field_label' => __( 'Text', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Text',
			),
			'email'            => array(
				'field_label' => __( 'Email', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Email',
			),
			'number'           => array(
				'field_label' => __( 'Number', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Number',
			),
			'hidden'           => array(
				'field_label' => __( 'Hidden', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Hidden',
			),
			'select'           => array(
				'field_label' => __( 'Select', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Select',
			),
			'textarea'         => array(
				'field_label' => __( 'Textarea', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Textarea',
			),
			'datepicker'       => array(
				'field_label' => __( 'Date', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Date',
			),
			'radio'            => array(
				'field_label' => __( 'Radio', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Radio',
			),
			'checkbox'         => array(
				'field_label' => __( 'Checkbox', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Checkbox',
			),
			'text_info'        => array(
				'field_label' => __( 'Text Info', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Text_Info',
			),
			'heading'          => array(
				'field_label' => __( 'Heading', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Heading',
			),
			'range'            => array(
				'field_label' => __( 'Range', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Range',
			),
			'date_range'       => array(
				'field_label' => __( 'Date Range', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Date_Range',
			),
			'file'             => array(
				'field_label' => __( 'File', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_File',
			),
			'country_dropdown' => array(
				'field_label' => __( 'Country Dropdown', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Country_Dropdown',
			),
			'tel'              => array(
				'field_label' => __( 'Tel', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Tel',
			),
			'trips_list'       => array(
				'field_label' => __( 'Trips List', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Trips_List',
			),
			'radio'            => array(
				'field_label' => __( 'Trips List', 'sureforms' ),
				'field_class' => 'Sureforms_Form_Field_Radio',
			),
		);

		$field_types = apply_filters( 'sureforms_form_builder_field_types', $field_types );

		return $field_types;
	}

	/**
	 * Load field types classes.
	 *
	 * @return array Field types array.
	 * @since X.X.X
	 */
	private function field_types() {
		$fields        = $this->register_field_types();
		$field_classes = wp_list_pluck( $fields, 'field_class' );

		return $field_classes;
	}

	/**
	 * Process form field before render.
	 *
	 * @return string Form field output.
	 * @since X.X.X
	 */
	private function process() {
		$output = '';
		if ( ! empty( $this->fields ) ) :
			foreach ( $this->fields as $field ) :
				$field = $this->form_arguments( $field );
				if ( $field ) :
					$content = $this->process_single( $field );
					$output .= ( in_array( $field['type'], array( 'hidden', 'heading' ), true ) ) ? $content : $this->template( $field, $content );
				endif;
			endforeach;
		endif;
		return $output;
	}

	/**
	 * Form field render template.
	 *
	 * @param array         $field form field.
	 * @param field content $content Content.
	 * @return mixed $content
	 * @since X.X.X
	 */
	public function template( $field, $content ) {
		ob_start();
			$classes = ( isset( $field['wrapper_class'] ) ) ? $field['wrapper_class'] : '';
		?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<label class="<?php echo esc_attr( $field['label_class'] ); ?>" for="<?php echo esc_attr( $field['id'] ); ?>">
					<?php echo esc_attr( $field['field_label'] ); ?>
					<?php if ( isset( $field['validations']['required'] ) && ! empty( $field['field_label'] ) ) : ?>
						<span class="required">*</span>
					<?php endif; ?>
				</label>
				<?php echo wp_kses_post( $content ); ?>
			</div>
		<?php
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Render form field.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function render() {
		echo $this->process();
	}

	/**
	 * Render input field.
	 *
	 * @param array $field Field.
	 * @return void
	 * @since X.X.X
	 */
	public function render_input( $field ) {
		if ( ! $field ) {
			return;
		}

		echo $this->process_single( $field );
	}

	/**
	 * Process single field based on arguments.
	 *
	 * @param array $field Field.
	 * @return void
	 * @since X.X.X
	 */
	private function process_single( $field ) {
		$field = $this->form_arguments( $field );
		if ( $field && class_exists( $this->field_types[ $field['type'] ] ) ) {
			$field_init = new $this->field_types[ $field['type'] ]();
			return $field_init->init( $field )->render( false );
		}
	}

	/**
	 * Verify field arguments
	 *
	 * @param array $field Field.
	 * @return array $field
	 * @since X.X.X
	 */
	public function form_arguments( $field ) {
		if ( ! empty( $field['type'] ) && array_key_exists( $field['type'], $this->field_types ) ) {

			$field['field_label']   = isset( $field['field_label'] ) ? $field['field_label'] : '';
			$field['name']          = isset( $field['name'] ) ? $field['name'] : '';
			$field['id']            = isset( $field['id'] ) ? $field['id'] : $field['name'];
			$field['label_class']   = isset( $field['label_class'] ) ? $field['label_class'] : '';
			$field['class']         = isset( $field['class'] ) ? $field['class'] : '';
			$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
			$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
			$field['wrapper_class'] = ( 'text_info' === $field['type'] ) ? $field['wrapper_class'] . ' sureforms-info-field' : $field['wrapper_class'];
			$field['default']       = isset( $field['default'] ) ? $field['default'] : '';
			$field['attributes']    = isset( $field['attributes'] ) ? $field['attributes'] : array();
			$field['remove_wrap']   = isset( $field['remove_wrap'] ) ? $field['remove_wrap'] : false;

			if ( isset( $field['validations']['required'] ) && ( false === $field['validations']['required'] || '' === $field['validations']['required'] || 'false' === $field['validations']['required'] ) ) {
				unset( $field['validations']['required'] );
			}

			if ( empty( $field['attributes']['placeholder'] ) && ! empty( $field['placeholder'] ) ) {
				$field['attributes']['placeholder'] = $field['placeholder'];
			}

			if ( empty( $field['attributes']['rows'] ) && ! empty( $field['rows'] ) ) {
				$field['attributes']['rows'] = $field['rows'];
			}

			if ( empty( $field['attributes']['cols'] ) && ! empty( $field['cols'] ) ) {
				$field['attributes']['cols'] = $field['cols'];
			}

			return $field;
		}

		return false;
	}
}
