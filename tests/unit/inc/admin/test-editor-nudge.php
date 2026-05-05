<?php
/**
 * Class Test_Editor_Nudge
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Admin\Editor_Nudge;

/**
 * Tests for Editor_Nudge.
 */
class Test_Editor_Nudge extends TestCase {

	/**
	 * Editor_Nudge instance.
	 *
	 * @var Editor_Nudge
	 */
	protected $nudge;

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

	/**
	 * Test post used as the editing target across allow_load / handle_dismiss tests.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Secondary test post — used to verify per-post dismissal isolation.
	 *
	 * @var int
	 */
	protected $other_post_id;

	protected function setUp(): void {
		parent::setUp();
		$this->nudge         = Editor_Nudge::get_instance();
		$this->admin_id      = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->post_id       = self::factory()->post->create( [ 'post_type' => 'page', 'post_title' => 'Contact Us' ] );
		$this->other_post_id = self::factory()->post->create( [ 'post_type' => 'page', 'post_title' => 'About' ] );
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		delete_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY );
		delete_post_meta( $this->other_post_id, Editor_Nudge::DISMISS_META_KEY );
		wp_delete_post( $this->post_id, true );
		wp_delete_post( $this->other_post_id, true );
		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->subscriber_id );
		unset( $_POST['nonce'], $_POST['post_id'] );
		$GLOBALS['post']           = null;
		$GLOBALS['current_screen'] = null;
		parent::tearDown();
	}

	/**
	 * Configure a block-editor screen for the given post type so allow_load()
	 * can pass the screen + post-type checks under PHPUnit.
	 *
	 * @param string $post_type Post type slug, e.g. 'page' or SRFM_FORMS_POST_TYPE.
	 */
	protected function force_block_editor_screen( $post_type ) {
		$screen                 = \WP_Screen::get( $post_type );
		$screen->id             = $post_type;
		$screen->post_type      = $post_type;
		$screen->is_block_editor( true );
		set_current_screen( $screen );
	}

	/**
	 * Bind the global $post to a given post so allow_load()'s
	 * get_current_post_id() resolves to that post.
	 *
	 * @param int $post_id Post ID to bind.
	 */
	protected function set_current_post( $post_id ) {
		$GLOBALS['post'] = get_post( $post_id );
	}

	// ---------------------------------------------------------------
	// get_current_post_id()
	// ---------------------------------------------------------------

	/**
	 * Invoke the protected `get_current_post_id` helper via reflection so
	 * the coverage tool registers a direct test for it. Covers three
	 * branches: no `$post` global, non-WP_Post `$post`, and a valid
	 * `WP_Post` instance.
	 */
	public function test_get_current_post_id() {
		$method = new \ReflectionMethod( Editor_Nudge::class, 'get_current_post_id' );
		$method->setAccessible( true );

		// No $post global → 0.
		$GLOBALS['post'] = null;
		$this->assertSame(
			0,
			$method->invoke( $this->nudge ),
			'get_current_post_id() must return 0 when no $post global is set.'
		);

		// Non-WP_Post value (e.g. left-over array) → 0.
		$GLOBALS['post'] = [ 'ID' => $this->post_id ];
		$this->assertSame(
			0,
			$method->invoke( $this->nudge ),
			'get_current_post_id() must return 0 when $post is not a WP_Post.'
		);

		// Valid WP_Post → the post ID.
		$GLOBALS['post'] = get_post( $this->post_id );
		$this->assertSame(
			(int) $this->post_id,
			$method->invoke( $this->nudge ),
			'get_current_post_id() must return the post ID when $post is a valid WP_Post.'
		);
	}

	// ---------------------------------------------------------------
	// allow_load()
	// ---------------------------------------------------------------

	/**
	 * Non-admin context should always bail out early.
	 */
	public function test_allow_load_returns_false_outside_admin() {
		wp_set_current_user( $this->admin_id );

		// PHPUnit runs without is_admin() true by default.
		$this->assertFalse( $this->nudge->allow_load() );
	}

	/**
	 * Users without the required capability should not see the nudge.
	 */
	public function test_allow_load_returns_false_for_user_without_capability() {
		wp_set_current_user( $this->subscriber_id );
		$this->assertFalse( $this->nudge->allow_load() );
	}

	/**
	 * A post with an active dismissal meta should suppress the nudge.
	 */
	public function test_allow_load_returns_false_when_post_already_dismissed() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( 'page' );
		$this->set_current_post( $this->post_id );
		update_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, time() );

		$this->assertFalse(
			$this->nudge->allow_load(),
			'allow_load() must return false when the current post has a fresh dismissal recorded.'
		);
	}

	/**
	 * After the user dismisses the nudge on Post A, navigating to Post B
	 * (which has no dismissal of its own) must still show the nudge —
	 * dismissal is scoped to the post, not the user.
	 */
	public function test_allow_load_isolated_per_post() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( 'page' );

		// Dismiss on Post A.
		update_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, time() );

		// Editing Post B — no dismissal — must still allow the nudge.
		$this->set_current_post( $this->other_post_id );

		$this->assertTrue(
			$this->nudge->allow_load(),
			'A dismissal on one post must not silence the nudge on a different post.'
		);
	}

	/**
	 * A dismissal older than the expiry window should no longer suppress
	 * the nudge — guards against the "permanent dismissal with no recovery"
	 * footgun. Asserts both the helper and allow_load() to lock the
	 * rollover behavior end-to-end.
	 */
	public function test_allow_load_treats_expired_dismissal_as_inactive() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( 'page' );
		$this->set_current_post( $this->post_id );

		$expired_at = time() - Editor_Nudge::DISMISS_EXPIRY_SECONDS - DAY_IN_SECONDS;
		update_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, $expired_at );

		$this->assertFalse(
			$this->nudge->is_dismissal_active( $this->post_id ),
			'Expired dismissal timestamp must not count as active.'
		);

		$this->assertTrue(
			$this->nudge->allow_load(),
			'allow_load() must surface the nudge again once the dismissal expires.'
		);
	}

	/**
	 * Sanity check that a fresh dismissal is treated as active for the post.
	 */
	public function test_is_dismissal_active_returns_true_for_recent_timestamp() {
		update_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, time() );

		$this->assertTrue( $this->nudge->is_dismissal_active( $this->post_id ) );
	}

	/**
	 * Positive-path coverage: when capability + block-editor screen +
	 * non-SureForms post type + no active dismissal all align,
	 * allow_load() must return true.
	 */
	public function test_allow_load_returns_true_when_all_conditions_met() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( 'page' );
		$this->set_current_post( $this->post_id );

		$this->assertTrue(
			$this->nudge->allow_load(),
			'allow_load() must return true on a block-editor screen for a capable user with no active dismissal.'
		);
	}

	/**
	 * The SureForms form CPT editor must never show the nudge — even when
	 * every other condition is satisfied.
	 */
	public function test_allow_load_returns_false_on_sureforms_form_screen() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( SRFM_FORMS_POST_TYPE );

		$this->assertFalse(
			$this->nudge->allow_load(),
			'allow_load() must return false on the SureForms form CPT screen.'
		);
	}

	/**
	 * Logged-out visitor should never load the nudge script.
	 */
	public function test_allow_load_returns_false_for_logged_out_user() {
		wp_set_current_user( 0 );
		$this->assertFalse( $this->nudge->allow_load() );
	}

	// ---------------------------------------------------------------
	// End-to-end "once dismissed, won't show again on this post"
	// ---------------------------------------------------------------

	/**
	 * Dismissing the nudge via the AJAX handler must immediately mark
	 * subsequent allow_load() calls for the same post as false — the user
	 * dismisses, reloads, and the nudge does not return.
	 */
	public function test_nudge_does_not_show_after_successful_dismissal() {
		wp_set_current_user( $this->admin_id );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$this->force_block_editor_screen( 'page' );
		$this->set_current_post( $this->post_id );

		// Pre-condition: with no dismissal recorded, the nudge is allowed.
		$this->assertTrue(
			$this->nudge->allow_load(),
			'Pre-condition failed — nudge should be allowed before dismissal.'
		);

		// Simulate the dismissal AJAX flow.
		$_POST['nonce']   = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		$_POST['post_id'] = $this->post_id;

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			ob_end_clean();
		}

		// Post-condition: the same post must no longer surface the nudge.
		$this->assertFalse(
			$this->nudge->allow_load(),
			'Once dismissed, allow_load() must return false for the same post on subsequent calls.'
		);
		$this->assertTrue(
			$this->nudge->is_dismissal_active( $this->post_id ),
			'Dismissal timestamp must be persisted on the post meta.'
		);
	}

	// ---------------------------------------------------------------
	// enqueue_scripts()
	// ---------------------------------------------------------------

	/**
	 * enqueue_scripts() must no-op when allow_load() is false.
	 */
	public function test_enqueue_scripts_noops_when_not_allowed() {
		wp_set_current_user( 0 );

		$handle = SRFM_SLUG . '-editor-nudge';

		$this->nudge->enqueue_scripts();

		$this->assertFalse(
			wp_script_is( $handle, 'enqueued' ),
			'Nudge script should not be enqueued when allow_load() is false.'
		);
		$this->assertFalse(
			wp_style_is( $handle, 'enqueued' ),
			'Nudge stylesheet should not be enqueued when allow_load() is false.'
		);
	}

	/**
	 * enqueue_scripts should be wired to admin_enqueue_scripts in the constructor.
	 */
	public function test_enqueue_scripts_is_hooked_to_admin_enqueue_scripts() {
		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', [ $this->nudge, 'enqueue_scripts' ] ),
			'enqueue_scripts must be registered on admin_enqueue_scripts.'
		);
	}

	// ---------------------------------------------------------------
	// handle_dismiss()
	// ---------------------------------------------------------------

	/**
	 * Dismiss AJAX action should be registered.
	 */
	public function test_handle_dismiss_is_hooked_to_ajax_action() {
		$this->assertNotFalse(
			has_action( 'wp_ajax_srfm_editor_nudge_dismiss', [ $this->nudge, 'handle_dismiss' ] ),
			'handle_dismiss must be registered on the wp_ajax_srfm_editor_nudge_dismiss hook.'
		);
	}

	/**
	 * A user lacking the required capability should receive a 403 error and not update the meta.
	 */
	public function test_handle_dismiss_rejects_user_without_capability() {
		wp_set_current_user( $this->subscriber_id );
		$_POST['nonce']   = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		$_POST['post_id'] = $this->post_id;

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertEmpty(
				get_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismiss meta must not be written when capability check fails.'
			);
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException for user without required capability.' );
	}

	/**
	 * A capable user with a valid nonce should persist the dismissed flag
	 * to the targeted post's meta as the current Unix timestamp.
	 */
	public function test_handle_dismiss_persists_post_meta_on_success() {
		wp_set_current_user( $this->admin_id );
		$_POST['nonce']   = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		$_POST['post_id'] = $this->post_id;

		$before = time();

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertTrue( $data['success'] );

			$stored = (int) get_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, true );
			$this->assertGreaterThanOrEqual(
				$before,
				$stored,
				'Dismiss meta should be set to a fresh Unix timestamp.'
			);
			$this->assertLessThanOrEqual(
				time() + 1,
				$stored,
				'Dismiss meta timestamp must not be in the future.'
			);
			$this->assertTrue( $this->nudge->is_dismissal_active( $this->post_id ) );

			// Other posts must remain untouched — per-post isolation.
			$this->assertEmpty(
				get_post_meta( $this->other_post_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismissing on one post must not write meta on any other post.'
			);
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException after successful dismiss.' );
	}

	/**
	 * A capable user with an invalid/missing nonce must be rejected and
	 * the post meta must not be written.
	 */
	public function test_handle_dismiss_rejects_invalid_nonce() {
		wp_set_current_user( $this->admin_id );
		$_POST['nonce']   = 'definitely-not-a-valid-nonce';
		$_POST['post_id'] = $this->post_id;

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertEmpty(
				get_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismiss meta must not be written when the nonce check fails.'
			);
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException for invalid nonce.' );
	}

	/**
	 * The nudge never targets the SureForms form CPT, so a dismiss request
	 * pointed at one must be rejected before any meta is written — even
	 * when capability and nonce both pass.
	 */
	public function test_handle_dismiss_rejects_sureforms_form_post_type() {
		wp_set_current_user( $this->admin_id );

		$form_id = self::factory()->post->create(
			[
				'post_type'  => SRFM_FORMS_POST_TYPE,
				'post_title' => 'Contact Us Form',
			]
		);

		$_POST['nonce']   = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		$_POST['post_id'] = $form_id;

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertEmpty(
				get_post_meta( $form_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismiss meta must not be written for the SureForms form CPT.'
			);
			wp_delete_post( $form_id, true );
			return;
		}
		ob_end_clean();
		wp_delete_post( $form_id, true );
		$this->fail( 'Expected WPDieException for SureForms form post type.' );
	}

	/**
	 * Even with `manage_options`, the dismiss handler must enforce
	 * `edit_post` per-post — a user who lacks edit access to a specific
	 * post must not be able to write dismissal meta against it.
	 *
	 * Simulated by filtering `map_meta_cap` to deny `edit_post` on the
	 * targeted post, since stock administrators normally have it.
	 */
	public function test_handle_dismiss_rejects_user_without_edit_post_capability() {
		wp_set_current_user( $this->admin_id );
		$_POST['nonce']   = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		$_POST['post_id'] = $this->post_id;

		$target_post_id = $this->post_id;
		$deny_edit_post = static function ( $caps, $cap, $user_id, $args ) use ( $target_post_id ) {
			if ( 'edit_post' === $cap && isset( $args[0] ) && (int) $args[0] === (int) $target_post_id ) {
				return [ 'do_not_allow' ];
			}
			return $caps;
		};

		add_filter( 'map_meta_cap', $deny_edit_post, 10, 4 );

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			remove_filter( 'map_meta_cap', $deny_edit_post, 10 );
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertEmpty(
				get_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismiss meta must not be written when edit_post is denied for the post.'
			);
			return;
		}
		remove_filter( 'map_meta_cap', $deny_edit_post, 10 );
		ob_end_clean();
		$this->fail( 'Expected WPDieException for user without edit_post on the targeted post.' );
	}

	/**
	 * A capable user with a valid nonce but a missing or invalid post_id
	 * must be rejected without writing any meta — guards against stray
	 * dismiss requests targeting the wrong post.
	 */
	public function test_handle_dismiss_rejects_missing_post_id() {
		wp_set_current_user( $this->admin_id );
		$_POST['nonce'] = wp_create_nonce( Editor_Nudge::NONCE_ACTION );
		unset( $_POST['post_id'] );

		ob_start();
		try {
			$this->nudge->handle_dismiss();
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertEmpty(
				get_post_meta( $this->post_id, Editor_Nudge::DISMISS_META_KEY, true ),
				'Dismiss meta must not be written when post_id is missing.'
			);
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException for missing post_id.' );
	}
}
