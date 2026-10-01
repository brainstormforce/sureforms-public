<?php
/**
 * Class Test_Suremail_Adapter
 *
 * @package sureforms
 */

use SRFM\Inc\Email_Delivery\Suremail_Adapter;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/email-delivery-fixtures.php';
require_once __DIR__ . '/fake-surecontact-status.php';

/**
 * The read-only view of SureMail. SureMail itself is not loaded in the suite.
 */
class Test_Suremail_Adapter extends TestCase {
	use SRFM_Email_Delivery_Fixtures;

	protected function setUp(): void {
		parent::setUp();
		$this->reset_email_delivery_state();
	}

	protected function tearDown(): void {
		$this->reset_email_delivery_state();
		delete_transient( Suremail_Adapter::QUOTA_TRANSIENT );
		wp_clear_scheduled_hook( Suremail_Adapter::QUOTA_REFRESH_HOOK );
		\SureMails\Inc\API\SureContactStatus::$response = null;
		parent::tearDown();
	}

	public function test_is_active() {
		$this->assertSame( defined( 'SUREMAILS_VERSION' ), Suremail_Adapter::is_active() );
	}

	public function test_get_version() {
		$this->assertSame( defined( 'SUREMAILS_VERSION' ) ? (string) constant( 'SUREMAILS_VERSION' ) : '', Suremail_Adapter::get_version() );
	}

	public function test_supports_surecontact() {
		$version = Suremail_Adapter::get_version();
		$this->assertSame( '' !== $version && version_compare( $version, Suremail_Adapter::SURECONTACT_MIN_VERSION, '>=' ), Suremail_Adapter::supports_surecontact() );
	}

	public function test_is_onboarding_completed() {
		$this->assertFalse( Suremail_Adapter::is_onboarding_completed() );
		update_option( Suremail_Adapter::ONBOARDING_OPTION, 'yes' );
		$this->assertTrue( Suremail_Adapter::is_onboarding_completed() );
	}

	public function test_get_readiness() {
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( false ) ], 'sc1' );
		$readiness = Suremail_Adapter::get_readiness();

		$this->assertSame( 'option', $readiness['source'] );
		$this->assertSame( 1, $readiness['connections'] );
		$this->assertSame( 'SURECONTACT', $readiness['default_type'] );
		$this->assertTrue( $readiness['surecontact']['connected'] );
		$this->assertFalse( $readiness['surecontact']['email_verified'] );

		update_option( Suremail_Adapter::CONNECTIONS_OPTION, 'garbage' );
		$this->assertSame( 0, Suremail_Adapter::get_readiness()['connections'], 'Malformed storage reads as no connection.' );
	}

	public function test_get_readiness_prefers_the_suremail_contract() {
		add_filter(
			'suremails_delivery_readiness',
			static function () {
				return [
					'contract'          => 1,
					'connections_count' => 2,
				];
			}
		);
		$this->assertSame( 'contract', Suremail_Adapter::get_readiness()['source'] );
	}

	public function test_admin_url() {
		$this->assertStringContainsString( 'page=suremail', Suremail_Adapter::admin_url() );
		$this->assertStringEndsWith( '#/logs', Suremail_Adapter::admin_url( '#/logs' ) );
	}

	public function test_setup_url() {
		$this->assertStringEndsWith( '#/onboarding/connection', Suremail_Adapter::setup_url() );
		update_option( Suremail_Adapter::ONBOARDING_OPTION, 'yes' );
		$this->assertStringEndsWith( '#/connections', Suremail_Adapter::setup_url() );
	}

	public function test_logs_url() {
		$this->assertStringEndsWith( '#/logs', Suremail_Adapter::logs_url() );
	}

	private function quota( $used, $limit, $period = 'monthly', $remaining = null ) {
		return [
			'used'       => $used,
			'limit'      => $limit,
			'remaining'  => null === $remaining ? max( 0, $limit - $used ) : $remaining,
			'period'     => $period,
			'checked_at' => 1,
		];
	}

	private function connect_surecontact() {
		$this->activate_plugins( [ \SRFM\Inc\Email_Delivery\Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
	}

	private function status_returns( $cap, $code = 200 ) {
		\SureMails\Inc\API\SureContactStatus::$response = new WP_REST_Response(
			null === $cap ? [ 'success' => true ] : [
				'success' => true,
				'cap'     => $cap,
			],
			$code
		);
	}

	public function test_get_surecontact_quota() {
		$this->assertNull( Suremail_Adapter::get_surecontact_quota() );

		set_transient( Suremail_Adapter::QUOTA_TRANSIENT, [ 'known' => false ], HOUR_IN_SECONDS );
		$this->assertNull( Suremail_Adapter::get_surecontact_quota(), 'A failed refresh reads as unknown.' );

		set_transient(
			Suremail_Adapter::QUOTA_TRANSIENT,
			[
				'known'      => true,
				'used'       => '85',
				'limit'      => '100',
				'remaining'  => 15,
				'period'     => 'monthly',
				'checked_at' => 123,
			],
			HOUR_IN_SECONDS
		);
		$this->assertSame( array_merge( $this->quota( 85, 100 ), [ 'checked_at' => 123 ] ), Suremail_Adapter::get_surecontact_quota() );
	}

	public function test_is_quota_low() {
		$this->assertTrue( Suremail_Adapter::is_quota_low( $this->quota( 80, 100 ) ) );
		$this->assertTrue( Suremail_Adapter::is_quota_low( $this->quota( 100, 100 ) ) );
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 79, 100 ) ) );
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 5000, -1, 'monthly', 0 ) ), 'No cap.' );
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 3, 3, 'daily' ) ), 'Unverified accounts are the limited state.' );
		$this->assertFalse( Suremail_Adapter::is_quota_low( null ) );
	}

	public function test_maybe_schedule_quota_refresh() {
		Suremail_Adapter::maybe_schedule_quota_refresh();
		$this->assertFalse( wp_next_scheduled( Suremail_Adapter::QUOTA_REFRESH_HOOK ), 'No SureContact connection, nothing to refresh.' );

		$this->connect_surecontact();
		Suremail_Adapter::maybe_schedule_quota_refresh();
		$this->assertNotFalse( wp_next_scheduled( Suremail_Adapter::QUOTA_REFRESH_HOOK ) );

		wp_clear_scheduled_hook( Suremail_Adapter::QUOTA_REFRESH_HOOK );
		set_transient( Suremail_Adapter::QUOTA_TRANSIENT, [ 'known' => false ], HOUR_IN_SECONDS );
		Suremail_Adapter::maybe_schedule_quota_refresh();
		$this->assertFalse( wp_next_scheduled( Suremail_Adapter::QUOTA_REFRESH_HOOK ), 'Not while the cached copy is fresh.' );
	}

	/**
	 * The fetch goes through SureMail's own handler and stores the cap it
	 * returns; field names as seen live from SureContact through SureMail 2.0.2.
	 */
	public function test_refresh_surecontact_quota() {
		$this->connect_surecontact();
		$this->status_returns(
			[
				'verified'  => true,
				'period'    => 'Monthly',
				'limit'     => '400',
				'used'      => '12',
				'remaining' => 388,
				'resets_at' => '2026-11-01T00:00:00+00:00',
			]
		);

		Suremail_Adapter::refresh_surecontact_quota();

		$this->assertSame( 'sc1', \SureMails\Inc\API\SureContactStatus::$asked_for );
		$quota = Suremail_Adapter::get_surecontact_quota();
		$this->assertSame( 12, $quota['used'] );
		$this->assertSame( 400, $quota['limit'] );
		$this->assertSame( 388, $quota['remaining'] );
		$this->assertSame( 'monthly', $quota['period'], 'period goes through sanitize_key.' );
	}

	public function test_refresh_surecontact_quota_fills_in_remaining_and_clamps() {
		$this->connect_surecontact();
		$this->status_returns(
			[
				'limit' => 100,
				'used'  => -5,
			]
		);

		Suremail_Adapter::refresh_surecontact_quota();

		$quota = Suremail_Adapter::get_surecontact_quota();
		$this->assertSame( 0, $quota['used'], 'A negative count is clamped.' );
		$this->assertSame( 100, $quota['remaining'], 'remaining falls back to limit - used.' );
		$this->assertSame( '', $quota['period'] );
	}

	public function test_refresh_surecontact_quota_caches_unknown_for_a_missing_or_failed_cap() {
		$this->connect_surecontact();

		$this->status_returns( null );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertSame( [ 'known' => false ], get_transient( Suremail_Adapter::QUOTA_TRANSIENT ), 'No cap.' );

		$this->status_returns( [ 'used' => 3 ] );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertSame( [ 'known' => false ], get_transient( Suremail_Adapter::QUOTA_TRANSIENT ), 'A cap without a limit.' );

		$this->status_returns( [ 'limit' => 100 ], 502 );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertSame( [ 'known' => false ], get_transient( Suremail_Adapter::QUOTA_TRANSIENT ), 'SureContact unreachable.' );
	}

	/**
	 * A used-up quota is re-checked within half an hour and a low one within
	 * an hour, so an upgrade or a reset clears the warning soon.
	 */
	public function test_refresh_surecontact_quota_rechecks_a_low_quota_sooner() {
		$this->connect_surecontact();
		$expiry = function () {
			return (int) get_option( '_transient_timeout_' . Suremail_Adapter::QUOTA_TRANSIENT ) - time();
		};

		$this->status_returns( [ 'limit' => 100, 'used' => 100, 'period' => 'monthly' ] );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertLessThanOrEqual( Suremail_Adapter::QUOTA_EXHAUSTED_TTL, $expiry() );

		$this->status_returns( [ 'limit' => 100, 'used' => 90, 'period' => 'monthly' ] );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertLessThanOrEqual( Suremail_Adapter::QUOTA_LOW_TTL, $expiry() );
		$this->assertGreaterThan( Suremail_Adapter::QUOTA_EXHAUSTED_TTL, $expiry() );

		$this->status_returns( [ 'limit' => 100, 'used' => 10, 'period' => 'monthly' ] );
		Suremail_Adapter::refresh_surecontact_quota();
		$this->assertGreaterThan( Suremail_Adapter::QUOTA_LOW_TTL, $expiry() );
	}

	public function test_refresh_surecontact_quota_runs_on_its_cron_hook() {
		$this->assertNotFalse( has_action( Suremail_Adapter::QUOTA_REFRESH_HOOK, [ Suremail_Adapter::class, 'refresh_surecontact_quota' ] ) );
	}

	public function test_upgrade_url() {
		$url = Suremail_Adapter::upgrade_url();

		$this->assertStringContainsString( 'source=sureforms', $url );
		$this->assertStringEndsWith( '#/dashboard', $url, 'The query string goes before the hash route.' );
	}
}
