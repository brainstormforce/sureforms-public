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
	 * Test build_where_conditions search includes submitted form data (form_data LIKE).
	 *
	 * Regression: search previously matched only numeric entry IDs and form titles —
	 * form_data was never in the WHERE, so text a respondent submitted could never be
	 * found; a non-numeric term matching no form title even forced an always-empty
	 * `ID = 0` condition.
	 */
	public function test_build_where_conditions_search_includes_form_data() {
		$args   = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => 'jane@example.com',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );

		$found_form_data_like = false;
		$found_forced_empty   = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( ! is_array( $condition ) || ! isset( $condition['key'] ) ) {
					continue;
				}
				if ( 'form_data' === $condition['key'] && 'LIKE' === $condition['compare'] ) {
					$found_form_data_like = true;
					$this->assertSame( 'jane@example.com', $condition['value'] );
				}
				if ( 'ID' === $condition['key'] && '=' === $condition['compare'] && 0 === $condition['value'] ) {
					$found_forced_empty = true;
				}
			}
		}

		$this->assertTrue( $found_form_data_like, 'Search must include a form_data LIKE condition.' );
		$this->assertFalse( $found_forced_empty, 'Non-numeric search must not force an empty (ID = 0) result.' );
	}

	/**
	 * Test build_where_conditions numeric search matches both entry ID and form data.
	 *
	 * A numeric term can be submitted data too (phone number, zip code), so it must
	 * produce the form_data LIKE condition alongside the exact ID match.
	 */
	public function test_build_where_conditions_numeric_search_includes_form_data() {
		$args   = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '9876543210',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );

		$found_id_match  = false;
		$found_form_data = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( ! is_array( $condition ) || ! isset( $condition['key'] ) ) {
					continue;
				}
				if ( 'ID' === $condition['key'] && '=' === $condition['compare'] ) {
					$found_id_match = true;
				}
				if ( 'form_data' === $condition['key'] && 'LIKE' === $condition['compare'] ) {
					$found_form_data = true;
				}
			}
		}

		$this->assertTrue( $found_id_match );
		$this->assertTrue( $found_form_data );
	}

	/**
	 * Test build_where_conditions skips form_data LIKE for short text terms.
	 *
	 * The LIKE cannot use an index, so text terms under 3 characters must not trigger
	 * the form_data scan; with no other usable condition the search must force an
	 * empty result (never fall through to returning all entries).
	 */
	public function test_build_where_conditions_short_text_search_skips_form_data() {
		$args   = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => 'ab',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );

		$found_form_data    = false;
		$found_forced_empty = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( ! is_array( $condition ) || ! isset( $condition['key'] ) ) {
					continue;
				}
				if ( 'form_data' === $condition['key'] ) {
					$found_form_data = true;
				}
				if ( 'ID' === $condition['key'] && '=' === $condition['compare'] && 0 === $condition['value'] ) {
					$found_forced_empty = true;
				}
			}
		}

		$this->assertFalse( $found_form_data, 'Terms under 3 characters must not scan form_data.' );
		$this->assertTrue( $found_forced_empty, 'A short term with no other match must force an empty result.' );
	}

	/**
	 * Test build_where_conditions short numeric term still searches by entry ID.
	 */
	public function test_build_where_conditions_short_numeric_search_keeps_id_match() {
		$args   = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '55',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );

		$found_id_match  = false;
		$found_form_data = false;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( ! is_array( $condition ) || ! isset( $condition['key'] ) ) {
					continue;
				}
				if ( 'ID' === $condition['key'] && '=' === $condition['compare'] && 55 === $condition['value'] ) {
					$found_id_match = true;
				}
				if ( 'form_data' === $condition['key'] ) {
					$found_form_data = true;
				}
			}
		}

		$this->assertTrue( $found_id_match, 'Short numeric terms must still match the entry ID.' );
		$this->assertFalse( $found_form_data, 'Short numeric terms must not scan form_data.' );
	}

	/**
	 * Test build_where_conditions escapes LIKE wildcards in the search term.
	 *
	 * The query compiler wraps the value in "%...%" itself; user-typed "%" and "_"
	 * must be escaped so they match literally instead of acting as wildcards.
	 */
	public function test_build_where_conditions_search_escapes_like_wildcards() {
		global $wpdb;

		$args   = [
			'form_id'   => 0,
			'status'    => 'all',
			'search'    => '100%_done',
			'date_from' => '',
			'date_to'   => '',
			'entry_ids' => [],
		];
		$result = $this->call_private_method_static( Entries::class, 'build_where_conditions', [ $args ] );

		$like_value = null;
		foreach ( $result as $group ) {
			foreach ( $group as $condition ) {
				if ( is_array( $condition ) && isset( $condition['key'] ) && 'form_data' === $condition['key'] ) {
					$like_value = $condition['value'];
				}
			}
		}

		$this->assertSame( $wpdb->esc_like( '100%_done' ), $like_value );
	}

	/**
	 * Test write_csv_header neutralizes formula injection in column labels.
	 *
	 * Labels are decoded out of stored form_data keys, so an anonymous submitter can
	 * influence them — see #2996. Data cells were already escaped; the header was not.
	 */
	public function test_write_csv_header_escapes_formula_in_labels() {
		$stream = fopen( 'php://temp', 'r+' );

		$this->call_private_method_static(
			Entries::class,
			'write_csv_header',
			[ $stream, [ 'abc123' => "=cmd|'/c calc'!A1" ] ]
		);

		rewind( $stream );
		$csv = stream_get_contents( $stream );
		fclose( $stream );

		// Parse the row back rather than substring-matching: the payload contains a
		// space, so fputcsv() quotes the field either way and a naive ",=cmd" negative
		// assertion would pass even with the fix reverted.
		$cells = str_getcsv( trim( $csv ) );

		$this->assertSame( "'=cmd|'/c calc'!A1", end( $cells ), 'Formula label must be quote-prefixed in the header cell.' );
	}

	/**
	 * Test escape_csv_formula neutralizes each dangerous leading character and leaves
	 * ordinary and numeric values alone.
	 *
	 * Now public so Pro's separate partial-entries writer can share it instead of
	 * carrying its own weaker copy.
	 */
	public function test_escape_csv_formula() {
		foreach ( [ '=', '+', '-', '@', "\t", "\r" ] as $trigger ) {
			$this->assertSame(
				"'" . $trigger . 'HYPERLINK("http://evil")',
				Entries::escape_csv_formula( $trigger . 'HYPERLINK("http://evil")' ),
				'Leading ' . wp_json_encode( $trigger ) . ' must be neutralized.'
			);
		}

		// Ordinary text is untouched.
		$this->assertSame( 'Full Name', Entries::escape_csv_formula( 'Full Name' ) );
		$this->assertSame( '', Entries::escape_csv_formula( '' ) );

		// Numeric columns must stay numeric in the spreadsheet.
		foreach ( [ '42', '-5', '3.14', '-0.5', '1e3' ] as $number ) {
			$this->assertSame( $number, Entries::escape_csv_formula( $number ), $number . ' must remain numeric.' );
		}
	}

	/**
	 * Test write_csv_header leaves ordinary labels untouched.
	 */
	public function test_write_csv_header_keeps_plain_labels_unchanged() {
		$stream = fopen( 'php://temp', 'r+' );

		$this->call_private_method_static(
			Entries::class,
			'write_csv_header',
			[ $stream, [ 'abc123' => 'Full Name' ] ]
		);

		rewind( $stream );
		$csv = stream_get_contents( $stream );
		fclose( $stream );

		$this->assertStringContainsString( 'Full Name', $csv );
		$this->assertStringNotContainsString( "'Full Name", $csv );
	}

	/**
	 * Export must keep a field's value in its column even after the field's label
	 * or slug changed, because that rewrites the storage key while the block ID
	 * stays the same. Old entries are stored under the earlier key variant, so a
	 * column read with a single frozen key dropped them to blank — see #1574731.
	 *
	 * One block ID stored under two label variants must still produce one column
	 * with both entries' values, not a column that only carries the last variant.
	 */
	public function test_write_csv_rows_keeps_values_across_label_variants() {
		$label = static function ( $text ) {
			return rtrim( base64_encode( $text ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Matches the plugin's own field-key label encoding.
		};

		// Same block ID (77b939be), two label variants of the "llamada" checkbox.
		$key_old = 'srfm-checkbox-77b939be-lbl-' . $label( 'Old label' ) . '-llamada';
		$key_new = 'srfm-checkbox-77b939be-lbl-' . $label( 'New label' ) . '-llamada';

		$results = [
			[
				'ID'         => 31,
				'created_at' => '2026-05-14 10:00:00',
				'status'     => 'read',
				'form_data'  => [ $key_old => 'Yes' ],
			],
			[
				'ID'         => 695,
				'created_at' => '2026-09-29 10:00:00',
				'status'     => 'read',
				'form_data'  => [ $key_new => 'Ticked' ],
			],
		];

		$block_data = $this->call_private_method_static( Entries::class, 'build_block_key_map_and_labels', [ $results ] );

		$stream = fopen( 'php://temp', 'r+' );
		$this->call_private_method_static( Entries::class, 'write_csv_rows', [ $stream, $results, $block_data['map'] ] );
		rewind( $stream );
		$csv = stream_get_contents( $stream );
		fclose( $stream );

		$rows = array_map( 'str_getcsv', array_filter( explode( "\n", trim( $csv ) ) ) );

		// Both variants collapse to a single value column (Entry ID, Date, Status, value).
		$this->assertCount( 4, $rows[0], 'The two label variants of one block must share one column.' );

		$values = [];
		foreach ( $rows as $row ) {
			$values[ $row[0] ] = $row[3];
		}

		$this->assertSame( 'Yes', $values['31'], 'A value stored under the earlier label variant must still export.' );
		$this->assertSame( 'Ticked', $values['695'], 'A value stored under the later label variant must still export.' );
	}

	/**
	 * Create a published form with one entry whose single field holds $value.
	 *
	 * @param string $title Form title.
	 * @param string $value Field value written to the entry.
	 * @return int Entry ID.
	 */
	private function make_form_entry( $title, $value ) {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);

		return (int) \SRFM\Inc\Database\Tables\Entries::add(
			[
				'form_id'   => $form_id,
				'form_data' => [ 'srfm-input-abc12345-lbl-' . rtrim( base64_encode( 'Name' ), '=' ) . '-input' => $value ],
			]
		);
	}

	/**
	 * Two forms whose titles sanitize identically each keep their own CSV member
	 * and rows inside the ZIP.
	 */
	public function test_export_entries_keeps_same_titled_forms_separate() {
		$entry_a = $this->make_form_entry( 'Contact', 'MARKER_A' );
		$entry_b = $this->make_form_entry( 'Contact', 'MARKER_B' );

		$result = Entries::export_entries( [ 'entry_ids' => [ $entry_a, $entry_b ] ] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'zip', $result['type'] );

		$zip = new \ZipArchive();
		$this->assertTrue( $zip->open( $result['filepath'] ) );

		$members = [];
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name             = $zip->getNameIndex( $i );
			$members[ $name ] = $zip->getFromName( $name );
		}
		$zip->close();
		wp_delete_file( $result['filepath'] );

		$this->assertCount( 2, $members, 'Each form needs its own CSV member.' );
		$all = implode( "\n", $members );
		$this->assertStringContainsString( 'MARKER_A', $all );
		$this->assertStringContainsString( 'MARKER_B', $all );
	}

	/**
	 * A second export of the same form must not replace the file of an earlier,
	 * not-yet-downloaded export.
	 */
	public function test_export_entries_does_not_reuse_an_earlier_export_file() {
		$entry_a = $this->make_form_entry( 'Survey', 'MARKER_A' );
		$first   = Entries::export_entries( [ 'entry_ids' => [ $entry_a ] ] );

		$entry_b = $this->make_form_entry( 'Survey', 'MARKER_B' );
		$second  = Entries::export_entries( [ 'entry_ids' => [ $entry_b ] ] );

		$this->assertTrue( $first['success'] );
		$this->assertTrue( $second['success'] );
		$this->assertNotSame( $first['filepath'], $second['filepath'] );
		$this->assertStringContainsString( 'MARKER_A', (string) file_get_contents( $first['filepath'] ) );
		$this->assertStringContainsString( 'MARKER_B', (string) file_get_contents( $second['filepath'] ) );

		wp_delete_file( $first['filepath'] );
		wp_delete_file( $second['filepath'] );
	}

	/**
	 * Export paths are unique per call, inside the temp dir, and survive the
	 * sanitize_file_name() the download handler applies.
	 */
	public function test_get_export_temp_filepath() {
		$temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) );
		$first    = Entries::get_export_temp_filepath( 'srfm-entries-%d0%bf', 'csv' );
		$second   = Entries::get_export_temp_filepath( 'srfm-entries-%d0%bf', 'csv' );

		$this->assertNotSame( $first, $second );
		$this->assertSame( 0, strpos( $first, $temp_dir ) );
		$this->assertSame( basename( $first ), sanitize_file_name( basename( $first ) ) );
		$this->assertMatchesRegularExpression( '/^srfm-entries-d0bf-[a-f0-9]{32}\.csv$/', basename( $first ) );
	}

	/**
	 * The per-export token is dropped from the name the browser saves.
	 */
	public function test_get_export_download_filename() {
		$token = str_repeat( 'a1', 16 );

		$this->assertSame( 'srfm-entries-contact.csv', Entries::get_export_download_filename( "srfm-entries-contact-{$token}.csv" ) );
		$this->assertSame( 'srfm-partial-entries-contact.csv', Entries::get_export_download_filename( "srfm-partial-entries-contact-{$token}.csv" ) );
		// Names without a token (older callers) are served unchanged.
		$this->assertSame( 'srfm-entries-event-registration.csv', Entries::get_export_download_filename( 'srfm-entries-event-registration.csv' ) );
	}

	/**
	 * open_export_zip() returns null when ZipArchive::open() reports an error
	 * code, and never reuses an existing archive.
	 */
	public function test_open_export_zip() {
		$path = Entries::get_export_temp_filepath( 'srfm-entries-export', 'zip' );

		$zip = Entries::open_export_zip( $path );
		$this->assertInstanceOf( \ZipArchive::class, $zip );
		$zip->addFromString( 'a.csv', 'a' );
		$zip->close();

		$this->assertNull( Entries::open_export_zip( $path ), 'An existing archive must not be reopened.' );
		wp_delete_file( $path );
	}

	/**
	 * Abandoned export files older than a day are removed; fresh exports and
	 * files this plugin did not name are left alone.
	 */
	public function test_delete_stale_export_files() {
		$stale = Entries::get_export_temp_filepath( 'srfm-entries-contact', 'csv' );
		$fresh = Entries::get_export_temp_filepath( 'srfm-entries-contact', 'csv' );
		$other = wp_normalize_path( trailingslashit( get_temp_dir() ) ) . 'srfm-entries-unrelated.csv';

		foreach ( [ $stale, $fresh, $other ] as $file ) {
			file_put_contents( $file, 'x' );
		}
		touch( $stale, time() - DAY_IN_SECONDS - 60 );
		touch( $other, time() - DAY_IN_SECONDS - 60 );

		Entries::delete_stale_export_files();

		$this->assertFileDoesNotExist( $stale );
		$this->assertFileExists( $fresh );
		$this->assertFileExists( $other );

		wp_delete_file( $fresh );
		wp_delete_file( $other );
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
