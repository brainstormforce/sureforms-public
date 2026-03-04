<?php
/**
 * Create Form Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
use SRFM\Inc\Create_New_Form;
use SRFM\Inc\Helper;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create_Form ability class.
 *
 * Creates a new SureForms form using the existing Field_Mapping engine.
 *
 * @since x.x.x
 */
class Create_Form extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id    = 'sureforms/create-form';
		$this->label = __( 'Create SureForms Form', 'sureforms' );
		$description = __( 'Create a new SureForms form with specified title, fields, metadata, and status. Supports all standard field types (input, email, textarea, dropdown, checkbox, multi-choice, phone, number, url, address, gdpr, payment, inline-button).', 'sureforms' );

		/**
		 * Filter the description for the create-form ability.
		 *
		 * Pro and third-party plugins can append their supported field types.
		 *
		 * @param string $description The ability description.
		 * @since x.x.x
		 */
		$this->description = apply_filters( 'srfm_ability_create_form_description', $description );
		$this->capability  = 'edit_posts';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_annotations() {
		return [
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => false,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_input_schema() {
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
		 * Filter the allowed field types for the create-form ability.
		 *
		 * Pro and third-party plugins can use this to add their own field types.
		 *
		 * @param array<string> $field_types Array of field type slugs.
		 * @since x.x.x
		 */
		$field_types = apply_filters( 'srfm_ability_create_form_field_types', $field_types );

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
		 * Filter additional field properties for the create-form ability schema.
		 *
		 * Pro and third-party plugins can use this to add field-specific
		 * properties (e.g. upload, rating, date-picker, time-picker options).
		 *
		 * @param array<string,array<string,mixed>> $properties Additional field properties. Default empty array.
		 * @since x.x.x
		 */
		$additional_properties = apply_filters( 'srfm_ability_create_form_field_properties', [] );

		if ( ! empty( $additional_properties ) && is_array( $additional_properties ) ) {
			$field_properties = array_merge( $field_properties, $additional_properties );
		}

		return [
			'type'       => 'object',
			'properties' => [
				'formTitle'    => [
					'type'        => 'string',
					'description' => __( 'Title of the form in 5-10 words.', 'sureforms' ),
				],
				'formFields'   => [
					'type'        => 'array',
					'description' => __( 'Array of form field definitions.', 'sureforms' ),
					'items'       => [
						'type'       => 'object',
						'properties' => $field_properties,
						'required'   => [ 'label', 'fieldType' ],
					],
				],
				'formMetaData' => [
					'type'        => 'object',
					'description' => __( 'Optional form metadata including confirmation, email, compliance, and styling settings.', 'sureforms' ),
					'properties'  => [
						'formConfirmation'  => [
							'type'       => 'object',
							'properties' => [
								'confirmationMessage' => [
									'type'        => 'string',
									'description' => __( 'Message displayed after successful submission.', 'sureforms' ),
								],
							],
						],
						'emailConfirmation' => [
							'type'       => 'object',
							'properties' => [
								'name'      => [ 'type' => 'string' ],
								'subject'   => [ 'type' => 'string' ],
								'emailBody' => [ 'type' => 'string' ],
							],
						],
						'compliance'        => [
							'type'       => 'object',
							'properties' => [
								'enableCompliance'      => [ 'type' => 'boolean' ],
								'neverStoreEntries'     => [ 'type' => 'boolean' ],
								'autoDeleteEntries'     => [ 'type' => 'boolean' ],
								'autoDeleteEntriesDays' => [ 'type' => 'string' ],
							],
						],
						'instantForm'       => [
							'type'       => 'object',
							'properties' => [
								'instantForm'         => [ 'type' => 'boolean' ],
								'showTitle'           => [ 'type' => 'boolean' ],
								'bannerColor'         => [ 'type' => 'string' ],
								'useBannerColorAsBackground' => [ 'type' => 'boolean' ],
								'formBackgroundColor' => [ 'type' => 'string' ],
								'formWidth'           => [ 'type' => 'integer' ],
								'formSlug'            => [ 'type' => 'string' ],
							],
						],
						'general'           => [
							'type'       => 'object',
							'properties' => [
								'useLabelAsPlaceholder' => [ 'type' => 'boolean' ],
								'submitText'            => [ 'type' => 'string' ],
							],
						],
						'styling'           => [
							'type'       => 'object',
							'properties' => [
								'primaryColor'       => [ 'type' => 'string' ],
								'textColor'          => [ 'type' => 'string' ],
								'textColorOnPrimary' => [ 'type' => 'string' ],
								'fieldSpacing'       => [ 'type' => 'string' ],
								'submitAlignment'    => [ 'type' => 'string' ],
							],
						],
					],
				],
				'formStatus'   => [
					'type'        => 'string',
					'description' => __( 'Form publish status.', 'sureforms' ),
					'enum'        => [ 'publish', 'draft', 'private' ],
					'default'     => 'draft',
				],
			],
			'required'   => [ 'formTitle', 'formFields' ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'   => [ 'type' => 'integer' ],
				'title'     => [ 'type' => 'string' ],
				'status'    => [ 'type' => 'string' ],
				'edit_url'  => [ 'type' => 'string' ],
				'shortcode' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the create-form ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_title  = sanitize_text_field( Helper::get_string_value( $input['formTitle'] ?? '' ) );
		$form_fields = $input['formFields'];
		$form_status = ! empty( $input['formStatus'] ) ? sanitize_text_field( Helper::get_string_value( $input['formStatus'] ) ) : 'draft';
		$meta_data   = ! empty( $input['formMetaData'] ) ? Helper::get_array_value( $input['formMetaData'] ) : [];

		$allowed_statuses = [ 'publish', 'draft', 'private' ];
		if ( ! in_array( $form_status, $allowed_statuses, true ) ) {
			$form_status = 'draft';
		}

		if ( empty( $form_title ) ) {
			return new \WP_Error(
				'srfm_missing_title',
				__( 'Form title is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $form_fields ) || ! is_array( $form_fields ) ) {
			return new \WP_Error(
				'srfm_missing_fields',
				__( 'At least one form field is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Build a mock WP_REST_Request to reuse Field_Mapping.
		$request = new WP_REST_Request( 'POST' );
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formFields' => $form_fields,
				],
			]
		);

		$post_content = Field_Mapping::generate_gutenberg_fields_from_questions( $request );

		if ( empty( $post_content ) ) {
			return new \WP_Error(
				'srfm_field_mapping_failed',
				__( 'Failed to generate form fields from the provided data.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get default post meta.
		$post_metas = Create_New_Form::get_default_meta_keys();

		// Apply metadata overrides from input.
		$post_metas = $this->apply_metadata_overrides( $post_metas, $meta_data );

		$post_id = wp_insert_post(
			[
				'post_title'   => $form_title,
				'post_content' => $post_content,
				'post_status'  => $form_status,
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'meta_input'   => $post_metas,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( empty( $post_id ) ) {
			return new \WP_Error(
				'srfm_create_failed',
				__( 'Failed to create the form.', 'sureforms' ),
				[ 'status' => 500 ]
			);
		}

		return [
			'form_id'   => $post_id,
			'title'     => $form_title,
			'status'    => $form_status,
			'edit_url'  => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			'shortcode' => sprintf( '[sureforms id="%d"]', $post_id ),
		];
	}

	/**
	 * Apply metadata overrides from ability input to post meta.
	 *
	 * @param array<string,mixed> $post_metas Default post meta values.
	 * @param array<string,mixed> $meta_data  Metadata from ability input.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	private function apply_metadata_overrides( $post_metas, $meta_data ) {
		if ( empty( $meta_data ) ) {
			return $post_metas;
		}

		// General settings.
		$general = Helper::get_array_value( $meta_data['general'] ?? [] );
		if ( ! empty( $general ) ) {
			if ( isset( $general['submitText'] ) ) {
				$post_metas['_srfm_submit_button_text'] = sanitize_text_field( Helper::get_string_value( $general['submitText'] ) );
			}
			if ( isset( $general['useLabelAsPlaceholder'] ) ) {
				$post_metas['_srfm_use_label_as_placeholder'] = (bool) $general['useLabelAsPlaceholder'];
			}
		}

		// Confirmation message.
		$form_confirmation = Helper::get_array_value( $meta_data['formConfirmation'] ?? [] );
		if ( ! empty( $form_confirmation['confirmationMessage'] ) ) {
			$post_metas['_srfm_confirmation_message'] = wp_kses_post( Helper::get_string_value( $form_confirmation['confirmationMessage'] ) );
		}

		// Instant form settings.
		$instant = Helper::get_array_value( $meta_data['instantForm'] ?? [] );
		if ( ! empty( $instant ) ) {
			if ( isset( $instant['instantForm'] ) && $instant['instantForm'] ) {
				$post_metas['_srfm_instant_form'] = 'enabled';
			}
			if ( isset( $instant['showTitle'] ) ) {
				$post_metas['_srfm_single_page_form_title'] = $instant['showTitle'] ? 1 : 0;
			}
			if ( ! empty( $instant['formWidth'] ) ) {
				$width = Helper::get_integer_value( $instant['formWidth'] );
				if ( $width >= 560 && $width <= 1000 ) {
					$post_metas['_srfm_form_container_width'] = $width;
				}
			}
			if ( ! empty( $instant['formBackgroundColor'] ) ) {
				$post_metas['_srfm_bg_color'] = sanitize_hex_color( Helper::get_string_value( $instant['formBackgroundColor'] ) );
			}
		}

		// Styling.
		$styling = Helper::get_array_value( $meta_data['styling'] ?? [] );
		if ( ! empty( $styling ) ) {
			if ( ! empty( $styling['submitAlignment'] ) ) {
				$valid_alignments = [ 'left', 'center', 'right', 'full-width' ];
				if ( in_array( $styling['submitAlignment'], $valid_alignments, true ) ) {
					$post_metas['_srfm_submit_alignment'] = sanitize_text_field( Helper::get_string_value( $styling['submitAlignment'] ) );
				}
			}

			if ( 'full-width' === ( $styling['submitAlignment'] ?? '' ) ) {
				$post_metas['_srfm_submit_width']         = '100%';
				$post_metas['_srfm_submit_width_backend'] = '100%';
			}
		}

		// Compliance settings.
		$compliance = Helper::get_array_value( $meta_data['compliance'] ?? [] );
		if ( ! empty( $compliance ) ) {
			if ( ! empty( $compliance['enableCompliance'] ) ) {
				$post_metas['_srfm_compliance'] = true;

				if ( ! empty( $compliance['neverStoreEntries'] ) ) {
					$post_metas['_srfm_compliance_opt'] = 'do-not-store';
				} elseif ( ! empty( $compliance['autoDeleteEntries'] ) && ! empty( $compliance['autoDeleteEntriesDays'] ) ) {
					$post_metas['_srfm_compliance_opt']  = 'auto-delete';
					$post_metas['_srfm_compliance_days'] = Helper::get_integer_value( $compliance['autoDeleteEntriesDays'] );
				}
			}
		}

		return $post_metas;
	}
}
