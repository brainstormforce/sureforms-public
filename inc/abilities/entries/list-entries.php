<?php
/**
 * List Entries Ability.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Entries;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * List_Entries ability class.
 *
 * Lists form submission entries with filtering, sorting, and pagination.
 *
 * @since 2.5.2
 */
class List_Entries extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since 2.5.2
	 */
	public function __construct() {
		$this->id          = 'sureforms/list-entries';
		$this->label       = __( 'List Form Entries', 'sureforms' );
		$this->description = __( 'List SureForms form submission entries with optional filtering by form, status, date range, and search. Supports pagination and sorting.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_annotations() {
		return [
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.0,
			'openWorldHint' => false,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'form_id'   => [
					'type'        => 'integer',
					'description' => __( 'Filter entries by form ID. Use 0 or omit for all forms.', 'sureforms' ),
					'default'     => 0,
				],
				'status'    => [
					'type'        => 'string',
					'description' => __( 'Filter entries by status.', 'sureforms' ),
					'enum'        => [ 'all', 'read', 'unread', 'trash' ],
					'default'     => 'all',
				],
				'search'    => [
					'type'        => 'string',
					'description' => __( 'Search entries by entry ID.', 'sureforms' ),
				],
				'date_from' => [
					'type'        => 'string',
					'description' => __( 'Start date for filtering entries (YYYY-MM-DD format).', 'sureforms' ),
				],
				'date_to'   => [
					'type'        => 'string',
					'description' => __( 'End date for filtering entries (YYYY-MM-DD format).', 'sureforms' ),
				],
				'per_page'  => [
					'type'        => 'integer',
					'description' => __( 'Number of entries per page (1-100).', 'sureforms' ),
					'default'     => 20,
				],
				'page'      => [
					'type'        => 'integer',
					'description' => __( 'Page number for pagination.', 'sureforms' ),
					'default'     => 1,
				],
				'orderby'   => [
					'type'        => 'string',
					'description' => __( 'Column to order results by.', 'sureforms' ),
					'enum'        => [ 'created_at', 'ID', 'form_id', 'status' ],
					'default'     => 'created_at',
				],
				'order'     => [
					'type'        => 'string',
					'description' => __( 'Sort direction.', 'sureforms' ),
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
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
				'entries'      => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'         => [ 'type' => 'integer' ],
							'form_id'    => [ 'type' => 'integer' ],
							'form_title' => [ 'type' => 'string' ],
							'status'     => [ 'type' => 'string' ],
							'created_at' => [ 'type' => 'string' ],
						],
					],
				],
				'total'        => [ 'type' => 'integer' ],
				'total_pages'  => [ 'type' => 'integer' ],
				'current_page' => [ 'type' => 'integer' ],
				'per_page'     => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute the list-entries ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.5.2
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$per_page = isset( $input['per_page'] ) ? Helper::get_integer_value( $input['per_page'] ) : 20;
		$per_page = max( 1, min( 100, $per_page ) );

		$args = [
			'form_id'   => Helper::get_integer_value( $input['form_id'] ?? 0 ),
			'status'    => ! empty( $input['status'] ) ? sanitize_text_field( Helper::get_string_value( $input['status'] ) ) : 'all',
			'search'    => ! empty( $input['search'] ) ? sanitize_text_field( Helper::get_string_value( $input['search'] ) ) : '',
			'date_from' => ! empty( $input['date_from'] ) ? sanitize_text_field( Helper::get_string_value( $input['date_from'] ) ) : '',
			'date_to'   => ! empty( $input['date_to'] ) ? sanitize_text_field( Helper::get_string_value( $input['date_to'] ) ) : '',
			'per_page'  => $per_page,
			'page'      => isset( $input['page'] ) ? Helper::get_integer_value( $input['page'] ) : 1,
			'orderby'   => ! empty( $input['orderby'] ) ? sanitize_text_field( Helper::get_string_value( $input['orderby'] ) ) : 'created_at',
			'order'     => ! empty( $input['order'] ) ? sanitize_text_field( Helper::get_string_value( $input['order'] ) ) : 'DESC',
		];

		$result = Entries::get_entries( $args );

		// Enrich entries with form title and strip heavyweight form_data.
		$entries = [];
		if ( ! empty( $result['entries'] ) && is_array( $result['entries'] ) ) {
			foreach ( $result['entries'] as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$form_id    = absint( $entry['form_id'] ?? 0 );
				$form_title = $form_id > 0 ? get_the_title( $form_id ) : '';

				$entries[] = [
					'id'         => absint( $entry['ID'] ?? 0 ),
					'form_id'    => $form_id,
					'form_title' => $form_title,
					'status'     => $entry['status'] ?? '',
					'created_at' => $entry['created_at'] ?? '',
				];
			}
		}

		return [
			'entries'      => $entries,
			'total'        => Helper::get_integer_value( $result['total'] ?? 0 ),
			'total_pages'  => Helper::get_integer_value( $result['total_pages'] ?? 0 ),
			'current_page' => Helper::get_integer_value( $result['current_page'] ?? 1 ),
			'per_page'     => Helper::get_integer_value( $result['per_page'] ?? $per_page ),
		];
	}
}
