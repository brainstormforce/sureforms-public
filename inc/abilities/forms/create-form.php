<?php
/**
 * Create Form Ability.
 *
 * @package sureforms
 * @since 2.5.2
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
 * @since 2.5.2
 */
class Create_Form extends Abstract_Ability {
	use Form_Field_Schema;
	use Form_Metadata;

	/**
	 * Constructor.
	 *
	 * @since 2.5.2
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
		 * @since 2.5.2
		 */
		$this->description = apply_filters( 'srfm_ability_create_form_description', $description );
		$this->capability  = 'manage_options';
		$this->gated       = 'srfm_abilities_api_edit';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.0,
			'openWorldHint' => false,
			'instructions'  => 'Confirm the form title, field list, and publish status with the user before creating.',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_input_schema() {
		$field_properties = $this->get_form_field_schema();

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
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
					'description' => __( 'Optional form metadata including confirmation, compliance, and styling settings.', 'sureforms' ),
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
				'formStatus'   => [
					'type'        => 'string',
					'description' => __( 'Form publish status.', 'sureforms' ),
					'enum'        => [ 'publish', 'draft', 'private' ],
					'default'     => 'draft',
				],
			],
			'required'             => [ 'formTitle', 'formFields' ],
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

		// Sanitize form fields before passing to Field_Mapping.
		$form_fields = $this->sanitize_form_fields( $form_fields );

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

		if ( is_wp_error( $post_content ) ) {
			return $post_content;
		}

		if ( empty( $post_content ) ) {
			return new \WP_Error(
				'srfm_field_mapping_failed',
				__( 'Failed to generate form fields from the provided data.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get default post meta.
		$post_metas = Create_New_Form::get_default_meta_keys();

		// Add serialized meta defaults for metadata overrides.
		$post_metas['_srfm_instant_form_settings'] = [
			'bg_type'                       => 'color',
			'bg_color'                      => '#ffffff',
			'bg_image'                      => '',
			'site_logo'                     => '',
			'cover_type'                    => 'color',
			'cover_color'                   => '#111C44',
			'cover_image'                   => '',
			'enable_instant_form'           => false,
			'form_container_width'          => 620,
			'single_page_form_title'        => true,
			'use_banner_as_page_background' => false,
		];
		$post_metas['_srfm_form_confirmation']     = [];
		$post_metas['_srfm_compliance']            = [];
		$post_metas['_srfm_forms_styling']         = [];
		$post_metas['_srfm_email_notification']    = [];
		$post_metas['_srfm_form_restriction']      = '';
		$post_metas['_srfm_form_custom_css']       = '';

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
}
