<?php
/**
 * Delete Entry Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Entries;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Delete_Entry ability class.
 *
 * Permanently deletes one or more form submission entries.
 *
 * @since x.x.x
 */
class Delete_Entry extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/delete-entry';
		$this->label       = __( 'Delete Entry', 'sureforms' );
		$this->description = __( 'Permanently delete one or more SureForms form submission entries. This action cannot be undone.', 'sureforms' );
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
			'destructive' => true,
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
				'entry_ids' => [
					'type'        => 'array',
					'description' => __( 'Array of entry IDs to permanently delete.', 'sureforms' ),
					'items'       => [ 'type' => 'integer' ],
				],
			],
			'required'   => [ 'entry_ids' ],
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
				'success' => [ 'type' => 'boolean' ],
				'deleted' => [ 'type' => 'integer' ],
				'errors'  => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];
	}

	/**
	 * Execute the delete-entry ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( array $input ) {
		$entry_ids = $input['entry_ids'] ?? [];

		if ( empty( $entry_ids ) || ! is_array( $entry_ids ) ) {
			return new \WP_Error(
				'srfm_missing_entry_ids',
				__( 'At least one entry ID is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		return Entries::delete_entries( $entry_ids );
	}
}
