<?php
/**
 * Export Entries Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Export;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Export_Entries ability class.
 *
 * Exports form entries as structured JSON with decoded field labels.
 *
 * @since x.x.x
 */
class Export_Entries extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/export-entries';
		$this->label       = __( 'Export Entries', 'sureforms' );
		$this->description = __( 'Export form submission entries as structured JSON with decoded field labels. Supports filtering by form, status, date range, and specific entry IDs.', 'sureforms' );
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
				'form_id'   => [
					'type'        => 'integer',
					'description' => __( 'Filter entries by form ID. Default 0 for all forms.', 'sureforms' ),
					'default'     => 0,
				],
				'entry_ids' => [
					'type'        => 'array',
					'description' => __( 'Specific entry IDs to export.', 'sureforms' ),
					'items'       => [ 'type' => 'integer' ],
				],
				'status'    => [
					'type'        => 'string',
					'description' => __( 'Filter by entry status. Default all.', 'sureforms' ),
					'enum'        => [ 'all', 'read', 'unread', 'trash' ],
					'default'     => 'all',
				],
				'date_from' => [
					'type'        => 'string',
					'description' => __( 'Filter entries from this date (Y-m-d).', 'sureforms' ),
				],
				'date_to'   => [
					'type'        => 'string',
					'description' => __( 'Filter entries to this date (Y-m-d).', 'sureforms' ),
				],
				'per_page'  => [
					'type'        => 'integer',
					'description' => __( 'Maximum entries to return. Default 100, max 500.', 'sureforms' ),
					'default'     => 100,
					'minimum'     => 1,
					'maximum'     => 500,
				],
			],
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
				'entries'     => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'         => [ 'type' => 'integer' ],
							'form_id'    => [ 'type' => 'integer' ],
							'form_title' => [ 'type' => 'string' ],
							'status'     => [ 'type' => 'string' ],
							'created_at' => [ 'type' => 'string' ],
							'fields'     => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'label' => [ 'type' => 'string' ],
										'value' => [],
									],
								],
							],
						],
					],
				],
				'total_count' => [ 'type' => 'integer' ],
				'truncated'   => [ 'type' => 'boolean' ],
			],
		];
	}

	/**
	 * Execute the export-entries ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id   = ! empty( $input['form_id'] ) ? absint( Helper::get_integer_value( $input['form_id'] ) ) : 0;
		$entry_ids = ! empty( $input['entry_ids'] ) && is_array( $input['entry_ids'] ) ? array_map( 'absint', $input['entry_ids'] ) : [];
		$status    = ! empty( $input['status'] ) ? sanitize_text_field( Helper::get_string_value( $input['status'] ) ) : 'all';
		$date_from = ! empty( $input['date_from'] ) ? sanitize_text_field( Helper::get_string_value( $input['date_from'] ) ) : '';
		$date_to   = ! empty( $input['date_to'] ) ? sanitize_text_field( Helper::get_string_value( $input['date_to'] ) ) : '';
		$per_page  = ! empty( $input['per_page'] ) ? min( absint( Helper::get_integer_value( $input['per_page'] ) ), 500 ) : 100;

		// Build where conditions.
		$where = $this->build_where_conditions( $form_id, $entry_ids, $status, $date_from, $date_to );

		// Fetch one extra to detect truncation.
		$args = [
			'where'   => $where,
			'columns' => 'ID',
			'limit'   => $per_page + 1,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		];

		$results   = Entries::get_all( $args, true );
		$truncated = count( $results ) > $per_page;

		if ( $truncated ) {
			$results = array_slice( $results, 0, $per_page );
		}

		if ( empty( $results ) ) {
			return [
				'entries'     => [],
				'total_count' => 0,
				'truncated'   => false,
			];
		}

		// Extract entry IDs from results.
		$fetched_ids = array_map(
			function ( $row ) {
				if ( is_array( $row ) ) {
					return absint( Helper::get_integer_value( $row['ID'] ?? 0 ) );
				}
				if ( is_object( $row ) && isset( $row->ID ) ) {
					return absint( Helper::get_integer_value( $row->ID ) );
				}
				return 0;
			},
			$results
		);

		// Get form IDs for these entries.
		$form_ids = Entries::get_form_ids_by_entries( $fetched_ids );

		// Build form title cache.
		$form_titles     = [];
		$excluded_fields = Helper::get_excluded_fields();

		foreach ( $form_ids as $fid ) {
			$post                = get_post( $fid );
			$form_titles[ $fid ] = $post ? $post->post_title : '';
		}

		// Fetch full entries and build structured output.
		$entries = [];
		foreach ( $fetched_ids as $eid ) {
			$entry_data = Entries::get( $eid );

			if ( empty( $entry_data ) ) {
				continue;
			}

			$entry_form_id = absint( Helper::get_integer_value( $entry_data['form_id'] ?? 0 ) );
			$form_data     = Helper::get_array_value( $entry_data['form_data'] ?? [] );
			$fields        = [];

			foreach ( $form_data as $field_key => $field_value ) {
				if ( in_array( $field_key, $excluded_fields, true ) ) {
					continue;
				}

				$label = Helper::get_field_label_from_key( $field_key );

				if ( empty( $label ) ) {
					$label = $field_key;
				}

				$fields[] = [
					'label' => $label,
					'value' => $field_value,
				];
			}

			$entries[] = [
				'id'         => absint( $entry_data['ID'] ?? 0 ),
				'form_id'    => $entry_form_id,
				'form_title' => $form_titles[ $entry_form_id ] ?? '',
				'status'     => $entry_data['status'] ?? '',
				'created_at' => $entry_data['created_at'] ?? '',
				'fields'     => $fields,
			];
		}

		return [
			'entries'     => $entries,
			'total_count' => count( $entries ),
			'truncated'   => $truncated,
		];
	}

	/**
	 * Build where conditions for the entries query.
	 *
	 * @param int        $form_id   Form ID filter.
	 * @param array<int> $entry_ids Specific entry IDs.
	 * @param string     $status    Entry status filter.
	 * @param string     $date_from Start date filter.
	 * @param string     $date_to   End date filter.
	 * @since x.x.x
	 * @return array<mixed>
	 */
	private function build_where_conditions( $form_id, $entry_ids, $status, $date_from, $date_to ) {
		$conditions = [];

		// Status filter.
		if ( 'all' === $status ) {
			$conditions[] = [
				'key'     => 'status',
				'compare' => '!=',
				'value'   => 'trash',
			];
		} else {
			$conditions[] = [
				'key'     => 'status',
				'compare' => '=',
				'value'   => $status,
			];
		}

		// Form ID filter.
		if ( $form_id ) {
			$conditions[] = [
				'key'     => 'form_id',
				'compare' => '=',
				'value'   => $form_id,
			];
		}

		// Specific entry IDs.
		if ( ! empty( $entry_ids ) ) {
			$conditions[] = [
				'key'     => 'ID',
				'compare' => 'IN',
				'value'   => $entry_ids,
			];
		}

		// Date range.
		if ( ! empty( $date_from ) ) {
			$conditions[] = [
				'key'     => 'created_at',
				'compare' => '>=',
				'value'   => $date_from . ' 00:00:00',
			];
		}

		if ( ! empty( $date_to ) ) {
			$conditions[] = [
				'key'     => 'created_at',
				'compare' => '<=',
				'value'   => $date_to . ' 23:59:59',
			];
		}

		return count( $conditions ) > 0 ? [ $conditions ] : [];
	}
}
