<?php
/**
 * Class Test_Database_Base
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Database\Tables\Entries;

/**
 * Tests for Base::prepare_where_clauses() via the Entries concrete subclass.
 * The method is protected, so we access it through ReflectionMethod.
 */
class Test_Database_Base extends TestCase {

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
		$this->entries_table         = Entries::get_instance();
		$reflection                  = new ReflectionClass( Entries::class );
		$this->prepare_where_clauses = $reflection->getMethod( 'prepare_where_clauses' );
		$this->prepare_where_clauses->setAccessible( true );
	}

	/**
	 * Helper to invoke prepare_where_clauses.
	 */
	private function prepare( array $where_clauses ): string {
		return $this->prepare_where_clauses->invoke( $this->entries_table, $where_clauses );
	}

	// ---------------------------------------------------------------
	// Empty / no-op cases
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
	// Single AND condition
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
	// Multiple AND groups (each group parenthesized, joined with AND)
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
	// OR group (the PR's new search logic)
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
	// LIKE operator
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
	// IN operator
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
	// Date range (two conditions in one AND group)
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
