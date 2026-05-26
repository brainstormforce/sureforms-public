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

use SRFM\Inc\Migrator\Importers\Cf7_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test-only subclass that:
 *  - Forces exist() to return true so import_forms() runs without CF7 active.
 *  - Exposes the protected build_form_content() for direct fixture testing.
 */
class Cf7_Importer_Testable extends Cf7_Importer {
	public function exist() {
		return true;
	}

	public function build_form_content_public( array $form ) {
		return $this->build_form_content( $form );
	}
}

class Test_Cf7_Importer extends TestCase {

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

	public function tear_down() {
		delete_option( 'srfm_imported_forms_map' );
		parent::tear_down();
	}

	/**
	 * Parser — `text` tag emits an srfm/input block with the label as field label.
	 */
	public function test_parser_text_tag_emits_input_block() {
		$importer = new Cf7_Importer_Testable();
		$post_id  = $this->create_cf7_form( "Your Name\n[text* your-name placeholder \"Jane Doe\"]" );

		$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Test Form' ] );

		$this->assertStringContainsString( 'wp:srfm/input', $content );
		$this->assertStringContainsString( '"label":"Your Name"', $content );
		$this->assertStringContainsString( '"required":true', $content );
		$this->assertStringContainsString( 'Jane Doe', $content );
	}

	/**
	 * Parser — `email`, `url`, `tel`, `number`, `textarea`, `date` each map to
	 * their expected srfm block.
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
			'GDPR'     => [ "[acceptance accept-this \"I agree\"]", 'wp:srfm/gdpr' ],
		];

		$importer = new Cf7_Importer_Testable();
		foreach ( $cases as $name => [ $template, $expected_block ] ) {
			$post_id = $this->create_cf7_form( $template, "Form: {$name}" );
			$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => "Form: {$name}" ] );
			$this->assertStringContainsString(
				$expected_block,
				$content,
				"Expected block {$expected_block} for tag {$name}"
			);
		}
	}

	/**
	 * Parser — unsupported tags (file, captchar) are tracked, not silently dropped.
	 */
	public function test_parser_unsupported_tags_are_reported() {
		$post_id  = $this->create_cf7_form(
			"Email\n[email* your-email]\nUpload\n[file your-file]"
		);
		$importer = new Cf7_Importer_Testable();

		$result = $importer->import_forms( [ $post_id ], true );

		$this->assertNotEmpty( $result['preview'], 'Dry-run preview should contain the imported form markup.' );
		$this->assertContains( 'Upload', $result['unsupported_fields'], 'File upload field should be reported as unsupported.' );
	}

	/**
	 * Parser — submit tag's quoted label is captured and used on the submit button.
	 */
	public function test_parser_submit_label_is_used() {
		$post_id  = $this->create_cf7_form( "[email* your-email]\n[submit \"Send it\"]" );
		$importer = new Cf7_Importer_Testable();

		$result = $importer->import_forms( [ $post_id ], true );

		$preview = reset( $result['preview'] );
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
	 */
	public function test_xss_label_with_comment_terminator_is_escaped() {
		$malicious = 'Bad --> <script>alert(1)</script> end';
		$post_id   = $this->create_cf7_form( "{$malicious}\n[text* your-name]" );

		$importer = new Cf7_Importer_Testable();
		$result   = $importer->import_forms( [ $post_id ], true );
		$markup   = reset( $result['preview'] );

		$this->assertNotFalse( $markup, 'Expected a preview markup string.' );

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
	 */
	public function test_reimport_is_idempotent_no_duplicates() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]\n[submit \"Send\"]" );
		$importer = new Cf7_Importer_Testable();

		$first = $importer->import_forms( [ $post_id ], false );
		$this->assertCount( 1, $first['imported'], 'First import should produce exactly one SureForms post.' );
		$first_srfm_id = (int) $first['imported'][0]['srfm_id'];
		$this->assertGreaterThan( 0, $first_srfm_id );

		// Second import — same source form.
		$second = $importer->import_forms( [ $post_id ], false );
		$this->assertCount( 1, $second['imported'], 'Re-import should still produce one entry, not duplicate.' );
		$second_srfm_id = (int) $second['imported'][0]['srfm_id'];

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
	 */
	public function test_reimport_after_deletion_recreates_post() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = new Cf7_Importer_Testable();

		$first   = $importer->import_forms( [ $post_id ], false );
		$srfm_id = (int) $first['imported'][0]['srfm_id'];

		// Hard-delete the SureForms post.
		wp_delete_post( $srfm_id, true );

		$second = $importer->import_forms( [ $post_id ], false );
		$this->assertCount( 1, $second['imported'] );
		$this->assertNotSame( $srfm_id, (int) $second['imported'][0]['srfm_id'], 'A fresh post should be created.' );
	}

	/**
	 * Dry-run must not insert any sureforms_form posts.
	 */
	public function test_dry_run_does_not_insert_posts() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = new Cf7_Importer_Testable();

		$before = (int) wp_count_posts( SRFM_FORMS_POST_TYPE )->publish;
		$result = $importer->import_forms( [ $post_id ], true );
		$after  = (int) wp_count_posts( SRFM_FORMS_POST_TYPE )->publish;

		$this->assertSame( $before, $after, 'Dry-run must not create any sureforms_form posts.' );
		$this->assertArrayHasKey( 'preview', $result );
		$this->assertNotEmpty( $result['preview'] );
	}
}
