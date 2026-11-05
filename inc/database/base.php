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
	 * Allowed operators for the database.
	 *
	 * @var array<string>
	 * @since 1.8.0
	 */
	private $allowed_where_operators = [ 'LIKE', 'IN', 'NOT IN', '=', '!=', '>', '<', '>=', '<=' ];

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
	 * Whether this table currently exists in the database.
	 *
	 * Deliberately `SHOW TABLES LIKE` rather than the existing get_columns():
	 * `SHOW COLUMNS FROM <missing table>` is a MySQL error, so it pollutes
	 * $wpdb->last_error, prints under WP_DEBUG_DISPLAY, and cannot tell "the table
	 * is gone" apart from "SHOW is denied". This returns a clean empty set instead.
	 *
	 * esc_like() matters because $wpdb->prefix contains `_`, which is a LIKE
	 * wildcard — without it `wp_srfm_entries` would also match `wpXsrfm_entries`.
	 * The comparison is against the real, unescaped name so the match stays exact.
	 *
	 * Fails safe: any DB-level error reports the table as present. A false "your
	 * database needs updating" on a transient connection blip is worse than a
	 * missed one, because the notice it drives asks the user to alter their schema.
	 *
	 * @since 2.12.6
	 * @return bool True when the table exists, or when existence cannot be determined.
	 */
	public function table_exists() {
		$wpdb  = $this->wpdb;
		$table = $this->get_tablename();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup; the caller owns caching, and a cached answer here would defeat the check.
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		if ( ! empty( $wpdb->last_error ) ) {
			return true;
		}

		return $found === $table;
	}

	/**
	 * A table holding this table's data under a different prefix, if there is one.
	 *
	 * Changing `$table_prefix` — a manual edit, a restored dump from a site with a
	 * different prefix, or a security plugin that renames tables and misses the ones
	 * it does not know about — leaves our data behind under the old name while the
	 * plugin looks for the new one. Creating a fresh empty table there would strand
	 * every stored entry, so look for the old one first and adopt it instead.
	 *
	 * Refuses to guess. Returns '' unless exactly one credible candidate exists, and
	 * only when that candidate carries every column this table's schema declares —
	 * an unrelated table that merely ends in the same words is never touched.
	 *
	 * On multisite, other blogs' tables are legitimate and belong to those blogs.
	 * Anything matching the `{base_prefix}{digits}_` pattern, or the base prefix
	 * itself, is excluded so a subsite can never adopt another subsite's data.
	 *
	 * @since 2.12.6
	 * @return string Full table name to adopt, or '' when there is nothing safe to adopt.
	 */
	public function find_adoptable_table() {
		$wpdb    = $this->wpdb;
		$correct = $this->get_tablename();
		$needle  = 'srfm_' . $this->table_suffix;

		// Wildcard on the left only: the name must *end* at the suffix, so a
		// deliberate copy such as `wp_srfm_entries_backup` is never a candidate.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup; a cached answer would defeat the check.
		$found = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $wpdb->esc_like( $needle ) ) );

		if ( ! empty( $wpdb->last_error ) || ! is_array( $found ) ) {
			return '';
		}

		$base       = $wpdb->base_prefix;
		$blog_table = '/^' . preg_quote( $base, '/' ) . '\d+_' . preg_quote( $needle, '/' ) . '$/';
		$candidates = [];

		foreach ( $found as $table ) {
			$table = (string) $table;

			// The table we are looking for, another blog's table, or the network's
			// main-site table — none of these are ours to rename.
			if ( $table === $correct || $base . $needle === $table || preg_match( $blog_table, $table ) ) {
				continue;
			}

			$candidates[] = $table;
		}

		// More than one and we cannot tell which holds the real data. Refuse rather
		// than pick, and let the caller fall back to creating an empty table.
		if ( 1 !== count( $candidates ) ) {
			return '';
		}

		return $this->has_expected_columns( $candidates[0] ) ? $candidates[0] : '';
	}

	/**
	 * Rename a differently-prefixed table into this table's expected name.
	 *
	 * RENAME rather than create-and-copy: it is atomic, needs no second copy of the
	 * data, and cannot half-succeed and leave rows in two places.
	 *
	 * @param string $from Full name of the table to adopt.
	 * @since 2.12.6
	 * @return bool True when the table is in place afterwards.
	 */
	public function adopt_table( $from ) {
		$wpdb = $this->wpdb;
		$to   = $this->get_tablename();

		if ( empty( $from ) || $from === $to ) {
			return false;
		}

		// Never rename over an existing table; the one already in place wins.
		if ( $this->table_exists() ) {
			return true;
		}

		$query = $wpdb->prepare( 'RENAME TABLE %1s TO %2s', str_replace( '`', '', $from ), str_replace( '`', '', $to ) ); // phpcs:ignore -- Same complex-placeholder pattern as create(): identifiers must not be quoted, and both names come from SHOW TABLES / $wpdb->prefix.

		if ( ! $query ) {
			// prepare() returned nothing usable; do not fall through to a raw query.
			return false;
		}

		$wpdb->query( $query ); // phpcs:ignore -- We are already using prepare above, and one-off DDL has nothing to cache.

		if ( ! empty( $wpdb->last_error ) ) {
			/** This action is documented in inc/database/base.php */
			do_action( 'srfm_db_upgrade_query_failed', $wpdb->last_error, 'RENAME TABLE', $to );
		}

		return $this->table_exists();
	}

	/**
	 * Stamp this site's owner signature onto a table's MySQL comment.
	 *
	 * Best-effort: a host that refuses ALTER simply leaves the table unstamped,
	 * which later reads as "ownership unproven" — the safe direction.
	 *
	 * @param string $table Full table name; defaults to this table's own name.
	 * @since 2.12.6
	 * @return void
	 */
	public function stamp_owner_signature( $table = '' ) {
		$wpdb  = $this->wpdb;
		$table = '' === $table ? $this->get_tablename() : $table;

		$query = $wpdb->prepare( 'ALTER TABLE %1s COMMENT = %s', str_replace( '`', '', $table ), $this->get_owner_signature() ); // phpcs:ignore -- Identifier must not be quoted; the comment value is a bound, quoted string.

		if ( ! $query ) {
			return;
		}

		$wpdb->query( $query ); // phpcs:ignore -- Prepared above; one-off DDL with nothing to cache.
	}

	/**
	 * Whether a table carries this site's owner signature.
	 *
	 * Gates adoption: on shared hosting a different install's identically-named,
	 * same-schema table can be the only candidate, and renaming it in would destroy
	 * that site's data. Deny by default — anything but an exact signature match
	 * (including a read error, an empty comment, or a legacy table stamped before
	 * this plugin wrote signatures) returns false.
	 *
	 * @param string $table Full table name to inspect.
	 * @since 2.12.6
	 * @return bool
	 */
	public function table_belongs_to_site( $table ) {
		$wpdb = $this->wpdb;
		$bare = str_replace( '`', '', (string) $table );

		if ( '' === $bare ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup; a cached answer would defeat the check.
		$comment = $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $bare ) );

		if ( ! empty( $wpdb->last_error ) || ! is_string( $comment ) || '' === $comment ) {
			return false;
		}

		return hash_equals( $this->get_owner_signature(), $comment );
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

			/**
			 * Fires when a table could not be created.
			 *
			 * Column changes have announced their failures since 2.11.0 but table
			 * creation never did — so the one failure that leaves a site with no
			 * table at all, a host denying CREATE TABLE, was the only silent one.
			 * Same signature as the ALTER case so one listener can handle both.
			 *
			 * @param string $last_error The database error.
			 * @param string $query      The query that failed.
			 * @param string $table_name The table it was for.
			 * @since 2.12.6
			 */
			do_action( 'srfm_db_upgrade_query_failed', $wpdb->last_error, $query, $this->get_tablename() );
		}

		if ( false !== $result ) {
			// Stamp our own table so a future adoption can prove it belongs to this
			// site before renaming it in. See stamp_owner_signature().
			$this->stamp_owner_signature();
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
				// Stop DB alteration if we have any error. A failed ALTER leaves the table
				// version un-bumped, so it retries on every request — expose the underlying
				// error so persistent failures are diagnosable (hook for logging/monitoring).
				$this->db_upgradable = false;

				/**
				 * Fires when a SureForms DB schema-upgrade query fails.
				 *
				 * @since 2.11.0
				 * @param string $last_error The DB error message ( $wpdb->last_error ).
				 * @param string $query      The ALTER query that failed.
				 * @param string $table      The table being altered.
				 */
				do_action( 'srfm_db_upgrade_query_failed', $this->wpdb->last_error, $query, $this->get_tablename() );
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

		// Reset cache so subsequent queries in the same request include the new row.
		$this->cache_reset();

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
		$result = $this->wpdb->delete( $this->get_tablename(), $where, $where_format );

		// Reset cache so subsequent queries in the same request exclude the deleted row.
		$this->cache_reset();

		return $result;
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
		if ( null !== $cached_results ) {
			// Return the cached data if exists. Tested against null rather than
			// truthiness: an empty result set is a real answer, and re-running the
			// query for it means every no-match lookup runs once per caller.
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
	 * @since 1.13.0
	 * @return array<mixed> The results of the query, typically an array of objects or associative arrays.
	 */
	public function get_records_by_args( $args = [], $set_limit = true ) {
		$_args           = wp_parse_args(
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
		$allowed_orderby = $this->get_allowed_orderby_columns();
		$orderby         = in_array( $_args['orderby'], $allowed_orderby, true ) ? $_args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( Helper::get_string_value( $_args['order'] ) ) ? 'ASC' : 'DESC';
		$extra_queries   = [
			sprintf( 'ORDER BY `%1$s` %2$s', $orderby, $order ),
		];

		if ( $set_limit ) {
			$extra_queries[] = sprintf( 'LIMIT %1$d, %2$d', absint( $_args['offset'] ), absint( $_args['limit'] ) );
		}
		return $this->get_results(
			$_args['where'],
			$_args['columns'],
			$extra_queries
		);
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
		if ( null !== $cached_results ) {
			// Return the cached data if exists. Tested against null rather than
			// truthiness: a count of zero is a real answer, and the editor exclusion
			// makes zero the common case rather than the exception.
			return Helper::get_integer_value( $cached_results );
		}

		// phpcs:ignore
		$results = Helper::get_integer_value( $wpdb->get_var( $query ) );

		// Execute the query and return the integer count.
		return Helper::get_integer_value( $this->cache_set( $query, $results ) );
	}

	/**
	 * The signature this plugin stamps on tables it owns on this site.
	 *
	 * A random per-site token, generated once and stored in options. Embedded in
	 * the table's MySQL comment at creation time; the comment survives RENAME, so a
	 * table that moved under a different prefix still carries it, while an unrelated
	 * install sharing the same database carries a different one.
	 *
	 * @since 2.12.6
	 * @return string
	 */
	protected function get_owner_signature() {
		$token = get_option( 'srfm_db_owner_token' );

		if ( ! is_string( $token ) || '' === $token ) {
			$token = wp_generate_password( 20, false );
			update_option( 'srfm_db_owner_token', $token, false );
		}

		return 'srfm-owner:' . $token;
	}

	/**
	 * Whether a table carries every column this table's schema declares.
	 *
	 * Guards adoption: a same-named table from an unrelated source should never be
	 * renamed into place just because its name matches.
	 *
	 * @param string $table Full table name to inspect.
	 * @since 2.12.6
	 * @return bool
	 */
	protected function has_expected_columns( $table ) {
		$wpdb = $this->wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup; a cached answer would defeat the check.
		$columns = $wpdb->get_col( $wpdb->prepare( 'SHOW COLUMNS FROM %1s', str_replace( '`', '', $table ) ) ); // phpcs:ignore -- Same complex-placeholder pattern as create(): an identifier must not be quoted, and the name comes from SHOW TABLES on this connection.

		if ( ! empty( $wpdb->last_error ) || ! is_array( $columns ) ) {
			return false;
		}

		foreach ( array_keys( $this->get_schema() ) as $column ) {
			if ( ! in_array( $column, $columns, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the allowed column names for ORDER BY clauses.
	 * Child classes may override this method to restrict orderable columns further.
	 *
	 * @since 2.6.0
	 * @return array<string>
	 */
	protected function get_allowed_orderby_columns() {
		return array_merge( array_keys( $this->get_schema() ), [ 'updated_at' ] );
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
	 * @since 2.12.7 -- Added support for "NOT IN" compare.
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
			$groups = [];
			$values = [];
			$schema = $this->get_schema();

			foreach ( $where_clauses as $key => $value ) {

				$relation = ! empty( $value['RELATION'] ) ? trim( $value['RELATION'] ) : 'AND';
				$relation = in_array( strtoupper( $relation ), [ 'AND', 'OR' ], true ) ? strtoupper( $relation ) : 'AND';

				if ( is_int( $key ) ) {
					$clause_parts = [];
					foreach ( $value as $_key => $_value ) {
						if ( is_int( $_key ) ) {
							// Normalised before the allowlist test. Payments'
							// builder upper-cases and trims, this one compared
							// strictly -- so a caller writing 'not in' was honoured
							// by one and silently dropped by the other. A dropped
							// condition used to be harmless; now that NOT IN is the
							// exclusion primitive, dropping it disables the
							// exclusion without a word.
							$compare = strtoupper( trim( Helper::get_string_value( $_value['compare'] ) ) );

							// Check if the operator is allowed.
							if ( ! in_array( $compare, $this->allowed_where_operators, true ) ) {
								continue;
							}

							// Skip if key is not in schema.
							if ( ! isset( $schema[ $_value['key'] ] ) ) {
								continue;
							}

							switch ( $compare ) {
								case 'LIKE':
									// Single quotes to match WP core. Under a MySQL session with
									// ANSI_QUOTES set (not in WP's incompatible_modes list, which
									// only names the compound ANSI mode) a double-quoted pattern
									// parses as an identifier and the query hard-fails, taking out
									// both the listing and its COUNT(*).
									$clause_parts[] = $_value['key'] . ' ' . $compare . " '%%" . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) ) . "%%'";
									$values[]       = $_value['value'];
									break;

								case 'IN':
								case 'NOT IN':
									// A scalar is a caller bug, not an empty set, and it must
									// surface. 'NOT IN' with value 5 -- a plausible typo for
									// [ 5 ] -- would otherwise drop the condition and exclude
									// nobody, with no error and a green test suite, while the
									// same typo on 'IN' fails closed. On a primitive whose only
									// job is scoping data, that asymmetry is a hazard.
									if ( ! is_array( $_value['value'] ) ) {
										_doing_it_wrong(
											__METHOD__,
											esc_html( "{$compare} requires an array value, received " . gettype( $_value['value'] ) . '.' ),
											'2.12.7'
										);
										break;
									}

									// An empty list cannot be interpolated: "col IN ()" is a syntax
									// error that fails the whole query, listing and COUNT alike.
									// An empty IN matches nothing, so '1 = 0' says that in any
									// relation. An empty NOT IN excludes nothing, but a literal
									// would be '1 = 1', and that makes an enclosing OR group
									// unconditionally true. Dropping the condition means the same
									// thing under AND and stays fail-closed under OR.
									if ( [] === $_value['value'] ) {
										if ( 'IN' === $compare ) {
											$clause_parts[] = '1 = 0';
										}
										break;
									}

									// Based on the number of values and datatype, it will create WHERE clause for $wpdb::prepare method. Eg: for ID with three values column: ID IN (%d, %d, %d).
									$datatype       = $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) );
									$clause_parts[] = $_value['key'] . ' ' . $compare . ' (' . implode( ', ', array_fill( 0, count( $_value['value'] ), $datatype ) ) . ')';
									$values         = array_merge( $values, $_value['value'] );
									break;

								default:
									$clause_parts[] = $_value['key'] . ' ' . $compare . ' ' . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $_value['key'] ]['type'] ) );
									$values[]       = $_value['value'];
									break;
							}
						}
					}

					if ( ! empty( $clause_parts ) ) {
						$groups[] = '(' . implode( ' ' . $relation . ' ', $clause_parts ) . ')';
					}
					continue;
				}

				if ( ! isset( $schema[ $key ] ) ) {
					// Skip strictly if current key is not in our schema.
					continue;
				}

				$groups[] = '(' . $key . ' = ' . $this->get_format_by_datatype( Helper::get_string_value( $schema[ $key ]['type'] ) ) . ')';
				$values[] = $value;
			}

			if ( empty( $groups ) ) {
				return '';
			}

			$where = ' WHERE ' . implode( ' AND ', $groups );

			if ( [] === $values ) {
				// Every branch that builds a placeholder also pushes a value, so an
				// empty list here means the only conditions were constant ones. There
				// is nothing for prepare() to fill, and calling it with no placeholder
				// trips _doing_it_wrong.
				return $where;
			}

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

			if ( 'array' === $value['type'] ) {
				$_data[ $key ] = $this->fit_json_to_column( $_data[ $key ], $key );
			}
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
	 * @since 1.8.0  - 'datetime': Returns the value as it is, assuming it is already in SQL DATETIME format.
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

			case 'datetime':
				// For datetime, we will return the value as it is because we are using sql DATETIME format.
				return $value;
		}
	}

	/**
	 * Keep characters outside the Basic Multilingual Plane, such as emoji, in a
	 * JSON column that cannot store them.
	 *
	 * A `utf8` (utf8mb3) column holds at most three bytes per character, and wpdb
	 * refuses a whole insert or update that carries a four-byte one. Such a
	 * character is written as its JSON escape (`😀`), which is ASCII and
	 * which json_decode() turns back into the same character, so the value reads
	 * back unchanged. A utf8mb4 column, and any value without such a character, is
	 * stored as it is.
	 *
	 * @param mixed  $json   JSON from encode_by_datatype().
	 * @param string $column Column name.
	 * @since 2.13.1
	 * @return mixed
	 */
	private function fit_json_to_column( $json, $column ) {
		if ( ! is_string( $json ) || 1 !== preg_match( '/[\x{10000}-\x{10FFFF}]/u', $json ) ) {
			return $json;
		}

		$charset = $this->wpdb->get_col_charset( $this->get_tablename(), $column );
		if ( ! is_string( $charset ) || 'utf8mb4' === $charset ) {
			return $json;
		}

		$escaped = preg_replace_callback(
			'/[\x{10000}-\x{10FFFF}]/u',
			static function ( $character ) {
				// Without JSON_UNESCAPED_UNICODE, the character comes back as its surrogate-pair escape.
				return substr( (string) wp_json_encode( $character[0] ), 1, -1 );
			},
			$json
		);

		return is_string( $escaped ) ? $escaped : $json;
	}
}
