<?php
/**
 * SureForms Database Tables Base Class.
 *
 * @link       https://sureforms.com
 * @since      x.x.x
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
 * @since x.x.x
 */
abstract class Base {

	/**
	 * WordPress Database class instance.
	 *
	 * @var \wpdb
	 * @since x.x.x
	 */
	protected $wpdb;

	/**
	 * Current database table prefix mixed with 'srfm_' as ending.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $table_prefix;

	/**
	 * Custom table suffix without any prefix. This needs to be overridden from child class.
	 * Eg: For entries table, suffix will be 'entries' which will be prefixed and finally named as 'wp_srfm_entries'.
	 *
	 * @var string
	 * @since x.x.x
	 * @override
	 */
	protected $table_suffix;

	/**
	 * Full table name mixed with table prefix and table suffix.
	 *
	 * @var string
	 * @since x.x.x
	 */
	private $table_name;

	/**
	 * Current table database result caches.
	 *
	 * @var array<mixed>
	 * @since x.x.x
	 */
	private $caches = [];

	/**
	 * Init class.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb         = $wpdb;
		$this->table_prefix = $this->wpdb->prefix . 'srfm_';
		$this->table_name   = $this->table_prefix . $this->table_suffix;
	}

	/**
	 * Returns the current table schema.
	 *
	 * @since x.x.x
	 * @return array
	 */
	abstract public function get_schema();

	/**
	 * Returns full table name.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_tablename() {
		return $this->table_name;
	}

	/**
	 * Retrieve a cached value by its key.
	 *
	 * @param string $key The cache key.
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return void
	 */
	protected function cache_reset() {
		$this->caches = [];
	}

	/**
	 * Conditionally returns current database charset or collate.
	 *
	 * @since x.x.x
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
	 * @param array $columns Array of columns.
	 * @since x.x.x
	 * @return int|bool
	 */
	public function create( $columns = [] ) {
		if ( empty( $columns ) ) {
			return false; // It's better to return a boolean for failure.
		}

		// Escape table name.
		$table_name = $this->wpdb->esc_like( $this->get_tablename() );

		// Prepare columns list.
		$columns_list = implode(
			', ',
			array_map(
				function ( $col ) {
					return $this->wpdb->esc_like( $col );
				},
				$columns
			)
		);

		$wpdb = $this->wpdb;

		// Execute the query.
		// phpcs:ignore
		return $wpdb->query( $wpdb->prepare( 'CREATE TABLE IF NOT EXISTS %s ( %s ) %s', $table_name, $columns_list, $this->get_charset_collate() ) );
	}

	/**
	 * Drop or delete current table.
	 *
	 * @since x.x.x
	 * @return int|bool
	 */
	public function drop() {
		$wpdb = $this->wpdb;

		// Escape table name.
		$table_name = $wpdb->esc_like( $this->get_tablename() );

		// Prepare the SQL query.
		$query = $wpdb->prepare(
			'DROP TABLE IF EXISTS %s',
			$table_name
		);

		// Execute the query.
		// phpcs:ignore
		return $wpdb->query( $query );
	}

	/**
	 * Check if current table exists.
	 *
	 * @since x.x.x
	 * @return boolean
	 */
	public function exists() {
		global $wpdb;

		// Escape table name.
		$table_name = $wpdb->esc_like( $this->get_tablename() );

		// Prepare the SQL query to check if the table exists.
		$query = $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		);

		// Check if the table exists.
		// phpcs:ignore
		if ( $wpdb->get_var( $query ) === $table_name ) {
			return true;
		}

		return false;
	}

	/**
	 * Insert data. Basically, a wrapper method for wpdb::insert.
	 *
	 * @param [type] $data Data to insert (in column => value pairs).
	 *                     Both `$data` columns and `$data` values should be "raw" (neither should be SQL escaped).
	 *                     Sending a null value will cause the column to be set to NULL - the corresponding
	 *                     format is ignored in this case.
	 * @param [type] $format Optional. An array of formats to be mapped to each of the value in `$data`.
	 *                       If string, that format will be used for all of the values in `$data`.
	 *                       A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                       If omitted, all values in `$data` will be treated as strings unless otherwise
	 *                       specified in wpdb::$field_types. Default null.
	 * @since x.x.x
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $data, $format = null ) {
		$processed_data = $this->process_data( $data );
		return $this->wpdb->insert(
			$this->get_tablename(),
			$processed_data['data'],
			! is_null( $format ) ? $format : $processed_data['format'] // If format is not null, use it otherwise use from schema.
		);
	}

	/**
	 * Retrieve results from the database based on the given WHERE clauses and selected columns.
	 *
	 * This method builds a SQL SELECT query with optional WHERE clauses and retrieves the results
	 * from the database. The results are cached to improve performance on subsequent requests.
	 *
	 * @param array  $where_clauses Optional. An associative array of WHERE clauses for the SQL query.
	 *                               Each key represents a column name, and each value is the value
	 *                               to match. If the value is an array, it will be used in an IN clause.
	 *                               Example: ['column1' => 'value1', 'column2' => ['value2', 'value3']].
	 *                               Default is an empty array.
	 * @param string $columns Optional. A string specifying which columns to select. Defaults to '*' (all columns).
	 * @since x.x.x
	 * @return array An associative array of results where each element represents a row, or an empty array if no results are found.
	 */
	public function get_results( $where_clauses = [], $columns = '*' ) {
		$wpdb = $this->wpdb;

		$table_name = $this->get_tablename();

		// Start building the query.
		$query = "SELECT {$columns} FROM {$table_name}";

		// If there are WHERE clauses, prepare and append them to the query.
		if ( is_array( $where_clauses ) && ! empty( $where_clauses ) ) {
			// Start constructing WHERE clause.
			$where_clause = [];
			$values       = [];

			foreach ( $where_clauses as $key => $value ) {
				// Check if value is an array for multiple values (IN clause).
				if ( is_array( $value ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $value ), '%s' ) );
					$where_clause[] = "{$key} IN ({$placeholders})";
					$values         = array_merge( $values, $value );
				} else {
					$where_clause[] = "{$key} = %s";
					$values[]       = $value;
				}
			}

			// Combine the WHERE clauses into a single string.
			$query .= ' WHERE ' . implode( ' AND ', $where_clause );

			// Prepare the query with placeholders.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare( $query, ...$values );
		}

		// Add a semicolon (optional, not necessary in practice).
		$query .= ';';

		$cached_results = $this->cache_get( $query );
		if ( $cached_results ) {
			// Return the cached data if exists.
			return $cached_results;
		}

		// phpcs:ignore
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result = $this->decode_by_datatype( $result );
			}
		}

		// Execute the query and return results.
		return $this->cache_set( $query, $results );
	}

	/**
	 * Get the SQL format specifier based on the provided data type.
	 *
	 * @param string $type The data type for which to get the SQL format specifier.
	 *                     Possible values: 'string', 'array', 'number', 'boolean'.
	 * @since x.x.x
	 * @return string The SQL format specifier. One of '%s' for string or array (converted to JSON), '%d' for number or boolean.
	 */
	protected function get_format_by_datatype( $type ) {
		switch ( $type ) {
			case 'string':
			case 'array': // Because array will be converted to json string.
				return '%s';

			case 'number':
			case 'boolean':
				return '%d';
		}
	}

	/**
	 * Decode data based on the schema data types.
	 *
	 * @param array $data An associative array of data where the key is the column name and the value is the data to decode.
	 *                    The data will be decoded if the column type in the schema is 'array' (JSON string).
	 * @since x.x.x
	 * @return array An associative array of decoded data based on the schema.
	 */
	protected function decode_by_datatype( $data ) {
		$_data = [];
		foreach ( $this->get_schema() as $key => $value ) {
			// Process defaults.
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$_data[ $key ] = 'array' === $value['type'] ? json_decode( $data[ $key ], true ) : $data[ $key ];
		}
		return $_data;
	}

	/**
	 * Encode a value based on the specified data type.
	 *
	 * @param mixed  $value The value to encode. The encoding will depend on the data type specified.
	 * @param string $type The data type for encoding. Possible values: 'string', 'number', 'boolean', 'array'.
	 * @since x.x.x
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
				return wp_json_encode( Helper::get_array_value( $value ) );
		}
	}

	/**
	 * Process and format data based on the schema.
	 *
	 * @param array $data An associative array of data where the key is the column name and the value is the data to process.
	 *                    Missing values will be replaced with default values specified in the schema.
	 * @since x.x.x
	 * @return array An associative array containing:
	 *                - 'data': Processed data with values encoded according to their data types.
	 *                - 'format': An array of format specifiers corresponding to the data values.
	 */
	protected function process_data( $data ) {
		$_data  = [];
		$format = [];
		foreach ( $this->get_schema() as $key => $value ) {
			// Process defaults.
			if ( ! isset( $data[ $key ] ) ) {
				if ( ! isset( $value['default'] ) ) {
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
}
