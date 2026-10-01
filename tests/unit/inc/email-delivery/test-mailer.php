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
		$this->assertFalse( Mailer::is_recipient_error( 'Invalid address:  (From): wordpress@localhost' ), 'A broken From address is the site\'s problem.' );
		$this->assertFalse( Mailer::is_recipient_error( 'SMTP Error: Could not authenticate.' ) );
		$this->assertFalse( Mailer::is_recipient_error( '' ) );
	}
}
