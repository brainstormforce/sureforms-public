<?php
/**
 * Sureforms entries.
 *
 * @package sureforms.
 * @since 2.0.0
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Entries Class.
 *
 * @since 2.0.0
 */
class Entries {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Constructor code here.
	}

	/**
	 * Get entries with filters, sorting, and pagination.
	 *
	 * @param array<string,mixed> $args {
	 *     Optional. An array of arguments to customize the query.
	 *
	 *     @type int          $form_id     Form ID to filter entries. Default 0 (all forms).
	 *     @type string       $status      Entry status: 'all', 'read', 'unread', 'trash'. Default 'all'.
	 *     @type string       $search      Search term to filter entries by entry ID. Default empty.
	 *     @type string       $date_from   Start date for filtering entries (YYYY-MM-DD format). Default empty.
	 *     @type string       $date_to     End date for filtering entries (YYYY-MM-DD format). Default empty.
	 *     @type string       $orderby     Column to order by. Default 'created_at'.
	 *     @type string       $order       Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 *     @type int          $per_page    Number of entries per page. Default 20.
	 *     @type int          $page        Current page number. Default 1.
	 *     @type array<mixed> $entry_ids   Specific entry IDs to fetch. Default empty array.
	 * }
	 *
	 * @since 2.0.0
	 * @return array<string,mixed> {
	 *     @type array<mixed> $entries      Array of entry objects.
	 *     @type int          $total        Total number of entries matching the query.
	 *     @type int          $per_page     Number of entries per page.
	 *     @type int          $current_page Current page number.
	 *     @type int          $total_pages  Total number of pages.
	 *     @type bool         $emptyTrash   Whether the trash is empty (true) or contains items (false).
	 * }
	 */
	public static function get_entries( $args = [] ) {
		$defaults = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'per_page'  => 20,
			'page'      => 1,
			'entry_ids' => [],
		];

		/**
		 * Parsed and sanitized arguments for the entries query.
		 *
		 * @var array{form_id: int, status: string, search: string, date_from: string, date_to: string, orderby: string, order: string, per_page: int, page: int, entry_ids: array<int>} $args
		 */
		$args = wp_parse_args( $args, $defaults );

		// Build where conditions.
		$where_conditions = self::build_where_conditions( $args );

		// Get total count for pagination.
		$total = EntriesTable::get_instance()->get_total_count( $where_conditions );

		// Calculate offset.
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		// Get entries.
		$entries = EntriesTable::get_all(
			[
				'where'   => $where_conditions,
				'columns' => '*',
				'orderby' => Helper::get_string_value( $args['orderby'] ),
				'order'   => Helper::get_string_value( $args['order'] ),
				'limit'   => absint( $args['per_page'] ),
				'offset'  => $offset,
			]
		);

		// Check if trash is empty.
		$trash_count = EntriesTable::get_instance()->get_total_count(
			[
				[
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => 'trash',
					],
				],
			]
		);

		return [
			'entries'      => $entries,
			'total'        => $total,
			'per_page'     => absint( $args['per_page'] ),
			'current_page' => absint( $args['page'] ),
			'total_pages'  => ceil( $total / absint( $args['per_page'] ) ),
			'emptyTrash'   => 0 === $trash_count,
		];
	}

	/**
	 * Update entry status (trash/untrash/read/unread).
	 *
	 * @param int|array<int> $entry_ids Entry ID or array of entry IDs.
	 * @param string         $status    New status: 'trash', 'unread', 'read', or 'restore'.
	 *
	 * @since 2.0.0
	 * @return array<string,mixed> {
	 *     @type bool  $success   Whether the operation was successful.
	 *     @type int   $updated   Number of entries updated.
	 *     @type array<string> $errors    Array of error messages.
	 * }
	 */
	public static function update_status( $entry_ids, $status ) {
		$entry_ids = is_array( $entry_ids ) ? array_map( 'absint', $entry_ids ) : [ absint( $entry_ids ) ];
		$entry_ids = array_filter( $entry_ids ); // Remove any zero values.

		if ( empty( $entry_ids ) ) {
			return [
				'success' => false,
				'updated' => 0,
				'errors'  => [ __( 'No valid entry IDs provided.', 'sureforms' ) ],
			];
		}

		// Validate status.
		$valid_statuses = [ 'trash', 'unread', 'read', 'restore' ];
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return [
				'success' => false,
				'updated' => 0,
				'errors'  => [ __( 'Invalid status provided.', 'sureforms' ) ],
			];
		}

		// Map 'restore' to 'unread'.
		$actual_status = 'restore' === $status ? 'unread' : $status;

		$updated = 0;
		$errors  = [];

		foreach ( $entry_ids as $entry_id ) {
			$result = EntriesTable::update( $entry_id, [ 'status' => $actual_status ] );

			if ( false === $result ) {
				$errors[] = sprintf(
					// translators: %d is the entry ID.
					__( 'Failed to update entry #%d.', 'sureforms' ),
					$entry_id
				);
			} else {
				++$updated;
			}
		}

		return [
			'success' => $updated > 0,
			'updated' => $updated,
			'errors'  => $errors,
		];
	}

	/**
	 * Permanently delete entries.
	 *
	 * @param int|array<int> $entry_ids Entry ID or array of entry IDs.
	 *
	 * @since 2.0.0
	 * @return array<string,mixed> {
	 *     @type bool  $success   Whether the operation was successful.
	 *     @type int   $deleted   Number of entries deleted.
	 *     @type array<string> $errors    Array of error messages.
	 * }
	 */
	public static function delete_entries( $entry_ids ) {
		$entry_ids = is_array( $entry_ids ) ? array_map( 'absint', $entry_ids ) : [ absint( $entry_ids ) ];
		$entry_ids = array_filter( $entry_ids );

		if ( empty( $entry_ids ) ) {
			return [
				'success' => false,
				'deleted' => 0,
				'errors'  => [ __( 'No valid entry IDs provided.', 'sureforms' ) ],
			];
		}

		$deleted = 0;
		$errors  = [];

		foreach ( $entry_ids as $entry_id ) {
			$result = EntriesTable::delete( $entry_id );

			if ( false === $result ) {
				$errors[] = sprintf(
					// translators: %d is the entry ID.
					__( 'Failed to delete entry #%d.', 'sureforms' ),
					$entry_id
				);
			} else {
				++$deleted;
			}
		}

		return [
			'success' => $deleted > 0,
			'deleted' => $deleted,
			'errors'  => $errors,
		];
	}

	/**
	 * Export entries to CSV format.
	 *
	 * @param array<string,mixed> $args {
	 *     Optional. An array of arguments for export.
	 *
	 *     @type int|array<int> $entry_ids Entry ID or array of entry IDs to export.
	 *     @type int            $form_id   Form ID to export all entries from.
	 *     @type string         $status    Entry status filter.
	 *     @type string         $search    Search term filter.
	 *     @type string         $date_from Start date for filtering entries (YYYY-MM-DD format).
	 *     @type string         $date_to   End date for filtering entries (YYYY-MM-DD format).
	 * }
	 *
	 * @since 2.0.0
	 * @return array<string,mixed> {
	 *     @type bool   $success  Whether the export was successful.
	 *     @type string $filename Export filename (if single form).
	 *     @type string $filepath Full path to the exported file.
	 *     @type string $type     Export type: 'csv' or 'zip'.
	 *     @type string $error    Error message if failed.
	 * }
	 */
	public static function export_entries( $args = [] ) {
		$defaults = [
			'entry_ids' => [],
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
		];

		/**
		 * Parsed and sanitized arguments for the export operation.
		 *
		 * @var array{entry_ids: int|array<int>, form_id: int, status: string, search: string, date_from: string, date_to: string} $args
		 */
		$args = wp_parse_args( $args, $defaults );

		// Get entry IDs to export.
		if ( empty( $args['entry_ids'] ) ) {
			// If no specific entry IDs provided, get all matching entries.
			$where_conditions = self::build_where_conditions( $args );
			$all_entries      = EntriesTable::get_all(
				[
					'where'   => $where_conditions,
					'columns' => 'ID',
				],
				false
			);
			$entry_ids        = array_map( 'absint', array_column( $all_entries, 'ID' ) );
		} else {
			/**
			 * Entry IDs converted to array of integers.
			 *
			 * @var array<int> $entry_ids
			 */
			$entry_ids = is_array( $args['entry_ids'] ) ? array_map( 'absint', $args['entry_ids'] ) : [ absint( (int) $args['entry_ids'] ) ];
		}

		if ( empty( $entry_ids ) ) {
			return [
				'success' => false,
				'error'   => __( 'No entries found to export.', 'sureforms' ),
			];
		}

		// Get form IDs from entry IDs.
		$form_ids       = EntriesTable::get_form_ids_by_entries( $entry_ids );
		$is_single_form = count( $form_ids ) === 1;

		$temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) );

		// Check if temp directory is writable.
		if ( ! wp_is_writable( $temp_dir ) ) {
			return [
				'success' => false,
				'error'   => __( 'Temporary directory is not writable.', 'sureforms' ),
			];
		}

		$csv_files = [];
		$zip       = null;
		$temp_zip  = '';

		// Create ZIP if multiple forms.
		if ( ! $is_single_form ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return [
					'success' => false,
					'error'   => __( 'ZipArchive class is not available.', 'sureforms' ),
				];
			}

			$temp_zip = $temp_dir . 'srfm-entries-export-' . time() . '.zip';
			$zip      = new \ZipArchive();

			if ( ! $zip->open( $temp_zip, \ZipArchive::CREATE ) ) {
				return [
					'success' => false,
					'error'   => __( 'Unable to create ZIP file.', 'sureforms' ),
				];
			}
		}

		$csv_filepath = '';

		// Process each form.
		foreach ( $form_ids as $form_id ) {
			$results = self::get_entries_data_for_export( $entry_ids, $form_id );

			if ( empty( $results ) ) {
				continue;
			}

			$sanitized_form_title = sanitize_title( get_the_title( $form_id ) );
			$sanitized_form_title = ! empty( $sanitized_form_title ) ? $sanitized_form_title : "srfm-entries-{$form_id}";

			$csv_filename = 'srfm-entries-' . $sanitized_form_title . '.csv';
			$csv_filepath = $temp_dir . $csv_filename;

			if ( file_exists( $csv_filepath ) ) {
				unlink( $csv_filepath );
			}

			$stream = fopen( $csv_filepath, 'wb' ); // phpcs:ignore -- Using fopen to decrease the memory use.

			if ( ! is_resource( $stream ) ) {
				continue;
			}

			$csv_files[] = $csv_filepath;

			// Build CSV content.
			$block_data = self::build_block_key_map_and_labels( $results );
			self::write_csv_header( $stream, $block_data['labels'] );
			self::write_csv_rows( $stream, $results, $block_data['map'] );

			fclose( $stream ); // phpcs:ignore -- Using fopen to decrease the memory use.

			// Add to ZIP if multiple forms.
			if ( ! $is_single_form && $zip && filesize( $csv_filepath ) > 0 ) {
				$zip->addFile( $csv_filepath, $csv_filename );
			}
		}

		// Single form - return CSV.
		if ( $is_single_form && ! empty( $csv_filepath ) && file_exists( $csv_filepath ) ) {
			return [
				'success'  => true,
				'filename' => basename( $csv_filepath ),
				'filepath' => $csv_filepath,
				'type'     => 'csv',
			];
		}

		// Multiple forms - return ZIP.
		if ( ! $is_single_form && $zip ) {
			$zip->close();

			// Clean up CSV files.
			foreach ( $csv_files as $csv_file ) {
				if ( file_exists( $csv_file ) ) {
					unlink( $csv_file );
				}
			}

			return [
				'success'  => true,
				'filename' => 'SureForms-Entries.zip',
				'filepath' => $temp_zip,
				'type'     => 'zip',
			];
		}

		return [
			'success' => false,
			'error'   => __( 'Failed to generate export file.', 'sureforms' ),
		];
	}

	/**
	 * Build where conditions for entry queries.
	 *
	 * @param array<string, int|string|array<int>> $args Query arguments.
	 *
	 * @since 2.0.0
	 * @return array<mixed> Where conditions array.
	 */
	private static function build_where_conditions( $args ) {
		$where_conditions = [];

		// Filter by entry IDs.
		if ( ! empty( $args['entry_ids'] ) && is_array( $args['entry_ids'] ) ) {
			$where_conditions[] = [
				[
					'key'     => 'ID',
					'compare' => 'IN',
					'value'   => array_map( 'absint', $args['entry_ids'] ),
				],
			];
			return $where_conditions;
		}

		// Filter by status.
		if ( 'all' !== $args['status'] ) {
			$where_conditions[] = [
				[
					'key'     => 'status',
					'compare' => '=',
					'value'   => Helper::get_string_value( $args['status'] ),
				],
			];
		} else {
			// Exclude trash when status is 'all'.
			$where_conditions[] = [
				[
					'key'     => 'status',
					'compare' => '!=',
					'value'   => 'trash',
				],
			];
		}

		// Filter by form ID.
		$form_id = Helper::get_integer_value( $args['form_id'] ?? 0 );
		if ( ! empty( $args['form_id'] ) && $form_id > 0 ) {
			$where_conditions[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		// Filter by date range.
		if ( ! empty( $args['date_from'] ) || ! empty( $args['date_to'] ) ) {
			$date_conditions = [];

			if ( ! empty( $args['date_from'] ) ) {
				$date_conditions[] = [
					'key'     => 'created_at',
					'compare' => '>=',
					'value'   => Helper::get_string_value( $args['date_from'] ),
				];
			}

			if ( ! empty( $args['date_to'] ) ) {
				$date_conditions[] = [
					'key'     => 'created_at',
					'compare' => '<=',
					'value'   => Helper::get_string_value( $args['date_to'] ),
				];
			}

			if ( count( $date_conditions ) > 1 ) {
				$where_conditions[] = $date_conditions;
			}
		}

		// Filter by search (entry ID only).
		if ( ! empty( $args['search'] ) ) {
			$search_term        = Helper::get_integer_value( $args['search'] );
			$where_conditions[] = [
				[
					'key'     => 'ID',
					'compare' => '=',
					'value'   => $search_term,
				],
			];
		}

		return $where_conditions;
	}

	/**
	 * Get entries data for export based on entry IDs and form ID.
	 *
	 * @param array<int> $entry_ids Entry IDs.
	 * @param int        $form_id   Form ID.
	 *
	 * @since 2.0.0
	 * @return array<mixed> Entry data.
	 */
	private static function get_entries_data_for_export( $entry_ids, $form_id ) {
		return EntriesTable::get_all(
			[
				'where'   => [
					[
						[
							'key'     => 'ID',
							'compare' => 'IN',
							'value'   => $entry_ids,
						],
						[
							'key'     => 'form_id',
							'compare' => '=',
							'value'   => $form_id,
						],
					],
				],
				'columns' => '*',
			],
			false
		);
	}

	/**
	 * Build block key map and labels for CSV export.
	 *
	 * @param array<mixed> $results Entry results.
	 *
	 * @since 2.0.0
	 * @return array{map: array<string,string>, labels: array<string,string>} Map and labels.
	 */
	private static function build_block_key_map_and_labels( $results ) {
		$block_key_map = [];
		$block_labels  = [];
		$excluded      = Helper::get_excluded_fields();

		foreach ( $results as $entry ) {
			$form_data = is_array( $entry ) && isset( $entry['form_data'] ) ? Helper::get_array_value( $entry['form_data'] ) : [];

			foreach ( $form_data as $srfm_key => $value ) {
				if ( in_array( $srfm_key, $excluded, true ) ) {
					continue;
				}

				$block_id = Helper::get_block_id_from_key( $srfm_key );

				if ( empty( $block_id ) ) {
					continue;
				}

				$block_key_map[ $block_id ] = $srfm_key;
				$block_labels[ $block_id ]  = Helper::get_field_label_from_key( $srfm_key );
			}
		}

		return [
			'map'    => $block_key_map,
			'labels' => $block_labels,
		];
	}

	/**
	 * Write CSV header row.
	 *
	 * @param resource      $stream       File stream.
	 * @param array<string> $block_labels Block labels.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private static function write_csv_header( $stream, $block_labels ) {
		$header = array_merge(
			[ __( 'Entry ID', 'sureforms' ), __( 'Date', 'sureforms' ), __( 'Status', 'sureforms' ) ],
			array_values( $block_labels )
		);
		fputcsv( $stream, $header ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
	}

	/**
	 * Write CSV data rows.
	 *
	 * @param resource             $stream        File stream.
	 * @param array<mixed>         $results       Entry results.
	 * @param array<string,string> $block_key_map Block key map.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private static function write_csv_rows( $stream, $results, $block_key_map ) {
		foreach ( $results as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$row       = [];
			$row[]     = isset( $entry['ID'] ) ? Helper::get_integer_value( $entry['ID'] ) : '';
			$row[]     = isset( $entry['created_at'] ) ? Helper::get_string_value( $entry['created_at'] ) : '';
			$row[]     = isset( $entry['status'] ) ? Helper::get_string_value( $entry['status'] ) : '';
			$form_data = isset( $entry['form_data'] ) ? Helper::get_array_value( $entry['form_data'] ) : [];

			foreach ( $block_key_map as $srfm_key ) {
				$field_value = $form_data[ $srfm_key ] ?? '';
				$row[]       = self::normalize_field_values( $field_value );
			}

			fputcsv( $stream, $row ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		}
	}

	/**
	 * Normalize field values for CSV export.
	 *
	 * @param mixed $field_value Field value.
	 *
	 * @since 2.0.0
	 * @return string Normalized value.
	 */
	private static function normalize_field_values( $field_value ) {
		// Handle arrays (multi-select, checkboxes, etc.).
		if ( is_array( $field_value ) ) {
			return implode( ', ', array_map( 'sanitize_text_field', $field_value ) );
		}

		return sanitize_text_field( Helper::get_string_value( $field_value ) );
	}
}
