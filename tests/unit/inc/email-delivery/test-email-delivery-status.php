<?php
/**
 * Class Test_Email_Delivery_Status
 *
 * @package sureforms
 */

use SRFM\Inc\Client_Logger;
use SRFM\Inc\Email_Delivery\Mailer;
use SRFM\Inc\Email_Delivery\Status;
use SRFM\Inc\Email_Delivery\Suremail_Adapter;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests for mailer detection, the SureMail adapter and the email delivery status.
 *
 * SureMail is never loaded in the suite, so it is "activated" through the
 * active_plugins option and its storage is written directly — the same shape
 * SureMail 2.0.x writes.
 */
class Test_Email_Delivery_Status extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->reset_state();
	}

	protected function tearDown(): void {
		$this->reset_state();
		parent::tearDown();
	}

	private function reset_state() {
		remove_all_filters( 'pre_option_active_plugins' );
		remove_all_filters( 'srfm_smtp_plugins' );
		remove_all_filters( 'srfm_active_mailer' );
		remove_all_filters( 'srfm_email_delivery_status' );
		remove_all_filters( 'suremails_delivery_readiness' );
		delete_option( Status::OUTCOMES_OPTION );
		delete_option( Suremail_Adapter::CONNECTIONS_OPTION );
		delete_option( Suremail_Adapter::ONBOARDING_OPTION );
		delete_option( Client_Logger::FAILURES_OPTION );
		Status::reset();
	}

	private function activate_plugins( array $plugins ) {
		remove_all_filters( 'pre_option_active_plugins' );
		add_filter(
			'pre_option_active_plugins',
			function () use ( $plugins ) {
				return $plugins;
			}
		);
		Status::reset();
	}

	private function set_suremail_connections( array $connections, $default_id = '' ) {
		update_option(
			Suremail_Adapter::CONNECTIONS_OPTION,
			[
				'connections'        => $connections,
				'default_connection' => [
					'type'             => '',
					'email'            => '',
					'id'               => $default_id,
					'connection_title' => '',
				],
				'log_emails'         => 'yes',
			]
		);
		Status::reset();
	}

	private function surecontact_row( $verified ) {
		return [
			'type'             => 'SURECONTACT',
			'connection_title' => 'SureContact',
			'from_email'       => 'owner@example.com',
			'priority'         => 1,
			'email_verified'   => $verified,
			'id'               => 'sc1',
		];
	}

	// ---------------------------------------------------------------------
	// Mailer.
	// ---------------------------------------------------------------------

	public function test_mailer_is_none_without_mail_plugins() {
		$this->activate_plugins( [ 'akismet/akismet.php' ] );
		$this->assertSame( 'none', Mailer::get_active()['slug'] );
		$this->assertFalse( Mailer::has_mailer() );
	}

	public function test_suremail_wins_over_another_mail_plugin() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php', Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$this->assertSame( 'suremail', Mailer::get_active()['slug'], 'A connected SureMail sends.' );
	}

	public function test_other_mail_plugin_is_named() {
		$this->activate_plugins( [ 'fluent-smtp/fluent-smtp.php' ] );
		$mailer = Mailer::get_active();
		$this->assertSame( 'other', $mailer['slug'] );
		$this->assertSame( 'FluentSMTP', $mailer['label'] );
	}

	public function test_newsletter_plugin_is_not_a_mailer() {
		$this->activate_plugins( [ 'newsletter/plugin.php' ] );
		$this->assertSame( 'none', Mailer::get_active()['slug'] );
	}

	public function test_known_plugins_are_filterable() {
		add_filter(
			'srfm_smtp_plugins',
			function ( $plugins ) {
				$plugins['host-relay/host-relay.php'] = 'Host Relay';
				return $plugins;
			}
		);
		$this->activate_plugins( [ 'host-relay/host-relay.php' ] );
		$this->assertSame( 'Host Relay', Mailer::get_active()['label'] );
	}

	public function test_active_mailer_filter_overrides_and_rejects_garbage() {
		$this->activate_plugins( [] );

		add_filter( 'srfm_active_mailer', '__return_empty_array' );
		$this->assertSame( 'none', Mailer::get_active()['slug'], 'Invalid filter output falls back to detection.' );

		remove_all_filters( 'srfm_active_mailer' );
		add_filter(
			'srfm_active_mailer',
			function () {
				return [ 'slug' => 'other', 'label' => 'Platform relay' ];
			}
		);
		$mailer = Mailer::get_active();
		$this->assertSame( 'other', $mailer['slug'] );
		$this->assertSame( 'Platform relay', $mailer['label'] );
		$this->assertSame( '', $mailer['file'] );
	}

	// ---------------------------------------------------------------------
	// Suremail_Adapter.
	// ---------------------------------------------------------------------

	public function test_readiness_survives_malformed_storage() {
		update_option( Suremail_Adapter::CONNECTIONS_OPTION, 'not-an-array' );
		$readiness = Suremail_Adapter::get_readiness();
		$this->assertSame( 0, $readiness['connections'] );
		$this->assertFalse( $readiness['surecontact']['connected'] );

		update_option( Suremail_Adapter::CONNECTIONS_OPTION, [ 'connections' => 'x', 'default_connection' => 5 ] );
		$this->assertSame( 0, Suremail_Adapter::get_readiness()['connections'] );
	}

	public function test_readiness_reads_surecontact_verification() {
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( false ) ], 'sc1' );
		$readiness = Suremail_Adapter::get_readiness();

		$this->assertSame( 1, $readiness['connections'] );
		$this->assertSame( 'SURECONTACT', $readiness['default_type'] );
		$this->assertSame( 'SureContact', $readiness['provider_label'] );
		$this->assertTrue( $readiness['surecontact']['connected'] );
		$this->assertFalse( $readiness['surecontact']['email_verified'] );
		$this->assertNull( $readiness['surecontact']['is_paid'], 'Absent flag is unknown, not false.' );
	}

	public function test_readiness_falls_back_to_priority_when_default_is_gone() {
		$this->set_suremail_connections(
			[
				'a' => [ 'type' => 'SMTP', 'connection_title' => 'Office SMTP', 'priority' => 2 ],
				'b' => [ 'type' => 'AMAZON_SES', 'connection_title' => '', 'priority' => 1 ],
			],
			'deleted-id'
		);
		$readiness = Suremail_Adapter::get_readiness();

		$this->assertTrue( $readiness['default_missing'] );
		$this->assertSame( 'AMAZON_SES', $readiness['default_type'] );
		$this->assertSame( 'Amazon Ses', $readiness['provider_label'] );
	}

	public function test_contract_filter_replaces_option_read() {
		$this->set_suremail_connections( [], '' );
		add_filter(
			'suremails_delivery_readiness',
			function () {
				return [
					'contract'           => 1,
					'status'             => 'limited',
					'connections_count'  => 1,
					'default_connection' => [ 'type' => 'SURECONTACT' ],
					'provider'           => [ 'email_verified' => false, 'is_paid' => false ],
				];
			}
		);
		$readiness = Suremail_Adapter::get_readiness();

		$this->assertSame( 'contract', $readiness['source'] );
		$this->assertSame( 1, $readiness['connections'] );
		$this->assertFalse( $readiness['surecontact']['email_verified'] );
	}

	public function test_setup_url_follows_suremail_onboarding() {
		$this->assertStringEndsWith( '#/onboarding/connection', Suremail_Adapter::setup_url() );

		update_option( Suremail_Adapter::ONBOARDING_OPTION, 'yes' );
		$this->assertStringEndsWith( '#/connections', Suremail_Adapter::setup_url() );
		$this->assertStringContainsString( 'page=suremail', Suremail_Adapter::logs_url() );
		$this->assertStringEndsWith( '#/logs', Suremail_Adapter::logs_url() );
	}

	// ---------------------------------------------------------------------
	// Status.
	// ---------------------------------------------------------------------

	public function test_not_configured_offers_suremail_setup() {
		$this->activate_plugins( [] );
		$status = Status::get_status();

		$this->assertSame( 'not_configured', $status['state'] );
		$this->assertSame( 'install_activate', $status['action']['type'] );
		$this->assertSame( 'Install', $status['suremail']['plugin_status'] );
		$this->assertSame( '', $status['links']['setup'], 'No SureMail link before it is active.' );
	}

	public function test_other_mailer_offers_nothing_to_install() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		$status = Status::get_status();

		$this->assertSame( 'other_mailer', $status['state'] );
		$this->assertSame( 'none', $status['action']['type'] );
		$this->assertStringContainsString( 'WP Mail SMTP', $status['title'] );
	}

	public function test_suremail_without_connection() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [], '' );
		$status = Status::get_status();

		$this->assertSame( 'suremail_no_connection', $status['state'] );
		$this->assertSame( 'open_suremail', $status['action']['type'] );
		$this->assertStringEndsWith( '#/onboarding/connection', $status['action']['url'] );
	}

	public function test_unverified_surecontact_is_limited() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( false ) ], 'sc1' );

		$this->assertSame( 'limited', Status::get_status()['state'] );
	}

	public function test_verified_surecontact_is_ready_and_never_says_delivered() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$status = Status::get_status();

		$this->assertSame( 'ready', $status['state'] );
		$this->assertStringContainsString( 'SureContact', $status['message'] );
		$this->assertStringNotContainsStringIgnoringCase( 'deliver', $status['message'] );
	}

	public function test_failure_needs_attention_and_later_success_clears_it() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );

		Status::record_outcome( false, 12, 'mailer_rejected' );
		$status = Status::get_status();
		$this->assertSame( 'attention', $status['state'] );
		$this->assertSame( 'view_logs', $status['action']['type'] );
		$this->assertSame( 12, $status['last_outcome']['failed_form_id'] );

		Status::record_outcome( true, 12 );
		$this->assertSame( 'ready', Status::get_status()['state'] );
	}

	/**
	 * A failure recorded before SureMail was set up, or while it has no
	 * connection: the fix is to connect, not SureMail's empty logs. Found on a
	 * fresh ZipWP site right after the one-click install.
	 */
	public function test_failure_with_suremail_but_no_connection_asks_to_connect() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [], '' );
		Status::record_outcome( false, 3, 'mailer_error' );

		$status = Status::get_status();

		$this->assertSame( 'attention', $status['state'] );
		$this->assertSame( 'open_suremail', $status['action']['type'] );
		$this->assertStringEndsWith( '#/onboarding/connection', $status['action']['url'] );
		$this->assertStringContainsString( 'no sending connection', $status['message'] );
	}

	public function test_failure_outside_the_window_is_ignored() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'     => 'failure',
				'last_failure_at' => time() - Status::ATTENTION_WINDOW - 60,
			]
		);

		$this->assertSame( 'other_mailer', Status::get_status()['state'] );
	}

	public function test_failure_on_a_form_without_entries_says_so() {
		$this->activate_plugins( [] );
		Status::record_outcome( false, 7, 'php_mail_failed', false );
		$status = Status::get_status();

		$this->assertSame( 'attention', $status['state'] );
		$this->assertFalse( $status['last_outcome']['entry_saved'] );
		$this->assertStringContainsString( 'not saved', $status['message'] );
		$this->assertSame( 'install_activate', $status['action']['type'] );
	}

	public function test_successes_are_throttled_while_healthy() {
		Status::record_outcome( true, 1 );
		$first = Status::get_outcomes()['last_success_at'];

		update_option( Status::OUTCOMES_OPTION, array_merge( Status::get_outcomes(), [ 'last_success_at' => $first - 10 ] ) );
		Status::record_outcome( true, 1 );

		$this->assertSame( $first - 10, Status::get_outcomes()['last_success_at'], 'A success within the interval is not rewritten.' );
	}

	public function test_lost_suremail_connection_needs_attention() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$this->assertSame( 'ready', Status::get_status()['state'] );
		$this->assertGreaterThan( 0, Status::get_outcomes()['suremail_connected_at'], 'Having a connection is remembered.' );

		// What SureMail leaves after SureContact revokes the connection remotely.
		$this->set_suremail_connections( [], '' );
		$status = Status::get_status();

		$this->assertSame( 'attention', $status['state'] );
		$this->assertSame( 'open_suremail', $status['action']['type'] );
	}

	/**
	 * Found on a fresh ZipWP site: the first notification fails through PHP
	 * mail(), the owner connects SureContact in one click, and the old failure
	 * must not keep reporting "SureMail did not accept a notification".
	 */
	public function test_failure_before_the_connection_does_not_need_attention() {
		$this->activate_plugins( [] );
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'     => 'failure',
				'last_failure_at' => time() - 120,
			]
		);
		Client_Logger::record_failure( 'notification', 7, 'Contact' );
		$this->assertSame( 'attention', Status::get_status()['state'] );

		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( false ) ], 'sc1' );

		$this->assertSame( 'limited', Status::get_status()['state'] );
	}

	public function test_failure_through_the_connection_still_needs_attention() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'           => 'failure',
				'last_failure_at'       => time() - 60,
				'suremail_connected_at' => time() - 300,
			]
		);
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );

		$this->assertSame( 'attention', Status::get_status()['state'] );
	}

	public function test_reconnecting_moves_the_connection_time_forward() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		update_option(
			Status::OUTCOMES_OPTION,
			[
				'last_result'           => 'failure',
				'last_failure_at'       => time() - 60,
				'suremail_connected_at' => time() - 600,
			]
		);

		// Connection lost: attention, and the loss is remembered.
		$this->set_suremail_connections( [], '' );
		$this->assertSame( 'attention', Status::get_status()['state'] );
		$this->assertGreaterThan( 0, Status::get_outcomes()['suremail_lost_at'] );

		// Reconnected: the failure was before it, so mail is no longer in doubt.
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$this->assertSame( 'ready', Status::get_status()['state'] );
		$this->assertSame( 0, Status::get_outcomes()['suremail_lost_at'] );
		$this->assertGreaterThanOrEqual( time() - 5, Status::get_outcomes()['suremail_connected_at'] );
	}

	public function test_form_checks_fault_counts_only_until_mail_works_again() {
		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		$fault_at = time() - 100;
		update_option( Client_Logger::FAILURES_OPTION, [ 'notification' => [ 'count' => 1, 'acked' => 0, 'at' => $fault_at, 'form_id' => 3 ] ] );

		$this->assertSame( 'attention', Status::get_status()['state'] );

		update_option( Status::OUTCOMES_OPTION, [ 'last_result' => 'success', 'last_success_at' => $fault_at + 50 ] );
		Status::reset();
		$this->assertSame( 'other_mailer', Status::get_status()['state'] );
	}

	public function test_status_is_filterable() {
		$this->activate_plugins( [] );
		add_filter(
			'srfm_email_delivery_status',
			function ( $status ) {
				$status['state'] = 'ready';
				return $status;
			}
		);
		$this->assertSame( 'ready', Status::get_status()['state'] );
	}

	public function test_rest_endpoint_requires_capability_and_returns_status() {
		$this->activate_plugins( [] );

		wp_set_current_user( 0 );
		$denied = rest_do_request( new WP_REST_Request( 'GET', '/sureforms/v1/email-delivery/status' ) );
		$this->assertContains( $denied->get_status(), [ 401, 403 ] );

		$admin = wp_insert_user(
			[
				'user_login' => 'srfm_email_delivery_admin',
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $admin );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/sureforms/v1/email-delivery/status' ) );
		wp_set_current_user( 0 );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $admin );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'not_configured', $response->get_data()['state'] );
	}
}
