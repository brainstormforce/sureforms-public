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
	 * Full table name mixed with table prefix and table suffix.
	 *
	 * @var string
	 * @since 0.0.10
	 */
	private $table_name;

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
	 * Returns the current table schema.
	 *
	 * @since 0.0.10
	 * @return array<string,array<mixed>>
	 */
	abstract public function get_schema();

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
		if ( empty( $columns ) ) {
			return false; // It's better to return a boolean for failure.
		}

		// Prepare columns list.
		$columns_list = implode(
			', ',
			$columns
		);

		// Execute the query.
		// phpcs:ignore
		return $this->wpdb->query( "CREATE TABLE IF NOT EXISTS {$this->get_tablename()} ( {$columns_list} ) {$this->get_charset_collate()}" );
	}

	/**
	 * Drop or delete current table.
	 *
	 * @since 0.0.10
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

		if ( ! $query ) {
			return false;
		}

		// Execute the query.
		// phpcs:ignore
		return $wpdb->query( $query );
	}

	/**
	 * Check if current table exists.
	 *
	 * @since 0.0.10
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
	 * @param array<mixed>    $data Data to insert (in column => value pairs).
	 *                        Both `$data` columns and `$data` values should be "raw" (neither should be SQL escaped).
	 *                        Sending a null value will cause the column to be set to NULL - the corresponding
	 *                        format is ignored in this case.
	 * @param string[]|string $format Optional. An array of formats to be mapped to each of the value in `$data`.
	 *                       If string, that format will be used for all of the values in `$data`.
	 *                       A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                       If omitted, all values in `$data` will be treated as strings unless otherwise
	 *                       specified in wpdb::$field_types. Default null.
	 * @since 0.0.10
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $data, $format = null ) {
		$prepared_data = $this->prepare_data( $data );

		if ( is_null( $format ) ) {
			$format = $prepared_data['format'];
		}

		// @phpstan-ignore-next-line
		return $this->wpdb->insert( $this->get_tablename(), $prepared_data['data'], $format );
	}

	/**
	 * Retrieve results from the database based on the given WHERE clauses and selected columns.
	 *
	 * This method builds a SQL SELECT query with optional WHERE clauses and retrieves the results
	 * from the database. The results are cached to improve performance on subsequent requests.
	 *
	 * @param array<mixed> $where_clauses Optional. An associative array of WHERE clauses for the SQL query.
	 *                              Each key represents a column name, and each value is the value
	 *                              to match. If the value is an array, it will be used in an IN clause.
	 *                              Example: ['column1' => 'value1', 'column2' => ['value2', 'value3']].
	 *                              Default is an empty array.
	 * @param string       $columns Optional. A string specifying which columns to select. Defaults to '*' (all columns).
	 * @since 0.0.10
	 * @return array<mixed> An associative array of results where each element represents a row, or an empty array if no results are found.
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

			// Current table schema.
			$schema = $this->get_schema();

			foreach ( $where_clauses as $key => $value ) {
				if ( ! isset( $schema[ $key ] ) ) {
					// Skip strictly if current key is not in our schema.
					continue;
				}

				// @phpstan-ignore-next-line
				$where_clause[] = $key . ' = ' . $this->get_format_by_datatype( $schema[ $key ]['type'] );
				$values[]       = $value;
			}

			if ( ! empty( $where_clause ) ) {
				// Combine the WHERE clauses into a single string.
				$query .= ' WHERE ' . implode( ' AND ', $where_clause );
			}

			// Prepare the query with placeholders.
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// @phpstan-ignore-next-line
			$query = $wpdb->prepare( $query, ...$values );
			// phpcs:enable
		}

		// Add a semicolon (optional, not necessary in practice).
		$query .= ';';

		$cached_results = $this->cache_get( $query );
		if ( $cached_results ) {
			// Return the cached data if exists.
			return Helper::get_array_value( $cached_results );
		}

		// phpcs:ignore
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result = $this->decode_by_datatype( $result );
			}
		}

		// Execute the query and return results.
		return Helper::get_array_value( $this->cache_set( $query, $results ) );
	}

	/**
	 * Prepare and format data based on the schema.
	 *
	 * @param array<mixed> $data An associative array of data where the key is the column name and the value is the data to process.
	 *                    Missing values will be replaced with default values specified in the schema.
	 * @since 0.0.10
	 * @return array<array<mixed>> An associative array containing:
	 *                - 'data': Prepared data with values encoded according to their data types.
	 *                - 'format': An array of format specifiers corresponding to the data values.
	 */
	protected function prepare_data( $data ) {
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
			// Process defaults.
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			// Lets decode from JSON to Array for the results.
			$_data[ $key ] = 'array' === $schema['type'] ? json_decode( Helper::get_string_value( $data[ $key ] ), true ) : $data[ $key ];
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
				return wp_json_encode( Helper::get_array_value( $value ) );
		}
	}
}
