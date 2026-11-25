<?php
/**
 * Class Test_Promotion
 *
 * @package sureforms
 */

use SRFM\Inc\Admin\Promotion;

/**
 * Tests for the time-boxed sale bar above the SureForms admin header.
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
	 * A timestamp inside the sale window.
	 *
	 * @var int
	 */
	protected $during;

	protected function setUp(): void {
		parent::setUp();
		$this->promotion     = Promotion::get_instance();
		$this->admin_id      = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->during        = strtotime( Promotion::STARTS_AT . ' UTC' ) + DAY_IN_SECONDS;
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		remove_all_filters( 'srfm_hide_promotions' );
		parent::tearDown();
	}

	/**
	 * The window opens at the start and closes at the end, both in UTC.
	 */
	public function test_is_live_window_boundaries() {
		$start = strtotime( Promotion::STARTS_AT . ' UTC' );
		$end   = strtotime( Promotion::ENDS_AT . ' UTC' );

		$this->assertFalse( $this->promotion->is_live( $start - 1 ), 'Closed before the start.' );
		$this->assertTrue( $this->promotion->is_live( $start ), 'Open at the start.' );
		$this->assertTrue( $this->promotion->is_live( $end - 1 ), 'Open until the last second.' );
		$this->assertFalse( $this->promotion->is_live( $end ), 'Closed at the end.' );
		$this->assertFalse( $this->promotion->is_live( $end + YEAR_IN_SECONDS ), 'Stays closed later.' );
	}

	/**
	 * An administrator gets the bar during the sale, with a tagged pricing link.
	 */
	public function test_admin_gets_promotion_during_sale() {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$this->markTestSkipped( 'SureForms Pro is loaded, so the bar is hidden.' );
		}
		wp_set_current_user( $this->admin_id );

		$data = $this->promotion->get_for_current_user( $this->during );

		$this->assertIsArray( $data );
		$this->assertSame( Promotion::ID, $data['id'] );
		$this->assertSame( strtotime( Promotion::ENDS_AT . ' UTC' ), $data['ends_at'] );
		$this->assertNotEmpty( $data['items'] );

		$query = [];
		parse_str( (string) wp_parse_url( $data['cta']['url'], PHP_URL_QUERY ), $query );
		$this->assertSame( 'top-bar', $query['utm_medium'] ?? null );
		$this->assertSame( 'bfcm-2026', $query['utm_campaign'] ?? null );
		$this->assertSame( 'sureforms_plugin', $query['utm_source'] ?? null );
		$this->assertSame( 'black-friday', $query['utm_content'] ?? null );
	}

	/**
	 * Each sale period starts at its calendar time, the last one runs until
	 * the end of the sale, and there is no period outside the sale.
	 */
	public function test_phase_follows_calendar() {
		$previous = '';
		foreach ( Promotion::PHASES as $phase => $starts_at ) {
			$start = strtotime( $starts_at . ' UTC' );
			$this->assertSame( $previous, $this->promotion->get_phase( $start - 1 ), "Before {$phase}." );
			$this->assertSame( $phase, $this->promotion->get_phase( $start ), "At the start of {$phase}." );
			$previous = $phase;
		}

		$end = strtotime( Promotion::ENDS_AT . ' UTC' );
		$this->assertSame( 'final-hours', $this->promotion->get_phase( $end - 1 ) );
		$this->assertSame( '', $this->promotion->get_phase( $end ), 'After the sale.' );
		$this->assertSame( Promotion::STARTS_AT, Promotion::PHASES['black-friday'], 'The first period opens the sale.' );
	}

	/**
	 * Every period has its own copy and button, tagged with the period. The
	 * copy never names Pro, and an annual price always says it is for the
	 * first year.
	 */
	public function test_each_phase_copy_rules() {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$this->markTestSkipped( 'SureForms Pro is loaded, so the bar is hidden.' );
		}
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
				if ( false !== strpos( $text, '$49' ) ) {
					$this->assertStringContainsString( 'for the first year', $text, "{$phase}: {$text}" );
				}
			}
		}

		$this->assertCount( count( Promotion::PHASES ), array_unique( $labels ), 'Each period has its own button.' );
	}

	/**
	 * Every case that should hide the bar returns null.
	 */
	public function test_promotion_hidden_cases() {
		$end = strtotime( Promotion::ENDS_AT . ' UTC' );

		wp_set_current_user( 0 );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Logged out.' );

		wp_set_current_user( $this->subscriber_id );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Cannot manage SureForms.' );

		wp_set_current_user( $this->admin_id );
		$this->assertNull( $this->promotion->get_for_current_user( $end ), 'After the sale.' );

		add_filter( 'srfm_hide_promotions', '__return_true' );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Promotions hidden.' );
		remove_all_filters( 'srfm_hide_promotions' );

		update_user_meta( $this->admin_id, Promotion::DISMISSED_META, Promotion::ID );
		$this->assertNull( $this->promotion->get_for_current_user( $this->during ), 'Dismissed.' );
	}

	/**
	 * Dismissing an earlier sale does not hide this one.
	 */
	public function test_other_dismissed_id_does_not_hide() {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$this->markTestSkipped( 'SureForms Pro is loaded, so the bar is hidden.' );
		}
		wp_set_current_user( $this->admin_id );
		update_user_meta( $this->admin_id, Promotion::DISMISSED_META, 'sureforms-bfcm-2025' );

		$this->assertIsArray( $this->promotion->get_for_current_user( $this->during ) );
	}

	/**
	 * The dismiss route is registered and needs the SureForms admin capability.
	 */
	public function test_dismiss_route_requires_capability() {
		$endpoints = $this->promotion->register_routes( [] );

		$this->assertArrayHasKey( 'promotion/dismiss', $endpoints );
		$permission = $endpoints['promotion/dismiss']['permission_callback'];

		wp_set_current_user( 0 );
		$this->assertFalse( (bool) call_user_func( $permission ), 'Logged out.' );

		wp_set_current_user( $this->subscriber_id );
		$this->assertFalse( (bool) call_user_func( $permission ), 'Subscriber.' );

		wp_set_current_user( $this->admin_id );
		$this->assertTrue( (bool) call_user_func( $permission ), 'Administrator.' );
	}

	/**
	 * A missing or wrong nonce is rejected and nothing is stored.
	 */
	public function test_dismiss_rejects_bad_nonce() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/promotion/dismiss' );
		$request->set_param( 'id', Promotion::ID );

		$response = $this->promotion->rest_dismiss( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 403, $response->get_error_data()['status'] );
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );

		$request->set_header( 'X-WP-Nonce', 'not-a-nonce' );
		$this->assertInstanceOf( WP_Error::class, $this->promotion->rest_dismiss( $request ) );
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );
	}

	/**
	 * Only the running promotion id can be dismissed; anything else is
	 * rejected and the submitted value is never stored.
	 *
	 * @dataProvider invalid_ids
	 *
	 * @param mixed $id Submitted id.
	 */
	public function test_dismiss_rejects_unknown_id( $id ) {
		wp_set_current_user( $this->admin_id );

		$request = $this->signed_request();
		if ( null !== $id ) {
			$request->set_param( 'id', $id );
		}

		$response = $this->promotion->rest_dismiss( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertSame( '', get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );
	}

	/**
	 * Ids that must not be accepted.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function invalid_ids() {
		return [
			'absent'        => [ null ],
			'empty'         => [ '' ],
			'older sale'    => [ 'sureforms-bfcm-2025' ],
			'array'         => [ [ Promotion::ID ] ],
			'padded'        => [ ' ' . Promotion::ID ],
			'other product' => [ 'surecookie-bfcm-2026' ],
		];
	}

	/**
	 * A valid request stores the dismissal for the current user only.
	 */
	public function test_dismiss_stores_for_current_user() {
		wp_set_current_user( $this->admin_id );

		$request = $this->signed_request();
		$request->set_param( 'id', Promotion::ID );

		$response = $this->promotion->rest_dismiss( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( Promotion::ID, get_user_meta( $this->admin_id, Promotion::DISMISSED_META, true ) );

		$other_admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->assertSame( '', get_user_meta( $other_admin, Promotion::DISMISSED_META, true ) );
	}

	/**
	 * Build a request carrying a valid REST nonce for the current user.
	 *
	 * @return WP_REST_Request
	 */
	private function signed_request() {
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/promotion/dismiss' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		return $request;
	}
}
