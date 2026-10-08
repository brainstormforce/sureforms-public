<?php
/**
 * Class Test_Mailer
 *
 * @package sureforms
 */

use SRFM\Inc\Email_Delivery\Mailer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/email-delivery-fixtures.php';

/**
 * Which plugin, if any, sends the site's mail.
 */
class Test_Mailer extends TestCase {
	use SRFM_Email_Delivery_Fixtures;

	protected function setUp(): void {
		parent::setUp();
		$this->reset_email_delivery_state();
	}

	protected function tearDown(): void {
		$this->reset_email_delivery_state();
		parent::tearDown();
	}

	public function test_get_known_plugins() {
		$known = Mailer::get_known_plugins();

		$this->assertSame( 'WP Mail SMTP', $known['wp-mail-smtp/wp_mail_smtp.php'] ?? '' );
		$this->assertArrayNotHasKey( 'newsletter/plugin.php', $known, 'A newsletter plugin does not send site mail.' );

		add_filter(
			'srfm_smtp_plugins',
			static function ( $plugins ) {
				$plugins['acme-mail/acme-mail.php'] = 'Acme Mail';
				return $plugins;
			}
		);
		$this->assertSame( 'Acme Mail', Mailer::get_known_plugins()['acme-mail/acme-mail.php'] ?? '' );
	}

	public function test_get_active() {
		$this->activate_plugins( [] );
		$this->assertSame( 'none', Mailer::get_active()['slug'] );

		$this->activate_plugins( [ 'fluent-smtp/fluent-smtp.php' ] );
		$this->assertSame( 'other', Mailer::get_active()['slug'] );
		$this->assertSame( 'FluentSMTP', Mailer::get_active()['label'] );

		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->assertSame( 'suremail', Mailer::get_active()['slug'] );
	}

	/**
	 * SureMail with no connection falls back to WordPress's default handler,
	 * which fires phpmailer_init, so an SMTP plugin active alongside it is
	 * what actually sends. Reported as such, the site never sits in a
	 * "SureMail lost its connection" state while another plugin sends.
	 */
	public function test_get_active_reports_the_smtp_plugin_behind_an_unconnected_suremail() {
		$this->activate_plugins( [ Mailer::SUREMAIL_FILE, 'wp-mail-smtp/wp_mail_smtp.php' ] );
		$this->set_suremail_connections( [] );
		$this->assertSame( 'WP Mail SMTP', Mailer::get_active()['label'] );

		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$this->assertSame( 'suremail', Mailer::get_active()['slug'], 'A connected SureMail sends itself.' );
	}

	public function test_has_mailer() {
		$this->activate_plugins( [] );
		$this->assertFalse( Mailer::has_mailer() );

		$this->activate_plugins( [ 'post-smtp/postman-smtp.php' ] );
		$this->assertTrue( Mailer::has_mailer() );
	}

	public function test_allow_php_mail_fallback() {
		$this->activate_plugins( [] );
		$this->assertFalse( Mailer::allow_php_mail_fallback( 1 ), 'Off by default.' );

		add_filter( 'srfm_allow_php_mail_fallback', '__return_true' );
		$this->assertTrue( Mailer::allow_php_mail_fallback( 1 ), 'The owner can opt in when no mail plugin is active.' );

		$this->activate_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		$this->assertFalse( Mailer::allow_php_mail_fallback( 1 ), 'Never around a mail plugin, filter or not.' );
		remove_all_filters( 'srfm_allow_php_mail_fallback' );
	}

	public function test_get_rejection_reason() {
		$this->activate_plugins( [] );
		$this->assertSame( 'php_mail_failed', Mailer::get_rejection_reason()['code'] );

		$this->activate_plugins( [ 'fluent-smtp/fluent-smtp.php' ] );
		$reason = Mailer::get_rejection_reason();
		$this->assertSame( 'mailer_rejected', $reason['code'] );
		$this->assertStringContainsString( 'FluentSMTP', $reason['message'] );

		$this->activate_plugins( [ Mailer::SUREMAIL_FILE ] );
		$this->set_suremail_connections( [ 'sc1' => $this->surecontact_row( true ) ], 'sc1' );
		$this->assertSame( 'suremail_rejected', Mailer::get_rejection_reason()['code'] );
	}

	public function test_is_recipient_error() {
		$this->assertTrue( Mailer::is_recipient_error( 'Invalid address:  (to): not-an-address' ) );
		$this->assertTrue( Mailer::is_recipient_error( 'Invalid address:  (Reply-To): x@' ) );
		$this->assertTrue( Mailer::is_recipient_error( 'You must provide at least one recipient email address.' ) );
		$this->assertTrue( Mailer::is_recipient_error( 'SMTP Error: The following recipients failed: visitor@exampel.con: 550 5.1.1 User unknown' ), 'Refused by the mail server.' );
		$this->assertFalse( Mailer::is_recipient_error( 'Invalid address:  (From): wordpress@localhost' ), 'A broken From address is the site\'s problem.' );
		$this->assertFalse( Mailer::is_recipient_error( 'SMTP Error: Could not authenticate.' ) );
		$this->assertFalse( Mailer::is_recipient_error( '' ) );
	}

	public function test_get_refused_addresses() {
		$this->assertSame(
			[ 'visitor@exampel.con' ],
			Mailer::get_refused_addresses( 'SMTP Error: The following recipients failed: Visitor@Exampel.con: 550 5.1.1 User unknown' )
		);
		$this->assertSame( [ 'not-an-address' ], Mailer::get_refused_addresses( 'Invalid address:  (to): not-an-address' ) );
		$this->assertSame( [], Mailer::get_refused_addresses( 'You must provide at least one recipient email address.' ) );
	}

	public function test_relay_denied_for_the_owner_address_is_a_site_failure() {
		// The real PHPMailer text for a host whose SMTP refuses outside domains.
		$relay = 'SMTP Error: The following recipients failed: owner@example.com: 554 5.7.1 <owner@example.com>: Relay access denied';
		$this->assertTrue( Mailer::is_recipient_error( $relay ), 'PHPMailer words it as a recipient failure.' );
		$this->assertFalse( Mailer::is_visitor_recipient_failure( $relay, [], false ), 'The owner\'s address: a broken setup, not the visitor.' );
		$this->assertFalse( Mailer::is_visitor_recipient_failure( $relay, [ 'visitor@example.org' ], false ) );

		$auth = 'SMTP Error: The following recipients failed: owner@example.com: 530 5.7.0 Authentication required';
		$this->assertFalse( Mailer::is_visitor_recipient_failure( $auth, [], false ) );
	}

	public function test_a_refused_visitor_address_is_not_a_site_failure() {
		$typo = 'SMTP Error: The following recipients failed: visitor@exampel.con: 550 5.1.1 User unknown';
		$this->assertTrue( Mailer::is_visitor_recipient_failure( $typo, [ 'Visitor@Exampel.con' ], false ) );

		// One visitor address and one owner address refused: the owner's counts.
		$both = 'SMTP Error: The following recipients failed: visitor@exampel.con: 550 User unknown, owner@example.com: 554 Relay access denied';
		$this->assertFalse( Mailer::is_visitor_recipient_failure( $both, [ 'visitor@exampel.con' ], false ) );

		// An owner-typed typo is still the owner's to fix.
		$this->assertFalse( Mailer::is_visitor_recipient_failure( 'Invalid address:  (to): admin@exmple', [], false ) );
		$this->assertTrue( Mailer::is_visitor_recipient_failure( 'Invalid address:  (to): bob@', [ 'bob@' ], true ) );
	}

	public function test_no_recipient_is_the_visitors_only_when_every_recipient_is_a_field() {
		$none = 'You must provide at least one recipient email address.';
		$this->assertTrue( Mailer::is_visitor_recipient_failure( $none, [], true ), 'The visitor left the only recipient field empty.' );
		$this->assertFalse( Mailer::is_visitor_recipient_failure( $none, [], false ), 'An owner address resolved to nothing.' );
		$this->assertFalse( Mailer::is_visitor_recipient_failure( 'SMTP Error: Could not authenticate.', [ 'a@b.co' ], true ) );
	}
}
