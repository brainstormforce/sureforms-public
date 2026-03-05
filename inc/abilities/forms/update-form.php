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
	use Form_Field_Schema_Trait;
	use Form_Metadata_Trait;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/update-form';
		$this->label       = __( 'Update SureForms Form', 'sureforms' );
		$this->description = __( 'Update an existing SureForms form title, status (publish/draft/private/trash), fields, and/or metadata settings. Use status "trash" to trash a form, or change from "trash" to another status to restore it. Providing formFields replaces all existing fields.', 'sureforms' );
		$this->capability  = 'manage_options';
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
		$field_properties = $this->get_form_field_schema();

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
					'description' => __( 'Optional form metadata including confirmation, compliance, and styling settings. Same schema as create-form.', 'sureforms' ),
					'properties'  => [
						'formConfirmation' => [
							'type'       => 'object',
							'properties' => [
								'confirmationMessage' => [
									'type'        => 'string',
									'description' => __( 'Message displayed after successful submission.', 'sureforms' ),
								],
							],
						],
						'compliance'       => [
							'type'       => 'object',
							'properties' => [
								'enableCompliance'      => [ 'type' => 'boolean' ],
								'neverStoreEntries'     => [ 'type' => 'boolean' ],
								'autoDeleteEntries'     => [ 'type' => 'boolean' ],
								'autoDeleteEntriesDays' => [ 'type' => 'string' ],
							],
						],
						'instantForm'      => [
							'type'       => 'object',
							'properties' => [
								'instantForm'         => [ 'type' => 'boolean' ],
								'showTitle'           => [ 'type' => 'boolean' ],
								'formBackgroundColor' => [ 'type' => 'string' ],
								'formWidth'           => [ 'type' => 'integer' ],
							],
						],
						'general'          => [
							'type'       => 'object',
							'properties' => [
								'useLabelAsPlaceholder' => [ 'type' => 'boolean' ],
								'submitText'            => [ 'type' => 'string' ],
							],
						],
						'styling'          => [
							'type'       => 'object',
							'properties' => [
								'submitAlignment' => [ 'type' => 'string' ],
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
			// Sanitize form fields before passing to Field_Mapping.
			$form_fields = $this->sanitize_form_fields( $input['formFields'] );

			$request = new WP_REST_Request( 'POST' );
			$request->set_param(
				'form_data',
				[
					'form' => [ 'formFields' => $form_fields ],
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
}
