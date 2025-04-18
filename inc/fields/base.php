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
	 * Stores the attributes of the block.
	 *
	 * @var array<mixed> $attributes Block attributes.
	 * @since 1.3.0
	 */
	protected $attributes;

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
	 * @var string $data_require_attr Value of the data-required attribute.
	 * @since 0.0.2
	 */
	protected $data_require_attr;

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
	 * Stores the duplicate message markup for a field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $duplicate_msg_markup;

	/**
	 * Stores attribute for aria-describedby.
	 *
	 * @var string
	 * @since 0.0.6
	 */
	protected $aria_described_by;

	/**
	 * Stores the minimum number of selections required.
	 *
	 * @var string
	 * @since 0.0.13
	 */
	protected $min_selection;

	/**
	 * Stores the maximum number of selections allowed.
	 *
	 * @var string
	 * @since 0.0.13
	 */
	protected $max_selection;

	/**
	 * Whether or not block is render in editing mode.
	 * If it is true, then block is currently rendered in edit entry.
	 *
	 * @var bool
	 * @since 1.3.0
	 */
	protected $is_editing = false;

	/**
	 * Currently rendered entry ID.
	 *
	 * @var int
	 * @since 1.3.0
	 */
	protected $entry_id = 0;

	/**
	 * Configuration array for the field.
	 *
	 * This associative array contains configuration settings for the field,
	 * which can be used for various JavaScript functionalities such as
	 * calculation, validation, and conditional logic (in future).
	 * The array data will be stored in the block field's "data-field-config"
	 * attribute as a JSON string.
	 *
	 * @var string $field_config Configuration settings for the field.
	 * @since 1.5.0
	 */
	protected $field_config;

	/**
	 * Render the sureforms default
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		return '';
	}

	/**
	 * Constructor for the Base class.
	 *
	 * @param array<mixed> $extra_classes Extra classes to be added to the field.
	 * @since 1.5.0
	 * @return string
	 */
	public function get_field_classes( $extra_classes = [] ) {
		$common_classes = [
			'srfm-block-single',
			'srfm-block',
			"srfm-{$this->slug}-block",
			"srf-{$this->slug}-{$this->block_id}-block",
			$this->block_width,
			$this->class_name,
			"srfm-slug-{$this->block_slug}",
			$this->conditional_class,
		];

		if ( ! empty( $extra_classes ) && is_array( $extra_classes ) ) {
			$common_classes = array_merge( $common_classes, $extra_classes );
		}

		return Helper::join_strings( $common_classes );
	}

	/**
	 * Setter for the properties of class based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 * @return void
	 */
	protected function set_properties( $attributes ) {
		$default_classes = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$filter_classes  = apply_filters( 'srfm_field_classes', $default_classes, [ 'attributes' => $attributes ] );
		$field_config    = apply_filters( 'srfm_field_config', [], [ 'attributes' => $attributes ] );

		$this->field_config       = $field_config ? htmlspecialchars( Helper::get_string_value( wp_json_encode( $field_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ), ENT_QUOTES, 'UTF-8' ) : '';
		$this->attributes         = $attributes;
		$this->required           = $attributes['required'] ?? false;
		$this->field_width        = $attributes['fieldWidth'] ?? '';
		$this->label              = $attributes['label'] ?? '';
		$this->help               = $attributes['help'] ?? '';
		$this->block_id           = isset( $attributes['block_id'] ) ? Helper::get_string_value( $attributes['block_id'] ) : '';
		$this->form_id            = isset( $attributes['formId'] ) ? Helper::get_string_value( $attributes['formId'] ) : '';
		$this->block_slug         = $attributes['slug'] ?? '';
		$this->class_name         = $filter_classes;
		$this->placeholder        = $attributes['placeholder'] ?? '';
		$this->default            = $attributes['defaultValue'] ?? '';
		$this->checked            = $attributes['checked'] ?? '';
		$this->options            = $attributes['options'] ?? '';
		$this->is_unique          = $attributes['isUnique'] ?? false;
		$this->conditional_class  = apply_filters( 'srfm_conditional_logic_classes', $this->form_id, $this->block_id );
		$this->data_require_attr  = $this->required ? 'true' : 'false';
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
		$this->min_selection      = $attributes['minValue'] ?? '';
		$this->max_selection      = $attributes['maxValue'] ?? '';

		$this->is_editing = isset( $attributes['isEditing'] ) ? true : false;
		$this->entry_id   = $attributes['entryID'] ?? 0;
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
			$this->error_msg = $attributes['errorMsg'] ?? '';
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
		$this->help_markup      = Helper::generate_common_form_markup( $this->form_id, 'help', '', '', $this->block_id, false, $this->help );
		$this->error_msg_markup = Helper::generate_common_form_markup( $this->form_id, 'error', '', '', $this->block_id, boolval( $this->required || $this->min_selection || $this->max_selection ), '', $this->error_msg, false, '', $override );
		$type                   = in_array( $this->slug, [ 'multi-choice', 'dropdown', 'address' ], true ) ? 'label_text' : 'label';
		$this->label_markup     = Helper::generate_common_form_markup( $this->form_id, $type, $this->label, $this->slug, $this->block_id . $input_label, boolval( $this->required ) );

		$this->duplicate_msg_markup = Helper::generate_common_form_markup( $this->form_id, 'error', '', '', $this->block_id, boolval( $this->required ), '', $this->error_msg, false, $this->duplicate_msg, $override );
	}

	/**
	 * This function creates placeholder markup from label
	 * works when user selects option 'Use labels as placeholder'
	 *
	 * @param string $input_label label of block where functionality is required.
	 * @since 0.0.7
	 * @return void
	 */
	protected function set_label_as_placeholder( $input_label = '' ) {
		$this->placeholder_attr = '';
		$placeholder            = Helper::generate_common_form_markup( $this->form_id, 'placeholder', $this->label, $this->slug, $this->block_id . $input_label, boolval( $this->required ) );
		if ( ! empty( $placeholder ) ) {
			$this->label_markup     = '';
			$this->placeholder_attr = ' placeholder="' . $placeholder . '" ';
		}
	}

	/**
	 * Setter for the aria-describedby attribute.
	 *
	 * @since 0.0.6
	 * @return void
	 */
	protected function set_aria_described_by() {
		$this->aria_described_by .= ' srfm-error-' . $this->block_id;
		$this->aria_described_by .= ! empty( $this->help ) ? ' srfm-description-' . $this->block_id : '';
	}
}
