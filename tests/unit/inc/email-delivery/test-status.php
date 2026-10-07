<?php
/**
 * Class Test_Status
 *
 * @package sureforms
 */

use SRFM\Inc\Email_Delivery\Mailer;
use SRFM\Inc\Email_Delivery\Status;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/email-delivery-fixtures.php';

/**
 * The email delivery status: public API of SRFM\Inc\Email_Delivery\Status.
 */
class Test_Status extends TestCase {
	use SRFM_Email_Delivery_Fixtures;

	protected function setUp(): void {
		parent::setUp();
		$this->reset_email_delivery_state();
	}

	protected function tearDown(): void {
		$this->reset_email_delivery_state();
		parent::tearDown();
	}

	public function test_get_status() {
		$this->activate_plugins( [] );
		$status = Status::get_status();

		$this->assertSame( 'not_configured', $status['state'] );
		$this->assertSame( 'install_activate', $status['action']['type'] );
		foreach ( [ 'state', 'severity', 'title', 'message', 'mailer', 'suremail', 'surecontact', 'last_outcome', 'action', 'links', 'source' ] as $key ) {
			$this->assertArrayHasKey( $key, $status );
		}
	}

	/**
	 * White-label sites turn SureForms' promotions off; the status still says
	 * email is not set up, but no longer offers to install SureMail.
	 */
	public function test_get_status_offers_no_install_with_promotions_hidden() {
		$this->activate_plugins( [] );
		add_filter( 'srfm_hide_promotions', '__return_true' );

		$status = Status::get_status();

		$this->assertSame( 'not_configured', $status['state'], 'The status text stays honest.' );
		$this->assertSame( 'none', $status['action']['type'] );
	}

	/**
	 * A SureContact row whose verification SureMail has not cached yet is not
	 * treated as limited: unknown is not unverified.
	 */
	public function test_get_status_treats_unknown_verification_as_ready() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( null ) ], 'sc1' );

		$status = Status::get_status();

		$this->assertSame( 'ready', $status['state'] );
		$this->assertNull( $status['surecontact']['email_verified'] );
	}

	public function test_record_outcome() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );

		Status::record_outcome( false, 5, 'mailer_rejected' );
		$this->assertSame( 'attention', Status::get_status()['state'] );

		Status::record_outcome( true, 5 );
		$this->assertSame( 'other_mailer', Status::get_status()['state'] );
	}

	public function test_get_outcomes() {
		update_option( Status::OUTCOMES_OPTION, 'not an array' );
		$outcomes = Status::get_outcomes();

		$this->assertSame( '', $outcomes['last_result'] );
		$this->assertSame( 0, $outcomes['last_failure_at'] );
		$this->assertTrue( $outcomes['last_failure_entry_saved'], 'Defaults to saved when unknown.' );

		Status::record_outcome( false, 9, 'Mailer Rejected!' );
		$outcomes = Status::get_outcomes();
		$this->assertSame( 9, $outcomes['last_failure_form_id'] );
		$this->assertSame( 'mailerrejected', $outcomes['last_failure_reason'], 'Reason codes are sanitized keys.' );
	}

	public function test_reset() {
		$this->activate_plugins( [] );
		$this->assertSame( 'not_configured', Status::get_status()['state'] );

		// Memoised until reset.
		$this->activate_plugins( [ 'fluent-smtp/fluent-smtp.php' ] );
		remove_all_filters( 'pre_option_active_plugins' );
		add_filter(
			'pre_option_active_plugins',
			static function () {
				return [ 'fluent-smtp/fluent-smtp.php' ];
			}
		);
		Status::reset();
		$this->assertSame( 'other_mailer', Status::get_status()['state'] );
	}

	public function test_get_offer() {
		$this->assertSame( [ 'free_emails' => 100 ], Status::get_offer() );

		$set = static function () {
			return 250;
		};
		add_filter( 'srfm_surecontact_free_emails', $set );
		$this->assertSame( 250, Status::get_offer()['free_emails'] );
		remove_filter( 'srfm_surecontact_free_emails', $set );

		// Nonsense from a filter hides the offer rather than showing it.
		foreach ( [ -5, 'lots', null ] as $bad ) {
			$filter = static function () use ( $bad ) {
				return $bad;
			};
			add_filter( 'srfm_surecontact_free_emails', $filter );
			$this->assertSame( 0, Status::get_offer()['free_emails'] );
			remove_filter( 'srfm_surecontact_free_emails', $filter );
		}
	}
}
