<?php
/**
 * Update Entry Status Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Entries;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update_Entry_Status ability class.
 *
 * Updates the status of one or more form submission entries.
 *
 * @since x.x.x
 */
class Update_Entry_Status extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/update-entry-status';
		$this->label       = __( 'Update Entry Status', 'sureforms' );
		$this->description = __( 'Update the status of one or more SureForms form submission entries. Supports read, unread, trash, and restore operations.', 'sureforms' );
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
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'entry_ids' => [
					'type'        => 'array',
					'description' => __( 'Array of entry IDs to update.', 'sureforms' ),
					'items'       => [ 'type' => 'integer' ],
				],
				'status'    => [
					'type'        => 'string',
					'description' => __( 'New status for the entries.', 'sureforms' ),
					'enum'        => [ 'read', 'unread', 'trash', 'restore' ],
				],
			],
			'required'             => [ 'entry_ids', 'status' ],
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
				'updated' => [ 'type' => 'integer' ],
				'errors'  => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];
	}

	/**
	 * Execute the update-entry-status ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$entry_ids = $input['entry_ids'] ?? [];
		$status    = sanitize_text_field( Helper::get_string_value( $input['status'] ?? '' ) );

		if ( empty( $entry_ids ) || ! is_array( $entry_ids ) ) {
			return new \WP_Error(
				'srfm_missing_entry_ids',
				__( 'At least one entry ID is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $status ) ) {
			return new \WP_Error(
				'srfm_missing_status',
				__( 'Status is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		return Entries::update_status( $entry_ids, $status );
	}
}
