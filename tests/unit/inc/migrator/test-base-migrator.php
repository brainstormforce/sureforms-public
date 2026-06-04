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
	 * Base_Migrator::import_forms() — when `skip_existing` is true, a re-run
	 * over a source that was already imported routes the form to the
	 * `skipped` bucket instead of overwriting the SureForms post. This is
	 * the onboarding-step's safety net against silently destroying user
	 * edits on a second onboarding pass.
	 *
	 * @return void
	 */
	public function test_import_forms_respects_skip_existing() {
		$post_id  = $this->make_cf7( "Name\n[text* n]", 'Skip me' );
		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );
		$result = $importer->import_forms( [ $post_id ], false, [], true );
		$this->assertCount( 0, $result['imported'] );
		$this->assertCount( 1, $result['skipped'] );
		$this->assertSame( (string) $post_id, (string) $result['skipped'][0]['source_id'] );
	}

	/**
	 * Base_Migrator::import_forms() — an explicit per-form behavior entry
	 * still wins over `skip_existing`. The onboarding default is "be safe",
	 * but the Settings → Migration tab can opt back into overwrite on a
	 * per-form basis without the safety net surprising users.
	 *
	 * @return void
	 */
	public function test_skip_existing_yields_to_explicit_behavior() {
		$post_id  = $this->make_cf7( "Name\n[text* n]", 'Update wins' );
		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );
		$result = $importer->import_forms(
			[ $post_id ],
			false,
			[ (string) $post_id => 'update' ],
			true
		);
		$this->assertCount( 1, $result['imported'] );
		$this->assertCount( 0, $result['skipped'] );
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
	 * Base_Migrator::resolve_cl_bucket() — keeps a bucket when it supports the
	 * operator, down-buckets text-style operators to `default`, and returns ''
	 * when no bucket supports the operator (caller drops the rule).
	 *
	 * @return void
	 */
	public function test_resolve_cl_bucket() {
		$importer = $this->make_importer();
		$method   = new \ReflectionMethod( $importer, 'resolve_cl_bucket' );
		$method->setAccessible( true );

		// Operator valid for the field's own bucket → bucket preserved.
		$this->assertSame( 'list', $method->invoke( $importer, '==', 'list' ) );
		$this->assertSame( 'number', $method->invoke( $importer, '>', 'number' ) );
		$this->assertSame( 'default', $method->invoke( $importer, 'includes', 'default' ) );

		// Text-style operator on a list/number field → down-bucket to default.
		$this->assertSame( 'default', $method->invoke( $importer, 'includes', 'list' ) );
		$this->assertSame( 'default', $method->invoke( $importer, 'startWith', 'number' ) );

		// Operator no bucket (incl. default) supports → '' so the rule is dropped.
		$this->assertSame( '', $method->invoke( $importer, 'isSelected', 'default' ) );
	}

	/**
	 * Base_Migrator::count_source_forms() — returns the source-form count
	 * without resolving per-form import mappings.
	 *
	 * @return void
	 */
	public function test_count_source_forms() {
		$this->make_cf7( "Name\n[text* a]", 'Count A' );
		$this->make_cf7( "Name\n[text* b]", 'Count B' );
		$importer = $this->make_importer();
		$this->assertSame( count( $importer->list_forms() ), $importer->count_source_forms() );
		$this->assertGreaterThanOrEqual( 2, $importer->count_source_forms() );
	}

	/**
	 * Base_Migrator::get_imported_map() — memoizes the map: the first read hits
	 * the option, subsequent reads return the cached array within the request.
	 *
	 * @return void
	 */
	public function test_get_imported_map() {
		$importer = $this->make_importer();
		$method   = new \ReflectionMethod( $importer, 'get_imported_map' );
		$method->setAccessible( true );

		// Empty option → empty array.
		$this->assertSame( [], $method->invoke( $importer ) );

		// After an import the map is populated and readable via the helper.
		$post_id = $this->make_cf7( "Name\n[text* n]", 'Mapped' );
		$importer->import_forms( [ $post_id ], false );
		$map = $method->invoke( $importer );
		$this->assertIsArray( $map );
		$this->assertNotEmpty( $map );
	}

	/**
	 * Base_Migrator::save_imported_map() — persists the map to the option and
	 * refreshes the request cache so get_imported_map() reflects the write.
	 *
	 * @return void
	 */
	public function test_save_imported_map() {
		$importer = $this->make_importer();
		$save     = new \ReflectionMethod( $importer, 'save_imported_map' );
		$save->setAccessible( true );
		$get = new \ReflectionMethod( $importer, 'get_imported_map' );
		$get->setAccessible( true );

		$payload = [ 123 => [ 'source_id' => '7', 'source_key' => 'cf7' ] ];
		$save->invoke( $importer, $payload );

		// Written to the option…
		$this->assertSame( $payload, get_option( Base_Migrator::IMPORTED_MAP_OPTION ) );
		// …and the request cache returns the same payload without a fresh read.
		$this->assertSame( $payload, $get->invoke( $importer ) );
	}

	/**
	 * Base_Migrator::apply_form_id_to_blocks() — injects `formId` as the first
	 * attribute of every srfm block (so SureForms can resolve conditional-logic
	 * classes at render) without disturbing the existing JSON_HEX-escaped attrs.
	 *
	 * @return void
	 */
	public function test_apply_form_id_to_blocks() {
		$importer = $this->make_importer();
		$method   = new \ReflectionMethod( $importer, 'apply_form_id_to_blocks' );
		$method->setAccessible( true );

		// A srfm block whose label carries JSON_HEX-escaped markup, exactly as the
		// Block_Templates emitters produce it ("<" serialised as the literal
		// six-character sequence <, not a raw "<").
		$markup = '<!-- wp:srfm/input {"block_id":"abc12345","label":"\u003Cb\u003EName"} /-->'
			. "\n" . '<!-- wp:columns --><div class="wp-block-columns"></div><!-- /wp:columns -->';

		$out = (string) $method->invoke( $importer, $markup, 728 );

		// formId injected as the first attribute on the srfm block.
		$this->assertStringContainsString( '<!-- wp:srfm/input {"formId":"728","block_id":"abc12345"', $out );
		// Existing JSON_HEX escaping is preserved (the < sequence is untouched,
		// never decoded to a raw "<" that could break the block delimiter).
		$this->assertStringContainsString( '\u003Cb\u003EName', $out );
		$this->assertStringNotContainsString( '<b>Name', $out );
		// Non-srfm blocks (core/columns) are left untouched.
		$this->assertStringContainsString( '<!-- wp:columns -->', $out );
		// Guard: invalid form id returns the markup unchanged.
		$this->assertSame( $markup, (string) $method->invoke( $importer, $markup, 0 ) );
	}
}
