<?php
/**
 * Class Test_Cf7_Importer
 *
 * Covers the load-bearing invariants of the CF7 importer:
 *  1. Per-tag parser output for every CF7 tag the importer claims to support.
 *  2. XSS regression — labels containing `-->` must not break out of the block
 *     comment delimiter (see block-templates.php encode_attrs flags).
 *  3. Idempotency — re-importing the same source form updates the existing
 *     SureForms post; the imported-map option grows by 1, not 2.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/class-cf7-importer-testable.php';

class Test_Cf7_Importer extends TestCase {
	/**
	 * Build a test-only Cf7_Importer subclass.
	 *
	 * @return Cf7_Importer_Testable
	 */
	private function make_importer() {
		return new Cf7_Importer_Testable();
	}

	/**
	 * Helper — create a fake CF7 post with the given _form shortcode template.
	 *
	 * @param string $template CF7 shortcode template.
	 * @param string $title    Optional CF7 form title.
	 * @return int Post id.
	 */
	private function create_cf7_form( $template, $title = 'Test Form' ) {
		$post_id = wp_insert_post(
			[
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);
		update_post_meta( $post_id, '_form', $template );
		return (int) $post_id;
	}

	/**
	 * Extract a single imported srfm_id from import_forms() result.
	 *
	 * @param array<string,mixed> $result Import result.
	 * @return int
	 */
	private function first_srfm_id( array $result ) {
		if ( ! isset( $result['imported'] ) || ! is_array( $result['imported'] ) ) {
			return 0;
		}
		$first = reset( $result['imported'] );
		if ( ! is_array( $first ) || ! isset( $first['srfm_id'] ) ) {
			return 0;
		}
		return (int) $first['srfm_id'];
	}

	/**
	 * Extract the first preview markup string from a dry-run result.
	 *
	 * @param array<string,mixed> $result Import result.
	 * @return string
	 */
	private function first_preview( array $result ) {
		if ( ! isset( $result['preview'] ) || ! is_array( $result['preview'] ) ) {
			return '';
		}
		$preview = reset( $result['preview'] );
		return is_string( $preview ) ? $preview : '';
	}

	/**
	 * Tear down — purge sureforms_form and wpcf7_contact_form posts plus the
	 * importer mapping option so each test starts from a clean slate.
	 *
	 * This TestCase (Yoast polyfill base) does NOT use WP_UnitTestCase
	 * transactions, so the cleanup has to be explicit.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( 'srfm_imported_forms_map' );
		$post_types = [ SRFM_FORMS_POST_TYPE, 'wpcf7_contact_form' ];
		foreach ( $post_types as $post_type ) {
			$ids = get_posts(
				[
					'post_type'      => $post_type,
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
	 * Parser — `text` tag emits an srfm/input block with the label as field label.
	 *
	 * @return void
	 */
	public function test_parser_text_tag_emits_input_block() {
		$importer = $this->make_importer();
		$post_id  = $this->create_cf7_form( "Your Name\n[text* your-name placeholder \"Jane Doe\"]" );

		$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Test Form' ] );

		$this->assertStringContainsString( 'wp:srfm/input', $content );
		$this->assertStringContainsString( '"label":"Your Name"', $content );
		$this->assertStringContainsString( '"required":true', $content );
		$this->assertStringContainsString( 'Jane Doe', $content );
	}

	/**
	 * Parser — CF7 templates often wrap rows in `<p>...</p>`. The HTML wrapper
	 * must be stripped from the field label so the editor doesn't render a
	 * literal "<p>" prefix.
	 *
	 * @return void
	 */
	public function test_parser_strips_html_wrapper_from_label() {
		$importer = $this->make_importer();
		$post_id  = $this->create_cf7_form( "<p>Your Name (required)\n[text* your-name]</p>" );

		$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'HTML Wrap' ] );

		$this->assertStringContainsString( '"label":"Your Name (required)"', $content );
		// JSON_HEX_TAG escapes `<` as < — the encoded form must not leak.
		$this->assertStringNotContainsString( '<p>', $content, 'HTML wrapper must be stripped from label before JSON encoding.' );
	}

	/**
	 * After insert, the stored post_content must still contain valid JSON
	 * unicode escapes (e.g. `<` for `<`), NOT a slash-stripped `u003C`.
	 *
	 * wp_insert_post applies wp_unslash to post_content; the migrator must
	 * wp_slash() the markup first or every JSON escape gets corrupted —
	 * imported forms then open as empty placeholders in Gutenberg.
	 *
	 * Uses the acceptance tag, whose quoted text becomes the label verbatim
	 * (no HTML stripping), so we can plant a `<` for the encoder to escape.
	 *
	 * @return void
	 */
	public function test_stored_post_content_preserves_unicode_escapes() {
		$post_id  = $this->create_cf7_form( '[acceptance accept-this "I agree <click>"]' );
		$importer = $this->make_importer();

		$importer->import_forms( [ $post_id ], false );

		$srfm_posts = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
			]
		);
		$this->assertNotEmpty( $srfm_posts );
		$content = $srfm_posts[0]->post_content;

		$this->assertStringContainsString(
			'<',
			$content,
			'JSON unicode escape backslash was stripped from stored post_content — markup must be wp_slash()ed before wp_insert_post.'
		);
		$this->assertStringNotContainsString(
			'u003C',
			$content,
			'Unicode escape lost its backslash — wp_unslash ate it.'
		);
	}

	/**
	 * Parser — `email`, `url`, `tel`, `number`, `textarea`, `date` each map to
	 * their expected srfm block.
	 *
	 * @return void
	 */
	public function test_parser_each_supported_tag_emits_correct_block() {
		$cases = [
			'Email'    => [ "Email\n[email* your-email]", 'wp:srfm/email' ],
			'URL'      => [ "Website\n[url your-url]", 'wp:srfm/url' ],
			'Phone'    => [ "Phone\n[tel your-tel]", 'wp:srfm/phone' ],
			'Number'   => [ "Age\n[number your-age min:1 max:120]", 'wp:srfm/number' ],
			'Textarea' => [ "Message\n[textarea* your-message]", 'wp:srfm/textarea' ],
			'Select'   => [ "Country\n[select your-country \"USA\" \"Canada\"]", 'wp:srfm/dropdown' ],
			'Checkbox' => [ "Topics\n[checkbox topics \"News\" \"Updates\"]", 'wp:srfm/checkbox' ],
			'Radio'    => [ "Gender\n[radio gender \"Male\" \"Female\"]", 'wp:srfm/multi-choice' ],
			'GDPR'     => [ '[acceptance accept-this "I agree"]', 'wp:srfm/gdpr' ],
		];

		$importer = $this->make_importer();
		foreach ( $cases as $name => $case ) {
			$template       = $case[0];
			$expected_block = $case[1];
			$post_id        = $this->create_cf7_form( $template, "Form: {$name}" );
			$content        = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => "Form: {$name}" ] );
			$this->assertStringContainsString(
				$expected_block,
				$content,
				"Expected block {$expected_block} for tag {$name}"
			);
		}
	}

	/**
	 * Parser — unsupported tags (file, captchar) are tracked, not silently dropped.
	 *
	 * @return void
	 */
	public function test_parser_unsupported_tags_are_reported() {
		$post_id  = $this->create_cf7_form(
			"Email\n[email* your-email]\nUpload\n[file your-file]"
		);
		$importer = $this->make_importer();

		$result = $importer->import_forms( [ $post_id ], true );

		$this->assertNotEmpty( $this->first_preview( $result ), 'Dry-run preview should contain the imported form markup.' );
		$this->assertContains( 'Upload', $result['unsupported_fields'], 'File upload field should be reported as unsupported.' );
	}

	/**
	 * Parser — submit tag's quoted label is captured and used on the submit button.
	 *
	 * @return void
	 */
	public function test_parser_submit_label_is_used() {
		$post_id  = $this->create_cf7_form( "[email* your-email]\n[submit \"Send it\"]" );
		$importer = $this->make_importer();

		$result  = $importer->import_forms( [ $post_id ], true );
		$preview = $this->first_preview( $result );
		$this->assertStringContainsString( 'Send it', $preview );
	}

	/**
	 * XSS regression — a CF7 label containing the HTML comment terminator
	 * `-->` must not appear literally in the emitted block markup. JSON_HEX_TAG
	 * in encode_attrs() must escape `>` so the block-comment delimiter cannot
	 * be broken out of.
	 *
	 * If this test fails, attacker-controlled CF7 content can inject HTML into
	 * the SureForms editor.
	 *
	 * @return void
	 */
	public function test_xss_label_with_comment_terminator_is_escaped() {
		$malicious = 'Bad --> <script>alert(1)</script> end';
		$post_id   = $this->create_cf7_form( "{$malicious}\n[text* your-name]" );

		$importer = $this->make_importer();
		$result   = $importer->import_forms( [ $post_id ], true );
		$markup   = $this->first_preview( $result );

		$this->assertNotEmpty( $markup, 'Expected a preview markup string.' );

		// The attacker payload must not appear verbatim — both the comment terminator
		// and the script tag must be JSON-escaped (`>`, `<`) by encode_attrs().
		$this->assertStringNotContainsString(
			'Bad --> ',
			$markup,
			'Comment terminator from CF7 label leaked into block markup — JSON_HEX_TAG escaping is missing.'
		);
		$this->assertStringNotContainsString(
			'<script>',
			$markup,
			'Raw <script> tag from CF7 label must not appear in emitted block markup.'
		);

		// Strip the legitimate block-comment delimiters (opener `<!-- wp:... -->`,
		// self-closer `<!-- wp:... /-->`, and closer `<!-- /wp:... -->`) and
		// confirm no stray `-->` survives from the attacker payload.
		$without_legit = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', '', $markup );
		$this->assertStringNotContainsString(
			'-->',
			(string) $without_legit,
			'Comment terminator survived outside legitimate block delimiters.'
		);
	}

	/**
	 * Idempotency — importing the same CF7 form twice must update the existing
	 * SureForms post rather than creating a duplicate. The imported-map option
	 * must grow by 1, not 2.
	 *
	 * This is the load-bearing claim of the entire PR.
	 *
	 * @return void
	 */
	public function test_reimport_is_idempotent_no_duplicates() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]\n[submit \"Send\"]" );
		$importer = $this->make_importer();

		$first         = $importer->import_forms( [ $post_id ], false );
		$first_srfm_id = $this->first_srfm_id( $first );
		$this->assertCount( 1, $first['imported'], 'First import should produce exactly one SureForms post.' );
		$this->assertGreaterThan( 0, $first_srfm_id );

		// Second import — same source form.
		$second         = $importer->import_forms( [ $post_id ], false );
		$second_srfm_id = $this->first_srfm_id( $second );
		$this->assertCount( 1, $second['imported'], 'Re-import should still produce one entry, not duplicate.' );

		$this->assertSame(
			$first_srfm_id,
			$second_srfm_id,
			'Re-import must update the same SureForms post (idempotency).'
		);

		// SureForms post count for this source must be exactly 1.
		$srfm_posts = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
		$this->assertCount( 1, $srfm_posts, 'Exactly one SureForms post should exist after two imports of the same source.' );

		// Imported-map option must contain exactly 1 entry.
		$map = get_option( 'srfm_imported_forms_map' );
		$this->assertIsArray( $map );
		$this->assertCount( 1, $map, 'Imported-map option must hold exactly one entry per source form.' );
	}

	/**
	 * Idempotency — when a previously imported SureForms post is hard-deleted,
	 * the next re-import recreates it (the stale map entry is pruned, no fatal).
	 *
	 * @return void
	 */
	public function test_reimport_after_deletion_recreates_post() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = $this->make_importer();

		$first   = $importer->import_forms( [ $post_id ], false );
		$srfm_id = $this->first_srfm_id( $first );

		// Hard-delete the SureForms post.
		wp_delete_post( $srfm_id, true );

		$second  = $importer->import_forms( [ $post_id ], false );
		$new_id  = $this->first_srfm_id( $second );
		$this->assertCount( 1, $second['imported'] );
		$this->assertNotSame( $srfm_id, $new_id, 'A fresh post should be created.' );
	}

	/**
	 * Dry-run must not insert any sureforms_form posts.
	 *
	 * @return void
	 */
	public function test_dry_run_does_not_insert_posts() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = $this->make_importer();

		$counts = wp_count_posts( SRFM_FORMS_POST_TYPE );
		$before = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$result = $importer->import_forms( [ $post_id ], true );
		$counts = wp_count_posts( SRFM_FORMS_POST_TYPE );
		$after  = isset( $counts->publish ) ? (int) $counts->publish : 0;

		$this->assertSame( $before, $after, 'Dry-run must not create any sureforms_form posts.' );
		$this->assertNotEmpty( $this->first_preview( $result ), 'Dry-run result must include preview markup.' );
	}
}
