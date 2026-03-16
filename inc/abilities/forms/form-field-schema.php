<?php
/**
 * Form Field Schema Trait.
 *
 * Shared field-type schema and sanitization for form abilities.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Form_Field_Schema
 *
 * Provides reusable form-field schema definition and field sanitization
 * for Create_Form and Update_Form abilities.
 *
 * @since 2.5.2
 */
trait Form_Field_Schema {
	/**
	 * Get the form field items schema (field types + properties).
	 *
	 * @since 2.5.2
	 * @return array<string,mixed>
	 */
	protected function get_form_field_schema() {
		$field_types = [
			'input',
			'email',
			'url',
			'textarea',
			'multi-choice',
			'checkbox',
			'gdpr',
			'number',
			'phone',
			'dropdown',
			'address',
			'inline-button',
			'payment',
		];

		/**
		 * Filter the allowed field types for form abilities.
		 *
		 * Pro and third-party plugins can use this to add their own field types.
		 *
		 * @param array<string> $field_types Array of field type slugs.
		 * @since 2.5.2
		 */
		$field_types = apply_filters( 'srfm_ability_form_field_types', $field_types );

		// Core field properties.
		$field_properties = [
			'label'           => [
				'type'        => 'string',
				'description' => __( 'Field label. e.g. First Name', 'sureforms' ),
			],
			'required'        => [
				'type'        => 'boolean',
				'description' => __( 'Whether the field is required.', 'sureforms' ),
			],
			'fieldType'       => [
				'type'        => 'string',
				'description' => __( 'The field type.', 'sureforms' ),
				'enum'        => $field_types,
			],
			'helpText'        => [
				'type'        => 'string',
				'description' => __( 'Help text describing the field.', 'sureforms' ),
			],
			'defaultValue'    => [
				'type'        => 'string',
				'description' => __( 'Default value for the field.', 'sureforms' ),
			],
			'fieldOptions'    => [
				'type'        => 'array',
				'description' => __( 'Options for dropdown or multi-choice fields.', 'sureforms' ),
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'optionTitle' => [ 'type' => 'string' ],
						'label'       => [ 'type' => 'string' ],
					],
				],
			],
			'singleSelection' => [
				'type'        => 'boolean',
				'description' => __( 'Allow only single selection in multi-choice field.', 'sureforms' ),
			],
			'isUnique'        => [
				'type'        => 'boolean',
				'description' => __( 'Whether the field value must be unique.', 'sureforms' ),
			],
			'textLength'      => [
				'type'        => 'integer',
				'description' => __( 'Maximum character length.', 'sureforms' ),
			],
		];

		/**
		 * Filter additional field properties for form ability schemas.
		 *
		 * Pro and third-party plugins can use this to add field-specific
		 * properties (e.g. upload, rating, date-picker, time-picker options).
		 *
		 * @param array<string,array<string,mixed>> $properties Additional field properties. Default empty array.
		 * @since 2.5.2
		 */
		$additional_properties = apply_filters( 'srfm_ability_form_field_properties', [] );

		if ( ! empty( $additional_properties ) && is_array( $additional_properties ) ) {
			$field_properties = array_merge( $field_properties, $additional_properties );
		}

		return $field_properties;
	}

	/**
	 * Sanitize form field string properties before passing to Field_Mapping.
	 *
	 * @param array<int,array<string,mixed>> $fields Raw form fields from input.
	 * @since 2.5.2
	 * @return array<int,array<string,mixed>> Sanitized form fields.
	 */
	protected function sanitize_form_fields( array $fields ) {
		foreach ( $fields as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( isset( $field['label'] ) && is_string( $field['label'] ) ) {
				$fields[ $index ]['label'] = sanitize_text_field( $field['label'] );
			}
			if ( isset( $field['helpText'] ) && is_string( $field['helpText'] ) ) {
				$fields[ $index ]['helpText'] = sanitize_text_field( $field['helpText'] );
			}
			if ( isset( $field['defaultValue'] ) && is_string( $field['defaultValue'] ) ) {
				$fields[ $index ]['defaultValue'] = sanitize_text_field( $field['defaultValue'] );
			}
			if ( ! empty( $field['fieldOptions'] ) && is_array( $field['fieldOptions'] ) ) {
				// @phpstan-ignore-next-line -- $field['fieldOptions'] is validated as array above.
				$field_options = $field['fieldOptions'];
				foreach ( $field_options as $opt_index => $option ) {
					if ( ! is_array( $option ) ) {
						continue;
					}
					if ( isset( $option['optionTitle'] ) && is_string( $option['optionTitle'] ) ) {
						$option['optionTitle'] = sanitize_text_field( $option['optionTitle'] );
					}
					if ( isset( $option['label'] ) && is_string( $option['label'] ) ) {
						$option['label'] = sanitize_text_field( $option['label'] );
					}
					$field_options[ $opt_index ] = $option;
				}
				$fields[ $index ]['fieldOptions'] = $field_options;
			}
		}
		return $fields;
	}
}
