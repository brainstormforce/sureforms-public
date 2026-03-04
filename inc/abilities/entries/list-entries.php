<?php
/**
 * List Entries Ability.
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
 * List_Entries ability class.
 *
 * Lists form submission entries with filtering, sorting, and pagination.
 *
 * @since x.x.x
 */
class List_Entries extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( array $input ) {
		$per_page = isset( $input['per_page'] ) ? absint( $input['per_page'] ) : 20;
		$per_page = max( 1, min( 100, $per_page ) );

		$args = [
			'form_id'   => absint( $input['form_id'] ?? 0 ),
			'status'    => ! empty( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'all',
			'search'    => ! empty( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
			'date_from' => ! empty( $input['date_from'] ) ? sanitize_text_field( $input['date_from'] ) : '',
			'date_to'   => ! empty( $input['date_to'] ) ? sanitize_text_field( $input['date_to'] ) : '',
			'per_page'  => $per_page,
			'page'      => isset( $input['page'] ) ? absint( $input['page'] ) : 1,
			'orderby'   => ! empty( $input['orderby'] ) ? sanitize_text_field( $input['orderby'] ) : 'created_at',
			'order'     => ! empty( $input['order'] ) ? sanitize_text_field( $input['order'] ) : 'DESC',
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
			'total'        => (int) ( $result['total'] ?? 0 ),
			'total_pages'  => (int) ( $result['total_pages'] ?? 0 ),
			'current_page' => (int) ( $result['current_page'] ?? 1 ),
			'per_page'     => (int) ( $result['per_page'] ?? $per_page ),
		];
	}
}
