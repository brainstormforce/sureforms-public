<?php
/**
 * Bulk Get Entries Ability.
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
 * Bulk_Get_Entries ability class.
 *
 * Retrieves detailed information about multiple form submission entries
 * in a single call, including parsed form data with decrypted labels.
 *
 * @since x.x.x
 */
class Bulk_Get_Entries extends Abstract_Ability {
	/**
	 * Maximum number of entries that can be fetched in a single call.
	 *
	 * @since x.x.x
	 */
	public const MAX_ENTRIES = 50;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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

			// Parse form data with decrypted labels.
			$form_data       = [];
			$excluded_fields = Helper::get_excluded_fields();
			$entry_form_data = $entry['form_data'] ?? [];

			if ( is_array( $entry_form_data ) ) {
				foreach ( $entry_form_data as $field_name => $value ) {
					if ( ! is_string( $field_name ) || in_array( $field_name, $excluded_fields, true ) ) {
						continue;
					}
					if ( false === str_contains( $field_name, '-lbl-' ) ) {
						continue;
					}

					$label_parts      = explode( '-lbl-', $field_name );
					$label            = isset( $label_parts[1] ) ? explode( '-', $label_parts[1] )[0] : '';
					$label            = $label ? Helper::decrypt( $label ) : '';
					$field_block_name = Helper::get_block_name_from_field( $field_name );

					$form_data[] = [
						'label'      => $label,
						'value'      => $value,
						'block_name' => $field_block_name,
					];
				}
			}

			// Get form info.
			$form_id    = absint( $entry['form_id'] ?? 0 );
			$form_title = get_post_field( 'post_title', $form_id );
			// Translators: %d is the form ID.
			$form_name = ! empty( $form_title ) ? $form_title : sprintf( __( 'SureForms Form #%d', 'sureforms' ), $form_id );

			// Build submission info.
			$submission_info = [
				'user_ip'      => $entry['submission_info']['user_ip'] ?? '',
				'browser_name' => $entry['submission_info']['browser_name'] ?? '',
				'device_name'  => $entry['submission_info']['device_name'] ?? '',
			];

			// Build user info.
			$user_id   = Helper::get_integer_value( $entry['user_id'] ?? 0 );
			$user_info = null;

			if ( 0 !== $user_id ) {
				$user_data = get_userdata( $user_id );

				if ( $user_data ) {
					$user_info = [
						'id'           => $user_id,
						'display_name' => $user_data->display_name,
						'profile_url'  => get_author_posts_url( $user_id ),
					];
				}
			}

			$entries[] = [
				'id'              => $entry_id,
				'form_id'         => $form_id,
				'form_name'       => $form_name,
				'status'          => $entry['status'] ?? '',
				'created_at'      => $entry['created_at'] ?? '',
				'form_data'       => $form_data,
				'submission_info' => $submission_info,
				'user'            => $user_info,
			];
		}

		return [
			'entries' => $entries,
			'errors'  => $errors,
		];
	}
}
