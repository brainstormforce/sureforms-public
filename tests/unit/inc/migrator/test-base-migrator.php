<?php
/**
 * Class Test_Base_Migrator
 *
 * Smoke tests for each non-abstract method on Base_Migrator, exercised
 * through Cf7_Importer (the concrete subclass) plus the test subclass that
 * forces `exist() === true`. Function names follow the project's
 * coverage-naming convention so the `check-test-coverage` action recognises
 * them as covering each method.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Base_Migrator;
use SRFM\Inc\Migrator\Importers\Cf7_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Base_Migrator extends TestCase {

	/**
	 * Build a Cf7_Importer subclass with `exist()` stubbed true.
	 *
	 * @return Cf7_Importer
	 */
	private function make_importer() {
		return new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}
		};
	}

	/**
	 * Helper — create a CF7 form post with a given template + title.
	 *
	 * @param string $template CF7 shortcode template.
	 * @param string $title    Form title.
	 * @return int Post id.
	 */
	private function make_cf7( $template, $title = 'BM Form' ) {
		$id = wp_insert_post(
			[
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);
		update_post_meta( $id, '_form', $template );
		return (int) $id;
	}

	/**
	 * Tear down — purge fixture posts + the importer mapping option.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( Base_Migrator::IMPORTED_MAP_OPTION );
		foreach ( [ SRFM_FORMS_POST_TYPE, 'wpcf7_contact_form' ] as $pt ) {
			$ids = get_posts(
				[
					'post_type'      => $pt,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]
			);
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
		}
		parent::tear_down();
	}

	/**
	 * Base_Migrator::get_key() — accessor for the protected $key set by subclasses.
	 *
	 * @return void
	 */
	public function test_get_key() {
		$this->assertSame( 'cf7', ( new Cf7_Importer() )->get_key() );
	}

	/**
	 * Base_Migrator::get_title() — accessor for the protected $title.
	 *
	 * @return void
	 */
	public function test_get_title() {
		$this->assertSame( 'Contact Form 7', ( new Cf7_Importer() )->get_title() );
	}

	/**
	 * Base_Migrator::list_forms() — enumerates source forms with import status.
	 *
	 * @return void
	 */
	public function test_list_forms() {
		$post_id = $this->make_cf7( "Name\n[text* n]", 'Listed' );
		$listing = $this->make_importer()->list_forms();
		$this->assertIsArray( $listing );
		$ids = array_map( static fn( $r ) => (int) ( $r['id'] ?? 0 ), $listing );
		$this->assertContains( $post_id, $ids );
	}

	/**
	 * Base_Migrator::import_forms() — full import pipeline; returns the
	 * imported / skipped / failed / unsupported_fields result shape.
	 *
	 * @return void
	 */
	public function test_import_forms() {
		$post_id = $this->make_cf7( "Name\n[text* your-name]" );
		$result  = $this->make_importer()->import_forms( [ $post_id ], false );
		$this->assertArrayHasKey( 'imported', $result );
		$this->assertArrayHasKey( 'skipped', $result );
		$this->assertArrayHasKey( 'failed', $result );
		$this->assertArrayHasKey( 'unsupported_fields', $result );
		$this->assertCount( 1, $result['imported'] );
	}

	/**
	 * Base_Migrator::insert_form_post() — exercised through import_forms; it
	 * creates a fresh sureforms_form post when no prior import is recorded.
	 *
	 * @return void
	 */
	public function test_insert_form_post() {
		$post_id = $this->make_cf7( "Name\n[text* n]", 'Insert me' );
		$before  = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0;
		$this->make_importer()->import_forms( [ $post_id ], false );
		$after = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0;
		$this->assertSame( (int) $before + 1, (int) $after );
	}

	/**
	 * Base_Migrator::update_form_post() — a second import of the same source
	 * with default `update` behaviour overwrites the existing post instead of
	 * inserting a new one.
	 *
	 * @return void
	 */
	public function test_update_form_post() {
		$post_id  = $this->make_cf7( "Name\n[text* n]", 'Update me' );
		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );
		$count_after_first = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0;
		$importer->import_forms( [ $post_id ], false );
		$count_after_second = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0;
		$this->assertSame( (int) $count_after_first, (int) $count_after_second );
	}

	/**
	 * Base_Migrator::find_existing_srfm_id() — after an import, list_forms
	 * returns `imported_srfm_id` > 0 for the just-imported source form.
	 *
	 * @return void
	 */
	public function test_find_existing_srfm_id() {
		$post_id  = $this->make_cf7( "Name\n[text* n]", 'Find me' );
		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );
		$listing = $importer->list_forms();
		$row     = array_values( array_filter( $listing, static fn( $r ) => (int) $r['id'] === $post_id ) )[0] ?? null;
		$this->assertNotNull( $row );
		$this->assertGreaterThan( 0, (int) ( $row['imported_srfm_id'] ?? 0 ) );
	}

	/**
	 * Base_Migrator::record_import_mapping() — populates the project-wide
	 * `srfm_imported_forms_map` option with the new mapping.
	 *
	 * @return void
	 */
	public function test_record_import_mapping() {
		$post_id = $this->make_cf7( "Name\n[text* n]", 'Map me' );
		$this->make_importer()->import_forms( [ $post_id ], false );
		$map = get_option( Base_Migrator::IMPORTED_MAP_OPTION );
		$this->assertIsArray( $map );
		$this->assertNotEmpty( $map );
		$first = (array) reset( $map );
		$this->assertSame( (string) $post_id, (string) ( $first['source_id'] ?? '' ) );
		$this->assertSame( 'cf7', $first['source_key'] ?? '' );
	}

	/**
	 * Base_Migrator::note_unsupported() — file/quiz/captcha tags get reported
	 * on the result `unsupported_fields` array.
	 *
	 * @return void
	 */
	public function test_note_unsupported() {
		$post_id = $this->make_cf7(
			"Email\n[email* e]\nResume\n[file your-file]",
			'With file'
		);
		$result = $this->make_importer()->import_forms( [ $post_id ], true );
		$this->assertNotEmpty( $result['unsupported_fields'] );
	}
}
