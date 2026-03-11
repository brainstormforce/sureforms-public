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
	use Form_Field_Schema;
	use Form_Metadata;

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
		$this->gated       = 'srfm_abilities_api_edit';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => true,
			'priority'      => 2.0,
			'openWorldHint' => false,
			'instructions'  => 'Confirm the changes with the user before executing. Setting status to trash moves the form out of production. Providing formFields replaces ALL existing fields.',
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
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
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
						'formConfirmation'  => [
							'type'       => 'object',
							'properties' => [
								'confirmationMessage' => [
									'type'        => 'string',
									'description' => __( 'Message displayed after successful submission.', 'sureforms' ),
								],
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
								'formBackgroundColor' => [ 'type' => 'string' ],
								'formWidth'           => [ 'type' => 'integer' ],
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
								'submitAlignment' => [ 'type' => 'string' ],
							],
						],
						'formStyling'       => [
							'type'        => 'object',
							'description' => __( 'Form styling settings including colors and spacing.', 'sureforms' ),
							'properties'  => [
								'primaryColor'       => [
									'type'        => 'string',
									'description' => __( 'Primary/accent color as hex (e.g. #111C44).', 'sureforms' ),
								],
								'textColor'          => [
									'type'        => 'string',
									'description' => __( 'Text color as hex (e.g. #1E1E1E).', 'sureforms' ),
								],
								'textColorOnPrimary' => [
									'type'        => 'string',
									'description' => __( 'Text color on primary backgrounds as hex (e.g. #FFFFFF).', 'sureforms' ),
								],
								'fieldSpacing'       => [
									'type'        => 'string',
									'description' => __( 'Spacing between fields.', 'sureforms' ),
									'enum'        => [ 'small', 'medium', 'large' ],
								],
							],
						],
						'emailNotification' => [
							'type'        => 'object',
							'description' => __( 'Email notification settings for the first notification. Merges with existing.', 'sureforms' ),
							'properties'  => [
								'status'       => [
									'type'        => 'boolean',
									'description' => __( 'Enable or disable the notification.', 'sureforms' ),
								],
								'name'         => [
									'type'        => 'string',
									'description' => __( 'Notification name.', 'sureforms' ),
								],
								'emailTo'      => [
									'type'        => 'string',
									'description' => __( 'Recipient email. Supports smart tags like {admin_email}.', 'sureforms' ),
								],
								'emailReplyTo' => [ 'type' => 'string' ],
								'fromName'     => [
									'type'        => 'string',
									'description' => __( 'Sender name. Supports smart tags like {site_title}.', 'sureforms' ),
								],
								'fromEmail'    => [ 'type' => 'string' ],
								'emailCc'      => [ 'type' => 'string' ],
								'emailBcc'     => [ 'type' => 'string' ],
								'subject'      => [
									'type'        => 'string',
									'description' => __( 'Email subject. Supports smart tags like {form_title}.', 'sureforms' ),
								],
								'emailBody'    => [
									'type'        => 'string',
									'description' => __( 'Email body. Supports smart tags like {all_data}.', 'sureforms' ),
								],
							],
						],
						'formRestriction'   => [
							'type'        => 'object',
							'description' => __( 'Form submission restrictions and scheduling.', 'sureforms' ),
							'properties'  => [
								'status'                 => [
									'type'        => 'boolean',
									'description' => __( 'Enable entry limit restriction.', 'sureforms' ),
								],
								'maxEntries'             => [
									'type'        => 'integer',
									'description' => __( 'Maximum number of entries allowed (0 = unlimited).', 'sureforms' ),
								],
								'date'                   => [
									'type'        => 'string',
									'description' => __( 'Expiry date (YYYY-MM-DD).', 'sureforms' ),
								],
								'hours'                  => [ 'type' => 'string' ],
								'minutes'                => [ 'type' => 'string' ],
								'meridiem'               => [
									'type' => 'string',
									'enum' => [ 'AM', 'PM' ],
								],
								'message'                => [
									'type'        => 'string',
									'description' => __( 'Message shown when form is closed.', 'sureforms' ),
								],
								'schedulingStatus'       => [
									'type'        => 'boolean',
									'description' => __( 'Enable form scheduling.', 'sureforms' ),
								],
								'startDate'              => [
									'type'        => 'string',
									'description' => __( 'Schedule start date (YYYY-MM-DD).', 'sureforms' ),
								],
								'startHours'             => [ 'type' => 'string' ],
								'startMinutes'           => [ 'type' => 'string' ],
								'startMeridiem'          => [
									'type' => 'string',
									'enum' => [ 'AM', 'PM' ],
								],
								'schedulingNotStartedMessage' => [ 'type' => 'string' ],
								'schedulingEndedMessage' => [ 'type' => 'string' ],
							],
						],
						'formCustomCss'     => [
							'type'        => 'string',
							'description' => __( 'Custom CSS for the form.', 'sureforms' ),
						],
					],
				],
			],
			'required'             => [ 'form_id' ],
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

			// Load serialized object metas needed for metadata overrides.
			$instant_raw      = get_post_meta( $form_id, '_srfm_instant_form_settings', true );
			$confirmation_raw = get_post_meta( $form_id, '_srfm_form_confirmation', true );
			$compliance_raw   = get_post_meta( $form_id, '_srfm_compliance', true );

			$current_metas['_srfm_instant_form_settings'] = Helper::get_array_value( is_array( $instant_raw ) ? $instant_raw : [] );
			$current_metas['_srfm_form_confirmation']     = Helper::get_array_value( is_array( $confirmation_raw ) ? $confirmation_raw : [] );
			$current_metas['_srfm_compliance']            = Helper::get_array_value( is_array( $compliance_raw ) ? $compliance_raw : [] );

			// Load additional serialized metas.
			$styling_raw = get_post_meta( $form_id, '_srfm_forms_styling', true );
			$notif_raw   = get_post_meta( $form_id, '_srfm_email_notification', true );

			$current_metas['_srfm_forms_styling']      = Helper::get_array_value( is_array( $styling_raw ) ? $styling_raw : [] );
			$current_metas['_srfm_email_notification'] = Helper::get_array_value( is_array( $notif_raw ) ? $notif_raw : [] );
			$current_metas['_srfm_form_restriction']   = Helper::get_string_value( get_post_meta( $form_id, '_srfm_form_restriction', true ) );
			$current_metas['_srfm_form_custom_css']    = Helper::get_string_value( get_post_meta( $form_id, '_srfm_form_custom_css', true ) );

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
