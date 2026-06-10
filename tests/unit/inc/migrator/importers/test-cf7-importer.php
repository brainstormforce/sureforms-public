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
	 * Return the first imported sureforms_form post or fail the test.
	 *
	 * Wraps `get_posts()` so PHPStan stops treating the returned value as
	 * `mixed` once we've asserted it's a real WP_Post.
	 *
	 * @return \WP_Post
	 */
	private function get_first_srfm_post() {
		$posts = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			]
		);
		$this->assertNotEmpty( $posts, 'Expected at least one sureforms_form post.' );
		$first = $posts[0];
		$this->assertInstanceOf( \WP_Post::class, $first );
		return $first;
	}

	/**
	 * Extract `_srfm_email_notification[0]['email_body']` as a string from a
	 * given post id. Returns '' when the meta is missing or malformed so the
	 * caller can assertStringContainsString without phpstan mixed-cast errors.
	 *
	 * @param int $post_id SureForms post id.
	 * @return string
	 */
	private function get_notification_body( $post_id ) {
		$meta = get_post_meta( (int) $post_id, '_srfm_email_notification', true );
		if ( ! is_array( $meta ) || ! isset( $meta[0] ) || ! is_array( $meta[0] ) ) {
			return '';
		}
		$body = $meta[0]['email_body'] ?? '';
		return is_string( $body ) ? $body : '';
	}

	/**
	 * Extract `_srfm_email_notification[0][$key]` as a string. Same defensive
	 * wrapper as `get_notification_body()` but for arbitrary keys (subject,
	 * email_to, etc.) so tests can assert against them without offset-on-mixed
	 * complaints.
	 *
	 * @param int    $post_id SureForms post id.
	 * @param string $key     Notification field key.
	 * @return string
	 */
	private function get_notification_field( $post_id, $key ) {
		$meta = get_post_meta( (int) $post_id, '_srfm_email_notification', true );
		if ( ! is_array( $meta ) || ! isset( $meta[0] ) || ! is_array( $meta[0] ) ) {
			return '';
		}
		$value = $meta[0][ $key ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Extract `_srfm_form_confirmation[0]['message']` as a string.
	 *
	 * @param int $post_id SureForms post id.
	 * @return string
	 */
	private function get_confirmation_message( $post_id ) {
		$meta = get_post_meta( (int) $post_id, '_srfm_form_confirmation', true );
		if ( ! is_array( $meta ) || ! isset( $meta[0] ) || ! is_array( $meta[0] ) ) {
			return '';
		}
		$msg = $meta[0]['message'] ?? '';
		return is_string( $msg ) ? $msg : '';
	}

	/**
	 * Pluck a single field from a list_forms() row.
	 *
	 * @param array<int,array<string,mixed>> $listing list_forms() output.
	 * @param int                             $needle  Source form id.
	 * @param string                          $key     Field name.
	 * @return string|int
	 */
	private function listing_field( array $listing, $needle, $key ) {
		foreach ( $listing as $row ) {
			$row_id = $row['id'] ?? 0;
			$row_id = is_int( $row_id ) || is_numeric( $row_id ) ? (int) $row_id : 0;
			if ( $row_id === (int) $needle ) {
				$value = $row[ $key ] ?? '';
				if ( is_string( $value ) || is_int( $value ) ) {
					return $value;
				}
				return '';
			}
		}
		return '';
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
	 * Regression: two form-tags on a single line (common in two-column CF7
	 * layouts, e.g. `[text* first-name] [text* last-name]`) must each emit a
	 * field. The importer previously matched only the first `[...]` per blob
	 * and silently dropped every subsequent tag on the line.
	 *
	 * @return void
	 */
	public function test_parser_two_tags_on_one_line_emit_both_fields() {
		$importer = $this->make_importer();
		$post_id  = $this->create_cf7_form( '[text* first-name] [text* last-name]' );

		$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Two Column' ] );

		$this->assertSame(
			2,
			substr_count( $content, 'wp:srfm/input' ),
			'Both tags sharing one line must import, not just the first.'
		);
		$this->assertStringContainsString( '"slug":"first-name"', $content );
		$this->assertStringContainsString( '"slug":"last-name"', $content );
	}

	/**
	 * The block-syntax acceptance tag (`[acceptance id] consent [/acceptance]`)
	 * must survive blob-splitting as a single field — the closing `[/acceptance]`
	 * must not be mistaken for a second tag.
	 *
	 * @return void
	 */
	public function test_parser_acceptance_block_not_split_by_closing_tag() {
		$importer = $this->make_importer();
		$post_id  = $this->create_cf7_form( '[acceptance accept-this] I agree to the terms [/acceptance]' );

		$content = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Consent' ] );

		$this->assertSame(
			1,
			substr_count( $content, 'wp:srfm/gdpr' ),
			'Acceptance block syntax must emit exactly one field.'
		);
		$this->assertStringContainsString( 'I agree to the terms', $content );
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
			'Checkbox' => [ "Topics\n[checkbox topics \"News\" \"Updates\"]", 'wp:srfm/multi-choice' ],
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
	 * CF7 [checkbox] is a multi-option group — it must map to srfm/multi-choice
	 * in multi-select mode (singleSelection:false) with all options preserved,
	 * NOT to the single-toggle srfm/checkbox (which drops every option).
	 *
	 * @return void
	 */
	public function test_checkbox_group_maps_to_multichoice_with_all_options() {
		$post_id  = $this->create_cf7_form(
			"Skills\n[checkbox skills \"JavaScript\" \"PHP\" \"React\" \"WordPress\"]"
		);
		$importer = $this->make_importer();
		$content  = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Skills' ] );

		$this->assertStringContainsString( 'wp:srfm/multi-choice', $content );
		$this->assertStringNotContainsString( 'wp:srfm/checkbox', $content, 'CF7 checkbox group must not become a single srfm/checkbox.' );
		// Multi-select, not radio.
		$this->assertStringContainsString( '"singleSelection":false', $content );
		// All four options survive.
		foreach ( [ 'JavaScript', 'PHP', 'React', 'WordPress' ] as $opt ) {
			$this->assertStringContainsString( $opt, $content, "Checkbox option {$opt} must be preserved." );
		}
	}

	/**
	 * CF7 block-form acceptance `[acceptance id] consent text [/acceptance]`
	 * must use the consent text as the GDPR field label, not the generic
	 * "Acceptance" fallback.
	 *
	 * @return void
	 */
	public function test_acceptance_block_form_uses_consent_text_as_label() {
		$post_id  = $this->create_cf7_form(
			"<p>[acceptance consent] I agree to the privacy policy. [/acceptance]</p>"
		);
		$importer = $this->make_importer();
		$content  = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Consent' ] );

		$this->assertStringContainsString( 'wp:srfm/gdpr', $content );
		$this->assertStringContainsString( 'I agree to the privacy policy.', $content );
		$this->assertStringNotContainsString( '"label":"Acceptance"', $content, 'Block-form consent text must override the generic label.' );
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
	 * Submit tag's quoted label is written to the `_srfm_submit_button_text`
	 * meta (SureForms auto-renders the submit button from it) — NOT emitted as
	 * a content block. The preview markup must contain no button block.
	 *
	 * @return void
	 */
	public function test_parser_submit_label_goes_to_meta_not_content() {
		$post_id  = $this->create_cf7_form( "[email* your-email]\n[submit \"Send it\"]" );
		$importer = $this->make_importer();

		// Dry-run preview must NOT contain a button block.
		$preview = $this->first_preview( $importer->import_forms( [ $post_id ], true ) );
		$this->assertStringNotContainsString( 'srfm/inline-button', $preview, 'Submit must not be a content block.' );
		$this->assertStringNotContainsString( 'srfm/button', $preview );

		// Real import writes the label to the submit-button meta.
		$importer->import_forms( [ $post_id ], false );
		$srfm_post   = $this->get_first_srfm_post();
		$submit_text = get_post_meta( $srfm_post->ID, '_srfm_submit_button_text', true );
		$this->assertSame( 'Send it', $submit_text );
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

	/**
	 * Two CF7 fields with identical labels must produce distinct SureForms
	 * slugs (collision dedupe).
	 *
	 * @return void
	 */
	public function test_slug_collision_dedupes_with_numeric_suffix() {
		// First case: two fields with distinct CF7 names — each gets its own
		// slug seeded from the name. No collision.
		$post_id  = $this->create_cf7_form(
			"Your Name\n[text* name1]\nYour Email\n[email* name2]"
		);
		$importer = $this->make_importer();
		$result   = $importer->import_forms( [ $post_id ], true );
		$preview  = $this->first_preview( $result );

		$this->assertStringContainsString( '"slug":"name1"', $preview );
		$this->assertStringContainsString( '"slug":"name2"', $preview );

		// Second case: two fields share the same CF7 name (which CF7 itself
		// allows, even though it's a footgun). The importer must dedupe so
		// the resulting SureForms post has unique slugs per field.
		wp_delete_post( $post_id, true );
		$post_id2 = $this->create_cf7_form(
			"Your Name\n[text* shared]\nYour Other Name\n[text* shared]"
		);
		$result2  = $importer->import_forms( [ $post_id2 ], true );
		$preview2 = $this->first_preview( $result2 );

		$this->assertStringContainsString( '"slug":"shared"', $preview2 );
		$this->assertStringContainsString( '"slug":"shared-2"', $preview2 );
	}

	/**
	 * CF7 `_mail.body` shortcodes like `[your-name]` must be rewritten to
	 * SureForms smart tags `{your-name}` (or the deduped slug) in the
	 * stored notification.
	 *
	 * @return void
	 */
	public function test_mail_template_translates_field_shortcodes() {
		$post_id = $this->create_cf7_form( "Name\n[text* your-name]" );
		update_post_meta(
			$post_id,
			'_mail',
			[
				'subject'   => 'New submission from [your-name]',
				'body'      => "Hello, [your-name] sent a message.\n--",
				'recipient' => 'admin@example.test',
			]
		);

		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );

		$srfm_post = $this->get_first_srfm_post();
		$this->assertStringContainsString( '{your-name}', $this->get_notification_body( $srfm_post->ID ) );
		$this->assertStringContainsString( '{your-name}', $this->get_notification_field( $srfm_post->ID, 'subject' ) );
		$this->assertSame( 'admin@example.test', $this->get_notification_field( $srfm_post->ID, 'email_to' ) );
	}

	/**
	 * CF7 system shortcodes in mail bodies must map to SureForms smart tags
	 * (e.g. `[_post_title]` → `{post_title}`).
	 *
	 * @return void
	 */
	public function test_mail_template_translates_system_shortcodes() {
		$post_id = $this->create_cf7_form( "Email\n[email* your-email]" );
		update_post_meta(
			$post_id,
			'_mail',
			[
				'body' => 'Posted on [_post_title] at [_url] by [_user_email]',
			]
		);
		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );

		$srfm_post = $this->get_first_srfm_post();
		$body      = $this->get_notification_body( $srfm_post->ID );
		$this->assertStringContainsString( '{post_title}', $body );
		$this->assertStringContainsString( '{current_url}', $body );
		$this->assertStringContainsString( '{email_address}', $body );
	}

	/**
	 * When the source CF7 form sets `_messages.mail_sent_ok`, that string
	 * must become the SureForms form-confirmation message (overriding the
	 * default "Thank you" stub).
	 *
	 * @return void
	 */
	public function test_confirmation_uses_mail_sent_ok_when_present() {
		$post_id = $this->create_cf7_form( "Email\n[email* your-email]" );
		update_post_meta(
			$post_id,
			'_messages',
			[ 'mail_sent_ok' => 'Got it, we\'ll be in touch shortly.' ]
		);

		$importer = $this->make_importer();
		$importer->import_forms( [ $post_id ], false );

		$srfm_post = $this->get_first_srfm_post();
		$message   = $this->get_confirmation_message( $srfm_post->ID );
		$this->assertStringContainsString( 'Got it', $message );
		$this->assertStringNotContainsString( 'Thank you', $message );
	}

	/**
	 * The CF7 Multi-Step Forms addon emits `[step]` wrappers — these must be
	 * surfaced as an unsupported-fields warning instead of being silently
	 * flattened.
	 *
	 * @return void
	 */
	public function test_step_tag_is_reported_as_unsupported_addon() {
		$post_id  = $this->create_cf7_form( "[step]\nName\n[text* your-name]\n[/step]" );
		$importer = $this->make_importer();

		$result = $importer->import_forms( [ $post_id ], true );

		$this->assertContains(
			'Multi-Step Forms addon',
			$result['unsupported_fields'],
			'Multi-Step `[step]` tag must trigger a dedicated unsupported-fields note.'
		);
	}

	/**
	 * The CF7 Conditional Fields addon emits `[group]` wrappers — these must
	 * be surfaced as an unsupported-fields warning.
	 *
	 * @return void
	 */
	public function test_group_tag_is_reported_as_unsupported_addon() {
		$post_id  = $this->create_cf7_form( "[group cond-a]\nName\n[text* your-name]\n[/group]" );
		$importer = $this->make_importer();

		$result = $importer->import_forms( [ $post_id ], true );

		$this->assertContains(
			'Conditional Fields addon',
			$result['unsupported_fields'],
			'Conditional Fields `[group]` tag must trigger a dedicated unsupported-fields note.'
		);
	}

	/**
	 * list_forms() must return `imported_srfm_id` AND `imported_srfm_edit_url`
	 * for forms that have a prior SureForms import — the UI uses the edit_url
	 * for the "Open existing form" link next to the "Previously imported" badge.
	 *
	 * @return void
	 */
	public function test_list_forms_returns_imported_srfm_id_and_edit_url() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = $this->make_importer();

		// Before import, no edit_url.
		$listing = $importer->list_forms();
		$this->assertSame( 0, (int) $this->listing_field( $listing, $post_id, 'imported_srfm_id' ) );
		$this->assertSame( '', (string) $this->listing_field( $listing, $post_id, 'imported_srfm_edit_url' ) );

		// After import, both fields populated.
		$importer->import_forms( [ $post_id ], false );
		$listing = $importer->list_forms();
		$this->assertGreaterThan( 0, (int) $this->listing_field( $listing, $post_id, 'imported_srfm_id' ) );
		$edit_url = (string) $this->listing_field( $listing, $post_id, 'imported_srfm_edit_url' );
		$this->assertStringContainsString( 'post.php?post=', $edit_url );
		$this->assertStringContainsString( 'action=edit', $edit_url );
	}

	/**
	 * Passing `behavior[ source_id ] = 'skip'` must leave the existing
	 * SureForms post untouched and report the form under `skipped`.
	 *
	 * @return void
	 */
	public function test_reimport_with_skip_behavior_leaves_existing_post_untouched() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = $this->make_importer();

		$first   = $importer->import_forms( [ $post_id ], false );
		$srfm_id = $this->first_srfm_id( $first );

		$snapshot_before = get_post( $srfm_id );
		$this->assertInstanceOf( \WP_Post::class, $snapshot_before );

		// Re-import with skip behavior.
		$second = $importer->import_forms( [ $post_id ], false, [ (string) $post_id => 'skip' ] );

		$this->assertEmpty( $second['imported'], 'Skipped re-import must not produce an `imported` entry.' );
		$this->assertCount( 1, $second['skipped'], 'Skipped re-import must report under `skipped`.' );
		$skipped_id = $second['skipped'][0]['srfm_id'] ?? 0;
		$skipped_id = is_int( $skipped_id ) || is_numeric( $skipped_id ) ? (int) $skipped_id : 0;
		$this->assertSame( $srfm_id, $skipped_id );

		$snapshot_after = get_post( $srfm_id );
		$this->assertInstanceOf( \WP_Post::class, $snapshot_after );
		$this->assertSame(
			$snapshot_before->post_modified_gmt,
			$snapshot_after->post_modified_gmt,
			'Existing post must not be modified when behavior=skip.'
		);
	}

	/**
	 * Passing `behavior[ source_id ] = 'create'` must insert a fresh
	 * SureForms post alongside the existing one — useful when the user
	 * wants a side-by-side draft to compare.
	 *
	 * @return void
	 */
	public function test_reimport_with_create_behavior_creates_second_post() {
		$post_id  = $this->create_cf7_form( "Name\n[text* your-name]" );
		$importer = $this->make_importer();

		$first         = $importer->import_forms( [ $post_id ], false );
		$first_srfm_id = $this->first_srfm_id( $first );

		$second          = $importer->import_forms( [ $post_id ], false, [ (string) $post_id => 'create' ] );
		$second_srfm_id  = $this->first_srfm_id( $second );

		$this->assertNotSame( $first_srfm_id, $second_srfm_id, 'create behavior must produce a different SureForms post.' );
		$this->assertGreaterThan( 0, $second_srfm_id );

		$count = wp_count_posts( SRFM_FORMS_POST_TYPE );
		$this->assertSame( 2, (int) $count->publish, 'Two SureForms posts must exist after one update and one create.' );
	}

	/**
	 * Cf7_Importer::exist() — true when CF7 constants are defined.
	 *
	 * @return void
	 */
	public function test_exist() {
		// Test subclass forces exist() → true regardless of CF7 presence,
		// covering the success branch import_forms relies on.
		$importer = $this->make_importer();
		$this->assertTrue( $importer->exist() );
	}

	/**
	 * Cf7_Importer::get_source_forms() — enumerates wpcf7_contact_form posts.
	 *
	 * @return void
	 */
	public function test_get_source_forms() {
		$post_id = $this->create_cf7_form( "Name\n[text* your-name]", 'List me' );
		$listing = $this->make_importer()->list_forms();
		$ids     = array_map( static fn( $row ) => (int) ( $row['id'] ?? 0 ), $listing );
		$this->assertContains( $post_id, $ids );
	}

	/**
	 * Cf7_Importer::get_source_form_id() — pulls the int id from a form descriptor.
	 *
	 * @return void
	 */
	public function test_get_source_form_id() {
		$post_id = $this->create_cf7_form( "Name\n[text* n]", 'For id' );
		$listing = $this->make_importer()->list_forms();
		$row     = array_values( array_filter( $listing, static fn( $r ) => (int) $r['id'] === $post_id ) )[0] ?? null;
		$this->assertNotNull( $row );
		$this->assertSame( $post_id, (int) $row['id'] );
	}

	/**
	 * Cf7_Importer::get_source_form_name() — pulls the title from a form descriptor.
	 *
	 * @return void
	 */
	public function test_get_source_form_name() {
		$post_id = $this->create_cf7_form( "Name\n[text* n]", 'My CF7 Form' );
		$listing = $this->make_importer()->list_forms();
		$row     = array_values( array_filter( $listing, static fn( $r ) => (int) $r['id'] === $post_id ) )[0] ?? null;
		$this->assertNotNull( $row );
		$this->assertSame( 'My CF7 Form', $row['name'] );
	}

	/**
	 * Cf7_Importer::get_form_metas() — emits SureForms email + confirmation meta.
	 *
	 * @return void
	 */
	public function test_get_form_metas() {
		$post_id = $this->create_cf7_form( "Name\n[text* n]", 'Meta test' );
		update_post_meta(
			$post_id,
			'_mail',
			[
				'recipient' => 'team@example.test',
				'subject'   => 'Hi',
				'body'      => 'Body',
			]
		);
		$this->make_importer()->import_forms( [ $post_id ], false );
		$srfm_post   = $this->get_first_srfm_post();
		$recipient   = $this->get_notification_field( $srfm_post->ID, 'email_to' );
		$this->assertSame( 'team@example.test', $recipient );
	}

	/**
	 * Cf7_Importer::build_form_content() — parses CF7 template into srfm markup.
	 *
	 * @return void
	 */
	public function test_build_form_content() {
		$post_id = $this->create_cf7_form( "Name\n[text* your-name placeholder \"Jane\"]" );
		$markup  = $this->make_importer()->build_form_content_public(
			[ 'id' => $post_id, 'name' => 'BFC' ]
		);
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"Name"', $markup );
	}
}
