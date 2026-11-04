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
	// use_insert
	// ---------------------------------------------------------------

	/**
	 * use_insert() returns the new row id, or false when wpdb rejects the write.
	 *
	 * An empty array is not a rejection: prepare_data() fills every column from
	 * the schema's defaults, so the insert succeeds and hands back an id. It also
	 * drops unknown columns, so there is no input from here that reaches wpdb in
	 * a state wpdb would refuse - the false branch is not reachable through this
	 * method, which is worth knowing rather than asserting wrongly.
	 */
	public function test_use_insert() {
		$insert_id = $this->base->use_insert( [] );

		$this->assertIsInt( $insert_id );
		$this->assertGreaterThan( 0, $insert_id );

		// Clean up the row this just created.
		$this->base->delete( [ 'ID' => $insert_id ] );
	}


	/**
	 * A 4-byte character such as an emoji survives a JSON column whose charset is
	 * not utf8mb4: it is stored as a JSON escape that decodes back to itself.
	 * WordPress otherwise refuses the whole insert.
	 *
	 * The column is reported as utf8 through wpdb's own pre_get_col_charset filter,
	 * which both wpdb's insert check and the plugin read, rather than altered: DDL
	 * commits the test database's open transaction and leaks data into later tests.
	 */
	public function test_prepare_data_keeps_emoji_on_a_utf8_column() {
		$table   = $this->entries_table->get_tablename();
		$utf8    = static function ( $charset, $for_table, $column ) use ( $table ) {
			return $for_table === $table && 'form_data' === $column ? 'utf8' : $charset;
		};
		$value   = "Hi \u{1F600} \u{65E5}\u{672C} \u{00FC}";
		$key     = 'srfm-textarea-em000001-lbl-TWVzc2FnZQ-message';
		$entries = $this->entries_table;

		add_filter( 'pre_get_col_charset', $utf8, 10, 3 );
		try {
			$id = Entries::add(
				[
					'form_id'   => 1,
					'form_data' => [ $key => $value ],
				]
			);
		} finally {
			remove_filter( 'pre_get_col_charset', $utf8, 10 );
		}

		$this->assertIsInt( $id, 'The insert is not refused.' );
		$this->assertSame( $value, Entries::get( $id )['form_data'][ $key ], 'The emoji reads back unchanged.' );

		$entries->delete( [ 'ID' => $id ] );
	}

	/**
	 * On a utf8mb4 column the value is stored as it is, so nothing changes for the
	 * usual install.
	 */
	public function test_prepare_data_leaves_utf8mb4_columns_unchanged() {
		$method = new \ReflectionMethod( $this->entries_table, 'prepare_data' );
		$method->setAccessible( true );
		$value = "Hi \u{1F600}";

		$prepared = $method->invoke( $this->entries_table, [ 'form_data' => [ 'k' => $value ] ] );

		$this->assertStringContainsString( "\u{1F600}", $prepared['data']['form_data'] );
	}

	// ---------------------------------------------------------------
	// encode_by_datatype
	// ---------------------------------------------------------------

	/**
	 * encode_by_datatype() casts each value to the storage shape its data type
	 * calls for: scalars to string / int / bool, an array to a JSON string, and
	 * a datetime straight through.
	 */
	public function test_encode_by_datatype() {
		$encode = new \ReflectionMethod( $this->entries_table, 'encode_by_datatype' );
		$encode->setAccessible( true );

		$this->assertSame( '42', $encode->invoke( $this->entries_table, 42, 'string' ) );
		$this->assertSame( 42, $encode->invoke( $this->entries_table, '42', 'number' ) );
		$this->assertTrue( $encode->invoke( $this->entries_table, 1, 'boolean' ) );
		$this->assertFalse( $encode->invoke( $this->entries_table, 0, 'boolean' ) );

		// 'array' is JSON-encoded so it round-trips back to the same array.
		$json = $encode->invoke( $this->entries_table, [ 'a' => 1 ], 'array' );
		$this->assertIsString( $json );
		$this->assertSame( [ 'a' => 1 ], json_decode( $json, true ) );

		// 'datetime' is already SQL-shaped, so it is returned untouched.
		$this->assertSame( '2026-01-01 00:00:00', $encode->invoke( $this->entries_table, '2026-01-01 00:00:00', 'datetime' ) );
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

	public function test_prepare_where_clauses_not_in_operator() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'user_id',
						'compare' => 'NOT IN',
						'value'   => [ 4, 5 ],
					],
				],
			]
		);

		// Without 'NOT IN' on the operator allowlist the whole condition is dropped
		// and the query silently matches every row, which is how an exclusion built
		// on this would fail open.
		$this->assertStringContainsString( 'NOT IN', $result );
		$this->assertStringContainsString( 'user_id', $result );
		$this->assertStringContainsString( '4', $result );
		$this->assertStringContainsString( '5', $result );
	}

	/**
	 * An empty list must not be interpolated into "col IN ()".
	 *
	 * That is a syntax error, and it fails the whole query rather than the one
	 * condition, taking out the listing and its COUNT together. An empty IN matches
	 * nothing, so it collapses to a constant that says so.
	 */
	public function test_prepare_where_clauses_empty_in_matches_nothing() {
		$in = $this->prepare(
			[
				[
					[
						'key'     => 'ID',
						'compare' => 'IN',
						'value'   => [],
					],
				],
			]
		);

		$this->assertStringNotContainsString( 'IN ()', $in );
		$this->assertStringContainsString( '1 = 0', $in, 'An empty IN matches nothing.' );
	}

	/**
	 * An empty NOT IN is dropped, never written as a constant.
	 *
	 * It excludes nothing, and the constant that says so is a literal true. Under
	 * AND that is a no-op, but the same clause list also builds OR groups, where a
	 * literal true makes the whole group match every row and neutralises the
	 * sibling conditions. Dropping the condition means the same thing under AND and
	 * narrows rather than widens under OR.
	 */
	public function test_prepare_where_clauses_empty_not_in_is_dropped() {
		$not_in = $this->prepare(
			[
				[
					[
						'key'     => 'ID',
						'compare' => 'NOT IN',
						'value'   => [],
					],
				],
			]
		);

		$this->assertStringNotContainsString( 'IN ()', $not_in );
		$this->assertStringNotContainsString( '1 = 1', $not_in, 'An empty NOT IN must not emit a literal true.' );
		$this->assertStringNotContainsString( 'NOT IN', $not_in, 'The condition is dropped, not built.' );
	}

	/**
	 * A scalar where an array belongs is a caller bug, and it must surface.
	 *
	 * `'NOT IN'` with `5` -- a plausible typo for `[ 5 ]` -- used to be folded in
	 * with the empty-array case and drop the condition, excluding nobody with no
	 * error and a green suite, while the same typo on `'IN'` failed closed. On a
	 * primitive whose only job is scoping data that asymmetry is the hazard.
	 */
	public function test_prepare_where_clauses_non_array_in_value_is_doing_it_wrong() {
		$notices = [];

		$observe = static function ( $function_name, $message ) use ( &$notices ) {
			$notices[] = $function_name . ': ' . $message;
		};

		add_action( 'doing_it_wrong_run', $observe, 10, 2 );
		// _doing_it_wrong() escalates to trigger_error() under WP_DEBUG, which
		// would abort the test rather than let it assert.
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		$result = $this->prepare(
			[
				[
					[
						'key'     => 'user_id',
						'compare' => 'NOT IN',
						// @phpstan-ignore-next-line -- Deliberately the wrong type.
						'value'   => 5,
					],
				],
			]
		);

		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		remove_action( 'doing_it_wrong_run', $observe, 10 );

		$this->assertNotEmpty( $notices, 'A scalar value must be reported, not swallowed.' );
		$this->assertStringContainsString( 'prepare_where_clauses', $notices[0] );
		$this->assertStringContainsString( 'NOT IN requires an array value', $notices[0] );
		// The received type, so the caller does not have to guess what it sent.
		$this->assertStringContainsString( 'integer', $notices[0] );

		// And it must not have built a condition out of the bad input.
		$this->assertStringNotContainsString( 'NOT IN', $result );
		$this->assertStringNotContainsString( '1 = 1', $result );
	}

	/**
	 * An operator is normalised before the allowlist test.
	 *
	 * Payments' builder upper-cases and trims; this one compared strictly. So a
	 * caller writing `'not in'` was honoured by one and silently dropped by the
	 * other -- and a dropped NOT IN is a silently disabled exclusion.
	 */
	public function test_prepare_where_clauses_normalises_operator_case() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'user_id',
						'compare' => ' not in ',
						'value'   => [ 4, 5 ],
					],
				],
			]
		);

		$this->assertStringContainsString( 'NOT IN', $result, 'A lower-case operator must still be honoured.' );
		$this->assertStringContainsString( '4', $result );
		$this->assertStringContainsString( '5', $result );

		// Still an allowlist: an unknown operator is dropped however it is cased.
		$this->assertSame(
			'',
			$this->prepare(
				[
					[
						[
							'key'     => 'user_id',
							'compare' => 'drop table',
							'value'   => [ 1 ],
						],
					],
				]
			)
		);
	}

	/**
	 * An empty NOT IN beside an OR sibling must not widen the group.
	 *
	 * This is the shape that matters: a group whose siblings are the only thing
	 * keeping a query narrow. The surviving clause must still be the sibling alone.
	 */
	public function test_prepare_where_clauses_empty_not_in_does_not_widen_an_or_group() {
		$result = $this->prepare(
			[
				[
					[
						'key'     => 'form_id',
						'compare' => '=',
						'value'   => 7,
					],
					[
						'key'     => 'user_id',
						'compare' => 'NOT IN',
						'value'   => [],
					],
					'RELATION' => 'OR',
				],
			]
		);

		$this->assertStringContainsString( 'form_id', $result, 'The sibling condition survives.' );
		$this->assertStringNotContainsString( '1 = 1', $result );
		$this->assertStringNotContainsString( ' OR ', $result, 'Nothing is left to OR the sibling against.' );
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

	// ---------------------------------------------------------------
	// maybe_add_new_columns — guard clause
	// ---------------------------------------------------------------

	/**
	 * maybe_add_new_columns() must short-circuit to false when given no
	 * columns, before attempting any schema ALTER. Guard-clause coverage for
	 * the function that gained the srfm_db_upgrade_query_failed failure hook.
	 */
	public function test_maybe_add_new_columns_returns_false_for_empty_input() {
		$this->assertFalse( $this->base->maybe_add_new_columns( [] ) );
		$this->assertFalse( $this->base->maybe_add_new_columns() );
	}

	// ---------------------------------------------------------------
	// table_exists
	// ---------------------------------------------------------------

	/**
	 * The happy path. Both concrete tables exist on a working install, so a false
	 * here would put a "your database needs updating" warning on every healthy site.
	 */
	public function test_table_exists_reports_a_present_table() {
		$this->assertTrue( $this->entries_table->table_exists() );
		$this->assertTrue( $this->base->table_exists() );
	}

	/**
	 * `$wpdb->prefix` contains an underscore, which is a LIKE wildcard. Without
	 * esc_like(), `wp_srfm_entries` would also match `wpXsrfm_entries` and the check
	 * could report a table that is not ours. Compare the returned name, not just
	 * emptiness, to pin that down.
	 */
	public function test_table_exists_matches_the_exact_table_name() {
		global $wpdb;

		$table = $this->entries_table->get_tablename();
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore -- Test assertion helper.

		$this->assertSame( $table, $found );
	}

	/**
	 * The table name is always derived from the live `$wpdb->prefix`, never cached
	 * from install time — that derivation is what makes a prefix change detectable
	 * rather than silently fatal.
	 */
	public function test_get_tablename() {
		global $wpdb;

		$this->assertSame( $wpdb->prefix . 'srfm_entries', $this->entries_table->get_tablename() );
		$this->assertSame( $wpdb->prefix . 'srfm_payments', $this->base->get_tablename() );
	}

	/**
	 * create() must refuse an empty column definition rather than emit a CREATE TABLE
	 * with no body. Guard-clause coverage for the function that gained the
	 * srfm_db_upgrade_query_failed failure hook.
	 */
	public function test_create() {
		$this->assertFalse( $this->entries_table->create( [] ) );
	}

	/**
	 * A name match is not a data match: has_expected_columns() is the guard that stops
	 * an unrelated table being renamed into place just because it ends in the right
	 * words. True for the real table, false for one carrying only an id.
	 */
	public function test_has_expected_columns() {
		global $wpdb;

		$method = new \ReflectionMethod( $this->entries_table, 'has_expected_columns' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->entries_table, $this->entries_table->get_tablename() ) );

		$bare = $wpdb->prefix . 'srfm_bare_probe';
		$wpdb->query( "CREATE TABLE `{$bare}` ( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY )" ); // phpcs:ignore -- Scratch table for this test.
		$wrong = $method->invoke( $this->entries_table, $bare );
		$wpdb->query( "DROP TABLE `{$bare}`" ); // phpcs:ignore -- Dropping the scratch table this test created.

		$this->assertFalse( $wrong );
	}

	/**
	 * Nothing is adoptable while the table is in place — the live table always wins,
	 * so a stray differently-prefixed copy must not be reported as a candidate.
	 *
	 * The missing-table cases, and every refusal guard, are covered in
	 * Test_Database_Register alongside the repair that consumes them.
	 */
	public function test_find_adoptable_table() {
		$this->assertSame( '', $this->entries_table->find_adoptable_table() );
	}

	/**
	 * adopt_table() renames a table, so its refusals matter more than its happy path:
	 * nothing to adopt is not a repair, and renaming a table onto itself is a MySQL
	 * error rather than a no-op. The successful adoption is covered in
	 * Test_Database_Register, where the rows can be seeded and read back.
	 */
	public function test_adopt_table() {
		$this->assertFalse( $this->entries_table->adopt_table( '' ) );
		$this->assertFalse( $this->entries_table->adopt_table( $this->entries_table->get_tablename() ) );
	}

	/**
	 * The owner signature is a stable, prefixed, per-site string.
	 */
	public function test_get_owner_signature() {
		$method = new ReflectionMethod( $this->entries_table, 'get_owner_signature' );
		$method->setAccessible( true );

		$signature = $method->invoke( $this->entries_table );

		$this->assertIsString( $signature );
		$this->assertStringStartsWith( 'srfm-owner:', $signature );
		$this->assertSame( $signature, $method->invoke( $this->entries_table ), 'The signature must be stable within a site.' );
	}

	/**
	 * Stamping writes this site's signature into the table's MySQL comment, where a
	 * later adoption can read it back. The comment survives RENAME, unlike an option.
	 */
	public function test_stamp_owner_signature() {
		global $wpdb;

		$method = new ReflectionMethod( $this->entries_table, 'get_owner_signature' );
		$method->setAccessible( true );
		$signature = $method->invoke( $this->entries_table );

		$table = $wpdb->prefix . 'srfm_stamp_probe';
		$wpdb->query( "CREATE TABLE `{$table}` ( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY )" ); // phpcs:ignore -- Scratch table for this test.

		$this->entries_table->stamp_owner_signature( $table );

		$comment = $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) ); // phpcs:ignore -- Reading back the comment this test wrote.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore -- Test teardown on a scratch table this test created.

		$this->assertSame( $signature, $comment );
	}

	/**
	 * Ownership is proven only by an exact signature match. A stamped table is ours;
	 * an unstamped one (as another install's table would be) and an empty name are
	 * denied.
	 */
	public function test_table_belongs_to_site() {
		global $wpdb;

		$owned   = $wpdb->prefix . 'srfm_owned_probe';
		$foreign = $wpdb->prefix . 'srfm_foreign_probe';
		$wpdb->query( "CREATE TABLE `{$owned}` ( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY )" ); // phpcs:ignore -- Scratch table for this test.
		$wpdb->query( "CREATE TABLE `{$foreign}` ( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY )" ); // phpcs:ignore -- Scratch table for this test.
		$this->entries_table->stamp_owner_signature( $owned );

		$owned_result   = $this->entries_table->table_belongs_to_site( $owned );
		$foreign_result = $this->entries_table->table_belongs_to_site( $foreign );
		$empty_result   = $this->entries_table->table_belongs_to_site( '' );

		$wpdb->query( "DROP TABLE IF EXISTS `{$owned}`" ); // phpcs:ignore -- Test teardown on a scratch table this test created.
		$wpdb->query( "DROP TABLE IF EXISTS `{$foreign}`" ); // phpcs:ignore -- Test teardown on a scratch table this test created.

		$this->assertTrue( $owned_result, 'A table carrying this site signature is ours.' );
		$this->assertFalse( $foreign_result, 'An unstamped table must be denied.' );
		$this->assertFalse( $empty_result, 'An empty name must be denied.' );
	}

	/**
	 * A dropped table must read as missing — this is the state the whole
	 * detect-and-repair feature exists to catch.
	 */
	public function test_table_exists_reports_a_dropped_table_as_missing() {
		global $wpdb;

		$table = $this->entries_table->get_tablename();

		$wpdb->query( "CREATE TABLE `{$table}_srfmbak` LIKE `{$table}`" ); // phpcs:ignore -- Preserving the schema across the drop under test.
		$wpdb->query( "DROP TABLE `{$table}`" ); // phpcs:ignore -- Reproducing the dropped-table state under test.

		$missing = $this->entries_table->table_exists();

		$wpdb->query( "RENAME TABLE `{$table}_srfmbak` TO `{$table}`" ); // phpcs:ignore -- Restoring the table this test dropped.

		$this->assertFalse( $missing );
		$this->assertTrue( $this->entries_table->table_exists() );
	}

	// ---------------------------------------------------------------
	// get_results
	// ---------------------------------------------------------------

	/**
	 * An empty result set is a cached answer, not a cache miss.
	 *
	 * The cache was read for truthiness, so `[]` looked like "nothing stored" and
	 * the query ran again for every caller. That was rare while every lookup
	 * matched something; the editor exclusion makes an empty windowed lookup the
	 * common case, so the dead-cache path starts firing on most rows.
	 *
	 * Asserted on the query count, because the return value is the same either way
	 * -- which is exactly why this was invisible.
	 */
	public function test_get_results_caches_an_empty_result_set() {
		global $wpdb;

		$where = [
			[
				[
					'key'     => 'form_id',
					'compare' => '=',
					// An id nothing can match, so the result is genuinely empty.
					'value'   => 987654321,
				],
			],
		];

		$reset = new ReflectionMethod( $this->entries_table, 'cache_reset' );
		$reset->setAccessible( true );
		$reset->invoke( $this->entries_table );

		$first = $this->entries_table->get_results( $where );
		$this->assertSame( [], $first, 'Precondition: this lookup matches nothing.' );

		$after = $wpdb->num_queries;
		$again = $this->entries_table->get_results( $where );

		$this->assertSame( [], $again );
		$this->assertSame(
			$after,
			$wpdb->num_queries,
			'The second identical lookup must come from the cache, not the database.'
		);
	}

	// ---------------------------------------------------------------
	// use_delete
	// ---------------------------------------------------------------

	/**
	 * A delete invalidates the per-request query cache.
	 *
	 * use_insert() and use_update() both reset the cache; use_delete() did not,
	 * so a count or lookup already cached earlier in the request kept answering
	 * with the deleted row still in it. Asserted through get_total_count(),
	 * because that is the reader the stale answer actually came back from.
	 */
	public function test_use_delete_resets_the_query_cache() {
		// An id nothing else can match, so the count is ours alone.
		$form_id = 987654322;
		$where   = [
			[
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			],
		];

		$entry_id = $this->entries_table->use_insert(
			[
				'form_id'    => $form_id,
				'created_at' => current_time( 'mysql' ),
			]
		);

		$this->assertIsInt( $entry_id, 'Precondition: the row has to exist before it can be deleted.' );

		// Prime the cache with the answer the delete below must invalidate.
		$this->assertSame( 1, $this->entries_table->get_total_count( $where ) );

		$this->entries_table->use_delete( [ 'ID' => $entry_id ], [ '%d' ] );

		$this->assertSame(
			0,
			$this->entries_table->get_total_count( $where ),
			'The count must reflect the delete, not the cached pre-delete answer.'
		);
	}

	// ---------------------------------------------------------------
	// cache_reset
	// ---------------------------------------------------------------

	/**
	 * cache_reset() empties the per-instance query cache.
	 */
	public function test_cache_reset() {
		$set   = new ReflectionMethod( $this->entries_table, 'cache_set' );
		$get   = new ReflectionMethod( $this->entries_table, 'cache_get' );
		$reset = new ReflectionMethod( $this->entries_table, 'cache_reset' );

		foreach ( [ $set, $get, $reset ] as $method ) {
			$method->setAccessible( true );
		}

		$set->invoke( $this->entries_table, 'srfm_cache_reset_probe', 'stored' );
		$this->assertSame( 'stored', $get->invoke( $this->entries_table, 'srfm_cache_reset_probe' ) );

		$reset->invoke( $this->entries_table );

		$this->assertNull(
			$get->invoke( $this->entries_table, 'srfm_cache_reset_probe' ),
			'The cached value must be gone after a reset.'
		);
	}
}
