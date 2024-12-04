<?php
/**
 * SureForms Database Entires Table Class.
 *
 * @link       https://sureforms.com
 * @since      0.0.10
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database\Tables;

use SRFM\Inc\Database\Base;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Entires Table Class.
 *
 * @since 0.0.10
 */
class Entries extends Base {
	use Get_Instance;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $table_suffix = 'entries';

	/**
	 * {@inheritDoc}
	 *
	 * @var int
	 */
	protected $table_version = 1;

	/**
	 * Current logs.
	 *
	 * @var array<array<string,mixed>> $logs
	 * The structure of each log entry is:
	 * [
	 *     'title'    => string,
	 *     'messages' => array<string>,
	 *     'timestamp' => int
	 * ]
	 *
	 * @since 0.0.10
	 */
	private $logs = [];

	/**
	 * {@inheritDoc}
	 */
	public function get_schema() {
		return [
			// Entry ID.
			'ID'              => [
				'type' => 'number',
			],
			// Submitted form ID.
			'form_id'         => [
				'type' => 'number',
			],
			// User ID.
			'user_id'         => [
				'type'    => 'number',
				'default' => 0,
			],
			// Current entry status: 'read', 'unread' and 'trash'.
			'status'          => [
				'type'    => 'string',
				'default' => 'unread',
			],
			// Entry's form type eg quiz, standard etc. Default empty or null means standard.
			'type'            => [
				'type' => 'string',
			],
			// Submitted form data by user.
			'form_data'       => [
				'type'    => 'array',
				'default' => [],
			],
			// Additional information about the current submitted data: i.e: Browser type, device etc.
			'submission_info' => [
				'type'    => 'array',
				'default' => [],
			],
			// Any additional notes that is added by the admin.
			'notes'           => [
				'type'    => 'array',
				'default' => [],
			],
			// Entry activities logs.
			'logs'            => [
				'type'    => 'array',
				'default' => [],
			],
			// Entry submitted date and time.
			'created_at'      => [
				'type' => 'datetime',
			],
			// Any misc extra data that needs to be saved.
			'extras'          => [
				'type'    => 'array',
				'default' => [],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_columns_definition() {
		return [
			'ID BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY',
			'form_id BIGINT(20) UNSIGNED',
			'user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0',
			'form_data LONGTEXT', // Note: @since 0.0.13 -- We have renamed `user_data` column to `form_data`.
			'logs LONGTEXT',
			'notes LONGTEXT',
			'submission_info LONGTEXT',
			'status VARCHAR(10)',
			'type VARCHAR(20)', // Note: @since 0.0.13 -- We have added type column, it will have entry's form type eg quiz, standard etc.
			'extras LONGTEXT',
			'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'INDEX idx_form_id (form_id)', // Indexing for the performance improvements.
			'INDEX idx_user_id (user_id)',
			'INDEX idx_form_id_created_at_status (form_id, created_at, status)', // Composite index for performance improvements.
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_new_columns_definition() {
		return [
			// Note: @since 0.0.13 -- We have added new columns `type`, `extras` and `user_id`.
			'type VARCHAR(20) AFTER status',
			'extras LONGTEXT AFTER status',
			'user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER form_id',
			'INDEX idx_user_id (user_id)',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_columns_to_rename() {
		return [
			// Note: @since 0.0.13 -- We have renamed `user_data` column to `form_data`.
			[
				'from' => 'user_data',
				'to'   => 'form_data',
			],
		];
	}

	/**
	 * Retrieve the key of the last log entry.
	 *
	 * @since 0.0.10
	 * @return int|null The key of the last log entry if logs exist, or null if no logs are present.
	 */
	public function get_last_log_key() {
		$key = array_key_last( $this->logs );
		return is_int( $key ) ? $key : null;
	}

	/**
	 * Add a new log entry.
	 *
	 * @param string        $title The title of the log entry.
	 * @param array<string> $messages Optional. An array of messages to include in the log entry. Default is an empty array.
	 * @since 0.0.10
	 * @return int|null The key of the newly added log entry, or null if the log could not be added.
	 */
	public function add_log( $title, $messages = [] ) {
		$this->logs[] = [
			'title'     => Helper::get_string_value( trim( $title ) ),
			'messages'  => Helper::get_array_value( $messages ),
			'timestamp' => time(),
		];

		return $this->get_last_log_key();
	}

	/**
	 * Update an existing log entry.
	 *
	 * @param int           $log_key The key of the log entry to update.
	 * @param string|null   $title Optional. The new title for the log entry. If null, the title will not be changed.
	 * @param array<string> $messages Optional. An array of new messages to add to the log entry.
	 * @since 0.0.10
	 * @return int|null The key of the updated log entry, or null if the log entry does not exist.
	 */
	public function update_log( $log_key, $title = null, $messages = [] ) {
		if ( empty( $this->logs[ $log_key ] ) ) {
			return null;
		}

		$logs = $this->logs;

		$logs[ $log_key ]['title']    = ! is_null( $title ) ? Helper::get_string_value( trim( $title ) ) : $logs[ $log_key ]['title'];
		$logs[ $log_key ]['messages'] = array_merge( Helper::get_array_value( $logs[ $log_key ]['messages'] ), Helper::get_array_value( $messages ) );

		$this->logs = $logs;
		return $log_key;
	}

	/**
	 * Retrieve all log entries.
	 *
	 * @since 0.0.10
	 * @return array<array<string,mixed>>
	 */
	public function get_logs() {
		return $this->logs;
	}

	/**
	 * Add a new entry to the database.
	 *
	 * @param array<mixed> $data An associative array of data for the new entry. Must include 'form_id'.
	 *                    If 'ID' is set, it will be removed before inserting.
	 * @since 0.0.10
	 * @return int|false The number of rows inserted, or false if the insertion fails.
	 */
	public static function add( $data ) {
		if ( empty( $data['form_id'] ) ) {
			return false;
		}

		if ( isset( $data['ID'] ) ) {
			// Unset ID if exists because we are creating a new entry, not updating.
			unset( $data['ID'] );
		}

		$instance = self::get_instance();

		if ( ! isset( $data['logs'] ) ) {
			// Add default logs if no logs provided.
			$data['logs'] = $instance->get_logs();
		}

		return $instance->use_insert( $data );
	}

	/**
	 * Update an entry by entry id.
	 *
	 * @param int                 $entry_id Entry ID.
	 * @param array<string,mixed> $data     Data to update.
	 * @since 0.0.13
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function update( $entry_id, $data = [] ) {
		if ( empty( $entry_id ) ) {
			return false;
		}
		return self::get_instance()->use_update( $data, [ 'ID' => absint( $entry_id ) ] );
	}

	/**
	 * Delete an entry by entry id.
	 *
	 * @param int $entry_id Entry ID to delete.
	 * @since 0.0.13
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete( $entry_id ) {
		return self::get_instance()->use_delete( [ 'ID' => absint( $entry_id ) ], [ '%d' ] );
	}

	/**
	 * Retrieve a specific entry from the database.
	 *
	 * @param int $entry_id The ID of the entry to retrieve.
	 * @since 0.0.10
	 * @return array<mixed> An associative array representing the entry, or an empty array if no entry is found.
	 */
	public static function get( $entry_id ) {
		$results = self::get_instance()->get_results(
			[
				'ID' => $entry_id,
			]
		);

		return isset( $results[0] ) ? Helper::get_array_value( $results[0] ) : [];
	}

	/**
	 * Retrieves a list of records based on the provided arguments.
	 *
	 * This method fetches results from the database, allowing for various
	 * customization options such as filtering, pagination, and sorting.
	 *
	 * @param array<string,mixed> $args {
	 *     Optional. An array of arguments to customize the query.
	 *
	 *     @type array  $where   An associative array of conditions to filter the results.
	 *     @type int    $limit   The maximum number of results to return. Default is 10.
	 *     @type int    $offset  The number of records to skip before starting to collect results. Default is 0.
	 *     @type string $orderby  The column by which to order the results. Default is 'created_at'.
	 *     @type string $order    The direction of the order (ASC or DESC). Default is 'DESC'.
	 * }
	 * @param bool                $set_limit Whether to set the limit on the query. Default is true.
	 *
	 * @since 0.0.13
	 * @return array<mixed> The results of the query, typically an array of objects or associative arrays.
	 */
	public static function get_all( $args = [], $set_limit = true ) {
		$_args         = wp_parse_args(
			$args,
			[
				'where'   => [],
				'columns' => '*',
				'limit'   => 10,
				'offset'  => 0,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			]
		);
		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', Helper::get_string_value( esc_sql( $_args['orderby'] ) ), Helper::get_string_value( esc_sql( $_args['order'] ) ) ),
		];

		if ( $set_limit ) {
			$extra_queries[] = sprintf( 'LIMIT %1$d, %2$d', absint( $_args['offset'] ), absint( $_args['limit'] ) );
		}
		return self::get_instance()->get_results(
			$_args['where'],
			$_args['columns'],
			$extra_queries
		);
	}

	/**
	 * Get the total count of entries by status.
	 *
	 * @param string              $status The status of the entries to count.
	 * @param int|null            $form_id The ID of the form to count entries for.
	 * @param array<string,mixed> $where_clause Additional where clause to add to the query.
	 * @since 0.0.13
	 * @return int The total number of entries with the specified status.
	 */
	public static function get_total_entries_by_status( $status = 'all', $form_id = 0, $where_clause = [] ) {
		switch ( $status ) {
			case 'all':
				$where_clause[] =
					[
						[
							'key'     => 'status',
							'compare' => '!=',
							'value'   => 'trash',
						],
					];
				if ( 0 < $form_id ) {
					$where_clause[] = [
						[
							'key'     => 'form_id',
							'compare' => '=',
							'value'   => $form_id,
						],
					];
				}
				return self::get_instance()->get_total_count( $where_clause );
			case 'unread':
			case 'trash':
				$where_clause[] = [
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => $status,
					],
				];
				return self::get_instance()->get_total_count( $where_clause );
			default:
				return self::get_instance()->get_total_count();
		}
	}

	/**
	 * Get the available months for entries.
	 *
	 * @param array<string,mixed> $where_clause Additional where clause to add to the query.
	 * @since 0.0.13
	 * @return array<int|string, mixed>
	 */
	public static function get_available_months( $where_clause = [] ) {
		$results = self::get_instance()->get_results(
			$where_clause,
			'DISTINCT DATE_FORMAT(created_at, "%Y%m") as month_value, DATE_FORMAT(created_at, "%M %Y") as month_label',
			[
				'ORDER BY month_value ASC',
			],
			false
		);

		$months = [];
		foreach ( $results as $result ) {
			if ( is_array( $result ) && isset( $result['month_value'], $result['month_label'] ) ) {
				$months[ $result['month_value'] ] = $result['month_label'];
			}
		}
		return $months;
	}

	/**
	 * Get all the entry ID's for a form.
	 * The data is used for checking unique field validation.
	 *
	 * @param int $form_id The ID of the form to fetch entry IDs for.
	 * @since 1.0.0
	 * @return array<mixed> An array of entry IDs.
	 */
	public static function get_all_entry_ids_for_form( $form_id ) {
		return self::get_instance()->get_results(
			[ 'form_id' => $form_id ],
			'ID',
			[
				'ORDER BY ID DESC',
			]
		);
	}

	/**
	 * Get form IDs associated with a list of entry IDs.
	 * This method retrieves the distinct form IDs that are linked to the provided entry IDs.
	 *
	 * @param array<int> $entry_ids An array of entry IDs to fetch associated form IDs for.
	 * @since 1.1.1
	 * @return array<int> An array of form IDs.
	 */
	public static function get_form_ids_by_entries( $entry_ids ) {
		if ( empty( $entry_ids ) && ! is_array( $entry_ids ) ) {
			return [];
		}

		$results = self::get_instance()->get_results(
			[
				[
					[
						'key'     => 'ID',
						'compare' => 'IN',
						'value'   => $entry_ids,
					],
				],
			],
			'DISTINCT form_id'
		);

		return array_map( 'absint', array_column( $results, 'form_id' ) );  // Flatten the array.
	}

	/**
	 * Get the form data for a specific entry.
	 *
	 * @param int $entry_id The ID of the entry to get the form data for.
	 * @since 1.0.0
	 * @return array<string,mixed> An associative array representing the entry's form data.
	 */
	public static function get_form_data( $entry_id ) {
		$result = self::get_instance()->get_results(
			[ 'ID' => $entry_id ],
			'form_data'
		);
		return isset( $result[0] ) && is_array( $result[0] ) ? Helper::get_array_value( $result[0]['form_data'] ) : [];
	}
}
