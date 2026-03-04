<?php
/**
 * Update Form Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Create_New_Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update_Form ability class.
 *
 * Updates an existing SureForms form's title, status, and/or metadata.
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
		$this->description = __( 'Update an existing SureForms form title, status (publish/draft/private/trash), and/or metadata settings. Use status "trash" to trash a form, or change from "trash" to another status to restore it.', 'sureforms' );
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
		$form_id = absint( $input['form_id'] );
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
			$new_status     = sanitize_text_field( $input['status'] );
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
			$new_title = sanitize_text_field( $input['title'] );

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
		if ( ! empty( $meta_data['general'] ) ) {
			$general = $meta_data['general'];

			if ( isset( $general['submitText'] ) ) {
				$post_metas['_srfm_submit_button_text'] = sanitize_text_field( $general['submitText'] );
			}
			if ( isset( $general['useLabelAsPlaceholder'] ) ) {
				$post_metas['_srfm_use_label_as_placeholder'] = (bool) $general['useLabelAsPlaceholder'];
			}
		}

		// Confirmation message.
		if ( ! empty( $meta_data['formConfirmation']['confirmationMessage'] ) ) {
			$post_metas['_srfm_confirmation_message'] = wp_kses_post( $meta_data['formConfirmation']['confirmationMessage'] );
		}

		// Instant form settings.
		if ( ! empty( $meta_data['instantForm'] ) ) {
			$instant = $meta_data['instantForm'];

			if ( isset( $instant['instantForm'] ) && $instant['instantForm'] ) {
				$post_metas['_srfm_instant_form'] = 'enabled';
			}
			if ( isset( $instant['showTitle'] ) ) {
				$post_metas['_srfm_single_page_form_title'] = $instant['showTitle'] ? 1 : 0;
			}
			if ( ! empty( $instant['formWidth'] ) ) {
				$width = absint( $instant['formWidth'] );
				if ( $width >= 560 && $width <= 1000 ) {
					$post_metas['_srfm_form_container_width'] = $width;
				}
			}
			if ( ! empty( $instant['formBackgroundColor'] ) ) {
				$post_metas['_srfm_bg_color'] = sanitize_hex_color( $instant['formBackgroundColor'] );
			}
		}

		// Styling.
		if ( ! empty( $meta_data['styling'] ) ) {
			$styling = $meta_data['styling'];

			if ( ! empty( $styling['submitAlignment'] ) ) {
				$valid_alignments = [ 'left', 'center', 'right', 'full-width' ];
				if ( in_array( $styling['submitAlignment'], $valid_alignments, true ) ) {
					$post_metas['_srfm_submit_alignment'] = sanitize_text_field( $styling['submitAlignment'] );
				}
			}

			if ( 'full-width' === ( $styling['submitAlignment'] ?? '' ) ) {
				$post_metas['_srfm_submit_width']         = '100%';
				$post_metas['_srfm_submit_width_backend'] = '100%';
			}
		}

		// Compliance settings.
		if ( ! empty( $meta_data['compliance'] ) ) {
			$compliance = $meta_data['compliance'];

			if ( ! empty( $compliance['enableCompliance'] ) ) {
				$post_metas['_srfm_compliance'] = true;

				if ( ! empty( $compliance['neverStoreEntries'] ) ) {
					$post_metas['_srfm_compliance_opt'] = 'do-not-store';
				} elseif ( ! empty( $compliance['autoDeleteEntries'] ) && ! empty( $compliance['autoDeleteEntriesDays'] ) ) {
					$post_metas['_srfm_compliance_opt']  = 'auto-delete';
					$post_metas['_srfm_compliance_days'] = absint( $compliance['autoDeleteEntriesDays'] );
				}
			}
		}

		return $post_metas;
	}
}
