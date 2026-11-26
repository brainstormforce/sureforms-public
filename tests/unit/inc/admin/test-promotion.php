<?php
/**
 * Class Test_Promotion
 *
 * @package sureforms
 */

use SRFM\Inc\Admin\Promotion;
use SRFM\Inc\Helper;

/**
 * Tests for the time-boxed sale bar above the SureForms admin header.
 *
 * Every count is checked against a pinned timestamp, so the tests keep their
 * meaning after the sale has ended.
 */
class Test_Promotion extends SRFM_Unit_Test_Case {

	/**
	 * Promotion instance.
	 *
	 * @var Promotion
	 */
	protected $promotion;

	/**
	 * Administrator user id.
	 *
	 * @var int
	 */
	protected $admin_id;

	/**
	 * Subscriber user id.
	 *
	 * @var int
	 */
	protected $subscriber_id;

	/**
	 * Users created by the test, deleted in tearDown(): this test case has no
	 * per-test transaction.
	 *
	 * @var array<int, int>
	 */
	protected $user_ids = [];

	/**
	 * A timestamp inside the sale window, in the black-friday period.
	 *
	 * @var int
	 */
	protected $during;

	/**
	 * The end of the sale, when the bar stops showing.
	 *
	 * @var int
	 */
	protected $end;

	protected function setUp(): void {
		parent::setUp();
		$this->promotion     = Promotion::get_instance();
		$this->admin_id      = $this->create_user( 'administrator' );
		$this->subscriber_id = $this->create_user( 'subscriber' );
		$this->during        = strtotime( Promotion::STARTS_AT . ' UTC' ) + DAY_IN_SECONDS;
		$this->end           = strtotime( Promotion::ENDS_AT . ' UTC' );
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		remove_filter( 'srfm_hide_promotions', '__return_true' );
		delete_option( Promotion::STATS_OPTION );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $this->user_ids as $user_id ) {
			wp_delete_user( $user_id );
		}
		$this->user_ids = [];

		parent::tearDown();
	}

	/**
	 * The window opens at the start and closes at the end, both in UTC.
	 */
	public function test_is_live_window_boundaries() {
		$start = strtotime( Promotion::STARTS_AT . ' UTC' );

		$this->assertFalse( $this->promotion->is_live( $start - 1 ), 'Closed before the start.' );
		$this->assertTrue( $this->promotion->is_live( $start ), 'Open at the start.' );
		$this->assertTrue( $this->promotion->is_live( $this->end - 1 ), 'Open until the last second.' );
		$this->assertFalse( $this->promotion->is_live( $this->end ), 'Closed at the end.' );
		$this->assertFalse( $this->promotion->is_live( $this->end + YEAR_IN_SECONDS ), 'Stays closed later.' );
	}

	/**
	 * The counts are reported through the sale and for 30 days after it, then
	 * never again.
	 */
	public function test_is_reporting_window() {
		$this->assertTrue( $this->promotion->is_reporting( $this->during ), 'During the sale.' );
		$this->assertTrue( $this->promotion->is_reporting( $this->end ), 'At the end of the sale.' );
		$this->assertTrue( $this->promotion->is_reporting( $this->end + Promotion::REPORTING_GRACE - 1 ), 'Until the grace period ends.' );
		$this->assertFalse( $this->promotion->is_reporting( $this->end + Promotion::REPORTING_GRACE ), 'Stops after the grace period.' );
		$this->assertSame( 30 * DAY_IN_SECONDS, Promotion::REPORTING_GRACE );
	}

	/**
	 * An administrator gets the bar during the sale, with a tagged pricing link.
	 */
	public function test_get_for_current_user_during_sale() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$data = $this->promotion->get_for_current_user( $this->during );

		$this->assertIsArray( $data );
		$this->assertSame( Promotion::ID, $data['id'] );
		$this->assertSame( $this->end, $data['ends_at'] );
		$this->assertNotEmpty( $data['items'] );
		$this->assertStringStartsWith( SRFM_WEBSITE . 'pricing/?', $data['cta']['url'] );

		$query = [];
		parse_str( (string) wp_parse_url( $data['cta']['url'], PHP_URL_QUERY ), $query );
		$this->assertSame( 'top-bar', $query['utm_medium'] ?? null );
		$this->assertSame( 'bfcm-2026', $query['utm_campaign'] ?? null );
		$this->assertSame( 'sureforms_plugin', $query['utm_source'] ?? null );
		$this->assertSame( 'black-friday', $query['utm_content'] ?? null );
	}

	/**
	 * Building the bar's data never writes: it runs on every admin page, and
	 * the bar is counted as shown only once it is on screen.
	 */
	public function test_get_for_current_user_is_read_only() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$this->assertIsArray( $this->promotion->get_for_current_user( $this->during ) );

		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::SEEN_META, true ), 'No seen flag.' );
		$this->assertFalse( get_option( Promotion::STATS_OPTION ), 'No count.' );
	}

	/**
	 * Each sale period starts at its calendar time, the last one runs until
	 * the end of the sale, and there is no period outside the sale.
	 */
	public function test_get_phase_follows_calendar() {
		$previous = '';
		foreach ( Promotion::PHASES as $phase => $starts_at ) {
			$start = strtotime( $starts_at . ' UTC' );
			$this->assertSame( $previous, $this->promotion->get_phase( $start - 1 ), "Before {$phase}." );
			$this->assertSame( $phase, $this->promotion->get_phase( $start ), "At the start of {$phase}." );
			$previous = $phase;
		}

		$this->assertSame( 'final-hours', $this->promotion->get_phase( $this->end - 1 ) );
		$this->assertSame( '', $this->promotion->get_phase( $this->end ), 'After the sale.' );
		$this->assertSame( Promotion::STARTS_AT, Promotion::PHASES['black-friday'], 'The first period opens the sale.' );
	}

	/**
	 * Every period has its own copy and button, tagged with the period. The
	 * copy never names Pro or annual plans, and the entry price always reads
	 * "Plans start at $49".
	 */
	public function test_get_for_current_user_copy_rules() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$labels = [];
		foreach ( Promotion::PHASES as $phase => $starts_at ) {
			$data = $this->promotion->get_for_current_user( strtotime( $starts_at . ' UTC' ) );

			$this->assertIsArray( $data, $phase );
			$this->assertSame( $phase, $data['phase'] );
			$this->assertNotEmpty( $data['items'], $phase );
			$labels[] = $data['cta']['label'];

			$query = [];
			parse_str( (string) wp_parse_url( $data['cta']['url'], PHP_URL_QUERY ), $query );
			$this->assertSame( $phase, $query['utm_content'] ?? null );

			foreach ( $data['items'] as $segment ) {
				$text = implode( ' ', wp_list_pluck( $segment, 'text' ) );
				$this->assertStringNotContainsString( 'Pro', $text, "{$phase}: {$text}" );
				$this->assertStringNotContainsString( 'Annual', $text, "{$phase}: {$text}" );
				$this->assertStringNotContainsString( '%s', $text, "{$phase}: placeholder left in the copy." );
				if ( false !== strpos( $text, '$49' ) ) {
					$this->assertSame( 'Plans start at $49', $text, "{$phase}: the entry price reads the same everywhere." );
				}
			}
		}

		$this->assertCount( count( Promotion::PHASES ), array_unique( $labels ), 'Each period has its own button.' );
	}

	/**
	 * A price sits inside one translatable sentence, so translators can move
	 * it, and it still gets its own highlight.
	 */
	public function test_get_for_current_user_prices_are_placeholders_in_a_sentence() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$moved = static function ( $translation, $text ) {
			return 'Plans start at %s' === $text ? '%s is the lowest price' : $translation;
		};
		add_filter( 'gettext', $moved, 10, 2 );

		try {
			$data = $this->promotion->get_for_current_user( $this->during );
		} finally {
			remove_filter( 'gettext', $moved, 10 );
		}

		$this->assertContains(
			[
				[
					'text'  => '$49',
					'style' => 'accent',
				],
				[
					'text'  => 'is the lowest price',
					'style' => 'plain',
				],
			],
			$data['items'],
			'The translated order is kept and the price keeps its highlight.'
		);
	}

	/**
	 * Every case that should hide the bar returns null.
	 */
	public function test_get_for_current_user_hidden_cases() {
		wp_set_current_user( 0 );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Logged out.' );

		wp_set_current_user( $this->subscriber_id );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Cannot manage SureForms.' );

		wp_set_current_user( $this->admin_id );
		$this->assertNull( $this->promotion->get_for_current_user( $this->end ), 'After the sale.' );

		add_filter( 'srfm_hide_promotions', '__return_true' );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Promotions hidden.' );
		remove_filter( 'srfm_hide_promotions', '__return_true' );

		update_user_meta( $this->admin_id, Promotion::DISMISSED_META, Promotion::ID );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Dismissed.' );
	}

	/**
	 * Dismissing an earlier sale does not hide this one.
	 */
	public function test_get_for_current_user_ignores_other_dismissed_id() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );
		update_user_meta( $this->admin_id, Promotion::DISMISSED_META, 'sureforms-bfcm-2025' );

		$this->assertIsArray( $this->promotion->get_for_current_user( $this->during ) );
	}

	// ─── Routes ──────────────────────────────────────────────────

	/**
	 * Every route is registered, is a POST, and uses the same capability
	 * check as the other SureForms admin routes.
	 */
	public function test_register_routes_use_the_shared_capability_check() {
		$endpoints = $this->promotion->register_routes( [] );

		foreach ( [ 'shown', 'click', 'dismiss' ] as $action ) {
			$route = 'promotion/' . $action;
			$this->assertArrayHasKey( $route, $endpoints );
			$this->assertSame( 'POST', $endpoints[ $route ]['methods'], $route );
			$this->assertSame( [ Helper::class, 'get_items_permissions_check' ], $endpoints[ $route ]['permission_callback'], $route );
			$this->assertSame( [ $this->promotion, 'rest_' . $action ], $endpoints[ $route ]['callback'], $route );
		}

		$permission = $endpoints['promotion/shown']['permission_callback'];

		wp_set_current_user( 0 );
		$this->assertInstanceOf( WP_Error::class, call_user_func( $permission ), 'Logged out.' );

		wp_set_current_user( $this->subscriber_id );
		$this->assertInstanceOf( WP_Error::class, call_user_func( $permission ), 'Subscriber.' );

		wp_set_current_user( $this->admin_id );
		$this->assertTrue( call_user_func( $permission ), 'Administrator.' );
	}

	/**
	 * Other routes in the registry are kept.
	 */
	public function test_register_routes_keeps_existing_routes() {
		$endpoints = $this->promotion->register_routes( [ 'other' => [ 'methods' => 'GET' ] ] );

		$this->assertArrayHasKey( 'other', $endpoints );
		$this->assertSame( [ 'promotion/shown', 'promotion/click', 'promotion/dismiss' ], array_slice( array_keys( $endpoints ), 1 ) );
	}

	/**
	 * A missing or wrong nonce is rejected on every route and nothing is
	 * stored or counted.
	 *
	 * @dataProvider handlers
	 *
	 * @param string $handler REST callback name.
	 */
	public function test_rest_rejects_bad_nonce( $handler ) {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/promotion/x' );
		$request->set_param( 'id', Promotion::ID );

		$response = $this->promotion->$handler( $request );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 403, $response->get_error_data()['status'] );

		$request->set_header( 'X-WP-Nonce', 'not-a-nonce' );
		$this->assertInstanceOf( WP_Error::class, $this->promotion->$handler( $request ) );

		$this->assert_nothing_stored();
	}

	/**
	 * Only the running promotion id is accepted on every route; anything else
	 * is rejected and the submitted value is never stored.
	 *
	 * @dataProvider handlers_and_invalid_ids
	 *
	 * @param string $handler REST callback name.
	 * @param mixed  $id      Submitted id.
	 */
	public function test_rest_rejects_unknown_id( $handler, $id ) {
		wp_set_current_user( $this->admin_id );

		$request = $this->signed_request();
		if ( null !== $id ) {
			$request->set_param( 'id', $id );
		}

		$response = $this->promotion->$handler( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assert_nothing_stored();
	}

	/**
	 * REST callbacks.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function handlers() {
		return [
			'shown'   => [ 'rest_shown' ],
			'click'   => [ 'rest_click' ],
			'dismiss' => [ 'rest_dismiss' ],
		];
	}

	/**
	 * Every REST callback with every id that must not be accepted.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function handlers_and_invalid_ids() {
		$ids = [
			'absent'        => null,
			'empty'         => '',
			'older sale'    => 'sureforms-bfcm-2025',
			'array'         => [ Promotion::ID ],
			'padded'        => ' ' . Promotion::ID,
			'other product' => 'surecookie-bfcm-2026',
		];

		$cases = [];
		foreach ( $this->handlers() as $name => $handler ) {
			foreach ( $ids as $label => $id ) {
				$cases[ "{$name}, {$label}" ] = [ $handler[0], $id ];
			}
		}
		return $cases;
	}

	/**
	 * A valid dismiss request stores the dismissal for the current user only.
	 */
	public function test_rest_dismiss_stores_for_current_user() {
		wp_set_current_user( $this->admin_id );

		$request = $this->signed_request();
		$request->set_param( 'id', Promotion::ID );

		$response = $this->promotion->rest_dismiss( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( Promotion::ID, get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );

		$this->assertSame( '', get_user_meta( $this->create_user( 'administrator' ), Promotion::DISMISSED_META, true ) );
	}

	// ─── Analytics counts ────────────────────────────────────────

	/**
	 * Counts are kept per sale period, only for known actions, and nothing is
	 * counted outside the sale.
	 */
	public function test_record_counts_per_phase() {
		$cyber = strtotime( Promotion::PHASES['cyber-monday'] . ' UTC' );

		$this->assertTrue( $this->promotion->record( 'click', $this->during ) );
		$this->assertTrue( $this->promotion->record( 'click', $this->during ) );
		$this->assertTrue( $this->promotion->record( 'click', $cyber ) );
		$this->assertTrue( $this->promotion->record( 'dismiss', $cyber ) );

		$this->assertFalse( $this->promotion->record( 'click', $this->end ), 'After the sale.' );
		$this->assertFalse( $this->promotion->record( 'hover', $this->during ), 'Unknown action.' );

		$stats = $this->promotion->get_stats();
		$this->assertSame( 2, $stats['click']['black-friday'] );
		$this->assertSame( 1, $stats['click']['cyber-monday'] );
		$this->assertSame( 0, $stats['click']['final-hours'] );
		$this->assertSame( 1, $stats['dismiss']['cyber-monday'] );
		$this->assertSame( 0, $stats['shown']['black-friday'] );
		$this->assertArrayNotHasKey( 'hover', $stats );
	}

	/**
	 * A stored option of the wrong shape reads as zeros instead of breaking.
	 */
	public function test_get_stats_survives_bad_option() {
		update_option( Promotion::STATS_OPTION, 'garbage' );
		$this->assertSame( 0, $this->promotion->get_stats()['click']['black-friday'] );

		update_option( Promotion::STATS_OPTION, [ 'click' => [ 'black-friday' => 'x', 'other' => 5 ] ] );
		$stats = $this->promotion->get_stats();
		$this->assertSame( 0, $stats['click']['black-friday'] );
		$this->assertArrayNotHasKey( 'other', $stats['click'] );
	}

	/**
	 * Each user is counted as shown once, and only while the bar is showing to
	 * them; a user who cannot see it keeps their one count for later.
	 */
	public function test_record_shown_once_per_user_while_showing() {
		$this->skip_if_pro();

		wp_set_current_user( $this->subscriber_id );
		$this->assertFalse( $this->promotion->record_shown( $this->subscriber_id, $this->during ), 'Cannot manage SureForms.' );

		wp_set_current_user( $this->admin_id );
		$this->assertFalse( $this->promotion->record_shown( $this->admin_id, $this->end ), 'After the sale.' );
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::SEEN_META, true ), 'Not used up while hidden.' );

		$this->assertTrue( $this->promotion->record_shown( $this->admin_id, $this->during ) );
		$this->assertFalse( $this->promotion->record_shown( $this->admin_id, $this->during ), 'Once per user.' );
		$this->assertSame( 1, $this->promotion->get_stats()['shown']['black-friday'] );

		$other = $this->create_user( 'administrator' );
		wp_set_current_user( $other );
		update_user_meta( $other, Promotion::DISMISSED_META, Promotion::ID );
		$this->assertFalse( $this->promotion->record_shown( $other, $this->during ), 'Dismissed.' );

		$this->assertSame( 1, $this->promotion->get_stats()['shown']['black-friday'] );
	}

	/**
	 * The capability is checked for the user being counted, not whoever is
	 * logged in.
	 */
	public function test_record_shown_checks_the_given_user() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$this->assertFalse( $this->promotion->record_shown( $this->subscriber_id, $this->during ) );
		$this->assertSame( 0, $this->promotion->get_stats()['shown']['black-friday'] );
	}

	/**
	 * A click is counted only while the bar is showing to the user.
	 */
	public function test_record_click_only_while_showing() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$this->assertTrue( $this->promotion->record_click( $this->admin_id, $this->during ) );
		$this->assertFalse( $this->promotion->record_click( $this->admin_id, $this->end ), 'After the sale.' );

		update_user_meta( $this->admin_id, Promotion::DISMISSED_META, Promotion::ID );
		$this->assertFalse( $this->promotion->record_click( $this->admin_id, $this->during ), 'Dismissed.' );

		$this->assertSame( 1, $this->promotion->get_stats()['click']['black-friday'] );
	}

	/**
	 * A dismissal is stored every time but counted once per user, and only
	 * while the bar was showing to them.
	 */
	public function test_dismiss_counted_once_while_showing() {
		$this->skip_if_pro();
		wp_set_current_user( $this->admin_id );

		$this->assertTrue( $this->promotion->dismiss( $this->admin_id, $this->during ) );
		$this->assertTrue( $this->promotion->dismiss( $this->admin_id, $this->during ), 'Idempotent.' );
		$this->assertSame( 1, $this->promotion->get_stats()['dismiss']['black-friday'] );

		$late = $this->create_user( 'administrator' );
		wp_set_current_user( $late );
		$this->assertTrue( $this->promotion->dismiss( $late, $this->end - 1 ) );
		$this->assertSame( Promotion::ID, get_user_meta( $late, Promotion::DISMISSED_META, true ) );
		$this->assertSame( 1, $this->promotion->get_stats()['dismiss']['final-hours'], 'Counted while showing.' );

		add_filter( 'srfm_hide_promotions', '__return_true' );
		$hidden = $this->create_user( 'administrator' );
		wp_set_current_user( $hidden );
		$this->assertTrue( $this->promotion->dismiss( $hidden, $this->during ) );
		$this->assertSame( Promotion::ID, get_user_meta( $hidden, Promotion::DISMISSED_META, true ), 'Still stored.' );
		$this->assertSame( 1, $this->promotion->get_stats()['dismiss']['black-friday'], 'Not counted while hidden.' );
	}

	/**
	 * Create a user and remember it for tearDown().
	 *
	 * @param string $role Role.
	 * @return int User id.
	 */
	private function create_user( $role ) {
		$user_id          = self::factory()->user->create( [ 'role' => $role ] );
		$this->user_ids[] = $user_id;
		return $user_id;
	}

	/**
	 * Pro hides the bar, so cases that need it showing cannot run with Pro
	 * loaded in the same process.
	 */
	private function skip_if_pro() {
		if ( Helper::has_pro() ) {
			$this->markTestSkipped( 'SureForms Pro is loaded, so the bar is hidden.' );
		}
	}

	/**
	 * Nothing was written for the administrator.
	 */
	private function assert_nothing_stored() {
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::SEEN_META, true ) );
		$this->assertSame( 0, array_sum( array_map( 'array_sum', $this->promotion->get_stats() ) ) );
	}

	/**
	 * Build a request carrying a valid REST nonce for the current user.
	 *
	 * @return WP_REST_Request
	 */
	private function signed_request() {
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/promotion/x' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		return $request;
	}
}
