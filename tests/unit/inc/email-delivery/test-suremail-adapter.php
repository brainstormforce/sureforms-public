<?php
/**
 * Class Test_Suremail_Adapter
 *
 * @package sureforms
 */

use SRFM\Inc\Email_Delivery\Suremail_Adapter;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/email-delivery-fixtures.php';

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
}
