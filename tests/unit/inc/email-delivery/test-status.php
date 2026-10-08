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

	/**
	 * Failures are kept per form: a working form B does not clear form A,
	 * whose notifications often go to other recipients.
	 */
	public function test_record_outcome_clears_only_the_succeeding_form() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );

		Status::record_outcome( false, 11, 'mailer_rejected' );
		Status::record_outcome( true, 22 );
		$this->assertSame( 'attention', Status::get_status()['state'], 'Form 11 still failed.' );

		Status::record_outcome( true, 11 );
		$this->assertSame( 'other_mailer', Status::get_status()['state'] );
	}

	/**
	 * The submit path is public: repeat failures from one form inside the
	 * write interval are not written again.
	 */
	public function test_record_outcome_throttles_repeat_failures() {
		Status::record_outcome( false, 11, 'mailer_rejected' );
		$first = Status::get_outcomes();

		Status::record_outcome( false, 11, 'mailer_rejected' );
		$this->assertSame( $first['consecutive_failures'], Status::get_outcomes()['consecutive_failures'] );

		update_option( Status::OUTCOMES_OPTION, array_merge( $first, [ 'open_forms' => [ 11 => time() - Status::FAILURE_WRITE_INTERVAL - 1 ] ] ) );
		Status::record_outcome( false, 11, 'mailer_rejected' );
		$this->assertSame( $first['consecutive_failures'] + 1, Status::get_outcomes()['consecutive_failures'] );
	}

	/**
	 * Outcomes stored before failures were kept per form keep their open
	 * failure through the update.
	 */
	public function test_get_outcomes_migrates_a_stored_failure() {
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'          => 'failure',
				'last_failure_at'      => time() - 60,
				'last_failure_form_id' => 7,
			]
		);

		$this->assertSame( [ 7 ], array_keys( Status::get_outcomes()['open_forms'] ) );
	}

	private function unverified_failure( $verified, $quota_remaining = null ) {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( $verified ) ], 'sc1' );
		// Connected a minute before the failure, as on a real site.
		update_option( Status::OUTCOMES_OPTION, [ 'suremail_connected_at' => time() - 60 ] );
		Status::reset();
		Status::record_outcome( false, 12, 'mailer_rejected' );
		if ( null !== $quota_remaining ) {
			set_transient(
				\SRFM\Inc\Email_Delivery\Suremail_Adapter::QUOTA_TRANSIENT,
				[
					'known'      => true,
					'used'       => 3 - $quota_remaining,
					'limit'      => 3,
					'remaining'  => $quota_remaining,
					'period'     => 'daily',
					'checked_at' => time(),
				],
				HOUR_IN_SECONDS
			);
		}
		Status::reset();
		return Status::get_status();
	}

	/**
	 * Found live: an unverified SureContact account accepts 3 emails a day, and
	 * the 4th notification failed with only "view logs". When SureContact's
	 * fresh quota confirms the allowance is used up, the status says verify.
	 */
	public function test_get_status_advises_verifying_when_the_daily_allowance_is_used_up() {
		$status = $this->unverified_failure( false, 0 );

		$this->assertSame( 'surecontact_cap', $status['cause'] );
		$this->assertSame( 'Verify your SureContact email to keep sending', $status['title'] );
		$this->assertStringContainsString( "today's are used up", $status['message'] );
		$this->assertSame( 'verify_surecontact', $status['action']['type'] );
		$this->assertNotSame( '', $status['links']['logs'], 'The logs stay one click away.' );
	}

	/**
	 * Any other failure through an unverified account -- a bad From domain,
	 * say -- keeps pointing at SureMail's logs, and asks for a fresh quota.
	 */
	public function test_get_status_points_to_logs_without_fresh_evidence() {
		wp_clear_scheduled_hook( \SRFM\Inc\Email_Delivery\Suremail_Adapter::QUOTA_REFRESH_HOOK );
		$status = $this->unverified_failure( false );

		$this->assertSame( '', $status['cause'] );
		$this->assertSame( 'view_logs', $status['action']['type'] );
		$this->assertNotFalse( wp_next_scheduled( \SRFM\Inc\Email_Delivery\Suremail_Adapter::QUOTA_REFRESH_HOOK ), 'The failure asked for a fresh quota.' );

		$status = $this->unverified_failure( false, 2 );
		$this->assertSame( '', $status['cause'], 'Allowance left: not the cap.' );
		wp_clear_scheduled_hook( \SRFM\Inc\Email_Delivery\Suremail_Adapter::QUOTA_REFRESH_HOOK );
	}

	public function test_get_status_never_tells_a_verified_account_to_verify() {
		$status = $this->unverified_failure( true, 0 );

		$this->assertSame( '', $status['cause'] );
		$this->assertSame( 'view_logs', $status['action']['type'] );
		delete_transient( \SRFM\Inc\Email_Delivery\Suremail_Adapter::QUOTA_TRANSIENT );
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

	/**
	 * A throttled repeat still records what it says that is new: here, a
	 * submission on a never-store form that was not saved.
	 */
	public function test_record_outcome_keeps_a_newer_reason_when_throttled() {
		Status::record_outcome( false, 11, 'mailer_rejected', true );
		Status::record_outcome( false, 11, 'mailer_error', false );

		$outcomes = Status::get_outcomes();
		$this->assertSame( 'mailer_error', $outcomes['last_failure_reason'] );
		$this->assertFalse( $outcomes['last_failure_entry_saved'] );
		$this->assertSame( 1, $outcomes['consecutive_failures'], 'Still one write-worthy failure.' );
	}

	/**
	 * A fails, then B fails, then A recovers: the status keeps describing B,
	 * which is still open, not A's old reason.
	 */
	public function test_record_outcome_describes_the_newest_open_failure_after_a_success() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'open_forms'   => [
					11 => time() - 120,
					22 => time() - 60,
				],
				'open_details' => [
					11 => [ 'reason' => 'mailer_rejected', 'mailer' => 'other', 'entry_saved' => true ],
					22 => [ 'reason' => 'mailer_error', 'mailer' => 'other', 'entry_saved' => false ],
				],
				'last_result'  => 'failure',
			]
		);

		Status::record_outcome( true, 11 );
		$outcomes = Status::get_outcomes();

		$this->assertSame( 'failure', $outcomes['last_result'] );
		$this->assertSame( 22, $outcomes['last_failure_form_id'] );
		$this->assertSame( 'mailer_error', $outcomes['last_failure_reason'] );
		$this->assertFalse( $outcomes['last_failure_entry_saved'] );
		$this->assertSame( 'attention', Status::get_status()['state'] );

		Status::record_outcome( true, 22 );
		$this->assertSame( 'success', Status::get_outcomes()['last_result'] );
	}

	/**
	 * Found on a fresh QA site: a form failed with no connection, SureMail was
	 * then connected, and the next notification failed within five minutes. The
	 * throttle kept the first failure's time, from before the connection, so the
	 * status discarded it as the old setup's and reported "ready".
	 */
	public function test_record_outcome_does_not_throttle_a_failure_across_a_new_connection() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$now = time();
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'          => 'failure',
				'last_failure_at'      => $now - 120,
				'last_failure_form_id' => 6,
				'open_forms'           => [ 6 => $now - 120 ],
				'suremail_connected_at' => $now - 60,
			]
		);
		$this->set_suremail_connections(
			[
				'p1' => [
					'type'     => 'PHPMAIL',
					'priority' => 10,
				],
			],
			'p1'
		);
		Status::reset();

		Status::record_outcome( false, 6, 'suremail_rejected' );

		$this->assertGreaterThan( $now - 60, Status::get_outcomes()['open_forms'][6], 'The failure carries its own time.' );
		Status::reset();
		$this->assertSame( 'attention', Status::get_status()['state'] );
	}

	/**
	 * The real sequence, with nothing seeded: a form fails, SureMail is then
	 * connected (so the connection time moves forward), and the new
	 * connection fails within the five-minute throttle. The status must say
	 * so, not read the first failure as the old setup's and report ready.
	 */
	public function test_a_failure_on_a_new_connection_within_the_throttle_reads_as_attention() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [] );
		Status::reset();

		Status::record_outcome( false, 6, 'suremail_rejected' );

		// Whole seconds: each step must land in a later second than the last.
		sleep( 1 );
		$this->set_suremail_connections(
			[
				'p1' => [
					'type'     => 'PHPMAIL',
					'priority' => 10,
				],
			],
			'p1'
		);
		Status::reset();
		Status::get_status();
		$connected_at = Status::get_outcomes()['suremail_connected_at'];
		$this->assertGreaterThan( 0, $connected_at, 'The new connection is noticed.' );

		sleep( 1 );
		Status::record_outcome( false, 6, 'suremail_rejected' );

		$this->assertGreaterThan( $connected_at, Status::get_outcomes()['open_forms'][6], 'The new failure carries its own time.' );
		Status::reset();
		$this->assertSame( 'attention', Status::get_status()['state'] );
	}

	/**
	 * The same sequence with no status read between connecting and the next
	 * failure, as on a site where nobody opens a SureForms screen: the new
	 * connection must be noticed by record_outcome() itself.
	 */
	public function test_a_failure_on_a_new_connection_is_noticed_without_a_status_read() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [] );
		Status::reset();

		Status::record_outcome( false, 6, 'suremail_rejected' );
		$first = Status::get_outcomes()['open_forms'][6];

		// Whole seconds: each step must land in a later second than the last.
		sleep( 1 );
		$this->set_suremail_connections(
			[
				'p1' => [
					'type'     => 'PHPMAIL',
					'priority' => 10,
				],
			],
			'p1'
		);
		Status::reset();

		sleep( 1 );
		Status::record_outcome( false, 6, 'suremail_rejected' );

		$this->assertGreaterThan( $first, Status::get_outcomes()['suremail_connected_at'], 'The connection is recorded by the failure itself.' );
		$this->assertGreaterThan( $first, Status::get_outcomes()['open_forms'][6], 'The new failure carries its own time.' );
		Status::reset();
		$this->assertSame( 'attention', Status::get_status()['state'] );
	}
}
