<?php
/**
 * Class Test_Entries
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Entries;

/**
 * Tests for the Entries class.
 */
class Test_Entries extends TestCase {

	/**
	 * Test get_entries returns correct structure with defaults.
	 */
	public function test_get_entries_default_args_returns_correct_structure() {
		$result = Entries::get_entries();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'entries', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'per_page', $result );
		$this->assertArrayHasKey( 'current_page', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertArrayHasKey( 'emptyTrash', $result );
	}

	/**
	 * Test get_entries default per_page is 20 and page is 1.
	 */
	public function test_get_entries_default_pagination_values() {
		$result = Entries::get_entries();
		$this->assertEquals( 20, $result['per_page'] );
		$this->assertEquals( 1, $result['current_page'] );
	}

	/**
	 * Test get_entries with custom pagination.
	 */
	public function test_get_entries_custom_pagination() {
		$result = Entries::get_entries( [
			'per_page' => 5,
			'page'     => 2,
		] );
		$this->assertEquals( 5, $result['per_page'] );
		$this->assertEquals( 2, $result['current_page'] );
	}

	/**
	 * Test update_status with empty entry IDs returns failure.
	 */
	public function test_update_status_empty_entry_ids() {
		$result = Entries::update_status( [], 'read' );
		$this->assertFalse( $result['success'] );
		$this->assertEquals( 0, $result['updated'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	/**
	 * Test update_status with zero entry ID returns failure.
	 */
	public function test_update_status_zero_entry_id() {
		$result = Entries::update_status( 0, 'read' );
		$this->assertFalse( $result['success'] );
		$this->assertEquals( 0, $result['updated'] );
	}

	/**
	 * Test update_status with invalid status returns failure.
	 */
	public function test_update_status_invalid_status() {
		$result = Entries::update_status( [ 1 ], 'invalid-status' );
		$this->assertFalse( $result['success'] );
		$this->assertEquals( 0, $result['updated'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	/**
	 * Test update_status accepts valid statuses.
	 */
	public function test_update_status_valid_statuses() {
		$valid_statuses = [ 'trash', 'unread', 'read', 'restore' ];
		foreach ( $valid_statuses as $status ) {
			$result = Entries::update_status( [ 999999 ], $status );
			// Should not fail due to invalid status - may fail because entry doesn't exist.
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'success', $result );
			$this->assertArrayHasKey( 'updated', $result );
		}
	}

	/**
	 * Test delete_entries with empty entry IDs returns failure.
	 */
	public function test_delete_entries_empty_ids() {
		$result = Entries::delete_entries( [] );
		$this->assertFalse( $result['success'] );
		$this->assertEquals( 0, $result['deleted'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	/**
	 * Test delete_entries with zero entry ID returns failure.
	 */
	public function test_delete_entries_zero_id() {
		$result = Entries::delete_entries( 0 );
		$this->assertFalse( $result['success'] );
		$this->assertEquals( 0, $result['deleted'] );
	}

	/**
	 * Test export_entries with no matching entries.
	 */
	public function test_export_entries_no_entries() {
		$result = Entries::export_entries( [
			'entry_ids' => [ 999999 ],
		] );
		$this->assertIsArray( $result );
		// With a non-existent entry, export should handle gracefully.
		$this->assertArrayHasKey( 'success', $result );
	}

	/**
	 * Test get_adjacent_entry_ids returns correct structure.
	 */
	public function test_get_adjacent_entry_ids_returns_correct_structure() {
		$result = Entries::get_adjacent_entry_ids( 999999 );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'previous_id', $result );
		$this->assertArrayHasKey( 'next_id', $result );
	}

	/**
	 * Test get_adjacent_entry_ids for non-existent entry returns nulls.
	 */
	public function test_get_adjacent_entry_ids_nonexistent_entry() {
		$result = Entries::get_adjacent_entry_ids( 999999 );
		$this->assertNull( $result['previous_id'] );
		$this->assertNull( $result['next_id'] );
	}

	/**
	 * Test build_where_conditions with status filter.
	 */
	public function test_build_where_conditions_status_filter() {
		$args = [
			'form_id'   => 0,
			'status'    => 'read',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should have a status = 'read' condition.
		$found_status = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( isset( $condition['key'] ) && 'status' === $condition['key'] && '=' === $condition['compare'] ) {
					$found_status = true;
					$this->assertEquals( 'read', $condition['value'] );
				}
			}
		}
		$this->assertTrue( $found_status );
	}

	/**
	 * Test build_where_conditions with 'all' status excludes trash.
	 */
	public function test_build_where_conditions_all_excludes_trash() {
		$args = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );
		$this->assertIsArray( $result );
		$found_trash_exclude = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( isset( $condition['key'] ) && 'status' === $condition['key'] && '!=' === $condition['compare'] && 'trash' === $condition['value'] ) {
					$found_trash_exclude = true;
				}
			}
		}
		$this->assertTrue( $found_trash_exclude );
	}

	/**
	 * Test build_where_conditions with entry_ids short-circuits.
	 */
	public function test_build_where_conditions_entry_ids_short_circuit() {
		$args = [
			'form_id'   => 5,
			'status'    => 'read',
			'search'    => '123',
			'date_from' => '2024-01-01',
			'date_to'   => '2024-12-31',
			'entry_ids' => [ 1, 2, 3 ],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );
		$this->assertIsArray( $result );
		// When entry_ids is provided, it returns early with just the ID IN condition.
		$this->assertCount( 1, $result );
		$this->assertEquals( 'ID', $result[0][0]['key'] );
		$this->assertEquals( 'IN', $result[0][0]['compare'] );
	}

	/**
	 * Test build_where_conditions with form_id filter.
	 */
	public function test_build_where_conditions_form_id_filter() {
		$args = [
			'form_id'   => 42,
			'status'    => 'all',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );
		$found_form_id = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( isset( $condition['key'] ) && 'form_id' === $condition['key'] ) {
					$found_form_id = true;
					$this->assertEquals( 42, $condition['value'] );
				}
			}
		}
		$this->assertTrue( $found_form_id );
	}

	/**
	 * Test build_where_conditions with search filter.
	 */
	public function test_build_where_conditions_search_filter() {
		$args = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '55',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );
		$found_search = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( isset( $condition['key'] ) && 'ID' === $condition['key'] && '=' === $condition['compare'] ) {
					$found_search = true;
				}
			}
		}
		$this->assertTrue( $found_search );
	}

	/**
	 * Helper method to call private static methods for testing.
	 */
	private function call_private_method_static( $class, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( $class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( null, $parameters );
	}
}
