<?php
/**
 * Bulk Get Entries Ability.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bulk_Get_Entries ability class.
 *
 * Retrieves detailed information about multiple form submission entries
 * in a single call, including parsed form data with decrypted labels.
 *
 * @since 2.5.2
 */
class Bulk_Get_Entries extends Abstract_Ability {
	use Entry_Parser;

	/**
	 * Maximum number of entries that can be fetched in a single call.
	 *
	 * @since 2.5.2
	 */
	public const MAX_ENTRIES = 50;

	/**
	 * Constructor.
	 *
	 * @since 2.5.2
	 */
	public function __construct() {
		$this->id          = 'sureforms/bulk-get-entries';
		$this->label       = __( 'Bulk Get Entry Details', 'sureforms' );
		$this->description = __( 'Retrieve detailed information about multiple SureForms form submission entries in a single call, including all submitted field data with labels.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
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
	 * @since 2.5.2
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'entry_ids' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => __( 'Array of entry IDs to retrieve (max 50).', 'sureforms' ),
				],
			],
			'required'   => [ 'entry_ids' ],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'entries' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'              => [ 'type' => 'integer' ],
							'form_id'         => [ 'type' => 'integer' ],
							'form_name'       => [ 'type' => 'string' ],
							'status'          => [ 'type' => 'string' ],
							'created_at'      => [ 'type' => 'string' ],
							'form_data'       => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'label'      => [ 'type' => 'string' ],
										'value'      => [],
										'block_name' => [ 'type' => 'string' ],
									],
								],
							],
							'submission_info' => [ 'type' => 'object' ],
							'user'            => [ 'type' => 'object' ],
						],
					],
				],
				'errors'  => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'entry_id' => [ 'type' => 'integer' ],
							'message'  => [ 'type' => 'string' ],
						],
					],
				],
			],
		];
	}

	/**
	 * Execute the bulk-get-entries ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.5.2
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$entry_ids = $input['entry_ids'] ?? [];

		if ( ! is_array( $entry_ids ) || empty( $entry_ids ) ) {
			return new \WP_Error(
				'srfm_invalid_entry_ids',
				__( 'entry_ids must be a non-empty array.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		if ( count( $entry_ids ) > self::MAX_ENTRIES ) {
			return new \WP_Error(
				'srfm_too_many_entry_ids',
				/* translators: %d is the maximum number of entries allowed. */
				sprintf( __( 'Maximum %d entry IDs allowed per request.', 'sureforms' ), self::MAX_ENTRIES ),
				[ 'status' => 400 ]
			);
		}

		$entry_ids = array_map( 'absint', $entry_ids );

		$entries = [];
		$errors  = [];

		foreach ( $entry_ids as $entry_id ) {
			$entry_id = Helper::get_integer_value( $entry_id );
			$entry    = EntriesTable::get( $entry_id );

			if ( empty( $entry ) ) {
				$errors[] = [
					'entry_id' => $entry_id,
					'message'  => __( 'Entry not found.', 'sureforms' ),
				];
				continue;
			}

			$parsed    = $this->parse_entry( $entry );
			$entries[] = array_merge( [ 'id' => $entry_id ], $parsed );
		}

		return [
			'entries' => $entries,
			'errors'  => $errors,
		];
	}
}
