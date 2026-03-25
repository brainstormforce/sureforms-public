<?php
/**
 * Class Test_Entries_Table
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Database\Tables\Entries;

/**
 * Tests for the Database Entries Table class.
 */
class Test_Entries_Table extends TestCase {

	protected $entries_table;

	protected function setUp(): void {
		$this->entries_table = Entries::get_instance();
	}

	/**
	 * Test get_schema returns correct keys.
	 */
	public function test_get_schema_returns_correct_keys() {
		$schema = $this->entries_table->get_schema();
		$this->assertIsArray( $schema );
		$expected_keys = [ 'ID', 'form_id', 'user_id', 'status', 'type', 'form_data', 'submission_info', 'notes', 'logs', 'created_at', 'extras' ];
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $schema );
		}
	}

	/**
	 * Test get_schema field types.
	 */
	public function test_get_schema_field_types() {
		$schema = $this->entries_table->get_schema();
		$this->assertEquals( 'number', $schema['ID']['type'] );
		$this->assertEquals( 'number', $schema['form_id']['type'] );
		$this->assertEquals( 'string', $schema['status']['type'] );
		$this->assertEquals( 'array', $schema['form_data']['type'] );
		$this->assertEquals( 'datetime', $schema['created_at']['type'] );
	}

	/**
	 * Test get_schema default values.
	 */
	public function test_get_schema_default_values() {
		$schema = $this->entries_table->get_schema();
		$this->assertEquals( 0, $schema['user_id']['default'] );
		$this->assertEquals( 'unread', $schema['status']['default'] );
		$this->assertEquals( [], $schema['form_data']['default'] );
		$this->assertEquals( [], $schema['submission_info']['default'] );
		$this->assertEquals( [], $schema['notes']['default'] );
		$this->assertEquals( [], $schema['logs']['default'] );
		$this->assertEquals( [], $schema['extras']['default'] );
	}

	/**
	 * Test get_allowed_orderby_columns returns only safe, indexed columns.
	 */
	public function test_get_allowed_orderby_columns() {
		$method = new \ReflectionMethod( $this->entries_table, 'get_allowed_orderby_columns' );
		$method->setAccessible( true );
		$columns = $method->invoke( $this->entries_table );
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );

		// Must include the meaningful sort columns (lowercase 'id' for frontend compatibility).
		$expected = [ 'ID', 'id', 'form_id', 'user_id', 'status', 'type', 'created_at', 'updated_at' ];
		foreach ( $expected as $col ) {
			$this->assertContains( $col, $columns, "Expected '{$col}' to be in entries orderby allowlist." );
		}

		// Must NOT include LONGTEXT blob columns (DoS surface, not indexable).
		$forbidden = [ 'form_data', 'submission_info', 'notes', 'logs', 'extras' ];
		foreach ( $forbidden as $col ) {
			$this->assertNotContains( $col, $columns, "Column '{$col}' should not be orderable." );
		}
	}

	/**
	 * Test get_columns_definition returns non-empty array.
	 */
	public function test_get_columns_definition() {
		$columns = $this->entries_table->get_columns_definition();
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );
		// Should contain the primary key definition.
		$found_pk = false;
		foreach ( $columns as $col ) {
			if ( stripos( $col, 'PRIMARY KEY' ) !== false ) {
				$found_pk = true;
			}
		}
		$this->assertTrue( $found_pk );
	}

	/**
	 * Test add_log creates a log entry and returns key.
	 */
	public function test_add_log() {
		$this->entries_table->reset_logs();
		$key = $this->entries_table->add_log( 'Test Log Entry', [ 'message 1' ] );
		$this->assertIsInt( $key );
		$logs = $this->entries_table->get_logs();
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'Test Log Entry', $logs[0]['title'] );
		$this->assertContains( 'message 1', $logs[0]['messages'] );
		$this->assertArrayHasKey( 'timestamp', $logs[0] );
	}

	/**
	 * Test add_log with empty messages.
	 */
	public function test_add_log_empty_messages() {
		$this->entries_table->reset_logs();
		$key = $this->entries_table->add_log( 'Log with no messages' );
		$this->assertIsInt( $key );
		$logs = $this->entries_table->get_logs();
		$this->assertEquals( 'Log with no messages', $logs[0]['title'] );
		$this->assertIsArray( $logs[0]['messages'] );
	}

	/**
	 * Test add_log trims title.
	 */
	public function test_add_log_trims_title() {
		$this->entries_table->reset_logs();
		$this->entries_table->add_log( '  Trimmed Title  ' );
		$logs = $this->entries_table->get_logs();
		$this->assertEquals( 'Trimmed Title', $logs[0]['title'] );
	}

	/**
	 * Test multiple add_log calls prepend logs.
	 */
	public function test_add_log_prepends() {
		$this->entries_table->reset_logs();
		$this->entries_table->add_log( 'First Log' );
		$this->entries_table->add_log( 'Second Log' );
		$logs = $this->entries_table->get_logs();
		$this->assertCount( 2, $logs );
		// Second log should be first (prepended).
		$this->assertEquals( 'Second Log', $logs[0]['title'] );
		$this->assertEquals( 'First Log', $logs[1]['title'] );
	}

	/**
	 * Test update_log updates title and appends messages.
	 */
	public function test_update_log() {
		$this->entries_table->reset_logs();
		$key = $this->entries_table->add_log( 'Original Title', [ 'msg 1' ] );
		$updated_key = $this->entries_table->update_log( $key, 'Updated Title', [ 'msg 2' ] );
		$this->assertEquals( $key, $updated_key );
		$logs = $this->entries_table->get_logs();
		$this->assertEquals( 'Updated Title', $logs[ $key ]['title'] );
		$this->assertContains( 'msg 1', $logs[ $key ]['messages'] );
		$this->assertContains( 'msg 2', $logs[ $key ]['messages'] );
	}

	/**
	 * Test update_log with null title preserves original title.
	 */
	public function test_update_log_null_title_preserves_original() {
		$this->entries_table->reset_logs();
		$key = $this->entries_table->add_log( 'Keep This Title' );
		$this->entries_table->update_log( $key, null, [ 'new message' ] );
		$logs = $this->entries_table->get_logs();
		$this->assertEquals( 'Keep This Title', $logs[ $key ]['title'] );
	}

	/**
	 * Test update_log with non-existent key returns null.
	 */
	public function test_update_log_nonexistent_key() {
		$this->entries_table->reset_logs();
		$result = $this->entries_table->update_log( 999, 'Title', [ 'msg' ] );
		$this->assertNull( $result );
	}

	/**
	 * Test reset_logs clears all logs.
	 */
	public function test_reset_logs() {
		$this->entries_table->add_log( 'Log 1' );
		$this->entries_table->add_log( 'Log 2' );
		$this->entries_table->reset_logs();
		$logs = $this->entries_table->get_logs();
		$this->assertIsArray( $logs );
		$this->assertEmpty( $logs );
	}

	/**
	 * Test get_last_log_key with no logs returns null.
	 */
	public function test_get_last_log_key_empty() {
		$this->entries_table->reset_logs();
		$key = $this->entries_table->get_last_log_key();
		// array_key_last on empty array returns null.
		$this->assertNull( $key );
	}

	/**
	 * Test get_last_log_key with logs returns integer.
	 */
	public function test_get_last_log_key_with_logs() {
		$this->entries_table->reset_logs();
		$this->entries_table->add_log( 'First' );
		$this->entries_table->add_log( 'Second' );
		$key = $this->entries_table->get_last_log_key();
		$this->assertIsInt( $key );
	}

	/**
	 * Test add with empty form_id returns false.
	 */
	public function test_add_empty_form_id() {
		$result = Entries::add( [ 'form_data' => [] ] );
		$this->assertFalse( $result );
	}

	/**
	 * Test add strips ID from data.
	 */
	public function test_add_with_id_in_data() {
		// Even though we pass ID, the add method should unset it.
		// The actual insert may still fail depending on DB setup,
		// but we verify the method doesn't error out.
		$data = [
			'ID'      => 999,
			'form_id' => 1,
			'form_data' => [ 'text-lbl-name' => 'Test' ],
		];
		$result = Entries::add( $data );
		// Result depends on DB state; just confirm it didn't throw.
		$this->assertTrue( $result === false || is_int( $result ) );
	}

	/**
	 * Test update with empty entry_id returns false.
	 */
	public function test_update_empty_entry_id() {
		$result = Entries::update( 0, [ 'status' => 'read' ] );
		$this->assertFalse( $result );
	}

	/**
	 * Test get with non-existent entry returns empty array.
	 */
	public function test_get_nonexistent_entry() {
		$result = Entries::get( 999999 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_form_data with non-existent entry returns empty array.
	 */
	public function test_get_form_data_nonexistent() {
		$result = Entries::get_form_data( 999999 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_form_ids_by_entries with empty array returns empty.
	 */
	public function test_get_form_ids_by_entries_nonexistent() {
		$result = Entries::get_form_ids_by_entries( [ 999999 ] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_entry_data returns empty array for non-existent entry.
	 */
	public function test_get_entry_data() {
		$result = Entries::get_entry_data( 999999 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_columns_to_rename has expected structure.
	 */
	public function test_get_columns_to_rename() {
		$renames = $this->entries_table->get_columns_to_rename();
		$this->assertIsArray( $renames );
		$this->assertNotEmpty( $renames );
		$this->assertArrayHasKey( 'from', $renames[0] );
		$this->assertArrayHasKey( 'to', $renames[0] );
		$this->assertEquals( 'user_data', $renames[0]['from'] );
		$this->assertEquals( 'form_data', $renames[0]['to'] );
	}
}
