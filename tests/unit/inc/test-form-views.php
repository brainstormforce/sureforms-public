<?php
/**
 * Class Test_Form_Views
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Views;
use SRFM\Inc\Submit_Token;

class Test_Form_Views extends TestCase {

	protected $views;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'SRFM\Inc\Form_Views' ) ) {
			$this->markTestSkipped( 'Form_Views class not available.' );
		}

		$this->views            = Form_Views::get_instance();
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		unset( $_GET['live_mode'] );
		wp_set_current_user( 0 );

		// Counting is opt-in and gated on this stamp, so an open window is the
		// precondition for every test about counting. The tests that assert the
		// never-enabled state delete it themselves first.
		update_option( Form_Views::TRACKING_STARTED_OPTION, time() - HOUR_IN_SECONDS );
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		delete_option( 'srfm_general_settings_options' );
		parent::tearDown();
	}

	/**
	 * Helper to call private methods.
	 */
	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}

	private function make_form() {
		return wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_title'  => 'Views Test Form',
				'post_status' => 'publish',
			]
		);
	}

	// ──────────────────────────────────────────────
	// get_views / increment_views
	// ──────────────────────────────────────────────

	public function test_get_views_defaults_to_zero() {
		$form_id = $this->make_form();
		$this->assertSame( 0, $this->views->get_views( $form_id ) );
		wp_delete_post( $form_id, true );
	}

	public function test_increment_views_creates_then_increments() {
		$form_id = $this->make_form();

		$this->call_private_method( $this->views, 'increment_views', [ $form_id ] );
		$this->assertSame( 1, $this->views->get_views( $form_id ), 'First increment should create the meta at 1.' );

		$this->call_private_method( $this->views, 'increment_views', [ $form_id ] );
		$this->assertSame( 2, $this->views->get_views( $form_id ), 'Second increment should bump to 2.' );

		wp_delete_post( $form_id, true );
	}

	// ──────────────────────────────────────────────
	// should_track (exclusions)
	// ──────────────────────────────────────────────

	public function test_should_track_true_for_anonymous_visitor() {
		wp_set_current_user( 0 );
		$this->assertTrue( $this->call_private_method( $this->views, 'should_track' ) );
	}

	/**
	 * The live-preview exclusion must work for a session WITHOUT edit_posts — a
	 * shared preview link or a lower-privileged collaborator — which the capability
	 * check does not catch. The signal travels on the beacon request itself, because
	 * should_track() runs on that separate POST and cannot see the previewed page's
	 * $_GET. Regression guard for the previously dead exclusion.
	 */
	public function test_should_track_excludes_live_preview_beacon_for_anonymous_session() {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'live_preview', '1' );

		$this->assertFalse(
			$this->call_private_method( $this->views, 'should_track', [ $request ] ),
			'A beacon carrying the live-preview signal must not be counted, even with no privileged user.'
		);
	}

	/**
	 * A normal front-end beacon (no live_mode on the previewed page, so live_preview
	 * arrives as '0') is still counted.
	 */
	public function test_should_track_counts_normal_beacon_without_live_preview() {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'live_preview', '0' );

		$this->assertTrue(
			$this->call_private_method( $this->views, 'should_track', [ $request ] ),
			'A normal front-end beacon must still be counted.'
		);
	}

	public function test_should_track_excludes_privileged_user() {
		$editor = wp_insert_user(
			[
				'user_login' => 'srfm_views_editor_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_views_editor_' . wp_rand() . '@example.com',
				'role'       => 'editor',
			]
		);
		wp_set_current_user( $editor );

		$this->assertFalse( $this->call_private_method( $this->views, 'should_track' ) );

		wp_set_current_user( 0 );
		wp_delete_user( $editor );
	}

	/**
	 * The privileged-user exclusion must survive REST's anonymous reset.
	 *
	 * Core's rest_cookie_check_errors() calls wp_set_current_user( 0 ) for any
	 * cookie-bearing REST request that carries no nonce ( wp-includes/rest-api.php ).
	 * The beacon sends only X-WP-Submit-Token, so that is every beacon request an
	 * administrator or editor makes while browsing their own site. Resolving identity
	 * with get_current_user_id() therefore sees 0 and counts their page views as
	 * anonymous traffic, which is exactly what the setting promises it will not do.
	 *
	 * This models the dispatch state rather than the pre-dispatch one: the auth cookie
	 * is present, the current user has already been reset.
	 */
	public function test_should_track_excludes_privileged_user_after_rest_anonymous_reset() {
		$editor = wp_insert_user(
			[
				'user_login' => 'srfm_views_rest_editor_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_views_rest_editor_' . wp_rand() . '@example.com',
				'role'       => 'editor',
			]
		);

		$expiration                    = time() + HOUR_IN_SECONDS;
		$_COOKIE[ LOGGED_IN_COOKIE ]   = wp_generate_auth_cookie( $editor, $expiration, 'logged_in' );

		// What core has already done by the time the permission callback runs.
		wp_set_current_user( 0 );

		$this->assertSame(
			$editor,
			\SRFM\Inc\Helper::get_submitting_user_id(),
			'Sanity: the auth cookie must still identify the editor after the reset.'
		);

		$this->assertFalse(
			$this->call_private_method( $this->views, 'should_track' ),
			'An editor browsing their own site must not be counted as a visitor.'
		);

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		wp_set_current_user( 0 );
		wp_delete_user( $editor );
	}

	// ──────────────────────────────────────────────
	// is_tracking_enabled (global General-settings toggle)
	// ──────────────────────────────────────────────

	public function test_is_tracking_enabled() {
		// Opt-in: absent option, absent key and a corrupted non-array value all deny.
		delete_option( 'srfm_general_settings_options' );
		$this->assertFalse( $this->views->is_tracking_enabled(), 'Absent option must default to disabled.' );

		update_option( 'srfm_general_settings_options', [ 'srfm_ip_log' => true ] );
		$this->assertFalse( $this->views->is_tracking_enabled(), 'Absent key must default to disabled.' );

		update_option( 'srfm_general_settings_options', 'not-an-array' );
		$this->assertFalse( $this->views->is_tracking_enabled(), 'A corrupted value must deny, not fatal.' );

		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		$this->assertFalse( $this->views->is_tracking_enabled(), 'Explicit false should hide the columns.' );

		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		$this->assertTrue( $this->views->is_tracking_enabled(), 'Explicit true should show the columns.' );

		delete_option( 'srfm_general_settings_options' );
	}

	/**
	 * Counting is gated on the tracking-started stamp, not on the display toggle.
	 *
	 * Before the feature has ever been switched on, nothing is counted — a hidden
	 * column that was quietly accumulating data was never really "off by default".
	 * Once the window is open, hiding the columns again only hides them, so
	 * switching back on reveals the period rather than a gap.
	 */
	public function test_should_track_follows_the_tracking_window() {
		wp_set_current_user( 0 );
		delete_option( 'srfm_general_settings_options' );

		// Never enabled → no stamp → nothing counted.
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		$this->assertFalse( $this->call_private_method( $this->views, 'should_track' ), 'No stamp means counting has not started.' );

		// Enabling writes the stamp, and counting begins.
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		$this->assertGreaterThan( 0, $this->views->get_tracking_started_at(), 'Enabling should open the window.' );
		$this->assertTrue( $this->call_private_method( $this->views, 'should_track' ), 'Counting should run once the window is open.' );

		// Disabling hides the columns but must NOT stop counting.
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		$this->assertFalse( $this->views->is_tracking_enabled(), 'The columns should be hidden.' );
		$this->assertTrue( $this->call_private_method( $this->views, 'should_track' ), 'Counting must continue while the columns are hidden.' );

		delete_option( 'srfm_general_settings_options' );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
	}

	// ──────────────────────────────────────────────
	// get_tracking_started_at (conversion-rate window)
	// ──────────────────────────────────────────────

	public function test_get_tracking_started_at() {
		delete_option( 'srfm_general_settings_options' );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );

		// A pure read: asking must never open the window, or a site that never
		// enabled the feature would start a window just by rendering the list.
		$this->assertSame( 0, $this->views->get_tracking_started_at(), 'Never enabled must read as 0.' );
		$this->assertFalse( get_option( Form_Views::TRACKING_STARTED_OPTION ), 'Reading must not write the stamp.' );

		$before = time();
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		$after   = time();
		$started = $this->views->get_tracking_started_at();

		$this->assertGreaterThanOrEqual( $before, $started, 'Enabling should stamp the current time.' );
		$this->assertLessThanOrEqual( $after, $started, 'Enabling should stamp the current time.' );

		// Write-once. An off/on cycle must not move the window, or the stored view
		// counts would be measured against an entry window shorter than they cover.
		//
		// Backdated deliberately: comparing against the stamp written a moment ago
		// would pass even with update_option(), because both writes land in the same
		// second. A known-old value is what actually distinguishes the two.
		update_option( Form_Views::TRACKING_STARTED_OPTION, 1000000000 );
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		$this->assertSame( 1000000000, $this->views->get_tracking_started_at(), 'Re-enabling must not re-stamp an open window.' );

		// Not autoloaded — this is read on the Forms list screen and by the beacon.
		global $wpdb;
		$autoload = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table name from $wpdb.
				Form_Views::TRACKING_STARTED_OPTION
			)
		);
		// WP 6.6+ stores 'off' for an explicit false; older cores store 'no'.
		$this->assertContains( $autoload, [ 'no', 'off' ], 'Stamp must not be autoloaded on every request.' );

		// Saving with the toggle off must never open a window.
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		$this->assertSame( 0, $this->views->get_tracking_started_at(), 'Saving while off must not open the window.' );

		delete_option( 'srfm_general_settings_options' );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
	}

	/**
	 * The stamp writer, called directly with each hook's argument shape.
	 *
	 * The other tests drive this through update_option(), which only ever exercises
	 * the `update_option_*` shape. This pins the contract that makes both hooks
	 * work: the new value is the SECOND parameter in both, even though their first
	 * parameters differ —
	 * `do_action( "update_option_{$option}", $old_value, $value, $option )` versus
	 * `do_action( "add_option_{$option}", $option, $value )`. An implementation that
	 * read the first argument would stamp off the previous value on one hook and off
	 * the option name on the other, and the add_option case is the fresh install
	 * this whole mechanism exists for.
	 */
	public function test_maybe_start_tracking() {
		$enabled  = [ 'srfm_form_views_tracking' => true ];
		$disabled = [ 'srfm_form_views_tracking' => false ];

		// update_option_* shape: ( $old_value, $value ).
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		$this->views->maybe_start_tracking( $disabled, $enabled );
		$this->assertGreaterThan( 0, $this->views->get_tracking_started_at(), 'The update_option_* shape should stamp.' );

		// add_option_* shape: ( $option_name, $value ) — a string first argument.
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		$this->views->maybe_start_tracking( 'srfm_general_settings_options', $enabled );
		$this->assertGreaterThan( 0, $this->views->get_tracking_started_at(), 'The add_option_* shape should stamp.' );

		// Write-once: an already-open window is never moved.
		update_option( Form_Views::TRACKING_STARTED_OPTION, 1000000000 );
		$this->views->maybe_start_tracking( $disabled, $enabled );
		$this->assertSame( 1000000000, $this->views->get_tracking_started_at(), 'An open window must not be moved.' );

		// Everything else denies without an explicit branch: toggle off, key absent,
		// empty array, and a corrupted non-array value.
		foreach ( [ $disabled, [ 'srfm_ip_log' => true ], [], 'not-an-array', null ] as $value ) {
			delete_option( Form_Views::TRACKING_STARTED_OPTION );
			$this->views->maybe_start_tracking( null, $value );
			$this->assertSame( 0, $this->views->get_tracking_started_at(), 'Only an enabled toggle may open the window.' );
		}

		delete_option( Form_Views::TRACKING_STARTED_OPTION );
	}

	// ──────────────────────────────────────────────
	// rate limiting
	// ──────────────────────────────────────────────

	/**
	 * IPs collapse to the network an attacker would have to rotate out of.
	 *
	 * A per-address bucket is no limit at all: a single actor routinely controls
	 * every address in an IPv6 /64, so each request could mint a fresh allowance —
	 * and, without a persistent object cache, a fresh pair of wp_options rows.
	 */
	public function test_network_bucket() {
		$bucket = function ( $ip ) {
			$m = new ReflectionMethod( Form_Views::class, 'network_bucket' );
			$m->setAccessible( true );
			return $m->invoke( null, $ip );
		};

		// Same IPv4 /24 → same bucket; different /24 → different.
		$this->assertSame( $bucket( '203.0.113.5' ), $bucket( '203.0.113.200' ), 'A /24 must share one bucket.' );
		$this->assertNotSame( $bucket( '203.0.113.5' ), $bucket( '203.0.114.5' ), 'Different /24s must not share.' );

		// Same IPv6 /64 → same bucket. This is the rotation an attacker gets for free.
		$this->assertSame(
			$bucket( '2001:db8:1:2::1' ),
			$bucket( '2001:db8:1:2:ffff:ffff:ffff:ffff' ),
			'A /64 must share one bucket.'
		);
		$this->assertNotSame(
			$bucket( '2001:db8:1:2::1' ),
			$bucket( '2001:db8:1:3::1' ),
			'Different /64s must not share.'
		);
	}

	/**
	 * The counter increments per hit and reports its running total.
	 *
	 * The previous implementation read a transient, compared, then wrote it back, so
	 * concurrent requests all read the same value and the limit only ever bound
	 * sequential traffic — on the one control standing between an anonymous caller
	 * and an unbounded write loop.
	 */
	public function test_hit_counter() {
		$hit = function ( $key ) {
			$m = new ReflectionMethod( Form_Views::class, 'hit_counter' );
			$m->setAccessible( true );
			return $m->invoke( null, $key );
		};

		$key = 'srfm_test_' . wp_rand();
		$this->assertSame( 1, $hit( $key ), 'First hit opens the window at 1.' );
		$this->assertSame( 2, $hit( $key ), 'Second hit increments.' );
		$this->assertSame( 3, $hit( $key ) );

		// Buckets are independent, or one busy form would throttle every other.
		$this->assertSame( 1, $hit( $key . '_other' ), 'A different key starts its own count.' );
	}

	// ──────────────────────────────────────────────
	// permissions_check (HMAC token)
	// ──────────────────────────────────────────────

	public function test_permissions_check_rejects_missing_token() {
		$form_id = $this->make_form();
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'form_id', $form_id );

		$result = $this->views->permissions_check( $request );
		$this->assertInstanceOf( 'WP_Error', $result );

		wp_delete_post( $form_id, true );
	}

	public function test_permissions_check_accepts_valid_token() {
		$form_id = $this->make_form();

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'form_id', $form_id );

		// A view-namespaced token is what the beacon sends, and it is accepted.
		$request->set_header( 'X-WP-Submit-Token', Submit_Token::generate( (int) $form_id, Submit_Token::NAMESPACE_VIEW ) );
		$this->assertTrue( $this->views->permissions_check( $request ) );

		// A submission token must NOT authorise counting. The two are separately
		// namespaced so a token scraped from the page cannot be repurposed, and so
		// this endpoint cannot be used as an oracle for whether a submit token is
		// still inside an accepted window.
		$request->set_header( 'X-WP-Submit-Token', Submit_Token::generate( (int) $form_id ) );
		$this->assertInstanceOf( 'WP_Error', $this->views->permissions_check( $request ), 'A submit token must not authorise the view beacon.' );

		wp_delete_post( $form_id, true );
	}

	// ──────────────────────────────────────────────
	// track_view (end to end)
	// ──────────────────────────────────────────────

	public function test_track_view_counts_for_anonymous_visitor() {
		$form_id = $this->make_form();
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'form_id', $form_id );

		$response = $this->views->track_view( $request );
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, $this->views->get_views( $form_id ) );

		wp_delete_post( $form_id, true );
	}

	public function test_track_view_does_not_count_privileged_user() {
		$form_id = $this->make_form();
		$admin   = wp_insert_user(
			[
				'user_login' => 'srfm_views_admin_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_views_admin_' . wp_rand() . '@example.com',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $admin );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/track-view' );
		$request->set_param( 'form_id', $form_id );

		$response = $this->views->track_view( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $this->views->get_views( $form_id ), 'Privileged users must not be counted.' );

		wp_set_current_user( 0 );
		wp_delete_user( $admin );
		wp_delete_post( $form_id, true );
	}

	// ──────────────────────────────────────────────
	// register_route / localize_beacon
	// ──────────────────────────────────────────────

	/**
	 * register_route adds the public forms/track-view POST route to the endpoints array.
	 */
	public function test_register_route() {
		$endpoints = $this->views->register_route( [] );

		$this->assertIsArray( $endpoints );
		$this->assertArrayHasKey( 'forms/track-view', $endpoints );
		$this->assertSame( 'POST', $endpoints['forms/track-view']['methods'] );
		$this->assertArrayHasKey( 'callback', $endpoints['forms/track-view'] );
		$this->assertArrayHasKey( 'permission_callback', $endpoints['forms/track-view'] );
		$this->assertArrayHasKey( 'form_id', $endpoints['forms/track-view']['args'] );

		// Non-array input is returned unchanged (defensive guard).
		$this->assertSame( 'unchanged', $this->views->register_route( 'unchanged' ) );
	}

	/**
	 * localize_beacon is a safe no-op when the form-submit script is not enqueued,
	 * and localizes the beacon flag onto it when it is.
	 */
	public function test_localize_beacon() {
		// Not enqueued → early return, nothing localized, no error.
		wp_dequeue_script( 'srfm-form-submit' );
		$this->views->localize_beacon();
		$this->assertFalse( wp_script_is( 'srfm-form-submit', 'enqueued' ) );

		// Enqueued → beacon data is attached to the script.
		wp_register_script( 'srfm-form-submit', '', [], '1.0.0', true );
		wp_enqueue_script( 'srfm-form-submit' );
		$this->views->localize_beacon();

		$data = wp_scripts()->get_data( 'srfm-form-submit', 'data' );
		$this->assertIsString( $data );
		$this->assertStringContainsString( 'srfm_view_beacon', $data );

		wp_dequeue_script( 'srfm-form-submit' );
		wp_deregister_script( 'srfm-form-submit' );
	}

	/**
	 * The localized flag must not depend on anything request-specific.
	 *
	 * localize_beacon() prints into HTML that a full-page cache stores and replays to
	 * every visitor. If the flag were computed from should_track(), whichever request
	 * happened to populate the cache would freeze its own answer for everyone — a page
	 * first cached while an editor was logged in would bake in '0' and silently stop
	 * counting site-wide until that cache entry expired. It is therefore gated on the
	 * site-wide tracking stamp, and the per-request exclusions stay in track_view().
	 */
	public function test_localize_beacon_flag_is_cache_safe() {
		wp_register_script( 'srfm-form-submit', '', [], '1.0.0', true );
		wp_enqueue_script( 'srfm-form-submit' );

		$editor = wp_insert_user(
			[
				'user_login' => 'srfm_views_beacon_editor_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_views_beacon_editor_' . wp_rand() . '@example.com',
				'role'       => 'editor',
			]
		);
		wp_set_current_user( $editor );

		// This user must be excluded from counting...
		$this->assertFalse(
			$this->call_private_method( $this->views, 'should_track' ),
			'Sanity: an editor must not be counted.'
		);

		// ...but the cacheable flag must still say the site is counting, or their
		// page view would disable the beacon for every later reader of that cache entry.
		$this->views->localize_beacon();
		$data = wp_scripts()->get_data( 'srfm-form-submit', 'data' );

		$this->assertStringContainsString( '"enabled":"1"', (string) $data );
		$this->assertStringContainsString( 'track-view', (string) $data, 'The beacon needs a resolved REST URL to fetch().' );

		wp_set_current_user( 0 );
		wp_delete_user( $editor );
		wp_dequeue_script( 'srfm-form-submit' );
		wp_deregister_script( 'srfm-form-submit' );
	}

	/**
	 * The admin_init repair opens a window only when the toggle is on and unstamped.
	 *
	 * This exists for settings that arrive by a route firing neither option hook — an
	 * import, a partial restore, a direct $wpdb write — which would otherwise show the
	 * columns while nothing was ever counted. It must not open a window on a site that
	 * never enabled the feature, and it must never move a window that is already open:
	 * the stored counts cover the original period, so advancing the stamp would measure
	 * them against a shorter entry window and inflate the conversion rate.
	 */
	public function test_maybe_repair_tracking_window() {
		// Toggle off → nothing to repair, and no window may be opened.
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		$this->views->maybe_repair_tracking_window();
		$this->assertSame( 0, $this->views->get_tracking_started_at(), 'A disabled site must not start counting.' );

		// Toggle on but never stamped (the imported-settings case) → repair it.
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
		$this->views->maybe_repair_tracking_window();
		$stamp = $this->views->get_tracking_started_at();
		$this->assertGreaterThan( 0, $stamp, 'An enabled but unstamped site must have its window opened.' );

		// Already stamped → must be left exactly where it was.
		$original = $stamp - DAY_IN_SECONDS;
		update_option( Form_Views::TRACKING_STARTED_OPTION, $original );
		$this->views->maybe_repair_tracking_window();
		$this->assertSame( $original, $this->views->get_tracking_started_at(), 'An open window must never be moved forward.' );

		delete_option( 'srfm_general_settings_options' );
		delete_option( Form_Views::TRACKING_STARTED_OPTION );
	}

	/**
	 * Two racing first-views can leave two meta rows; the counter must self-repair.
	 *
	 * wp_postmeta has no unique index on ( post_id, meta_key ), so add_post_meta()'s
	 * $unique flag is a SELECT followed by an INSERT. Left alone, every later UPDATE
	 * increments both rows while get_post_meta() reads only the first — the form
	 * silently reports roughly half its views for the rest of its life.
	 */
	public function test_duplicate_view_rows_are_collapsed_without_losing_counts() {
		$form_id = $this->make_form();

		// Simulate the race directly: two rows for the same key.
		add_post_meta( $form_id, Form_Views::META_KEY, 7 );
		add_post_meta( $form_id, Form_Views::META_KEY, 5 );
		$this->assertCount( 2, get_post_meta( $form_id, Form_Views::META_KEY, false ), 'Sanity: the race state exists.' );

		$this->call_private_method( $this->views, 'collapse_duplicate_view_rows', [ $form_id ] );

		$rows = get_post_meta( $form_id, Form_Views::META_KEY, false );
		$this->assertCount( 1, $rows, 'Duplicates must be folded into a single row.' );
		$this->assertSame( 12, $this->views->get_views( $form_id ), 'No counted view may be discarded by the repair.' );

		wp_delete_post( $form_id, true );
	}
}
