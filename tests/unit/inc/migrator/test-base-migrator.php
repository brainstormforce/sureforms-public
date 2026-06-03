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

	/**
	 * Base_Migrator::reserve_slug() — deduplicates colliding slugs within
	 * the same form by appending `-2`, `-3`, … and preserves a base name
	 * even when the seed sanitizes to an empty string.
	 *
	 * @return void
	 */
	public function test_reserve_slug() {
		$probe = new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}
			/**
			 * @param string $seed Seed.
			 * @return string
			 */
			public function reserve_public( $seed ) {
				return $this->reserve_slug( $seed );
			}
		};
		$this->assertSame( 'name', $probe->reserve_public( 'Name' ) );
		$this->assertSame( 'name-2', $probe->reserve_public( 'Name' ) );
		$this->assertSame( 'name-3', $probe->reserve_public( 'name' ) );
		$this->assertSame( 'field', $probe->reserve_public( '!@#$%' ) );
	}

	/**
	 * Base_Migrator::default_confirmation_message() — returns centered
	 * heading + body HTML with the canonical "Thank you" copy.
	 *
	 * @return void
	 */
	public function test_default_confirmation_message() {
		$probe = new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}
			/**
			 * @return string
			 */
			public function confirmation_public() {
				return $this->default_confirmation_message();
			}
		};
		$out = $probe->confirmation_public();
		$this->assertStringContainsString( 'Thank you', $out );
		$this->assertStringContainsString( 'text-align: center', $out );
		$this->assertStringContainsString( '<h2', $out );
	}

	/**
	 * Base_Migrator::dispatch_template() — known method names produce the
	 * corresponding `srfm/*` block markup; unknown names return an empty
	 * string so the caller can give filter subscribers a chance.
	 *
	 * @return void
	 */
	public function test_dispatch_template() {
		$probe = new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}
			/**
			 * @param string              $m Method name.
			 * @param array<string,mixed> $a Args.
			 * @return string
			 */
			public function dispatch_public( $m, array $a ) {
				return $this->dispatch_template( $m, $a );
			}
		};
		$this->assertStringContainsString( 'wp:srfm/input', $probe->dispatch_public( 'input', [ 'label' => 'X' ] ) );
		$this->assertStringContainsString( 'wp:srfm/email', $probe->dispatch_public( 'email', [ 'label' => 'X' ] ) );
		$this->assertStringContainsString( 'wp:srfm/dropdown', $probe->dispatch_public( 'dropdown', [ 'label' => 'X', 'options' => [ 'A' ] ] ) );
		$this->assertSame( '', $probe->dispatch_public( 'mystery_widget', [] ) );
	}

	/**
	 * The shared CL operator allowlist gates operators per bucket — the single
	 * source of truth every importer's map_operator() validates against.
	 *
	 * @return void
	 */
	public function test_cl_operator_allowed_gates_per_bucket() {
		$probe = new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}
			/**
			 * @param string $operator Operator slug.
			 * @param string $bucket   Block-type bucket.
			 * @return bool
			 */
			public function cl_allowed_public( $operator, $bucket ) {
				return $this->cl_operator_allowed( $operator, $bucket );
			}
		};
		// Valid combinations.
		$this->assertTrue( $probe->cl_allowed_public( 'includes', 'default' ) );
		$this->assertTrue( $probe->cl_allowed_public( '>', 'number' ) );
		$this->assertTrue( $probe->cl_allowed_public( 'isSelected', 'list' ) );
		$this->assertTrue( $probe->cl_allowed_public( 'datePickerIs', 'datepicker' ) );
		// Invalid combinations — these are what map_operator() drops.
		$this->assertFalse( $probe->cl_allowed_public( '>', 'default' ) );
		$this->assertFalse( $probe->cl_allowed_public( 'includes', 'list' ) );
		$this->assertFalse( $probe->cl_allowed_public( '==', 'datepicker' ) );
		// Unknown bucket falls back to the default set.
		$this->assertTrue( $probe->cl_allowed_public( '==', 'mystery_bucket' ) );
		$this->assertFalse( $probe->cl_allowed_public( 'in', 'mystery_bucket' ) );
	}
}
