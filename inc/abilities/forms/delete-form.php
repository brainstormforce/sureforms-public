<?php
/**
 * Delete Form Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Delete_Form ability class.
 *
 * Trashes or permanently deletes a SureForms form.
 *
 * @since x.x.x
 */
class Delete_Form extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/delete-form';
		$this->label       = __( 'Delete SureForms Form', 'sureforms' );
		$this->description = __( 'Move a SureForms form to trash, or permanently delete it when force is set to true.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_annotations() {
		return [
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to delete.', 'sureforms' ),
				],
				'force'   => [
					'type'        => 'boolean',
					'description' => __( 'If true, permanently deletes the form. Otherwise moves to trash.', 'sureforms' ),
					'default'     => false,
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'         => [ 'type' => 'integer' ],
				'deleted'         => [ 'type' => 'boolean' ],
				'previous_status' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the delete-form ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id = Helper::get_integer_value( $input['form_id'] ?? 0 );
		$force   = ! empty( $input['force'] );
		$post    = get_post( $form_id );

		if ( ! $post || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'srfm_form_not_found',
				__( 'Form not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		$previous_status = $post->post_status;

		if ( $force ) {
			$result = wp_delete_post( $form_id, true );
		} else {
			$result = wp_trash_post( $form_id );
		}

		if ( ! $result ) {
			return new \WP_Error(
				'srfm_delete_failed',
				__( 'Failed to delete the form.', 'sureforms' ),
				[ 'status' => 500 ]
			);
		}

		return [
			'form_id'         => $form_id,
			'deleted'         => true,
			'previous_status' => $previous_status,
		];
	}
}
