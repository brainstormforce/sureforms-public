<?php
/**
 * SureForms Database Tables Base Class.
 *
 * @link       https://sureforms.com
 * @since      0.0.10
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database;

use SRFM\Inc\Helper;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Tables Base Class
 *
 * @since 0.0.10
 */
abstract class Base {
	/**
	 * WordPress Database class instance.
	 *
	 * @var \wpdb
	 * @since 0.0.10
	 */
	protected $wpdb;

	/**
	 * Current database table prefix mixed with 'srfm_' as ending.
	 *
	 * @var string
	 * @since 0.0.10
	 */
	protected $table_prefix;

	/**
	 * Custom table suffix without any prefix. This needs to be overridden from child class.
	 * Eg: For entries table, suffix will be 'entries' which will be prefixed and finally named as 'wp_srfm_entries'.
	 *
	 * @var string
	 * @since 0.0.10
	 * @override
	 */
	protected $table_suffix;

	/**
	 * Version for current custom table. Default is 1.
	 * Unlike semantic versioning [eg: 1.0.0, 1.0.1] we use natural integer like 1, 2, 3... and so on.
	 * Update the table version from child class when any DB upgrade or alteration related changes are made.
	 *
	 * @var int
	 * @since 0.0.13
	 * @override
	 */
	protected $table_version = 1;

	/**
	 * Full table name mixed with table prefix and table suffix.
	 *
	 * @var string
	 * @since 0.0.10
	 */
	private $table_name;

	/**
	 * Whether or not the current database table is upgradable.
	 * Determines on the basis of the table version.
	 *
	 * @var bool
	 * @since 0.0.13
	 */
	private $db_upgradable;

	/**
	 * Current table database result caches.
	 *
	 * @var array<mixed>
	 * @since 0.0.10
	 */
	private $caches = [];

	/**
	 * Init class.
	 *
	 * @since 0.0.10
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb         = $wpdb;
		$this->table_prefix = $this->wpdb->prefix . 'srfm_';
		$this->table_name   = $this->table_prefix . $this->table_suffix;
	}

	/**
	 * Actions to initialize during object unload.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function __destruct() {
		/**
		 * Just incase if any developer forgets to stop the db upgrade after starting.
		 * This fallback handling will take care of such scenarios.
		 */
		$this->stop_db_upgrade();
	}

	/**
	 * Returns the current table schema.
	 *
	 * @since 0.0.10
	 * @return array<string,array<mixed>>
	 */
	abstract public function get_schema();

	/**
	 * Current table columns definition to create table. These definitions will be used by the create() method.
	 *
	 * @since 0.0.13
	 * @return array<string>
	 */
	abstract public function get_columns_definition();

	/**
	 * Any columns that needs to be added if the current table already exists. These definitions will be used by maybe_add_new_columns() method.
	 * Override this from child class if needed.
	 *
	 * @since 0.0.13
	 * @return array<string>
	 * @override
	 */
	public function get_new_columns_definition() {
		return [];
	}

	/**
	 * Array of columns that needs to be renamed to new column name. It will be used by maybe_rename_columns() method.
	 * Format:
	 * [
	 * [
	 * 'from' => 'old_column_name',
	 * 'to'   => 'new_column_name',
	 * 'type' => 'column type definition eg: LONGTEXT', // Optional.
	 * ],
	 * ]
	 *
	 * @since 0.0.13
	 * @return array<array<string,string>>
	 */
	public function get_columns_to_rename() {
		return [];
	}

	/**
	 * Start the database upgrade process.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function start_db_upgrade() {
		$versions     = Helper::get_array_value( get_option( 'srfm_database_table_versions', [] ) );
		$prev_version = ! empty( $versions[ $this->table_suffix ] ) ? absint( $versions[ $this->table_suffix ] ) : false;

		if ( ! $prev_version ) {
			/**
			 * If we are here then there is the chance that
			 * this site is the new site or fresh setup.
			 */
			$this->db_upgradable = true;
			return;
		}

		$this->db_upgradable = $this->table_version > $prev_version;
	}

	/**
	 * Stop the database upgrade process.
	 *
	 * @since 0.0.13
	 * @return bool Returns true on success.
	 */
	public function stop_db_upgrade() {
		if ( ! $this->db_upgradable ) {
			// Only upgrade when it is needed.
			return false;
		}

		$versions = Helper::get_array_value( get_option( 'srfm_database_table_versions', [] ) );

		$versions[ $this->table_suffix ] = $this->table_version;

		update_option( 'srfm_database_table_versions', $versions );

		return true;
	}

	/**
	 * Check if current table's DB is upgradable or not.
	 *
	 * @since 0.0.13
	 * @return bool True or false depending if DB is upgradable or not.
	 */
	public function is_db_upgradable() {
		return $this->db_upgradable;
	}

	/**
	 * Returns full table name.
	 *
	 * @since 0.0.10
	 * @return string
	 */
	public function get_tablename() {
		return $this->table_name;
	}

	/**
	 * Conditionally returns current database charset or collate.
	 *
	 * @since 0.0.10
	 * @return string
	 */
	public function get_charset_collate() {
		$charset_collate = '';

		if ( $this->wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $this->wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET {$this->wpdb->charset}";
			}
			if ( ! empty( $this->wpdb->collate ) ) {
				$charset_collate .= " COLLATE {$this->wpdb->collate}";
			}
		}

		return $charset_collate;
	}

	/**
	 * Create table.
	 *
	 * @param array<string> $columns Array of columns.
	 * @since 0.0.10
	 * @return int|bool
	 */
	public function create( $columns = [] ) {
		if ( ! $this->db_upgradable ) {
			// Only upgrade when it is needed.
			return false;
		}

		if ( empty( $columns ) ) {
			return false; // It's better to return a boolean for failure.
		}

		// Prepare columns list.
		$columns_list = implode(
			', ',
			$columns
		);

		$wpdb = $this->wpdb;

		// Execute the query.
		$query = $wpdb->prepare( 'CREATE TABLE IF NOT EXISTS %1s ( %2s ) %3s', $this->get_tablename(), $columns_list, $this->get_charset_collate() ); // phpcs:ignore -- It is okay to use complex placeholder here for the table name, column list and character set because we don't want to quote these variables.

		if ( ! $query ) {
			// If we are here, then we probably have bad query to work with and prepare method has returned null-ish value.
			return false;
		}

		$result = $wpdb->query( $query ); // phpcs:ignore -- We are already using prepare above.

		if ( false === $result ) {
			// Stop DB alteration if we have any error.
			$this->db_upgradable = false;
		}

		return $result;
	}

	/**
	 * Rename the column of the current table conditionally.
	 *
	 * @param array<array<string,string>> $rename_columns Array of columns to rename.
	 * @since 0.0.13
	 * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows affected/selected for all other queries. Boolean false on error.
	 */
	public function maybe_rename_columns( $rename_columns = [] ) {
		if ( ! $rename_columns ) {
			return false;
		}

		if ( ! $this->db_upgradable ) {
			// Only upgrade when it is needed.
			return false;
		}

		$existing_columns = $this->get_columns();

		if ( ! $existing_columns ) {
			// Table does not exists or is new table.
			return false;
		}

		$wpdb = $this->wpdb;

		$query_parts = [];
		foreach ( $rename_columns as $column ) {
			if ( empty( $existing_columns[ $column['from'] ] ) ) {
				// Bail if column is already renamed or does not exists.
				continue;
			}

			$query_part = $wpdb->prepare(
				'CHANGE %1s %2s %3s', // phpcs:ignore -- It is okay to use complex placeholders as we don't want values to be quoted.
				$column['from'],
				$column['to'],
				! empty( $column['type'] ) ? $column['type'] : $existing_columns[ $column['from'] ]['Type'] // This is column type i.e LONGTEXT, BIGINT etc.
			);

			if ( is_string( $query_part ) && $query_part ) {
				$query_parts[] = trim( $query_part );
			}
		}

		if ( empty( $query_parts ) ) {
			// No renaming required.
			return false;
		}

		$result = $wpdb->query( $wpdb->prepare( 'ALTER TABLE %1s ', $this->get_tablename() ) . implode( ', ', $query_parts ) . ';' ); // phpcs:ignore -- It is okay to use query directly here.

		if ( false === $result ) {
			// Stop DB alteration if we have any error.
			$this->db_upgradable = false;
		}

		return $result;
	}

	/**
	 * Adds the new columns to the current table conditionally.
	 *
	 * @param array<string> $new_columns The array of new columns to add. Same as the create method.
	 * @since 0.0.13
	 * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows affected/selected for all other queries. Boolean false on error.
	 */
	public function maybe_add_new_columns( $new_columns = [] ) {
		if ( ! $new_columns ) {
			return false;
		}

		if ( ! $this->db_upgradable ) {
			// Only upgrade when it is needed.
			return false;
		}

		$existing_columns = $this->get_columns();

		if ( ! $existing_columns ) {
			// Table does not exists or is new table.
			return false;
		}

		$existing_indexes = $this->get_indexes();

		$alter_queries = [];

		$wpdb = $this->wpdb;

		// Check and add each column if it does not exist.
		foreach ( $new_columns as $column_definition ) {
			preg_match( '/INDEX\s+(.*?)\s+\(/', $column_definition, $index_matches );

			if ( ! empty( $index_matches[1] ) ) {
				if ( isset( $existing_indexes[ $index_matches[1] ] ) ) {
					// Move to next element if current index already exists.
					continue;
				}
				// Stack and move to next if we are indexing.
				$alter_queries[] = $wpdb->prepare( 'ADD %1s', $column_definition ); // phpcs:ignore -- We don't need quote here.
				continue;
			}

			preg_match( '/(\w+)\s/', $column_definition, $column_matches );
			$column_name = $column_matches[1] ?? '';

			// If the column does not exist, add it.
			if ( ! isset( $existing_columns[ $column_name ] ) ) {
				$alter_queries[] = $wpdb->prepare( 'ADD COLUMN %1s', $column_definition ); // phpcs:ignore -- We don't need quote here.
			}
		}

		if ( $alter_queries ) {
			$query = $wpdb->prepare(
				'ALTER TABLE %1s %2s', // phpcs:ignore -- We don't want to quote the value strings for the query.
				$this->get_tablename(),
				implode( ', ', $alter_queries )
			);

			if ( ! $query ) {
				// If we are here then we probably have bad query and prepare method has returned null.
				return false;
			}

			// Execute the query.
			$result = $wpdb->query( $query ); // phpcs:ignore -- It is okay. We are already using prepare above and we need to do DB query directly here.

			if ( false === $result ) {
				// Stop DB alteration if we have any error.
				$this->db_upgradable = false;
			}

			return $result;
		}

		return false;
	}

	/**
	 * Returns an array columns of current table.
	 *
	 * @since 0.0.13
	 * @return array<string,array<string,mixed>>
	 */
	public function get_columns() {
		$wpdb = $this->wpdb;

		$columns = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %1s', $this->get_tablename() ), ARRAY_A ); // phpcs:ignore -- It is okay to use query db directly here.

		if ( empty( $columns ) ) {
			return [];
		}

		$_columns = [];
		if ( is_array( $columns ) ) {
			foreach ( $columns as $column ) {
				if ( ! is_string( $column['Field'] ) ) {
					continue;
				}

				$_columns[ $column['Field'] ] = $column;
			}
		}
		return $_columns;
	}

	/**
	 * Returns an array indexes of current table.
	 *
	 * @since 0.0.13
	 * @return array<mixed>
	 */
	public function get_indexes() {
		$wpdb = $this->wpdb;

		$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %1s', $this->get_tablename() ), ARRAY_A ); // phpcs:ignore -- We don't need quote here so this is fine.

		if ( empty( $indexes ) ) {
			return [];
		}

		$_indexes = [];
		if ( is_array( $indexes ) ) {
			foreach ( $indexes as $index ) {
				$_indexes[ $index['Key_name'] ] = $index; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}
		return $_indexes;
	}

	/**
	 * Insert data. Basically, a wrapper method for wpdb::insert.
	 *
	 * @param array<mixed>              $data Data to insert (in column => value pairs).
	 *                                  Both `$data` columns and `$data` values should be "raw" (neither should be SQL escaped).
	 *                                  Sending a null value will cause the column to be set to NULL - the corresponding
	 *                                  format is ignored in this case.
	 * @param array<string>|string|null $format Optional. An array of formats to be mapped to each of the value in `$data`.
	 *                       If string, that format will be used for all of the values in `$data`.
	 *                       A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                       If omitted, all values in `$data` will be treated as strings unless otherwise
	 *                       specified in wpdb::$field_types. Default null.
	 * @since 0.0.10
	 * @return int|false The id of the inserted entry, or false on error.
	 */
	public function use_insert( $data, $format = null ) {
		$prepared_data = $this->prepare_data( $data );

		if ( is_null( $format ) ) {
			/**
			 * Use formats from schema if not provided explicitly.
			 *
			 * @var array<string>|string|null $format Format specifier for the data.
			 */
			$format = $prepared_data['format'];
		}

		$result = $this->wpdb->insert( $this->get_tablename(), $prepared_data['data'], $format );
		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Update a row data of current table. Basically, a wrapper method for wpdb::update.
	 *
	 * @param array<string,mixed> $data            Data to update (in column => value pairs).
	 *                               Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                               Sending a null value will cause the column to be set to NULL - the corresponding
	 *                               format is ignored in this case.
	 * @param array<string,mixed> $where           A named array of WHERE clauses (in column => value pairs).
	 *                               Multiple clauses will be joined with ANDs.
	 *                               Both $where columns and $where values should be "raw".
	 *                               Sending a null value will create an IS NULL comparison - the corresponding
	 *                               format will be ignored in this case.
	 * @since 0.0.13
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function use_update( $data, $where ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- It is okay. This is our wrapper method.
		$prepared_data = $this->prepare_data( $data, true );

		/**
		 * Data format specifier.
		 *
		 * @var array<string>|string|null $format Format specifier for the data.
		 */
		$format = $prepared_data['format'];

		// Reset the cache on update.
		$this->cache_reset();

		return $this->wpdb->update(
			$this->get_tablename(),
			$prepared_data['data'],
			$where,
			$format
		);
	}

	/**
	 * Delete a row data of current table. Basically, a wrapper method for wpdb::delete.
	 *
	 * @param array<string,mixed>  $where        A named array of WHERE clauses (in column => value pairs).
	 *                             Multiple clauses will be joined with ANDs.
	 *                             Both $where columns and $where values should be "raw".
	 *                             Sending a null value will create an IS NULL comparison - the corresponding
	 *                             format will be ignored in this case.
	 * @param array<string>|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
	 *                                      If string, that format will be used for all of the items in $where.
	 *                                      A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                                      If omitted, all values in $data will be treated as strings unless otherwise
	 *                                      specified in wpdb::$field_types. Default null.
	 * @since 0.0.13
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function use_delete( $where, $where_format = null ) {
		return $this->wpdb->delete( $this->get_tablename(), $where, $where_format );
	}

	/**
	 * Retrieve results from the database based on the given WHERE clauses and selected columns.
	 *
	 * This method builds a SQL SELECT query with optional WHERE clauses and retrieves the results
	 * from the database. The results are cached to improve performance on subsequent requests.
	 *
	 * @param array<mixed>  $where_clauses Optional. An associative array of WHERE clauses for the SQL query.
	 *                               Each key represents a column name, and each value is the value
	 *                               to match. If the value is an array, it will be used in an IN clause.
	 *                               Example: ['column1' => 'value1', 'column2' => ['value2', 'value3']].
	 *                               Default is an empty array.
	 * @param string        $columns Optional. A string specifying which columns to select. Defaults to '*' (all columns).
	 * @param array<string> $extra_queries Optional. Array of extra queries to append at the end of main query.
	 * @param bool          $decode Optional. Whether to decode the results by datatype. Default is true.
	 * @since 0.0.10
	 * @return array<mixed> An associative array of results where each element represents a row, or an empty array if no results are found.
	 */
	public function get_results( $where_clauses = [], $columns = '*', $extra_queries = [], $decode = true ) {
		$wpdb = $this->wpdb;

		$table_name = $this->get_tablename();

		// Start building the query.
		$query = "SELECT {$columns} FROM {$table_name}";

		// If there are WHERE clauses, prepare and append them to the query.
		$query .= $this->prepare_where_clauses( $where_clauses );

		if ( ! empty( $extra_queries ) ) {
			$query .= ' ' . implode( ' ', array_map( 'trim', $extra_queries ) );
		}

		// Add a semicolon at the end of the query.
		$query = rtrim( trim( $query ), ';' ) . ';';

		$cached_results = $this->cache_get( $query );
		if ( $cached_results ) {
			// Return the cached data if exists.
			return Helper::get_array_value( $cached_results );
		}

		// phpcs:ignore
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( $decode && ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result = $this->decode_by_datatype( $result );
			}
		}

		// Execute the query and return results.
		return Helper::get_array_value( $this->cache_set( $query, $results ) );
	}

	/**
	 * Get the total number of rows in the table.
	 *
	 * @param array<mixed> $where_clauses Optional. An associative array of WHERE clauses for the SQL query.
	 * @since 0.0.13
	 * @return int The total number of rows in the table.
	 */
	public function get_total_count( $where_clauses = [] ) {
		$wpdb = $this->wpdb;

		$table_name = $this->get_tablename();

		// Start building the query.
		$query = "SELECT COUNT(*) FROM {$table_name}";

		// If there are WHERE clauses, prepare and append them to the query.
		$query .= $this->prepare_where_clauses( $where_clauses );

		// Add a semicolon at the end of the query.
		$query = rtrim( trim( $query ), ';' ) . ';';

		$cached_results = $this->cache_get( $query );
		if ( $cached_results ) {
			// Return the cached data if exists.
			return Helper::get_integer_value( $cached_results );
		}

		// phpcs:ignore
		$results = Helper::get_integer_value( $wpdb->get_var( $query ) );

		// Execute the query and return the integer count.
		return Helper::get_integer_value( $this->cache_set( $query, $results ) );
	}

	/**
	 * Retrieve a cached value by its key.
	 *
	 * @param string $key The cache key.
	 * @since 0.0.10
	 * @return mixed|null The cached value if it exists, or null if the key does not exist in the cache.
	 */
	protected function cache_get( $key ) {
		$key = md5( $key );
		if ( ! isset( $this->caches[ $key ] ) ) {
			return null;
		}
		return $this->caches[ $key ];
	}

	/**
	 * Store a value in the cache with the specified key.
	 *
	 * @param string $key The cache key.
	 * @param mixed  $value The value to store in the cache.
	 * @since 0.0.10
	 * @return mixed The stored value.
	 */
	protected function cache_set( $key, $value ) {
		$key                  = md5( $key );
		$this->caches[ $key ] = $value;
		return $value;
	}

	/**
	 * Reset the cache by clearing all stored values.
	 *
	 * @since 0.0.10
	 * @return void
	 */
	protected function cache_reset() {
		$this->caches = [];
	}

	/**
	 * Prepares WHERE clauses for a SQL query based on the provided conditions.
	 *
	 * This method constructs a WHERE statement by iterating through the
	 * specified conditions, appending them with the appropriate SQL syntax.
	 * It supports both single key-value pairs and arrays of conditions.
	 *
	 * @param array<mixed> $where_clauses {
	 *     An associative array of conditions to include in the WHERE clause.
	 *
	 *     @type string|array $key   The column name or an array of conditions.
	 *     @type array $value {
	 *         An associative array of comparison data.
	 *
	 *         @type string $key     The column name for comparison.
	 *         @type string $compare The comparison operator (e.g., '=', 'LIKE').
	 *         @type mixed  $value   The value to compare against.
	 *         @type string $RELATION Optional. The logical relation ('AND' or 'OR').
	 *     }
	 * }
	 *
	 * @since 1.1.1 -- Added support for "IN" compare.
	 * @since 0.0.13
	 * @return string The prepared SQL WHERE clause with placeholders, or an empty string if no clauses were provided.
	 */
	protected function prepare_where_clauses( $where_clauses = [] ) {
		if ( empty( $where_clauses ) ) {
			return '';
		}

		$wpdb = $this->wpdb;

		// If there are WHERE clauses, prepare and append them to the query.
		if ( is_array( $where_clauses ) ) {
			$where  = '';
			$values = [];
			$schema = $this->get_schema();

			foreach ( $where_clauses as $key => $value ) {

				$relation = ! empty( $value['RELATION'] ) ? trim( $value['RELATION'] ) : 'AND';

				if ( is_int( $key ) ) {
					foreach ( $value as $_key => $_value ) {
						if ( is_int( $_key ) ) {
							switch ( $_value['compare'] ) {
								case 'LIKE':
									$where   .= ' ' . $_value['key'] . ' ' . $_value['compare'] . ' "%%' . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) ) . '%%" ' . $relation;
									$values[] = $_value['value'];
									break;

								case 'IN':
									// Based on the number of values and datatype, it will create WHERE clause for $wpdb::prepare method. Eg: for ID with three values column: ID IN (%d, %d, %d).
									$datatype = $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) );
									$where   .= ' ' . $_value['key'] . ' ' . $_value['compare'] . ' (' . implode( ', ', array_fill( 0, count( $_value['value'] ), $datatype ) ) . ') ' . $relation;
									$values   = array_merge( $values, $_value['value'] );
									break;

								default:
									$where   .= ' ' . $_value['key'] . ' ' . $_value['compare'] . ' ' . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) ) . ' ' . $relation;
									$values[] = $_value['value'];
									break;
							}
						}
					}
					continue;
				}

				if ( ! isset( $schema[ $key ] ) ) {
					// Skip strictly if current key is not in our schema.
					continue;
				}

				$where   .= ' ' . $key . ' = ' . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $key ]['type'] ) ) . ' ' . $relation;
				$values[] = $value;
			}

			if ( ! $where ) {
				return '';
			}

			$where = ' WHERE ' . trim( trim( $where, $relation ) );

			// Prepare the query with placeholders.
			// @phpstan-ignore-next-line -- We are already assigning non-literal string above using "get_format_by_datatype" methods.
			return $wpdb->prepare( $where, ...$values ); // phpcs:ignore -- We are returning prepared sql query here. We are already using necessary placeholders in $where variable.
		}

		return '';
	}

	/**
	 * Prepare and format data based on the schema.
	 *
	 * @param array<mixed> $data An associative array of data where the key is the column name and the value is the data to process.
	 *                    Missing values will be replaced with default values specified in the schema.
	 * @param bool         $skip_defaults Whether or not to skip the defaults values. Pass true if updating the data.
	 * @since 0.0.10
	 * @return array<array<mixed>> An associative array containing:
	 *                - 'data': Prepared data with values encoded according to their data types.
	 *                - 'format': An array of format specifiers corresponding to the data values.
	 */
	protected function prepare_data( $data, $skip_defaults = false ) {
		$_data  = [];
		$format = [];
		foreach ( $this->get_schema() as $key => $value ) {
			// Process defaults.
			if ( ! isset( $data[ $key ] ) ) {
				if ( $skip_defaults || ! isset( $value['default'] ) ) {
					continue;
				}
				$data[ $key ] = $value['default'];
			}

			$format[]      = $this->get_format_by_datatype( $value['type'] ); // Format for the WP database methods.
			$_data[ $key ] = $this->encode_by_datatype( $data[ $key ], $value['type'] );
		}
		return [
			'data'   => $_data,
			'format' => $format,
		];
	}

	/**
	 * Get the SQL format specifier based on the provided data type.
	 *
	 * @param string $type The data type for which to get the SQL format specifier.
	 *                     Possible values: 'string', 'array', 'number', 'boolean'.
	 * @since 0.0.10
	 * @return string The SQL format specifier. One of '%s' for string or array (converted to JSON), '%d' for number or boolean.
	 */
	protected function get_format_by_datatype( $type ) {
		$format = '%s';
		switch ( $type ) {
			case 'string':
			case 'array': // Because array will be converted to json string.
				$format = '%s';
				break;

			case 'number':
			case 'boolean':
				$format = '%d';
				break;
		}

		return $format;
	}

	/**
	 * Decode data based on the schema data types.
	 *
	 * @param array<mixed> $data An associative array of data where the key is the column name and the value is the data to decode.
	 *                    The data will be decoded if the column type in the schema is 'array' (JSON string).
	 * @since 0.0.10
	 * @return array<mixed> An associative array of decoded data based on the schema.
	 */
	protected function decode_by_datatype( $data ) {
		$_data = [];
		foreach ( $this->get_schema() as $key => $schema ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			// Lets decode from JSON to Array for the results.
			$_data[ $key ] = 'array' === $schema['type'] ? Helper::get_array_value( json_decode( Helper::get_string_value( $data[ $key ] ), true ) ) : $data[ $key ];
		}
		return $_data;
	}

	/**
	 * Encode a value based on the specified data type.
	 *
	 * @param mixed  $value The value to encode. The encoding will depend on the data type specified.
	 * @param string $type The data type for encoding. Possible values: 'string', 'number', 'boolean', 'array'.
	 * @since 0.0.10
	 * @return mixed The encoded value. The type of the return value depends on the specified type:
	 *               - 'string': Encoded as a string.
	 *               - 'number': Encoded as an integer.
	 *               - 'boolean': Encoded as a boolean.
	 *               - 'array': Encoded as a JSON string.
	 */
	protected function encode_by_datatype( $value, $type ) {
		switch ( $type ) {
			case 'string':
				return Helper::get_string_value( $value );

			case 'number':
				return Helper::get_integer_value( $value );

			case 'boolean':
				return boolval( $value );

			case 'array':
				// Lets json_encode array values instead of serializing it.
				return Helper::encode_json( Helper::get_array_value( $value ) );
		}
	}
}
