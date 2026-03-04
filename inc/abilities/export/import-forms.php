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

		// Validate data structure.
		foreach ( $forms_data as $form_data ) {
			if ( ! is_array( $form_data ) || ! isset( $form_data['post'] ) || ! isset( $form_data['post_meta'] ) ) {
				return new \WP_Error(
					'srfm_invalid_form_data',
					__( 'Each form must have post and post_meta keys.', 'sureforms' ),
					[ 'status' => 400 ]
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
