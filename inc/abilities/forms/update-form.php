<?php
/**
 * Update Form Ability.
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
 * Update_Form ability class.
 *
 * Updates an existing SureForms form's title, status, fields, and/or metadata.
 *
 * @since x.x.x
 */
class Update_Form extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/update-form';
		$this->label       = __( 'Update SureForms Form', 'sureforms' );
		$this->description = __( 'Update an existing SureForms form title, status (publish/draft/private/trash), fields, and/or metadata settings. Use status "trash" to trash a form, or change from "trash" to another status to restore it. Providing formFields replaces all existing fields.', 'sureforms' );
		$this->capability  = 'edit_posts';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_annotations() {
		return [
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => true,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
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
		 * Filter the allowed field types for the update-form ability.
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
		 * Filter additional field properties for the update-form ability schema.
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
				'form_id'      => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to update.', 'sureforms' ),
				],
				'title'        => [
					'type'        => 'string',
					'description' => __( 'New title for the form.', 'sureforms' ),
				],
				'status'       => [
					'type'        => 'string',
					'description' => __( 'New status for the form.', 'sureforms' ),
					'enum'        => [ 'publish', 'draft', 'private', 'trash' ],
				],
				'formFields'   => [
					'type'        => 'array',
					'description' => __( 'Array of form field definitions. Providing this replaces all existing fields.', 'sureforms' ),
					'items'       => [
						'type'       => 'object',
						'properties' => $field_properties,
						'required'   => [ 'label', 'fieldType' ],
					],
				],
				'formMetaData' => [
					'type'        => 'object',
					'description' => __( 'Optional form metadata including confirmation, email, compliance, and styling settings. Same schema as create-form.', 'sureforms' ),
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
			],
			'required'   => [ 'form_id' ],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'         => [ 'type' => 'integer' ],
				'title'           => [ 'type' => 'string' ],
				'status'          => [ 'type' => 'string' ],
				'previous_status' => [ 'type' => 'string' ],
				'edit_url'        => [ 'type' => 'string' ],
				'updated_fields'  => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];
	}

	/**
	 * Execute the update-form ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id = Helper::get_integer_value( $input['form_id'] ?? 0 );
		$post    = get_post( $form_id );

		if ( ! $post || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'srfm_form_not_found',
				__( 'Form not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		$previous_status = $post->post_status;
		$updated_fields  = [];

		// Handle status changes.
		if ( ! empty( $input['status'] ) ) {
			$new_status     = sanitize_text_field( Helper::get_string_value( $input['status'] ) );
			$allowed_status = [ 'publish', 'draft', 'private', 'trash' ];

			if ( in_array( $new_status, $allowed_status, true ) ) {
				if ( 'trash' === $new_status && 'trash' !== $previous_status ) {
					wp_trash_post( $form_id );
					$updated_fields[] = 'status';
				} elseif ( 'trash' !== $new_status && 'trash' === $previous_status ) {
					wp_untrash_post( $form_id );
					// After untrash, set the desired status.
					wp_update_post(
						[
							'ID'          => $form_id,
							'post_status' => $new_status,
						]
					);
					$updated_fields[] = 'status';
				} elseif ( $new_status !== $previous_status ) {
					wp_update_post(
						[
							'ID'          => $form_id,
							'post_status' => $new_status,
						]
					);
					$updated_fields[] = 'status';
				}
			}
		}

		// Handle title changes.
		if ( ! empty( $input['title'] ) ) {
			$new_title = sanitize_text_field( Helper::get_string_value( $input['title'] ) );

			if ( $new_title !== $post->post_title ) {
				wp_update_post(
					[
						'ID'         => $form_id,
						'post_title' => $new_title,
					]
				);
				$updated_fields[] = 'title';
			}
		}

		// Handle metadata changes.
		if ( ! empty( $input['formMetaData'] ) && is_array( $input['formMetaData'] ) ) {
			$current_metas = [];
			$meta_keys     = Create_New_Form::get_default_meta_keys();

			foreach ( array_keys( $meta_keys ) as $meta_key ) {
				$current_metas[ $meta_key ] = get_post_meta( $form_id, $meta_key, true );
			}

			$updated_metas = $this->apply_metadata_overrides( $current_metas, $input['formMetaData'] );

			foreach ( $updated_metas as $meta_key => $meta_value ) {
				if ( isset( $current_metas[ $meta_key ] ) && $current_metas[ $meta_key ] === $meta_value ) {
					continue;
				}
				update_post_meta( $form_id, $meta_key, $meta_value );
			}

			$updated_fields[] = 'metadata';
		}

		// Handle field changes.
		if ( ! empty( $input['formFields'] ) && is_array( $input['formFields'] ) ) {
			$request = new WP_REST_Request( 'POST' );
			$request->set_param(
				'form_data',
				[
					'form' => [ 'formFields' => $input['formFields'] ],
				]
			);

			$post_content = Field_Mapping::generate_gutenberg_fields_from_questions( $request );

			if ( ! empty( $post_content ) ) {
				wp_update_post(
					[
						'ID'           => $form_id,
						'post_content' => $post_content,
					]
				);
				$updated_fields[] = 'fields';
			}
		}

		// Re-fetch post to get the current state.
		$updated_post = get_post( $form_id );

		return [
			'form_id'         => $form_id,
			'title'           => $updated_post ? $updated_post->post_title : $post->post_title,
			'status'          => $updated_post ? $updated_post->post_status : $post->post_status,
			'previous_status' => $previous_status,
			'edit_url'        => admin_url( 'post.php?post=' . $form_id . '&action=edit' ),
			'updated_fields'  => $updated_fields,
		];
	}

	/**
	 * Apply metadata overrides from ability input to post meta.
	 *
	 * Reuses the same logic as Create_Form for consistency.
	 *
	 * @param array<string,mixed> $post_metas Current post meta values.
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
