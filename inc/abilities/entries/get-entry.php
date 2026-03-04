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
	public function execute( array $input ) {
		$entry_id = absint( $input['entry_id'] );
		$entry    = EntriesTable::get( $entry_id );

		if ( empty( $entry ) ) {
			return new \WP_Error(
				'srfm_entry_not_found',
				__( 'Entry not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
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

		return [
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
}
