<?php
/**
 * Get Entry Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get_Entry ability class.
 *
 * Retrieves detailed information about a specific form submission entry,
 * including parsed form data with decrypted labels.
 *
 * @since x.x.x
 */
class Get_Entry extends Abstract_Ability {
	use Entry_Parser_Trait;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-entry';
		$this->label       = __( 'Get Entry Details', 'sureforms' );
		$this->description = __( 'Retrieve detailed information about a specific SureForms form submission entry, including all submitted field data with labels.', 'sureforms' );
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
				'entry_id' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the entry to retrieve.', 'sureforms' ),
				],
			],
			'required'   => [ 'entry_id' ],
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
		];
	}

	/**
	 * Execute the get-entry ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$entry_id = Helper::get_integer_value( $input['entry_id'] ?? 0 );
		$entry    = EntriesTable::get( $entry_id );

		if ( empty( $entry ) ) {
			return new \WP_Error(
				'srfm_entry_not_found',
				__( 'Entry not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		$parsed = $this->parse_entry( $entry );

		return array_merge( [ 'id' => $entry_id ], $parsed );
	}
}
