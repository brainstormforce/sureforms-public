<?php
/**
 * Class Test_Surecontact_Quota
 *
 * @package sureforms
 */

use SRFM\Inc\Email_Delivery\Suremail_Adapter;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * The cached SureContact quota behind the "almost used up" notice.
 */
class Test_Surecontact_Quota extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		delete_transient( Suremail_Adapter::QUOTA_TRANSIENT );
		wp_clear_scheduled_hook( Suremail_Adapter::QUOTA_REFRESH_HOOK );
	}

	protected function tearDown(): void {
		delete_transient( Suremail_Adapter::QUOTA_TRANSIENT );
		wp_clear_scheduled_hook( Suremail_Adapter::QUOTA_REFRESH_HOOK );
		delete_option( Suremail_Adapter::CONNECTIONS_OPTION );
		parent::tearDown();
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

	public function test_quota_is_unknown_until_refreshed() {
		$this->assertNull( Suremail_Adapter::get_surecontact_quota() );

		set_transient( Suremail_Adapter::QUOTA_TRANSIENT, [ 'known' => false ], HOUR_IN_SECONDS );
		$this->assertNull( Suremail_Adapter::get_surecontact_quota(), 'A failed refresh reads as unknown.' );

		set_transient( Suremail_Adapter::QUOTA_TRANSIENT, 'garbage', HOUR_IN_SECONDS );
		$this->assertNull( Suremail_Adapter::get_surecontact_quota() );
	}

	public function test_cached_quota_is_normalised() {
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

		$this->assertSame(
			[
				'used'       => 85,
				'limit'      => 100,
				'remaining'  => 15,
				'period'     => 'monthly',
				'checked_at' => 123,
			],
			Suremail_Adapter::get_surecontact_quota()
		);
	}

	public function test_quota_is_low_with_a_fifth_or_less_left() {
		$this->assertTrue( Suremail_Adapter::is_quota_low( $this->quota( 80, 100 ) ) );
		$this->assertTrue( Suremail_Adapter::is_quota_low( $this->quota( 100, 100 ) ) );
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 79, 100 ) ) );
	}

	public function test_unlimited_daily_and_unknown_quotas_are_never_low() {
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 5000, -1, 'monthly', 0 ) ), 'No cap.' );
		$this->assertFalse( Suremail_Adapter::is_quota_low( $this->quota( 3, 3, 'daily' ) ), 'Unverified accounts are the limited state.' );
		$this->assertFalse( Suremail_Adapter::is_quota_low( null ) );
	}

	public function test_refresh_without_suremail_caches_unknown() {
		Suremail_Adapter::refresh_surecontact_quota();

		$this->assertSame( [ 'known' => false ], get_transient( Suremail_Adapter::QUOTA_TRANSIENT ) );
		$this->assertNull( Suremail_Adapter::get_surecontact_quota() );
	}

	public function test_no_refresh_is_queued_without_a_surecontact_connection() {
		Suremail_Adapter::maybe_schedule_quota_refresh();

		$this->assertFalse( wp_next_scheduled( Suremail_Adapter::QUOTA_REFRESH_HOOK ) );
	}

	public function test_refresh_runs_on_its_cron_hook() {
		$this->assertNotFalse( has_action( Suremail_Adapter::QUOTA_REFRESH_HOOK, [ Suremail_Adapter::class, 'refresh_surecontact_quota' ] ) );
	}

	public function test_upgrade_link_opens_suremail_dashboard_with_the_source() {
		$url = Suremail_Adapter::upgrade_url();

		$this->assertStringContainsString( 'source=sureforms', $url );
		$this->assertStringEndsWith( '#/dashboard', $url, 'The query string goes before the hash route.' );
	}
}
