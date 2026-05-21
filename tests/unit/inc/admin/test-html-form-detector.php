<?php
/**
 * Class Test_Html_Form_Detector
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Admin\Html_Form_Detector;

/**
 * Tests for Html_Form_Detector.
 *
 * Coverage strategy: most of the detector is composed of pure
 * transforms (`shorthand_to_sides`, `scrub_html_for_ai`,
 * `build_form_metadata`, `pick_hex`, `strip_internal_hints`). Those
 * are tested directly via reflection — no WP fixtures needed. The
 * IO-heavy methods (`enqueue_scripts`, `handle_convert_html_form`,
 * `extract_fields_via_ai`) are covered with thin smoke tests that
 * verify the public contract under a controlled environment, not
 * the full integration path (which is exercised end-to-end in
 * Playwright runs).
 */
class Test_Html_Form_Detector extends TestCase {

	/**
	 * Detector instance under test.
	 *
	 * @var Html_Form_Detector
	 */
	protected $detector;

	/**
	 * Admin user id used across tests.
	 *
	 * @var int
	 */
	protected $admin_id;

	/**
	 * Subscriber user id used across tests.
	 *
	 * @var int
	 */
	protected $subscriber_id;

	protected function setUp(): void {
		parent::setUp();
		$this->detector      = Html_Form_Detector::get_instance();
		$this->admin_id      = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->subscriber_id );
		$GLOBALS['current_screen'] = null;
		parent::tearDown();
	}

	/**
	 * Invoke a protected method on the detector via reflection.
	 *
	 * Mirrors the pattern in test-editor-nudge.php — keeps the
	 * public surface of the class small while still letting us
	 * exercise the pure transforms in isolation.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Positional arguments to pass.
	 * @return mixed Method return value.
	 */
	protected function call_protected( $method, array $args = [] ) {
		$ref = new \ReflectionMethod( Html_Form_Detector::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $this->detector, $args );
	}

	/**
	 * Force a block-editor screen so allow_load() can clear its
	 * screen-related guard.
	 */
	protected function force_block_editor_screen( $post_type = 'page' ) {
		$screen            = \WP_Screen::get( $post_type );
		$screen->id        = $post_type;
		$screen->post_type = $post_type;
		$screen->is_block_editor( true );
		set_current_screen( $screen );
	}

	public function test_allow_load() {
		// Non-admin context: false.
		$this->assertFalse( $this->detector->allow_load() );

		// Admin user but non-block-editor screen: false.
		wp_set_current_user( $this->admin_id );
		set_current_screen( 'dashboard' );
		$this->assertFalse( $this->detector->allow_load() );

		// Admin + block editor on a regular post type: true.
		$this->force_block_editor_screen( 'page' );
		$this->assertTrue( $this->detector->allow_load() );

		// Admin + block editor on the SureForms CPT: false (we never
		// want to convert from inside the form editor itself).
		$this->force_block_editor_screen( SRFM_FORMS_POST_TYPE );
		$this->assertFalse( $this->detector->allow_load() );

		// Subscriber cap fails the manage_options gate.
		wp_set_current_user( $this->subscriber_id );
		$this->force_block_editor_screen( 'page' );
		$this->assertFalse( $this->detector->allow_load() );
	}

	public function test_enqueue_scripts() {
		// Without admin context, the script handle must not be enqueued.
		$this->detector->enqueue_scripts();
		$this->assertFalse( wp_script_is( SRFM_SLUG . '-html-form-detector', 'enqueued' ) );

		// With allow_load() passing, the handle should be enqueued.
		wp_set_current_user( $this->admin_id );
		$this->force_block_editor_screen( 'page' );
		$this->detector->enqueue_scripts();
		$this->assertTrue( wp_script_is( SRFM_SLUG . '-html-form-detector', 'enqueued' ) );
	}

	public function test_register_rest_endpoint() {
		$endpoints = $this->detector->register_rest_endpoint( [] );
		$this->assertArrayHasKey( 'convert-html-form', $endpoints );

		$args = $endpoints['convert-html-form']['args'];
		$this->assertSame( 'array', $args['parsed_fields']['type'] );
		$this->assertSame( 'object', $args['styling']['type'] );
		$this->assertSame( 'string', $args['html']['type'] );

		// Existing routes are preserved (additive contract).
		$existing  = [ 'foo' => [ 'methods' => 'GET' ] ];
		$endpoints = $this->detector->register_rest_endpoint( $existing );
		$this->assertArrayHasKey( 'foo', $endpoints );
		$this->assertArrayHasKey( 'convert-html-form', $endpoints );

		// Non-array input is normalised to an array (defensive contract).
		$endpoints = $this->detector->register_rest_endpoint( 'not-an-array' );
		$this->assertIsArray( $endpoints );
		$this->assertArrayHasKey( 'convert-html-form', $endpoints );
	}

	public function test_handle_convert_html_form() {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/convert-html-form' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_title', 'Test contact form' );
		$request->set_param(
			'parsed_fields',
			[
				[ 'label' => 'Name', 'fieldType' => 'input', 'required' => true ],
				[ 'label' => 'Email', 'fieldType' => 'email', 'required' => true ],
			]
		);
		$request->set_param( 'submit_text', 'Send' );
		$request->set_param( 'styling', [ 'textColor' => '#111111' ] );
		$request->set_param( 'confidence', 'high' );

		$result = $this->detector->handle_convert_html_form( $request );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'form_id', $result );
		$this->assertArrayHasKey( 'shortcode', $result );
		$this->assertGreaterThan( 0, $result['form_id'] );
		$this->assertSame( false, $result['used_ai'] );

		// Cleanup the created form.
		wp_delete_post( (int) $result['form_id'], true );
	}

	public function test_handle_convert_html_form_rejects_missing_nonce() {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/convert-html-form' );
		$request->set_param(
			'parsed_fields',
			[ [ 'label' => 'Name', 'fieldType' => 'input', 'required' => true ] ]
		);

		$result = $this->detector->handle_convert_html_form( $request );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'srfm_html_convert_nonce_failed', $result->get_error_code() );
	}

	public function test_handle_convert_html_form_rejects_subscriber_with_forbidden() {
		// Locks in the capability-before-nonce order. A subscriber
		// (who can hold a valid `wp_rest` nonce — every logged-in
		// user gets one via `apiFetch`) must be bounced on the cap
		// check, not the nonce check, so a future loosening of
		// `current_user_can` cannot promote them to a 'nonce_failed'
		// 403 that might be retried with a fresh nonce.
		wp_set_current_user( $this->subscriber_id );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/convert-html-form' );
		$request->add_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param(
			'parsed_fields',
			[ [ 'label' => 'Name', 'fieldType' => 'input', 'required' => true ] ]
		);

		$result = $this->detector->handle_convert_html_form( $request );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'srfm_html_convert_forbidden', $result->get_error_code() );
	}

	public function test_apply_native_card_styling() {
		$form_id = self::factory()->post->create( [ 'post_type' => SRFM_FORMS_POST_TYPE ] );

		$this->call_protected(
			'apply_native_card_styling',
			[
				$form_id,
				[
					'formBackgroundColor' => '#fff8e7',
					'formPadding'         => '24px',
					'formBorderRadius'    => '12px',
				],
			]
		);

		$styling = get_post_meta( $form_id, '_srfm_forms_styling', true );
		$this->assertSame( 'color', $styling['bg_type'] );
		$this->assertSame( '#fff8e7', $styling['bg_color'] );
		$this->assertSame( 24.0, $styling['form_padding_top'] );
		$this->assertSame( 12.0, $styling['form_border_radius_top'] );
		$this->assertSame( 'px', $styling['form_padding_unit'] );
		$this->assertTrue( $styling['form_padding_link'] );

		// Empty styling: meta stays untouched.
		$other_form = self::factory()->post->create( [ 'post_type' => SRFM_FORMS_POST_TYPE ] );
		$this->call_protected( 'apply_native_card_styling', [ $other_form, [] ] );
		$this->assertSame( '', get_post_meta( $other_form, '_srfm_forms_styling', true ) );

		wp_delete_post( $form_id, true );
		wp_delete_post( $other_form, true );
	}

	public function test_shorthand_to_sides() {
		// Single value applies to all four sides + `link` is true.
		$result = $this->call_protected( 'shorthand_to_sides', [ '24px' ] );
		$this->assertSame(
			[
				'top'    => 24.0,
				'right'  => 24.0,
				'bottom' => 24.0,
				'left'   => 24.0,
				'unit'   => 'px',
				'link'   => true,
			],
			$result
		);

		// Two values → vertical / horizontal.
		$result = $this->call_protected( 'shorthand_to_sides', [ '12px 16px' ] );
		$this->assertSame( 12.0, $result['top'] );
		$this->assertSame( 16.0, $result['right'] );
		$this->assertSame( 12.0, $result['bottom'] );
		$this->assertSame( 16.0, $result['left'] );
		$this->assertFalse( $result['link'] );

		// Three values → top, horizontal, bottom.
		$result = $this->call_protected( 'shorthand_to_sides', [ '5px 10px 15px' ] );
		$this->assertSame( 5.0, $result['top'] );
		$this->assertSame( 10.0, $result['right'] );
		$this->assertSame( 15.0, $result['bottom'] );
		$this->assertSame( 10.0, $result['left'] );

		// Four values → fully specified.
		$result = $this->call_protected( 'shorthand_to_sides', [ '1rem 2rem 3rem 4rem' ] );
		$this->assertSame( 'rem', $result['unit'] );
		$this->assertSame( 1.0, $result['top'] );
		$this->assertSame( 4.0, $result['left'] );

		// Disallowed unit rejected → null.
		$this->assertNull( $this->call_protected( 'shorthand_to_sides', [ '10vh' ] ) );

		// Garbage input rejected.
		$this->assertNull( $this->call_protected( 'shorthand_to_sides', [ 'calc(100% - 20px)' ] ) );
		$this->assertNull( $this->call_protected( 'shorthand_to_sides', [ '' ] ) );
		$this->assertNull( $this->call_protected( 'shorthand_to_sides', [ null ] ) );

		// Five+ tokens are too many — bail.
		$this->assertNull( $this->call_protected( 'shorthand_to_sides', [ '1px 2px 3px 4px 5px' ] ) );
	}

	public function test_extract_fields_via_ai() {
		// The middleware is unreachable in unit tests; the method must
		// surface a WP_Error rather than throwing or returning garbage.
		$result = $this->call_protected( 'extract_fields_via_ai', [ '<form><input type="text"/></form>' ] );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_scrub_html_for_ai() {
		$html = '<form action="/internal/submit" method="post">'
			. '<!-- INTERNAL_API_KEY=sk-live-abc123 do-not-leak -->'
			. '<input type="hidden" name="csrf" value="secret-token-123"/>'
			. '<input type="text" name="email" value="prefilled@example.com" required/>'
			. '<textarea name="draft">half-written reply containing pii@example.com</textarea>'
			. '<script>const API_TOKEN = "leak-me-please";</script>'
			. '<button type="submit">Send</button>'
			. '</form>';

		$scrubbed = $this->call_protected( 'scrub_html_for_ai', [ $html ] );

		// Hidden input gone.
		$this->assertStringNotContainsString( 'type="hidden"', $scrubbed );
		$this->assertStringNotContainsString( 'secret-token-123', $scrubbed );

		// Pre-filled value gone, but the structural element survives.
		$this->assertStringNotContainsString( 'prefilled@example.com', $scrubbed );
		$this->assertStringContainsString( 'name="email"', $scrubbed );

		// `required` boolean attribute is preserved (no `value=` to strip).
		$this->assertStringContainsString( 'required', $scrubbed );

		// `action` attribute is gone.
		$this->assertStringNotContainsString( '/internal/submit', $scrubbed );

		// HTML comments are stripped — they routinely host staging
		// notes / build hashes / API keys that have no business going
		// to the conversion middleware.
		$this->assertStringNotContainsString( 'INTERNAL_API_KEY', $scrubbed );
		$this->assertStringNotContainsString( 'do-not-leak', $scrubbed );

		// Textarea body is emptied so pre-filled drafts (often PII,
		// CSRF tokens, or server-rendered defaults) do not leave the
		// site. The opening/closing tags survive so the model still
		// sees a `<textarea>` at this position.
		$this->assertStringNotContainsString( 'half-written reply', $scrubbed );
		$this->assertStringNotContainsString( 'pii@example.com', $scrubbed );
		$this->assertMatchesRegularExpression( '#<textarea[^>]*>\s*</textarea>#', $scrubbed );

		// `<script>` body is emptied for the same reason — inline
		// tokens or endpoint config must not be forwarded.
		$this->assertStringNotContainsString( 'API_TOKEN', $scrubbed );
		$this->assertStringNotContainsString( 'leak-me-please', $scrubbed );
		$this->assertMatchesRegularExpression( '#<script[^>]*>\s*</script>#', $scrubbed );

		// Non-string input collapses to empty string defensively.
		$this->assertSame( '', $this->call_protected( 'scrub_html_for_ai', [ null ] ) );
	}

	public function test_build_form_metadata() {
		$meta = $this->call_protected(
			'build_form_metadata',
			[
				'Submit Now',
				[
					'textColor'          => '#111111',
					'primaryColor'       => '#c2410c',
					'textColorOnPrimary' => '#ffffff',
				],
			]
		);

		$this->assertSame( 'Submit Now', $meta['general']['submitText'] );
		$this->assertFalse( $meta['instantForm']['showTitle'] );
		$this->assertSame( '#111111', $meta['formStyling']['textColor'] );
		$this->assertSame( '#c2410c', $meta['formStyling']['primaryColor'] );
		$this->assertSame( 'medium', $meta['formStyling']['fieldSpacing'] );

		// Empty submit text omits the general slice.
		$meta = $this->call_protected( 'build_form_metadata', [ '', [] ] );
		$this->assertArrayNotHasKey( 'general', $meta );

		// Falsy / invalid hex falls back to the safe default.
		$meta = $this->call_protected( 'build_form_metadata', [ '', [ 'textColor' => 'not-a-hex' ] ] );
		$this->assertSame( '#1E1E1E', $meta['formStyling']['textColor'] );

		// Form background flows into instantForm.formBackgroundColor.
		$meta = $this->call_protected( 'build_form_metadata', [ '', [ 'formBackgroundColor' => '#abcdef' ] ] );
		$this->assertSame( '#abcdef', $meta['instantForm']['formBackgroundColor'] );
	}

	public function test_pick_hex() {
		$this->assertSame( '#abcdef', $this->call_protected( 'pick_hex', [ '#abcdef', '#000000' ] ) );
		$this->assertSame( '#000000', $this->call_protected( 'pick_hex', [ '', '#000000' ] ) );
		$this->assertSame( '#000000', $this->call_protected( 'pick_hex', [ 'not-a-hex', '#000000' ] ) );
		$this->assertSame( '#000000', $this->call_protected( 'pick_hex', [ null, '#000000' ] ) );
	}

	public function test_strip_internal_hints() {
		$result = $this->call_protected(
			'strip_internal_hints',
			[
				[
					[
						'label'        => 'Name',
						'fieldType'    => 'input',
						'_groupName'   => 'group_a',
						'_optionValue' => 'val',
						'_groupLabel'  => 'Group A',
						'confidence'   => 'high',
					],
					'not-an-array-entry',
				],
			]
		);

		$this->assertCount( 1, $result );
		$this->assertSame( 'Name', $result[0]['label'] );
		$this->assertArrayNotHasKey( '_groupName', $result[0] );
		$this->assertArrayNotHasKey( '_optionValue', $result[0] );
		$this->assertArrayNotHasKey( '_groupLabel', $result[0] );
		$this->assertArrayNotHasKey( 'confidence', $result[0] );
	}

	public function test_strip_unsafe_html_in_fields() {
		// Simulates a sloppy `srfm_html_form_detector_refine_fields`
		// callback that re-introduces raw HTML in properties
		// `Create_Form` does not know to sanitize. The defensive sweep
		// must strip tags from every string leaf at any depth.
		$result = $this->call_protected(
			'strip_unsafe_html_in_fields',
			[
				[
					[
						'label'        => 'Name <script>alert(1)</script>',
						'fieldType'    => 'input',
						'helpText'     => 'Hint <img src=x onerror=alert(1)>',
						'fieldOptions' => [
							[
								'label' => 'Yes <iframe src=//evil>',
								'value' => 'yes',
							],
							[
								'label' => 'No',
								'value' => 'no',
							],
						],
						'allowedFormats' => [ 'jpg<script>', 'png' ],
						'required'       => true,
						'maxFiles'       => 5,
					],
					'not-an-array-entry',
				],
			]
		);

		$this->assertCount( 1, $result );
		$this->assertSame( 'Name alert(1)', $result[0]['label'] );
		$this->assertSame( 'Hint ', $result[0]['helpText'] );
		$this->assertSame( 'Yes ', $result[0]['fieldOptions'][0]['label'] );
		$this->assertSame( 'yes', $result[0]['fieldOptions'][0]['value'] );
		$this->assertSame( 'No', $result[0]['fieldOptions'][1]['label'] );
		// Scalars survive.
		$this->assertTrue( $result[0]['required'] );
		$this->assertSame( 5, $result[0]['maxFiles'] );
		// Nested array of strings — each leaf cleaned.
		$this->assertSame( [ 'jpg', 'png' ], $result[0]['allowedFormats'] );
	}

	public function test_strip_form_for_preservation_kses_for_non_unfiltered() {
		// Site admin without `unfiltered_html` (mirrors a multisite
		// site admin) — `<script>` outside the form must be stripped.
		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$html = '<div class="wrap"><h2>Free eBook</h2>'
			. '<form><input type="email" name="email" required></form>'
			. '<p>Thanks for signing up.</p>'
			. '<script>alert("xss")</script>'
			. '</div>';

		$preserved = $this->call_protected( 'strip_form_for_preservation', [ $html ] );

		$this->assertStringContainsString( '<h2>Free eBook</h2>', $preserved );
		$this->assertStringContainsString( 'Thanks for signing up', $preserved );
		$this->assertStringNotContainsString( '<form', $preserved );
		$this->assertStringNotContainsString( '<script', $preserved );
		$this->assertStringNotContainsString( 'alert("xss")', $preserved );

		wp_delete_user( $user_id );
	}

	public function test_strip_form_for_preservation_preserves_form_only_block() {
		// When the source block held only the `<form>` (no wrapper),
		// the preserved markup must be empty so the caller can fall
		// through to the historical one-block-for-one-block swap.
		wp_set_current_user( $this->admin_id );

		$html      = '<form><input type="text" name="x"></form>';
		$preserved = $this->call_protected( 'strip_form_for_preservation', [ $html ] );

		$this->assertSame( '', $preserved );
	}

	public function test_strip_form_for_preservation_empty_input() {
		$this->assertSame( '', $this->call_protected( 'strip_form_for_preservation', [ '' ] ) );
		$this->assertSame( '', $this->call_protected( 'strip_form_for_preservation', [ null ] ) );
	}
}
