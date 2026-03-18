<?php
/**
 * Class Test_Database_Base
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Database\Tables\Payments;

/**
 * Tests for Base protected methods:
 *   - get_allowed_orderby_columns() via Payments (does not override)
 *   - get_records_by_args() ORDER BY security via Payments
 *   - prepare_where_clauses() via Entries (concrete subclass)
 */
class Test_Database_Base extends TestCase {

	/**
	 * @var Payments
	 */
	protected $base;

	/**
	 * @var Entries
	 */
	protected $entries_table;

	/**
	 * @var ReflectionMethod
	 */
	protected $prepare_where_clauses;

	protected function setUp(): void {
		parent::setUp();
		$this->base                  = Payments::get_instance();
		$this->entries_table         = Entries::get_instance();
		$reflection                  = new ReflectionClass( Entries::class );
		$this->prepare_where_clauses = $reflection->getMethod( 'prepare_where_clauses' );
		$this->prepare_where_clauses->setAccessible( true );
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * Helper to invoke the protected get_allowed_orderby_columns method.
	 *
	 * @return array<string>
	 */
	private function get_allowed_columns() {
		$method = new \ReflectionMethod( $this->base, 'get_allowed_orderby_columns' );
		$method->setAccessible( true );
		return $method->invoke( $this->base );
	}

	/**
	 * Helper to invoke prepare_where_clauses.
	 */
	private function prepare( array $where_clauses ): string {
		return $this->prepare_where_clauses->invoke( $this->entries_table, $where_clauses );
	}

	// ---------------------------------------------------------------
	// get_allowed_orderby_columns
	// ---------------------------------------------------------------

	/**
	 * Test get_allowed_orderby_columns returns an array.
	 */
	public function test_get_allowed_orderby_columns() {
		$columns = $this->get_allowed_columns();
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );
	}

	/**
	 * Test get_allowed_orderby_columns includes all schema keys.
	 */
	public function test_get_allowed_orderby_columns_includes_schema_keys() {
		$columns = $this->get_allowed_columns();
		$schema  = $this->base->get_schema();
		foreach ( array_keys( $schema ) as $key ) {
			$this->assertContains( $key, $columns, "Expected schema key '{$key}' to be in the allowlist." );
		}
	}

	/**
	 * Test get_allowed_orderby_columns always includes updated_at.
	 */
	public function test_get_allowed_orderby_columns_includes_updated_at() {
		$columns = $this->get_allowed_columns();
		$this->assertContains( 'updated_at', $columns );
	}

	/**
	 * Test get_allowed_orderby_columns does not include invalid columns.
	 */
	public function test_get_allowed_orderby_columns_rejects_invalid_column() {
		$columns = $this->get_allowed_columns();
		$this->assertNotContains( 'nonexistent_column', $columns );
		$this->assertNotContains( 'SLEEP(5)', $columns );
	}

	// ---------------------------------------------------------------
	// get_total_count
	// ---------------------------------------------------------------

	/**
	 * Test get_total_count returns an integer.
	 */
	public function test_get_total_count() {
		$result = $this->base->get_total_count();
		$this->assertIsInt( $result );
		$this->assertGreaterThanOrEqual( 0, $result );
	}

	// ---------------------------------------------------------------
	// get_records_by_args ORDER BY security
	// ---------------------------------------------------------------

	/**
	 * Test get_records_by_args returns an array with default args.
	 */
	public function test_get_records_by_args() {
		$result = $this->base->get_records_by_args();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args rejects SQL injection in orderby and still returns array.
	 */
	public function test_get_records_by_args_rejects_invalid_orderby() {
		$result = $this->base->get_records_by_args( [ 'orderby' => 'id` DESC; DROP TABLE wp_posts; --' ] );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args accepts a valid orderby column.
	 */
	public function test_get_records_by_args_with_valid_orderby() {
		$result = $this->base->get_records_by_args( [ 'orderby' => 'created_at', 'order' => 'ASC' ] );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args normalises an invalid order direction to DESC.
	 */
	public function test_get_records_by_args_normalises_invalid_order() {
		$result = $this->base->get_records_by_args( [ 'order' => 'INVALID; DROP TABLE--' ] );
		$this->assertIsArray( $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — empty / no-op cases
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_returns_empty_string_for_empty_input() {
		$result = $this->prepare( [] );
		$this->assertSame( '', $result );
	}

	public function test_prepare_where_clauses_skips_unknown_schema_keys() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'nonexistent_column',
						'compare' => '=',
						'value'   => 'test',
					],
				],
			]
		);
		$this->assertSame( '', $result );
	}

	public function test_prepare_where_clauses_skips_disallowed_operator() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => 'INVALID_OP',
						'value'   => 'read',
					],
				],
			]
		);
		$this->assertSame( '', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — single AND condition
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_single_equals_condition() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => 'read',
					],
				],
			]
		);
		$this->assertStringContainsString( 'WHERE', $result );
		$this->assertStringContainsString( 'status', $result );
		$this->assertStringContainsString( '=', $result );
		$this->assertStringContainsString( 'read', $result );
	}

	public function test_prepare_where_clauses_single_not_equals_condition() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => '!=',
						'value'   => 'trash',
					],
				],
			]
		);
		$this->assertStringContainsString( 'status', $result );
		$this->assertStringContainsString( '!=', $result );
		$this->assertStringContainsString( 'trash', $result );
	}

	public function test_prepare_where_clauses_numeric_equals_condition() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'ID',
						'compare' => '=',
						'value'   => 42,
					],
				],
			]
		);
		$this->assertStringContainsString( 'WHERE', $result );
		$this->assertStringContainsString( 'ID', $result );
		$this->assertStringContainsString( '42', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — multiple AND groups
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_multiple_groups_joined_with_and() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => '!=',
						'value'   => 'trash',
					],
				],
				[
					[
						'key'     => 'form_id',
						'compare' => '=',
						'value'   => 5,
					],
				],
			]
		);
		$this->assertStringContainsString( 'status', $result );
		$this->assertStringContainsString( 'form_id', $result );
		// Both groups joined with AND.
		$this->assertStringContainsString( 'AND', $result );
		// Each group is parenthesized.
		$this->assertMatchesRegularExpression( '/\(status[^)]+\).*AND.*\(form_id[^)]+\)/s', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — OR group
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_or_group_uses_or_within_parentheses() {
		$result = $this->prepare(
			[
				[
					'RELATION' => 'OR',
					[
						'key'     => 'ID',
						'compare' => '=',
						'value'   => 99,
					],
					[
						'key'     => 'form_id',
						'compare' => '=',
						'value'   => 3,
					],
				],
			]
		);
		$this->assertStringContainsString( 'WHERE', $result );
		// The two conditions should appear within one parenthesized group joined by OR.
		$this->assertMatchesRegularExpression( '/\([^)]*ID[^)]*OR[^)]*form_id[^)]*\)/s', $result );
	}

	public function test_prepare_where_clauses_or_group_combined_with_and_group() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => '!=',
						'value'   => 'trash',
					],
				],
				[
					'RELATION' => 'OR',
					[
						'key'     => 'ID',
						'compare' => '=',
						'value'   => 10,
					],
					[
						'key'     => 'form_id',
						'compare' => '=',
						'value'   => 2,
					],
				],
			]
		);
		// Must have parenthesized groups joined by AND.
		$this->assertMatchesRegularExpression( '/\([^)]+\)\s+AND\s+\([^)]+\)/s', $result );
		$this->assertStringContainsString( 'status', $result );
		$this->assertStringContainsString( 'OR', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — LIKE operator
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_like_operator() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'status',
						'compare' => 'LIKE',
						'value'   => 'read',
					],
				],
			]
		);
		$this->assertStringContainsString( 'LIKE', $result );
		$this->assertStringContainsString( 'status', $result );
		$this->assertStringContainsString( 'read', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — IN operator
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_in_operator() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'ID',
						'compare' => 'IN',
						'value'   => [ 1, 2, 3 ],
					],
				],
			]
		);
		$this->assertStringContainsString( 'IN', $result );
		$this->assertStringContainsString( 'ID', $result );
		$this->assertStringContainsString( '1', $result );
		$this->assertStringContainsString( '2', $result );
		$this->assertStringContainsString( '3', $result );
	}

	// ---------------------------------------------------------------
	// prepare_where_clauses — date range
	// ---------------------------------------------------------------

	public function test_prepare_where_clauses_date_range_uses_and() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => '2024-01-01 00:00:00',
					],
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => '2024-12-31 23:59:59',
					],
				],
			]
		);
		$this->assertStringContainsString( 'created_at', $result );
		$this->assertStringContainsString( '>=', $result );
		$this->assertStringContainsString( '<=', $result );
	}
}
