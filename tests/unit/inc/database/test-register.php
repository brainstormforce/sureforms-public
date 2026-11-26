<?php
/**
 * Class Test_Database_Register
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Database\Register;
use SRFM\Inc\Database\Tables\Entries;

/**
 * Tests for missing-entries-table detection and repair:
 *   - Register::is_entries_table_missing()
 *   - Register::repair_entries_table()
 *   - Register::get_adoptable_entries_table()
 *
 * These exercise real DDL against the test database. Every test restores the
 * entries table and removes any scratch table it created, because this suite
 * runs on a plain PHPUnit TestCase with no transaction to roll back.
 */
class Test_Database_Register extends TestCase {

	/**
	 * Scratch tables created by the running test, dropped in tearDown().
	 *
	 * @var array<string>
	 */
	private $scratch_tables = [];

	/**
	 * The real entries table name for this install.
	 *
	 * @var string
	 */
	private $entries_table;

	protected function setUp(): void {
		parent::setUp();

		$this->entries_table = Entries::get_instance()->get_tablename();

		$this->reset_detection_cache();
		$this->ensure_entries_table();
	}

	protected function tearDown(): void {
		global $wpdb;

		foreach ( $this->scratch_tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore -- Test teardown on a scratch table this test created.
		}

		$this->scratch_tables = [];

		$this->reset_detection_cache();
		$this->ensure_entries_table();

		parent::tearDown();
	}

	// ---------------------------------------------------------------
	// Detection
	// ---------------------------------------------------------------

	/**
	 * A healthy install must never raise the notice.
	 */
	public function test_is_entries_table_missing() {
		$this->assertFalse( Register::is_entries_table_missing( true ) );
	}

	/**
	 * The per-request health memo belongs to the site that computed it. After a
	 * switch to a site whose entries table is absent, the first site's healthy
	 * answer must not be reused.
	 */
	public function test_is_entries_table_missing_is_memoised_per_site() {
		global $wpdb, $blog_id;

		$this->record_entries_version( 2 );
		$this->assertFalse( Register::is_entries_table_missing(), 'Site 1 has its table.' );

		$original_prefix = $wpdb->prefix;
		$original_blog   = $blog_id;

		try {
			// What switch_to_blog() changes: the current blog id and $wpdb->prefix.
			// The scratch prefix has no srfm tables, so the entries table is missing there.
			$blog_id      = 9; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulating switch_to_blog(); restored in finally.
			$wpdb->prefix = $original_prefix . '9_';
			delete_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT );

			$this->assertTrue( Register::is_entries_table_missing(), 'Site 9 must not reuse site 1\'s memo.' );
		} finally {
			$blog_id      = $original_blog; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the simulated switch.
			$wpdb->prefix = $original_prefix;
		}

		$this->assertFalse( Register::is_entries_table_missing(), 'Site 1 keeps its own answer after restore.' );
	}

	/**
	 * The bug this feature exists for: the version stays recorded, so nothing
	 * recreates the table and nothing notices it is gone.
	 */
	public function test_detects_a_dropped_table_while_the_version_is_still_recorded() {
		$this->record_entries_version( 2 );
		$this->drop_entries_table();

		$this->assertTrue( Register::is_entries_table_missing( true ) );
	}

	/**
	 * A fresh install has no recorded version and init() is creating the table on
	 * this very request. Warning there would be a false alarm.
	 */
	public function test_stays_quiet_when_no_version_is_recorded() {
		$versions = (array) get_option( 'srfm_database_table_versions', [] );
		unset( $versions['entries'] );
		update_option( 'srfm_database_table_versions', $versions );

		$this->drop_entries_table();

		$this->assertFalse( Register::is_entries_table_missing( true ) );
	}

	/**
	 * Caching "missing" would keep the notice up for a day after a successful
	 * repair. Only the healthy answer may be cached.
	 */
	public function test_never_caches_the_missing_answer() {
		$this->record_entries_version( 2 );
		$this->drop_entries_table();

		Register::is_entries_table_missing( true );

		$this->assertFalse( get_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT ) );
	}

	/**
	 * The healthy answer is cached, so three consumers in one page load cost one
	 * query rather than three.
	 */
	public function test_caches_the_healthy_answer() {
		delete_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT );

		Register::is_entries_table_missing( true );

		$this->assertNotFalse( get_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT ) );
	}

	// ---------------------------------------------------------------
	// Repair — recreate
	// ---------------------------------------------------------------

	/**
	 * With nothing to adopt, repair creates the table from the schema.
	 */
	public function test_repair_entries_table() {
		$this->record_entries_version( 2 );
		$this->drop_entries_table();

		$this->assertTrue( Register::repair_entries_table() );
		$this->assertTrue( $this->table_is_present( $this->entries_table ) );
	}

	/**
	 * Repair must report the state of the database, not the return value of the
	 * CREATE. A repair that recorded the version while the table stayed missing
	 * would hide the problem permanently.
	 */
	public function test_repair_reports_the_actual_table_state() {
		$this->record_entries_version( 2 );
		$this->drop_entries_table();

		Register::repair_entries_table();

		$this->assertSame(
			$this->table_is_present( $this->entries_table ),
			! Register::is_entries_table_missing( true )
		);
	}

	// ---------------------------------------------------------------
	// Repair — adopt a differently-prefixed table
	// ---------------------------------------------------------------

	/**
	 * The prefix-mismatch case. Creating an empty table here would strand every
	 * stored entry behind a successful-looking repair, so the rows must survive.
	 */
	public function test_repair_adopts_a_differently_prefixed_table_and_keeps_the_rows() {
		global $wpdb;

		$this->record_entries_version( 2 );

		$orphan = $this->clone_entries_table( 'srfmtest1_srfm_entries' );
		// The orphan carries this site's owner signature, as a table this plugin
		// created under the old prefix would. CREATE TABLE ... LIKE does not copy the
		// comment, so stamp it explicitly.
		Entries::get_instance()->stamp_owner_signature( $orphan );
		$this->seed_row( $orphan, 'adopt-me' );
		$this->drop_entries_table();

		$this->assertTrue( Register::repair_entries_table() );

		// The rows moved, rather than a fresh empty table appearing beside them.
		$this->assertSame(
			'adopt-me',
			$wpdb->get_var( "SELECT notes FROM `{$this->entries_table}` WHERE notes = 'adopt-me'" ) // phpcs:ignore -- Reading back a row this test seeded.
		);
		$this->assertFalse( $this->table_is_present( $orphan ) );
	}

	/**
	 * Shared-DB safety: a same-schema table with no proof it belongs to this site
	 * (as an unrelated install's table would be) must never be renamed in. It is
	 * left untouched, recorded for manual recovery, and a fresh table is created.
	 */
	public function test_leaves_an_unverified_table_untouched_and_records_it() {
		global $wpdb;

		$this->record_entries_version( 2 );

		$orphan = $this->clone_entries_table( 'srfmtest1_srfm_entries' );
		// Guarantee no owner signature, whatever LIKE may or may not copy.
		$wpdb->query( "ALTER TABLE `{$orphan}` COMMENT = ''" ); // phpcs:ignore -- Scratch table for this test.
		$this->seed_row( $orphan, 'not-ours' );
		$this->drop_entries_table();

		$this->assertTrue( Register::repair_entries_table() );

		// The unverified table survives, and a fresh empty table is created beside it.
		$this->assertTrue( $this->table_is_present( $orphan ), 'An unverified table must not be renamed away.' );
		$this->assertTrue( $this->table_is_present( $this->entries_table ), 'A fresh table is created instead.' );
		$this->assertSame(
			'not-ours',
			$wpdb->get_var( "SELECT notes FROM `{$orphan}` WHERE notes = 'not-ours'" ) // phpcs:ignore -- Reading back a row this test seeded.
		);

		$recorded = get_option( 'srfm_entries_unrecovered' );
		$this->assertSame( $orphan, $recorded['table'] ?? '' );
		$this->assertSame( 'unverified', $recorded['reason'] ?? '' );

		delete_option( 'srfm_entries_unrecovered' );
	}

	/**
	 * A candidate that is ours but cannot be renamed in (e.g. the DB user lacks the
	 * privilege RENAME needs) must not fall through to a fresh empty table and
	 * report success — that would strand recoverable data behind a green result.
	 * It fails, and records the table for manual recovery.
	 */
	public function test_records_and_fails_when_a_verified_table_cannot_be_adopted() {
		$this->record_entries_version( 2 );
		$this->drop_entries_table();

		// Stand-in whose table verifies as ours but whose RENAME cannot complete.
		$double = new class() extends Entries {
			public function find_adoptable_table() {
				return $this->wpdb->prefix . 'srfmtest1_srfm_entries';
			}
			public function table_belongs_to_site( $table ) {
				return true;
			}
			public function adopt_table( $from ) {
				return false;
			}
		};

		$ref = new ReflectionProperty( Entries::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, $double );

		$fired = [];
		add_action(
			'srfm_entries_table_unrecovered',
			static function ( $table, $reason ) use ( &$fired ) {
				$fired = [ 'table' => $table, 'reason' => $reason ];
			},
			10,
			2
		);

		$result = Register::repair_entries_table();

		Entries::reset_instance();
		remove_all_actions( 'srfm_entries_table_unrecovered' );

		$this->assertFalse( $result, 'A found-but-unadoptable table must not report success.' );
		$this->assertFalse( $this->table_is_present( $this->entries_table ), 'No empty table may be manufactured over recoverable data.' );
		$this->assertSame( 'adopt_failed', $fired['reason'] ?? '' );

		$recorded = get_option( 'srfm_entries_unrecovered' );
		$this->assertSame( 'adopt_failed', $recorded['reason'] ?? '' );

		delete_option( 'srfm_entries_unrecovered' );
	}

	/**
	 * A failed entries write invalidates a stale "present" cache, so a table dropped
	 * after being cached healthy is noticed on the next check rather than up to a
	 * day later.
	 */
	/**
	 * The cache flush removes both the transient and the per-request memo.
	 */
	public function test_flush_entries_table_cache() {
		set_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );

		Register::flush_entries_table_cache();

		$this->assertFalse( get_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT ) );
	}

	public function test_failed_entry_write_flushes_the_present_cache() {
		global $wpdb;

		$this->record_entries_version( 2 );

		// Prime the healthy cache, then drop the table without clearing that cache.
		Register::is_entries_table_missing( true );
		set_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );
		$wpdb->query( "DROP TABLE IF EXISTS `{$this->entries_table}`" ); // phpcs:ignore -- Reproducing a live drop after the cache was primed.

		// A submission tries to save and fails against the missing table.
		Entries::add( [ 'form_id' => 123 ] );

		$this->assertFalse(
			get_transient( Register::ENTRIES_TABLE_CHECK_TRANSIENT ),
			'A failed entries write must invalidate the stale present cache.'
		);
	}

	/**
	 * Two candidates means we cannot tell which holds the real data. Guessing
	 * could rename the wrong one over the right one.
	 */
	public function test_refuses_to_adopt_when_two_candidates_exist() {
		$this->record_entries_version( 2 );

		$this->clone_entries_table( 'srfmtest1_srfm_entries' );
		$this->clone_entries_table( 'srfmtest2_srfm_entries' );
		$this->drop_entries_table();

		$this->assertSame( '', Entries::get_instance()->find_adoptable_table() );
	}

	/**
	 * A name match is not a data match. An unrelated table must never be renamed
	 * into place just because it ends in the right words.
	 */
	public function test_refuses_to_adopt_a_table_with_the_wrong_columns() {
		global $wpdb;

		$this->record_entries_version( 2 );

		$wrong = $wpdb->prefix . 'srfmtest_srfm_entries';
		$wpdb->query( "CREATE TABLE `{$wrong}` ( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY )" ); // phpcs:ignore -- Scratch table for this test.
		$this->scratch_tables[] = $wrong;

		$this->drop_entries_table();

		$this->assertSame( '', Entries::get_instance()->find_adoptable_table() );
	}

	/**
	 * A backup taken with a suffix is somebody's deliberate copy, not the live
	 * table. Adopting it would move their backup out from under them.
	 */
	public function test_ignores_a_suffixed_backup_table() {
		$this->record_entries_version( 2 );

		$this->clone_entries_table( 'srfm_entries_backup' );
		$this->drop_entries_table();

		$this->assertSame( '', Entries::get_instance()->find_adoptable_table() );
	}

	/**
	 * On multisite, `{base_prefix}{digits}_` tables belong to other blogs. A
	 * subsite adopting one would take another subsite's entries.
	 */
	public function test_never_adopts_another_blogs_table() {
		global $wpdb;

		$this->record_entries_version( 2 );

		$this->clone_entries_table( '', $wpdb->base_prefix . '7_srfm_entries' );
		$this->drop_entries_table();

		$this->assertSame( '', Entries::get_instance()->find_adoptable_table() );
	}

	/**
	 * The notice copy branches on this, so it must stay empty on a healthy site
	 * rather than reporting whatever happens to be lying around.
	 */
	public function test_get_adoptable_entries_table() {
		$this->clone_entries_table( 'srfmtest1_srfm_entries' );

		$this->assertSame( '', Register::get_adoptable_entries_table() );
	}

	/**
	 * The notice must not promise to keep entries from a table it will not adopt.
	 * An unverified candidate reports empty, so the copy falls back to the
	 * create-empty wording.
	 */
	public function test_get_adoptable_entries_table_ignores_an_unverified_table() {
		global $wpdb;

		$this->record_entries_version( 2 );

		$orphan = $this->clone_entries_table( 'srfmtest1_srfm_entries' );
		$wpdb->query( "ALTER TABLE `{$orphan}` COMMENT = ''" ); // phpcs:ignore -- Scratch table for this test.
		$this->drop_entries_table();

		$this->assertSame( '', Register::get_adoptable_entries_table() );
	}

	/**
	 * A candidate proven to belong to this site is reported, so the notice can
	 * promise recovery.
	 */
	public function test_get_adoptable_entries_table_reports_a_verified_table() {
		$this->record_entries_version( 2 );

		$orphan = $this->clone_entries_table( 'srfmtest1_srfm_entries' );
		Entries::get_instance()->stamp_owner_signature( $orphan );
		$this->drop_entries_table();

		$this->assertSame( $orphan, Register::get_adoptable_entries_table() );
	}

	/**
	 * The table map is what init() and the repair both iterate, so a table dropping
	 * out of it would silently stop being created or repaired at all.
	 */
	public function test_get_db_tables() {
		$tables = Register::get_db_tables();

		$this->assertArrayHasKey( 'entries', $tables );
		$this->assertArrayHasKey( 'payments', $tables );

		foreach ( $tables as $table ) {
			$this->assertInstanceOf( \SRFM\Inc\Database\Base::class, $table );
		}
	}

	// ---------------------------------------------------------------
	// adopt_table — guards
	// ---------------------------------------------------------------

	/**
	 * Nothing to adopt is not a repair. Returning true here would let the caller
	 * report success while the table stayed missing.
	 */
	public function test_adopt_table_refuses_an_empty_name() {
		$this->assertFalse( Entries::get_instance()->adopt_table( '' ) );
	}

	/**
	 * Renaming a table onto itself is a MySQL error, not a no-op.
	 */
	public function test_adopt_table_refuses_to_rename_a_table_onto_itself() {
		$this->assertFalse( Entries::get_instance()->adopt_table( $this->entries_table ) );
	}

	/**
	 * The live table always wins. Renaming over it would destroy the rows the site
	 * is actually using in order to install an older copy.
	 */
	public function test_adopt_table_never_renames_over_the_live_table() {
		global $wpdb;

		$orphan = $this->clone_entries_table( 'srfmtest1_srfm_entries' );
		$this->seed_row( $orphan, 'should-not-win' );

		$this->assertTrue( Entries::get_instance()->adopt_table( $orphan ) );

		// The live table is untouched and the candidate is still sitting there.
		$this->assertTrue( $this->table_is_present( $orphan ) );
		$this->assertNull(
			$wpdb->get_var( "SELECT notes FROM `{$this->entries_table}` WHERE notes = 'should-not-win'" ) // phpcs:ignore -- Confirming the seeded row did not move.
		);
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * Clear both the static memo and the transient.
	 */
	private function reset_detection_cache() {
		Register::flush_entries_table_cache();
	}

	/**
	 * Write a stored version for the entries table, reproducing the state that
	 * stops create() from ever running again.
	 *
	 * @param int $version Version to record.
	 */
	private function record_entries_version( $version ) {
		$versions            = (array) get_option( 'srfm_database_table_versions', [] );
		$versions['entries'] = $version;
		update_option( 'srfm_database_table_versions', $versions );
	}

	/**
	 * Drop the entries table and clear the caches that would hide it.
	 */
	private function drop_entries_table() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS `{$this->entries_table}`" ); // phpcs:ignore -- Reproducing the dropped-table state under test.
		$this->reset_detection_cache();
	}

	/**
	 * Put the entries table back, whatever the test did to it.
	 */
	private function ensure_entries_table() {
		if ( $this->table_is_present( $this->entries_table ) ) {
			return;
		}

		Register::repair_entries_table();
		$this->reset_detection_cache();
	}

	/**
	 * Copy the entries table's structure to another name.
	 *
	 * @param string $suffix Name appended to $wpdb->prefix. Ignored when $absolute is given.
	 * @param string $absolute Full table name, bypassing the prefix.
	 * @return string The table name created.
	 */
	private function clone_entries_table( $suffix, $absolute = '' ) {
		global $wpdb;

		$table = '' !== $absolute ? $absolute : $wpdb->prefix . $suffix;

		$wpdb->query( "CREATE TABLE `{$table}` LIKE `{$this->entries_table}`" ); // phpcs:ignore -- Scratch table for this test.
		$this->scratch_tables[] = $table;

		return $table;
	}

	/**
	 * Insert one identifiable row.
	 *
	 * @param string $table  Table to insert into.
	 * @param string $marker Value written to `notes` and read back after adoption.
	 */
	private function seed_row( $table, $marker ) {
		global $wpdb;

		$wpdb->insert( $table, [ 'notes' => $marker ] ); // phpcs:ignore -- Seeding a scratch table for this test.
	}

	/**
	 * Whether a table is in the database right now.
	 *
	 * @param string $table Full table name.
	 * @return bool
	 */
	private function table_is_present( $table ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table; // phpcs:ignore -- Test assertion helper.
	}
}
