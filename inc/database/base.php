<?php
/**
 * SureForms Database Base Class.
 *
 * @link       https://sureforms.com
 * @since      x.x.x
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Base {

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
	 * Returns full table name.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_tablename() {
		return $this->table_name;
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
	public function create( $columns = array() ) {
		if ( empty( $columns ) ) {
			return;
		}

		$_columns = implode( ',', $columns );

		$queries = [
			'CREATE TABLE IF NOT EXISTS',
			$this->get_tablename(),
			"( $_columns )",
			$this->get_charset_collate(),
		];

		return $this->wpdb->query( implode( ' ', $queries ) );
	}

	/**
	 * Drop or delete current table.
	 *
	 * @since x.x.x
	 * @return int|bool
	 */
	public function drop() {
		$queries = [
			'DROP TABLE IF EXISTS',
			$this->get_tablename(),
		];
		return $this->wpdb->query( implode( ' ', $queries ) );
	}

	/**
	 * Check if current table exists.
	 *
	 * @since x.x.x
	 * @return boolean
	 */
	public function exists() {
		$query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->wpdb->esc_like( $this->get_tablename() ) );
		if ( $this->wpdb->get_var( $query ) == $this->get_tablename() ) {
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
		return $this->wpdb->insert( $this->get_tablename(), $data, $format );
	}
}
