<?php
/**
 * Form Field Base Class.
 *
 * This file defines the base class for form fields in the SureForms package.
 *
 * @package SureForms
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Field Base Class
 *
 * Defines the base class for form fields.
 *
 * @since 0.0.1
 */
class Base {
	/**
	 * Flag indicating if the field is required.
	 *
	 * @var bool
	 * @since 0.0.2
	 */
	protected $required;

	/**
	 * Width of the field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $field_width;

	/**
	 * Stores the label for an input field.
	 * The value of this variable specifies the text displayed as the label for the corresponding input field when rendered.
	 *
	 * @var string $label Label used for the input field.
	 * @since 0.0.2
	 */
	protected $label;

	/**
	 * Stores the string that provides help text.
	 *
	 * @var string $help
	 * @since 0.0.2
	 */
	protected $help;

	/**
	 * Validation error message for the fields.
	 *
	 * @var string $error_msg Input field validation error message.
	 * @since 0.0.2
	 */
	protected $error_msg;

	/**
	 * Represents the identifier of the block.
	 *
	 * @var string $block_id Unique identifier representing the block.
	 * @since 0.0.2
	 */
	protected $block_id;

	/**
	 * Stores the ID of the form.
	 *
	 * @var string $form_id Form ID.
	 * @since 0.0.2
	 */
	protected $form_id;

	/**
	 * Stores the block slug.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $block_slug;

	/**
	 * Stores the conditional class.
	 *
	 * @var string $conditional_class class name.
	 * @since 0.0.2
	 */
	protected $conditional_class;

	/**
	 * Indicates whether the attribute should be set to true or false.
	 *
	 * @var string $aria_require_attr Value of the aria-required attribute.
	 * @since 0.0.2
	 */
	protected $aria_require_attr;

	/**
	 * Dynamically sets the CSS class for block width based on the field width.
	 *
	 * @var string $block_width The CSS class for block width, dynamically generated from $field_width.
	 * @since 0.0.2
	 */
	protected $block_width;

	/**
	 * Stores the class name.
	 *
	 * @var string $class_name The value of the class name attribute.
	 * @since 0.0.2
	 */
	protected $class_name;

	/**
	 * Stores the placeholder text.
	 *
	 * @var string $placeholder HTML field placeholder.
	 * @since 0.0.2
	 */
	protected $placeholder;

	/**
	 * Stores the HTML placeholder attribute.
	 *
	 * @var string $placeholder_attr HTML field placeholder attribute.
	 * @since 0.0.2
	 */
	protected $placeholder_attr;

	/**
	 * Default value for the field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $default;

	/**
	 * HTML attribute string for the default value.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $default_value_attr;

	/**
	 * Stores the input label.
	 *
	 * @var string $input_label input label.
	 * @since 0.0.2
	 */
	protected $input_label;

	/**
	 * Stores the default fallback value of the label for an input field if nothing is specified.
	 *
	 * @var string $input_label_fallback Default fallback value for the input label.
	 * @since 0.0.2
	 */
	protected $input_label_fallback;

	/**
	 * Stores the field name.
	 *
	 * @var string $field_name HTML field name.
	 * @since 0.0.2
	 */
	protected $field_name;

	/**
	 * Stores the slug.
	 *
	 * @var string $slug slug value.
	 * @since 0.0.2
	 */
	protected $slug;

	/**
	 * Unique slug which combines the slug, block ID, and input label.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $unique_slug;

	/**
	 * Checked state for the field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $checked;

	/**
	 * HTML attribute string for the checked state.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $checked_attr;

	/**
	 * Options for the field.
	 *
	 * @var array<mixed>
	 * @since 0.0.2
	 */
	protected $options;

	/**
	 * Allowed HTML tags for the field.
	 *
	 * @var array<string, array<array<string>>>
	 * @since 0.0.2
	 */
	protected $allowed_tags;

	/**
	 * Flag indicating if the field value must be unique.
	 *
	 * @var bool
	 * @since 0.0.2
	 */
	protected $is_unique;

	/**
	 * Stores the flag if the field value must be unique.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $aria_unique;

	/**
	 * Duplicate value message for the field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $duplicate_msg;

	/**
	 * Stores the help text markup.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $help_markup;

	/**
	 * Stores the error message markup.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $error_msg_markup;

	/**
	 * Stores the HTML label markup.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $label_markup;

	/**
	 * Stores the error icon to be used in HTML markup.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $error_svg;

	/**
	 * Stores the duplicate message markup for a field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $duplicate_msg_markup;

	/**
	 * Setter for the properties of class based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_properties( $attributes ) {
		$this->required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$this->field_width        = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$this->label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$this->help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$this->block_id           = isset( $attributes['block_id'] ) ? Helper::get_string_value( $attributes['block_id'] ) : '';
		$this->form_id            = isset( $attributes['formId'] ) ? Helper::get_string_value( $attributes['formId'] ) : '';
		$this->block_slug         = isset( $attributes['slug'] ) ? $attributes['slug'] : '';
		$this->class_name         = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$this->placeholder        = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$this->default            = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$this->checked            = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$this->options            = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$this->is_unique          = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$this->conditional_class  = apply_filters( 'srfm_conditional_logic_classes', $this->form_id, $this->block_id );
		$this->aria_require_attr  = $this->required ? 'true' : 'false';
		$this->block_width        = $this->field_width ? ' srfm-block-width-' . str_replace( '.', '-', $this->field_width ) : '';
		$this->placeholder_attr   = $this->placeholder ? ' placeholder="' . $this->placeholder . '" ' : '';
		$this->default_value_attr = $this->default ? ' value="' . $this->default . '" ' : '';
		$this->checked_attr       = $this->checked ? 'checked' : '';
		$this->aria_unique        = $this->is_unique ? 'true' : 'false';
		$this->allowed_tags       = [
			'a' => [
				'href'   => [],
				'target' => [],
			],
		];
	}

	/**
	 * Setter for the label of input field if available, otherwise provided value is utilized.
	 * Invokes the set_field_name() function to set the field_name property.
	 *
	 * @param string $value The default fallback text.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_input_label( $value ) {
		$this->input_label_fallback = $this->label ? $this->label : $value;
		$this->input_label          = '-lbl-' . Helper::encrypt( $this->input_label_fallback );
		$this->set_field_name( $this->input_label );
	}

	/**
	 * Setter for the field name property.
	 *
	 * @param string $string Contains $input_label value or the $unique_slug based on the block.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_field_name( $string ) {
		$this->field_name = $string . '-' . $this->block_slug;
	}

	/**
	 * Setter for error message for the block.
	 *
	 * @param array<mixed> $attributes Block attributes, expected to contain 'errorMsg' key.
	 * @param string       $key meta key name.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_error_msg( $attributes, $key = '' ) {
		if ( empty( $key ) ) {
			$this->error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		} else {
			$this->error_msg = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : Helper::get_default_dynamic_block_option( $key );
		}
	}

	/**
	 * Setter for duplicate message value for the block.
	 *
	 * @param array<mixed> $attributes Block attributes, expected to contain 'errorMsg' key.
	 * @param string       $key meta key name.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_duplicate_msg( $attributes, $key ) {
		$this->duplicate_msg = ! empty( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : Helper::get_default_dynamic_block_option( $key );
	}

	/**
	 * Setter for unique slug.
	 *
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_unique_slug() {
		$this->unique_slug = 'srfm-' . $this->slug . '-' . $this->block_id . $this->input_label;
	}

	/**
	 * Setter for the markup properties used in rendering the HTML markup of blocks.
	 * The parameters are for generating the label and error message markup of blocks.
	 *
	 * @param string $input_label Optional. Additional label to be appended to the block ID.
	 * @param bool   $override Optional. Override for error markup. Default is false.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_markup_properties( $input_label = '', $override = false ) {
		$this->help_markup          = Helper::generate_common_form_markup( $this->form_id, 'help', '', '', '', false, $this->help );
		$this->error_msg_markup     = Helper::generate_common_form_markup( $this->form_id, 'error', '', '', '', boolval( $this->required ), '', $this->error_msg, false, '', $override );
		$this->label_markup         = Helper::generate_common_form_markup( $this->form_id, 'label', $this->label, $this->slug, $this->block_id . $input_label, boolval( $this->required ) );
		$this->error_svg            = Helper::fetch_svg( 'error', 'srfm-error-icon' );
		$this->duplicate_msg_markup = Helper::generate_common_form_markup( $this->form_id, 'error', '', '', '', boolval( $this->required ), '', $this->error_msg, false, $this->duplicate_msg, $this->is_unique );
	}

	/**
	 * Render the sureforms default
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		return '';
	}
}
