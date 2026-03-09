<?php
/**
 * List Forms Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Helper;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * List_Forms ability class.
 *
 * Lists all SureForms forms with optional filtering.
 *
 * @since x.x.x
 */
class List_Forms extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/list-forms';
		$this->label       = __( 'List SureForms Forms', 'sureforms' );
		$this->description = __( 'Retrieve a list of SureForms forms with optional filtering by status, search query, and pagination.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
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
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'status'   => [
					'type'        => 'string',
					'description' => __( 'Filter by form status. Defaults to "publish".', 'sureforms' ),
					'enum'        => [ 'publish', 'draft', 'trash', 'any' ],
					'default'     => 'any',
				],
				'search'   => [
					'type'        => 'string',
					'description' => __( 'Search forms by title.', 'sureforms' ),
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => __( 'Number of forms per page. Defaults to 10.', 'sureforms' ),
					'default'     => 10,
					'minimum'     => 1,
					'maximum'     => 100,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => __( 'Page number for pagination. Defaults to 1.', 'sureforms' ),
					'default'     => 1,
					'minimum'     => 1,
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'forms' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'          => [ 'type' => 'integer' ],
							'title'       => [ 'type' => 'string' ],
							'status'      => [ 'type' => 'string' ],
							'date'        => [ 'type' => 'string' ],
							'entry_count' => [ 'type' => 'integer' ],
						],
					],
				],
				'total' => [ 'type' => 'integer' ],
				'pages' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute the list-forms ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$status   = ! empty( $input['status'] ) ? sanitize_text_field( Helper::get_string_value( $input['status'] ) ) : 'any';
		$search   = ! empty( $input['search'] ) ? sanitize_text_field( Helper::get_string_value( $input['search'] ) ) : '';
		$per_page = ! empty( $input['per_page'] ) ? Helper::get_integer_value( $input['per_page'] ) : 10;
		$page     = ! empty( $input['page'] ) ? Helper::get_integer_value( $input['page'] ) : 1;

		$per_page = max( 1, min( 100, $per_page ) );

		$query_args = [
			'post_type'      => SRFM_FORMS_POST_TYPE,
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		$query = new WP_Query( $query_args );
		$forms = [];

		if ( $query->have_posts() ) {
			$form_ids     = wp_list_pluck( $query->posts, 'ID' );
			$entry_counts = $this->get_entry_counts( $form_ids );

			foreach ( $query->posts as $post ) {
				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				$forms[] = [
					'id'          => $post->ID,
					'title'       => $post->post_title,
					'status'      => $post->post_status,
					'date'        => $post->post_date,
					'entry_count' => $entry_counts[ $post->ID ] ?? 0,
				];
			}
		}

		return [
			'forms' => $forms,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
		];
	}

	/**
	 * Get entry counts for multiple forms in a single query.
	 *
	 * @param array<int> $form_ids Array of form IDs.
	 * @since x.x.x
	 * @return array<int,int> Map of form_id => entry count.
	 */
	private function get_entry_counts( array $form_ids ) {
		if ( empty( $form_ids ) ) {
			return [];
		}

		global $wpdb;

		$table_name   = $wpdb->prefix . 'srfm_entries';
		$placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Batch query to avoid N+1; results are not cached as they reflect real-time entry counts.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name and placeholders are constructed from $wpdb->prefix and array_fill(), not user input.
				"SELECT form_id, COUNT(*) as cnt FROM {$table_name} WHERE form_id IN ({$placeholders}) AND status != 'trash' GROUP BY form_id",
				...$form_ids
			),
			ARRAY_A
		);

		$counts = [];
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$counts[ (int) $row['form_id'] ] = (int) $row['cnt'];
			}
		}

		return $counts;
	}
}
