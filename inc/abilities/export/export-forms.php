<?php
/**
 * Export Forms Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Export;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Export;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Export_Forms ability class.
 *
 * Exports SureForms forms with their metadata as JSON.
 *
 * @since x.x.x
 */
class Export_Forms extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/export-forms';
		$this->label       = __( 'Export Forms', 'sureforms' );
		$this->description = __( 'Export one or more SureForms forms with their post data and metadata as structured JSON.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_annotations() {
		return [
			'readonly'    => true,
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
				'form_ids' => [
					'type'        => 'array',
					'description' => __( 'Array of form IDs to export.', 'sureforms' ),
					'items'       => [ 'type' => 'integer' ],
					'minItems'    => 1,
				],
			],
			'required'   => [ 'form_ids' ],
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
				'forms' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'post'      => [ 'type' => 'object' ],
							'post_meta' => [ 'type' => 'object' ],
						],
					],
				],
				'count' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute the export-forms ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_ids = $input['form_ids'] ?? [];

		if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
			return new \WP_Error(
				'srfm_missing_form_ids',
				__( 'At least one form ID is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Validate each form ID.
		$validated_ids = [];
		foreach ( $form_ids as $form_id ) {
			$post = get_post( absint( $form_id ) );
			if ( $post && SRFM_FORMS_POST_TYPE === $post->post_type ) {
				$validated_ids[] = $post->ID;
			}
		}

		if ( empty( $validated_ids ) ) {
			return new \WP_Error(
				'srfm_no_valid_forms',
				__( 'No valid SureForms forms found for the provided IDs.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$forms = Export::get_instance()->get_forms_with_meta( $validated_ids );

		return [
			'forms' => $forms,
			'count' => count( $forms ),
		];
	}
}
