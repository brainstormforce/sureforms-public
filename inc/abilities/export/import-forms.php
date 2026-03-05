<?php
/**
 * Import Forms Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Export;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Export;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Import_Forms ability class.
 *
 * Imports SureForms forms from previously exported JSON data.
 *
 * @since x.x.x
 */
class Import_Forms extends Abstract_Ability {
	/**
	 * Maximum number of forms that can be imported at once.
	 *
	 * @since x.x.x
	 */
	public const MAX_IMPORT_FORMS = 50;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/import-forms';
		$this->label       = __( 'Import Forms', 'sureforms' );
		$this->description = __( 'Import SureForms forms from exported JSON data. Creates new form posts from the provided data structure.', 'sureforms' );
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
			'idempotent'  => false,
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
				'forms_data'     => [
					'type'        => 'array',
					'description' => __( 'Array of form data objects, each with post and post_meta keys (same format as export output).', 'sureforms' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'post'      => [ 'type' => 'object' ],
							'post_meta' => [ 'type' => 'object' ],
						],
						'required'   => [ 'post', 'post_meta' ],
					],
				],
				'default_status' => [
					'type'        => 'string',
					'description' => __( 'Default status for imported forms. Defaults to draft.', 'sureforms' ),
					'enum'        => [ 'draft', 'publish', 'private' ],
					'default'     => 'draft',
				],
			],
			'required'   => [ 'forms_data' ],
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
				'forms_mapping'  => [
					'type'        => 'object',
					'description' => __( 'Mapping of old form IDs to new form IDs.', 'sureforms' ),
				],
				'imported_count' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute the import-forms ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$forms_data     = $input['forms_data'] ?? [];
		$default_status = ! empty( $input['default_status'] ) ? sanitize_text_field( Helper::get_string_value( $input['default_status'] ) ) : 'draft';

		if ( empty( $forms_data ) || ! is_array( $forms_data ) ) {
			return new \WP_Error(
				'srfm_missing_forms_data',
				__( 'Forms data is required for import.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Enforce max import limit.
		if ( count( $forms_data ) > self::MAX_IMPORT_FORMS ) {
			return new \WP_Error(
				'srfm_too_many_forms',
				/* translators: %d: maximum number of forms allowed per import */
				sprintf( __( 'Cannot import more than %d forms at once.', 'sureforms' ), self::MAX_IMPORT_FORMS ),
				[ 'status' => 400 ]
			);
		}

		// Get allowed meta keys from the Export class.
		$allowed_meta_keys = Export::get_instance()->get_unserialized_post_metas();

		// Validate and sanitize data structure.
		foreach ( $forms_data as $index => $form_data ) {
			if ( ! is_array( $form_data ) || ! isset( $form_data['post'] ) || ! isset( $form_data['post_meta'] ) ) {
				return new \WP_Error(
					'srfm_invalid_form_data',
					__( 'Each form must have post and post_meta keys.', 'sureforms' ),
					[ 'status' => 400 ]
				);
			}

			// Validate post_type if provided.
			if ( ! empty( $form_data['post']['post_type'] ) && SRFM_FORMS_POST_TYPE !== $form_data['post']['post_type'] ) {
				return new \WP_Error(
					'srfm_invalid_post_type',
					__( 'Only SureForms form post type is allowed for import.', 'sureforms' ),
					[ 'status' => 400 ]
				);
			}

			// Defense-in-depth: sanitize post_title and post_content at the ability layer.
			if ( isset( $form_data['post']['post_title'] ) ) {
				$forms_data[ $index ]['post']['post_title'] = sanitize_text_field( $form_data['post']['post_title'] );
			}

			if ( isset( $form_data['post']['post_content'] ) ) {
				$forms_data[ $index ]['post']['post_content'] = wp_kses_post( $form_data['post']['post_content'] );
			}

			// Whitelist meta keys — only allow known meta keys through.
			if ( is_array( $form_data['post_meta'] ) ) {
				$forms_data[ $index ]['post_meta'] = array_intersect_key(
					$form_data['post_meta'],
					array_flip( $allowed_meta_keys )
				);
			}
		}

		$result = Export::get_instance()->import_forms_with_meta( $forms_data, $default_status );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return [
			'forms_mapping'  => $result,
			'imported_count' => count( $result ),
		];
	}
}
