<?php
/**
 * SureForms Database Tables Register Class.
 *
 * @link       https://sureforms.com
 * @since      0.0.10
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Helper;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Tables Register Class
 *
 * @since 0.0.13
 */
class Register {
	/**
	 * Transient caching a healthy entries-table check.
	 *
	 * Only the healthy answer is ever cached. A missing table is re-queried every
	 * time, so the warning disappears the moment the table is back rather than
	 * lingering for the rest of the TTL.
	 *
	 * @since 2.12.6
	 */
	public const ENTRIES_TABLE_CHECK_TRANSIENT = 'srfm_entries_table_present';

	/**
	 * Per-request memo for the entries-table check.
	 *
	 * The check is consulted from three places in one admin page load (notice
	 * registration, notice rendering, analytics), and this keeps that to a single
	 * transient read. Keyed by blog id: the answer belongs to the site that
	 * computed it and must not survive a switch_to_blog().
	 *
	 * @var array<int,bool>
	 * @since 2.12.6
	 * @since x.x.x Keyed by blog id.
	 */
	private static $entries_table_present = [];

	/**
	 * Init database registration.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public static function init() {
		/*
		 * ### Here, order is important. ###
		 * 1. Start the DB upgrade which also manages the internal versioning of each tables.
		 * 2. Init create method, which create the table if the table does not exists.
		 * 3. Init maybe_add_new_columns, it only runs if we have new columns definition and DB is upgradable ( has new version ).
		 * 4. Init maybe_rename_columns, it only runs if got any columns to rename and DB is upgradable ( has new version ).
		 * 5. Finally, stop the DB upgrade and update the current version in option table.
		 *
		 * Replaced self::get_db_tables() to static::get_db_tables() for allowing overrides.
		 * @since 1.13.0
		 */
		foreach ( static::get_db_tables() as $instance ) {
			$instance->start_db_upgrade();

			if ( $instance->is_db_upgradable() ) {
				// Only execute below methods if DB is upgradable.
				$instance->create( $instance->get_columns_definition() );
				$instance->maybe_add_new_columns( $instance->get_new_columns_definition() );
				$instance->maybe_rename_columns( $instance->get_columns_to_rename() );
			}

			// Stop the upgrade process of current table and move to next.
			$instance->stop_db_upgrade();
		}
	}

	/**
	 * Returns an array of instances/objects of our custom tables.
	 *
	 * @since 0.0.13
	 * @return array<string,\SRFM\Inc\Database\Base>
	 */
	public static function get_db_tables() {
		return [
			'entries'  => Entries::get_instance(),
			'payments' => Payments::get_instance(),
		];
	}

	/**
	 * Whether the entries table is missing from the database.
	 *
	 * A dropped table is never recreated on its own: `srfm_database_table_versions`
	 * still records the current version, so start_db_upgrade() marks the table
	 * non-upgradable and create() early-returns. Nothing else in the plugin notices,
	 * and every submission silently fails to save. This is what surfaces that.
	 *
	 * Returns false while the stored version is absent — Register::init() is going
	 * to create the table on this very request, so reporting it missing would show
	 * a "your database needs updating" notice during a perfectly normal install.
	 *
	 * @param bool $force Skip both caches and re-query.
	 * @since 2.12.6
	 * @return bool
	 */
	public static function is_entries_table_missing( $force = false ) {
		$blog = get_current_blog_id();

		if ( ! $force && isset( self::$entries_table_present[ $blog ] ) ) {
			return ! self::$entries_table_present[ $blog ];
		}

		if ( ! $force && get_transient( self::ENTRIES_TABLE_CHECK_TRANSIENT ) ) {
			self::$entries_table_present[ $blog ] = true;
			return false;
		}

		$versions = Helper::get_array_value( get_option( 'srfm_database_table_versions', [] ) );

		// No recorded version means this is a fresh install or an upgrade in
		// progress; init() creates the table this request. Nothing to warn about.
		if ( empty( $versions['entries'] ) ) {
			self::$entries_table_present[ $blog ] = true;
			return false;
		}

		$present = Entries::get_instance()->table_exists();

		// Cache only the healthy answer. See the transient's docblock.
		if ( $present ) {
			set_transient( self::ENTRIES_TABLE_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );
			// Backfill the owner signature once, so a table created before this plugin
			// wrote signatures can still be proven ours if the prefix later changes.
			if ( ! get_option( 'srfm_entries_owner_stamped' ) ) {
				Entries::get_instance()->stamp_owner_signature();
				update_option( 'srfm_entries_owner_stamped', 1, false );
			}
		} else {
			delete_transient( self::ENTRIES_TABLE_CHECK_TRANSIENT );
		}

		self::$entries_table_present[ $blog ] = $present;

		return ! $present;
	}

	/**
	 * Recreate the entries table when it is missing.
	 *
	 * Clearing the stored version is the whole repair: start_db_upgrade() then
	 * treats the table as new, and init() runs the same ordered steps it runs on a
	 * fresh install. Reusing init() rather than re-implementing those steps is
	 * deliberate — the repair path cannot drift from the install path.
	 *
	 * Only the `entries` key is removed. The option is shared with the payments
	 * table and, when Pro is active, three more; clearing the whole option would
	 * force an unrelated re-upgrade pass across all of them from free-plugin code.
	 *
	 * The result is a fresh existence check, never create()'s return value. A
	 * repair that reported success on the strength of create() alone could record
	 * the version again while the table was still missing — which would hide the
	 * problem permanently and be worse than doing nothing.
	 *
	 * @since 2.12.6
	 * @return bool True when the table exists afterwards.
	 */
	public static function repair_entries_table() {
		$entries = Entries::get_instance();

		// Prefer adopting the site's own data over manufacturing an empty table.
		// A changed table prefix leaves the rows behind under the old name, and
		// creating a fresh table would strand every stored entry while looking like
		// a successful repair.
		$adoptable = $entries->find_adoptable_table();

		if ( '' !== $adoptable ) {
			if ( $entries->table_belongs_to_site( $adoptable ) ) {
				// Provenance confirms this site owns the table: safe to rename it in.
				if ( $entries->adopt_table( $adoptable ) ) {
					self::run_entries_schema_catchup();
					delete_option( 'srfm_entries_unrecovered' );
					return ! self::is_entries_table_missing( true );
				}

				// The table is ours but the rename failed — e.g. the DB user lacks the
				// privilege RENAME needs, or a metadata-lock timeout. Manufacturing an
				// empty table over recoverable data and reporting success would be worse
				// than doing nothing, so refuse and leave a durable trail for support.
				self::record_unrecovered_entries_table( $adoptable, 'adopt_failed' );
				return false;
			}

			// A single same-schema candidate exists but its ownership cannot be
			// proven. On shared hosting it may belong to an unrelated install, and
			// renaming it in would destroy that site's data. Leave it untouched,
			// record it so it can be recovered by hand, then fall back to a fresh
			// table below.
			self::record_unrecovered_entries_table( $adoptable, 'unverified' );
		}

		$versions = Helper::get_array_value( get_option( 'srfm_database_table_versions', [] ) );

		unset( $versions['entries'] );
		update_option( 'srfm_database_table_versions', $versions );

		static::init();

		return ! self::is_entries_table_missing( true );
	}

	/**
	 * Drop the cached entries-table detection so the next check re-queries.
	 *
	 * Called when a live entries write fails: a table cached as present earlier in
	 * the day may have just been dropped, and trusting the stale healthy answer
	 * would hide the notice for up to the transient's lifetime.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public static function flush_entries_table_cache() {
		self::$entries_table_present = [];
		delete_transient( self::ENTRIES_TABLE_CHECK_TRANSIENT );
	}

	/**
	 * The entries table sitting under a different prefix, if there is one.
	 *
	 * Exposed so the notice can say whether the repair will bring existing entries
	 * back with it or start from empty — the difference matters a great deal to
	 * whoever is about to click the button.
	 *
	 * @since 2.12.6
	 * @return string Full table name, or '' when there is nothing to adopt.
	 */
	public static function get_adoptable_entries_table() {
		if ( ! self::is_entries_table_missing() ) {
			return '';
		}

		$entries   = Entries::get_instance();
		$candidate = $entries->find_adoptable_table();

		// Only report a candidate the repair would actually adopt — one we can prove
		// belongs to this site. An unverifiable table is left untouched and a fresh
		// table created instead, so the notice must not promise to keep its entries.
		if ( '' === $candidate || ! $entries->table_belongs_to_site( $candidate ) ) {
			return '';
		}

		return $candidate;
	}

	/**
	 * Re-run the install-time schema steps against a just-adopted table.
	 *
	 * An adopted table can carry an older revision of this plugin's schema (a
	 * superset of column names still passes the adoption guard), so reuse the same
	 * ordered create/add-column/rename pass the install path runs rather than
	 * assuming the adopted schema is already current. create() is CREATE TABLE IF
	 * NOT EXISTS, so it is a no-op on the table that is now in place.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	private static function run_entries_schema_catchup() {
		$versions = Helper::get_array_value( get_option( 'srfm_database_table_versions', [] ) );

		unset( $versions['entries'] );
		update_option( 'srfm_database_table_versions', $versions );

		static::init();
	}

	/**
	 * Record a table that holds recoverable entries but was not adopted.
	 *
	 * Fired when a candidate is found that either cannot be proven to belong to
	 * this site or could not be renamed in. Persists the name and reason so a
	 * support engineer can find and reconnect it by hand, rather than letting it
	 * be discarded silently the moment a fresh empty table exists.
	 *
	 * @param string $table  Full name of the table left in place.
	 * @param string $reason 'unverified' or 'adopt_failed'.
	 * @since 2.12.6
	 * @return void
	 */
	private static function record_unrecovered_entries_table( $table, $reason ) {
		update_option(
			'srfm_entries_unrecovered',
			[
				'table'  => $table,
				'reason' => $reason,
				'time'   => time(),
			],
			false
		);

		/**
		 * Fires when entries data was found but not automatically recovered.
		 *
		 * @param string $table  Full name of the table left in place.
		 * @param string $reason 'unverified' or 'adopt_failed'.
		 * @since 2.12.6
		 */
		do_action( 'srfm_entries_table_unrecovered', $table, $reason );
	}
}
