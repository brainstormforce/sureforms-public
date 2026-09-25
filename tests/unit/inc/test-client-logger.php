<?php
/**
 * Class Test_Client_Logger
 *
 * @package sureforms
 */

use SRFM\Inc\Client_Logger;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests for the client debug log: the enable/expire gate, the payload schema,
 * PII scrubbing, and the size cap.
 *
 * These write real files under the uploads directory, so every test restores the
 * setting and deletes the log — this suite runs on a plain TestCase with no
 * transaction to roll back.
 */
class Test_Client_Logger extends TestCase {

	/**
	 * General settings as they were before the test, restored in tearDown().
	 *
	 * These tests switch logging on and off. Without the restore, the last test
	 * leaves logging off for every later test class, and anything that depends on
	 * Client_Logger recording (such as the notification fault tests in
	 * test-form-submit.php) fails only when run after this class.
	 *
	 * @var mixed
	 */
	private $general_settings_backup;

	protected function setUp(): void {
		parent::setUp();

		$this->general_settings_backup = get_option( 'srfm_general_settings_options', null );
		$this->set_logging( true );
		Client_Logger::clear();
		delete_option( Client_Logger::FAILURES_OPTION );
		delete_option( Client_Logger::FAILURES_OPTION );
	}

	protected function tearDown(): void {
		Client_Logger::clear();
		delete_option( Client_Logger::FILENAME_OPTION );

		if ( null === $this->general_settings_backup ) {
			delete_option( 'srfm_general_settings_options' );
		} else {
			update_option( 'srfm_general_settings_options', $this->general_settings_backup );
		}

		parent::tearDown();
	}

	// ---------------------------------------------------------------
	// The gate
	// ---------------------------------------------------------------

	/**
	 * The setting is the authority. The frontend flag is baked into cached HTML
	 * and can be a whole cache TTL out of date, so a false here must stop writes
	 * no matter what the browser was told.
	 */
	public function test_is_enabled() {
		$this->assertTrue( Client_Logger::is_enabled() );

		$this->set_logging( false );

		$this->assertFalse( Client_Logger::is_enabled() );
	}

	/**
	 * Logging is on by default, including on an install whose stored settings
	 * predate the option — an absent key must read as on, not off, or every
	 * existing site silently gets nothing.
	 */
	public function test_is_enabled_defaults_to_on() {
		$general = (array) get_option( 'srfm_general_settings_options', [] );
		unset( $general['srfm_enable_logs'] );
		update_option( 'srfm_general_settings_options', $general );

		$this->assertTrue( Client_Logger::is_enabled() );
	}

	/**
	 * `srfm_enable_logs` can turn logging off without touching the stored
	 * setting (SureForms Pro's Distraction Free), and removing the callback
	 * restores the stored value.
	 */
	public function test_is_enabled_is_filterable() {
		$this->assertTrue( Client_Logger::is_enabled() );

		add_filter( 'srfm_enable_logs', '__return_false' );
		$this->assertFalse( Client_Logger::is_enabled() );
		remove_filter( 'srfm_enable_logs', '__return_false' );

		$general = (array) get_option( 'srfm_general_settings_options', [] );
		$this->assertTrue( ! isset( $general['srfm_enable_logs'] ) || (bool) $general['srfm_enable_logs'], 'The stored setting is untouched.' );
		$this->assertTrue( Client_Logger::is_enabled() );
	}

	// ---------------------------------------------------------------
	// Writing
	// ---------------------------------------------------------------

	/**
	 * The guard sits on the function that writes, not only on today's single
	 * caller. Without it a future caller writes to disk on a site that never
	 * switched logging on — and creates the directory doing it.
	 */
	public function test_append() {
		$this->assertTrue( Client_Logger::append( [ 'type' => 'error', 'message' => 'kept' ] ) );

		$this->set_logging( false );

		$this->assertFalse( Client_Logger::append( [ 'type' => 'error', 'message' => 'dropped' ] ) );
	}

	/**
	 * Once full the log stops accepting lines rather than trimming or rotating.
	 * The person who reproduced the bug is the one whose lines would be discarded.
	 */
	public function test_is_full() {
		$this->assertFalse( Client_Logger::is_full() );

		$this->fill_log();

		$this->assertTrue( Client_Logger::is_full() );
		$this->assertFalse( Client_Logger::append( [ 'type' => 'error', 'message' => 'after cap' ] ) );
	}

	/**
	 * A capped file must still parse. A cap implemented by truncating bytes would
	 * leave a half-written line and make the download unreadable.
	 */
	public function test_get_file_size() {
		$this->assertSame( 0, Client_Logger::get_file_size() );

		$this->fill_log();

		$path  = Client_Logger::get_log_path( false );
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		$this->assertNotEmpty( $lines );

		foreach ( $lines as $line ) {
			$this->assertNotNull( json_decode( $line, true ), 'Every line must be valid JSON.' );
		}
	}

	/**
	 * The log lives under uploads, in its own directory, behind guards.
	 */
	public function test_get_log_path() {
		// Remove the directory first: the guards are written at creation time, so a
		// leftover directory from an earlier test would make this pass without ever
		// exercising the code that writes them.
		$existing = Client_Logger::get_log_path( false );

		if ( '' !== $existing ) {
			$dir = dirname( $existing );

			Client_Logger::clear();

			// Remove everything, not just the files this class knows about: an
			// earlier test in another class may have left a log behind, and a
			// non-empty directory would make the rmdir fail.
			foreach ( (array) glob( $dir . '/{,.}*', GLOB_BRACE ) as $file ) {
				if ( is_string( $file ) && is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}

			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture teardown of a directory this suite created.
			}
		}

		$path = Client_Logger::get_log_path();

		$this->assertStringContainsString( '/sureforms/logs/', $path );
		$this->assertStringEndsWith( '.log', $path );

		$dir = dirname( $path );

		$this->assertFileExists( $dir . '/.htaccess' );
		// index.html, not index.php: an index.html is what suppresses an nginx
		// autoindex listing, which is the failure mode .htaccess cannot cover.
		$this->assertFileExists( $dir . '/index.html' );
	}

	/**
	 * Clearing must remove the file, not merely empty the reported size.
	 */
	public function test_clear() {
		Client_Logger::append( [ 'type' => 'error', 'message' => 'temporary' ] );
		$path = Client_Logger::get_log_path( false );

		$this->assertFileExists( $path );

		Client_Logger::clear();

		$this->assertFileDoesNotExist( $path );
	}

	// ---------------------------------------------------------------
	// Schema and scrubbing
	// ---------------------------------------------------------------

	/**
	 * The endpoint is anonymous, so it must never append caller-supplied text.
	 * Redaction governs values; the schema governs shape. Without this an attacker
	 * writes arbitrary content into a file an administrator later opens.
	 */
	public function test_sanitize_entry() {
		$entry = Client_Logger::sanitize_entry(
			[
				'type'        => 'network',
				'status'      => 500,
				'duration_ms' => 812,
				'message'     => 'Submission responded 500',
				'evil'        => '<script>alert(1)</script>',
				'body'        => 'Fatal error in plugin.php',
			]
		);

		$this->assertArrayNotHasKey( 'evil', $entry );
		$this->assertSame( 'network', $entry['type'] );
		$this->assertSame( 500, $entry['status'] );
	}

	/**
	 * An unrecognised type is the fallthrough case and must produce nothing, so a
	 * caller cannot invent a category.
	 */
	public function test_sanitize_entry_rejects_an_unknown_type() {
		$this->assertSame( [], Client_Logger::sanitize_entry( [ 'type' => 'anything', 'message' => 'x' ] ) );
		$this->assertSame( [], Client_Logger::sanitize_entry( [ 'message' => 'no type at all' ] ) );
	}

	/**
	 * A type on its own says nothing and would just pad the file toward its cap.
	 */
	public function test_sanitize_entry_requires_some_detail() {
		$this->assertSame( [], Client_Logger::sanitize_entry( [ 'type' => 'error' ] ) );
	}

	/**
	 * Field keys are logged, values never are. The download must not become a
	 * personal-data export.
	 */
	public function test_sanitize_entry_keeps_field_keys_only() {
		$entry = Client_Logger::sanitize_entry(
			[
				'type'       => 'network',
				'status'     => 400,
				'field_keys' => array_fill( 0, 150, 'srfm-input-lbl-abc' ),
			]
		);

		// Capped so one request cannot consume the file.
		$this->assertCount( 100, $entry['field_keys'] );
	}

	/**
	 * Capping the count but not each key's length let one request write ~1MB of
	 * field keys and fill the log in a single call. Because the log stops rather
	 * than evicting, that silently disabled the feature until an admin cleared it
	 * -- a no-auth denial of service against the plugin's own diagnostics.
	 *
	 * The previous test used short fixed strings and passed throughout.
	 */
	public function test_sanitize_entry_clamps_each_field_key_length() {
		$entry = Client_Logger::sanitize_entry(
			[
				'type'       => 'network',
				'status'     => 400,
				'field_keys' => array_fill( 0, 100, str_repeat( 'A', 10000 ) ),
			]
		);

		foreach ( $entry['field_keys'] as $key ) {
			$this->assertLessThanOrEqual( Client_Logger::MAX_KEY_LENGTH, mb_strlen( $key ) );
		}

		Client_Logger::append( $entry );

		// One request must not come close to the cap.
		$this->assertLessThan( Client_Logger::MAX_FILE_SIZE / 10, Client_Logger::get_file_size() );
		$this->assertFalse( Client_Logger::is_full() );
	}

	/**
	 * Two different budgets in one request return two different excerpts.
	 *
	 * The memo is keyed by blog id and budget. Keying it by reusing $max_chars
	 * made the key a string, and the byte-budget comparison inside the line loop
	 * coerces "1:1200" to 1 -- so every excerpt collapsed to a single line, on
	 * every consumer, silently. Nothing caught it because no test had asked for
	 * two budgets in the same process.
	 */
	public function test_get_tail_budget_is_not_the_memo_key() {
		Client_Logger::clear();

		for ( $i = 0; $i < 40; $i++ ) {
			Client_Logger::append(
				Client_Logger::sanitize_entry(
					[
						'type'        => 'network',
						'status'      => 500,
						'form_id'     => 42,
						'form_title'  => 'Contact Form',
						'message'     => 'Submission responded 500 (text/html): a long server failure message repeated to fill the log ' . $i,
						'duration_ms' => 900,
					]
				)
			);
		}

		$small = Client_Logger::get_tail( 1200 );
		$large = Client_Logger::get_tail( 8000 );

		// The budget is a byte count, so a small one keeps several whole lines --
		// not one, which is what a string key produced.
		$this->assertGreaterThan( 1, $small['shown'], 'A 1200-byte budget holds more than one entry.' );
		$this->assertGreaterThan(
			$small['shown'],
			$large['shown'],
			'A larger budget must return more entries, not the memoised smaller one.'
		);
		$this->assertGreaterThan( strlen( $small['text'] ), strlen( $large['text'] ) );

		// And the memo still works: the same budget twice is one read.
		$this->assertSame( $small, Client_Logger::get_tail( 1200 ) );

		Client_Logger::clear();
	}

	/**
	 * Webhook credentials are masked in every shape they actually arrive in.
	 *
	 * The round-1 finding this answers was about a Slack token surviving in a URL
	 * path. The first version of these rules missed it in the largest sink: a WP
	 * REST error body is slash-escaped, and two of the four body sinks log the raw
	 * response text, so every URL rule saw `https:\/\/` and matched nothing. It
	 * also only fired when the credential keyword was the last token of the name,
	 * so AWS_SECRET_ACCESS_KEY, stripe_secret_key and X-Hub-Signature all passed
	 * through untouched.
	 *
	 * @dataProvider provide_secrets
	 * @param string $input  Log text as it would arrive.
	 * @param string $secret The substring that must not survive.
	 */
	public function test_scrub_text_masks_credentials( $input, $secret ) {
		$this->assertStringNotContainsString(
			$secret,
			Client_Logger::scrub_text( $input ),
			'This is written to a downloadable log and pasted into a support email.'
		);
	}

	/**
	 * Inputs whose secret must not survive scrub_text().
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function provide_secrets() {
		return [
			'slack in a path'         => [ 'POST https://hooks.slack.com/services/T0/B0/xoxb-secrettoken failed', 'xoxb-secrettoken' ],
			'slack slash-escaped'     => [ '{"message":"post to https:\\/\\/hooks.slack.com\\/services\\/T1\\/B2\\/xoxb-escapedtoken failed"}', 'xoxb-escapedtoken' ],
			'discord webhook'         => [ 'Discord https://discord.com/api/webhooks/123/AbCdEfGhIjKlMnOp rejected', 'AbCdEfGhIjKlMnOp' ],
			'aws prefixed name'       => [ 'AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMIK7MDENG', 'wJalrXUtnFEMIK7MDENG' ],
			'stripe suffixed name'    => [ 'stripe_secret_key: sk_live_51H8xyzABCDEF', 'sk_live_51H8xyzABCDEF' ],
			'private key'             => [ 'private_key=MIIEvQIBADANBgkq', 'MIIEvQIBADANBgkq' ],
			'basic auth'              => [ 'Authorization: Basic dXNlcjpwYXNzd29yZA==', 'dXNlcjpwYXNzd29yZA' ],
			'bearer token'            => [ 'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.payload.sig', 'eyJhbGciOiJIUzI1NiJ9' ],
			'signature header'        => [ 'X-Hub-Signature: sha256=9f86d081884c7d659a2f1234567890ab', '9f86d081884c7d659a2f' ],
			'url userinfo'            => [ 'https://admin:hunter2pass@api.example.com/v1/x', 'hunter2pass' ],
			'json token value'        => [ '{"token": "abc123def456", "ok": false}', 'abc123def456' ],
			'query string'            => [ 'GET https://example.com/reset?key=supersecretvalue failed', 'supersecretvalue' ],
		];
	}

	/**
	 * The diagnosis survives, which is the half with no coverage before now.
	 *
	 * An earlier version of these rules truncated every URL path, including
	 * same-origin ones -- but `source` is a stack frame, not a page address, so
	 * that deleted the filename, the line and column, and which plugin threw. It
	 * also redacted ordinary prose, because the credential rule accepted a bare
	 * space as a separator: "Invalid token provided" and "password protected" are
	 * among the most common things support reads out of this log.
	 *
	 * @dataProvider provide_diagnostics
	 * @param string $input  Log text as it would arrive.
	 * @param string $needle The substring support needs to still be there.
	 */
	public function test_scrub_text_keeps_the_diagnosis( $input, $needle ) {
		$this->assertStringContainsString(
			$needle,
			Client_Logger::scrub_text( $input ),
			'Redaction must not cost the reason the log is read.'
		);
	}

	/**
	 * Inputs whose diagnostic content must survive scrub_text().
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function provide_diagnostics() {
		return [
			'own stack frame'   => [ 'at handleSubmit (' . home_url( '/wp-content/plugins/sureforms/assets/js/form-submit.min.js' ) . ':12:3456)', 'form-submit.min.js' ],
			'own line number'   => [ 'at handleSubmit (' . home_url( '/wp-content/plugins/sureforms/assets/js/form-submit.min.js' ) . ':12:3456)', ':12:3456' ],
			'prose token'       => [ 'Invalid token provided', 'token provided' ],
			'prose password'    => [ 'This form is password protected', 'password protected' ],
			'captcha message'   => [ 'captcha token verification failed', 'token verification failed' ],
			'prose secret'      => [ 'The secret provided was rejected', 'secret provided' ],
			'http status'       => [ 'Submission responded 500 (text/html)', '500' ],
			'foreign host kept' => [ 'POST https://hooks.slack.com/services/T0/B0/tok failed', 'hooks.slack.com' ],
		];
	}

	/**
	 * Logging off records nothing, not merely displays nothing.
	 *
	 * The display side was already gated. The counter was not: the two record_failure()
	 * calls in form-submit.php sit beside an append() that the enabled check does stop,
	 * so a site with logging switched off kept accumulating failure state invisibly --
	 * and switching logging back on surfaced every fault from the quiet period, behind a
	 * View details report whose debug log is empty because nothing was ever written.
	 *
	 * Asserted across the toggle rather than in one state, because "no notice right now"
	 * was already true and is what hid this.
	 */
	public function test_nothing_is_recorded_while_logging_is_disabled() {
		$general = get_option( 'srfm_general_settings_options', [] );
		$general = is_array( $general ) ? $general : [];

		delete_option( Client_Logger::FAILURES_OPTION );
		Client_Logger::clear();

		update_option( 'srfm_general_settings_options', array_merge( $general, [ 'srfm_enable_logs' => false ] ) );
		$this->assertFalse( Client_Logger::is_enabled(), 'Fixture must actually switch logging off.' );

		// Every path that reports a fault: the server-side categories called directly
		// from form-submit.php, and the client entries that route through append().
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );
		Client_Logger::record_failure( 'integration', 42, 'Contact Form' );
		Client_Logger::append(
			Client_Logger::sanitize_entry(
				[
					'type'    => 'network',
					'status'  => 500,
					'form_id' => 42,
					'message' => 'Submission responded 500',
				]
			)
		);

		$this->assertSame( 0, Client_Logger::get_file_size(), 'Nothing may be written to the log.' );
		$this->assertSame( [], Client_Logger::get_failures(), 'Nothing may be counted either.' );
		$this->assertSame( [], Client_Logger::get_open_failures(), 'So there is nothing to raise a notice about.' );

		// Switching logging on must not surface a backlog from the quiet period.
		update_option( 'srfm_general_settings_options', array_merge( $general, [ 'srfm_enable_logs' => true ] ) );

		$this->assertTrue( Client_Logger::is_enabled() );
		$this->assertSame(
			[],
			Client_Logger::get_open_failures(),
			'Turning logging on must not reveal faults from while it was off -- there is no log behind them.'
		);

		// And recording works again, so the guard is not simply always denying.
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );
		$this->assertArrayHasKey( 'notification', Client_Logger::get_open_failures() );

		update_option( 'srfm_general_settings_options', $general );
		delete_option( Client_Logger::FAILURES_OPTION );
		Client_Logger::clear();
	}

	/**
	 * An after-submission failure the browser only guessed at raises no notice.
	 *
	 * The step runs after the entry is saved. When the client reports it without a
	 * status it is reporting an abandoned fetch -- the page unloading behind a
	 * redirect confirmation, which is what keepalive exists to survive -- and that
	 * says nothing about whether the server ran srfm_after_submission_process. It is
	 * guarded by is_after_submission_process_triggered and usually has.
	 *
	 * This shipped as type 'error', which is_fault() counts unconditionally, so every
	 * Safari "TypeError: Load failed" raised a non-dismissible notice reading
	 * "Visitors may not be able to reach you, and their entries were not saved" about
	 * entries that were saved and visitors who saw the success message.
	 */
	public function test_a_browser_side_after_submission_failure_is_not_a_fault() {
		$entry = Client_Logger::sanitize_entry(
			[
				'type'    => 'after_submission',
				'form_id' => 4355,
				'message' => 'After-submission step failed: TypeError: Load failed',
			]
		);

		$this->assertNotEmpty( $entry, 'The type must survive sanitisation, or it is never logged at all.' );
		$this->assertFalse(
			Client_Logger::is_fault( $entry ),
			'A fetch the browser abandoned is not evidence the server did not do the work.'
		);
	}

	/**
	 * A server-confirmed after-submission failure is a fault, and an integration one.
	 *
	 * srfm_after_submission_process is where integrations and webhooks hook in, so a
	 * 5xx there means a third party did not receive the entry -- which is what the
	 * integration notice says. The submission notice says entries were not saved, and
	 * by this point they have been.
	 */
	public function test_a_server_confirmed_after_submission_failure_is_an_integration_fault() {
		delete_option( Client_Logger::FAILURES_OPTION );
		Client_Logger::clear();

		$entry = Client_Logger::sanitize_entry(
			[
				'type'    => 'after_submission',
				'status'  => 500,
				'form_id' => 4355,
				'message' => 'After-submission step responded 500',
			]
		);

		$this->assertTrue( Client_Logger::is_fault( $entry ), 'The server said it failed.' );

		Client_Logger::append( $entry );

		$open = Client_Logger::get_open_failures();

		$this->assertArrayHasKey( 'integration', $open, 'A failed after-submission step is an integration failure.' );
		$this->assertArrayNotHasKey(
			'submission',
			$open,
			'It must not claim the entry was never saved -- it was saved before this step ran.'
		);

		delete_option( Client_Logger::FAILURES_OPTION );
		Client_Logger::clear();
	}

	/**
	 * A visitor-correctable stop is not written to the log at all.
	 *
	 * "This field is required" and "Please verify that you are not a robot" were
	 * the most common lines in a real log, and every one of them was pasted into
	 * a support report about some other failure. They are dropped at the shape
	 * gate, so no caller -- the REST route or a future server-side one -- can
	 * write them.
	 */
	public function test_sanitize_entry_drops_a_blocked_entry() {
		$entry = Client_Logger::sanitize_entry(
			[
				'type'    => 'blocked',
				'form_id' => 4355,
				'message' => 'Blocked before submit: Please verify that you are not a robot.',
			]
		);

		$this->assertSame( [], $entry );
	}

	/**
	 * A site on a non-default port keeps its own stack frames.
	 *
	 * The URL pattern captures the whole authority, so a frame from a site served on
	 * :8443 arrives as `example.test:8443`, while $site_host is PHP_URL_HOST and never
	 * carries a port. Every own frame therefore failed the same-origin check and was
	 * truncated to /[path] -- deleting the filename, the line and the column, which is
	 * the exact loss this exemption exists to prevent. Local installs, staging behind a
	 * proxy and anything not on 80/443 were all affected.
	 *
	 * The existing provider cannot catch this: it builds its frames from home_url(),
	 * which has no port on the test site, so both sides of the comparison match by
	 * accident.
	 */
	public function test_scrub_text_keeps_own_frames_on_a_non_default_port() {
		$home = static function () {
			return 'https://example.test:8443';
		};

		add_filter( 'home_url', $home, 99 );

		$own = Client_Logger::scrub_text(
			'at handleSubmit (https://example.test:8443/wp-content/plugins/sureforms/assets/js/form-submit.min.js:12:3456)'
		);

		// A foreign host, same port, so the port strip cannot have turned the
		// exemption into "anything carrying a port is ours".
		$foreign = Client_Logger::scrub_text(
			'POST https://hooks.slack.com:8443/services/T0/B0/tok failed'
		);

		remove_filter( 'home_url', $home, 99 );

		$this->assertStringContainsString( 'form-submit.min.js', $own, 'The filename is the diagnosis.' );
		$this->assertStringContainsString( ':12:3456', $own, 'So are the line and column.' );
		$this->assertStringNotContainsString( '[path]', $own, 'Our own frame must not be truncated.' );

		$this->assertStringContainsString( 'hooks.slack.com', $foreign, 'The origin still identifies the third party.' );
		$this->assertStringNotContainsString( 'tok', $foreign, 'A foreign path may still hold a credential.' );
	}

	/**
	 * A phone number is written 555-123-4567, not 5551234567, so a
	 * contiguous-digits rule never sees a real one. The earlier test only used an
	 * unformatted run and passed regardless.
	 */
	public function test_scrub_text_redacts_formatted_numbers() {
		foreach ( [
			'Phone 555-123-4567 rejected',
			'Call (555) 123-4567 now',
			'Intl +44 7700 900123 failed',
			'Card 4111 1111 1111 1111 declined',
		] as $sample ) {
			$scrubbed = Client_Logger::scrub_text( $sample );

			$this->assertStringContainsString( '[number]', $scrubbed, $sample );
			$this->assertDoesNotMatchRegularExpression( '/\d{3}[\s.-]\d{3}/', $scrubbed, $sample );
		}
	}

	/**
	 * A token after # is as sensitive as one after ?, and the URL rule only
	 * stripped query strings.
	 */
	public function test_scrub_text_strips_url_fragments() {
		$this->assertStringNotContainsString(
			's3cr3t',
			Client_Logger::scrub_text( 'Failed at https://example.com/reset#token=s3cr3t' )
		);
	}

	/**
	 * Redacting submitted values is not enough on its own: error text interpolates
	 * user input constantly, and page URLs carry addresses and reset keys in their
	 * query strings.
	 */
	public function test_scrub_text() {
		$this->assertStringNotContainsString(
			'bob@example.com',
			Client_Logger::scrub_text( 'Invalid email: bob@example.com' )
		);
		$this->assertStringNotContainsString(
			'447700900123',
			Client_Logger::scrub_text( 'Phone 447700900123 rejected' )
		);
		$this->assertStringNotContainsString(
			'reset_key',
			Client_Logger::scrub_text( 'Failed at https://example.com/page?reset_key=secret' )
		);
	}

	/**
	 * The scrubber must not eat the diagnosis. Provider error codes, WordPress
	 * error slugs and bracketed code lists are the payload of a CAPTCHA or
	 * validation rejection -- a future tightening of the redaction rules that
	 * strips them leaves entries that say a submission failed and nothing more.
	 */
	public function test_scrub_text_preserves_error_codes() {
		$message = 'Submission responded 200 (application/json) (srfm_invalid_form_id): Google reCAPTCHA verification failed. [invalid-input-response, timeout-or-duplicate]';

		$scrubbed = Client_Logger::scrub_text( $message );

		$this->assertStringContainsString( 'invalid-input-response', $scrubbed );
		$this->assertStringContainsString( 'timeout-or-duplicate', $scrubbed );
		$this->assertStringContainsString( 'srfm_invalid_form_id', $scrubbed );
		$this->assertStringContainsString( '200', $scrubbed );
	}

	/**
	 * Long text must be clamped so one entry cannot fill the file, and markup must
	 * not survive into a document someone opens.
	 */
	public function test_scrub_text_clamps_and_strips() {
		$scrubbed = Client_Logger::scrub_text( str_repeat( 'a', 5000 ) );

		$this->assertSame( Client_Logger::MAX_TEXT_LENGTH, mb_strlen( $scrubbed ) );
		$this->assertStringNotContainsString( '<script>', Client_Logger::scrub_text( '<script>alert(1)</script> hi' ) );
	}

	/**
	 * Every failure category the form can produce must survive the schema, or the
	 * "logs all submission failures" claim quietly stops being true for one of them.
	 *
	 * The categories are: a failure the server rejected, a request that never
	 * reached PHP, a response that was not JSON, and a failure caught in the
	 * browser before the request went out (client-side validation, captcha,
	 * payment) or after it returned (the after-submission step, a notification
	 * email).
	 */
	public function test_sanitize_entry_accepts_every_failure_category() {
		$cases = [
			'server rejection'  => [ 'type' => 'network', 'status' => 200, 'field_keys' => [ 'srfm-email-lbl-x' ] ],
			'transport failure' => [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ],
			'non-JSON response' => [ 'type' => 'response', 'status' => 500, 'body' => 'PHP Fatal error' ],
			'blocked pre-submit' => [ 'type' => 'message', 'message' => 'Blocked before submit: field validation failed.' ],
		];

		foreach ( $cases as $label => $raw ) {
			$this->assertNotSame( [], Client_Logger::sanitize_entry( $raw ), $label . ' must be loggable.' );
		}
	}

	/**
	 * The tail is what goes into a support email, where the transport sets the
	 * limit. Whole lines only -- half a JSON object helps nobody -- and the newest
	 * entries, because those describe the failure being reported.
	 */
	public function test_get_tail() {
		for ( $i = 1; $i <= 12; $i++ ) {
			Client_Logger::append( [ 'type' => 'error', 'message' => 'failure number ' . $i ] );
		}

		$tail = Client_Logger::get_tail( 400 );

		$this->assertSame( 12, $tail['total'] );
		$this->assertLessThan( 12, $tail['shown'], 'A budget smaller than the log must truncate.' );
		$this->assertStringContainsString( 'failure number 12', $tail['text'], 'The newest entry must survive.' );
		$this->assertStringNotContainsString( 'failure number 1"', $tail['text'], 'The oldest must be dropped first.' );

		foreach ( explode( "\n", $tail['text'] ) as $line ) {
			$this->assertNotNull( json_decode( $line, true ), 'Every line must be whole JSON.' );
		}
	}

	/**
	 * The budget is a ceiling even when the newest entry alone is over it.
	 *
	 * An empty excerpt is worse than a long one, so the newest entry is always kept
	 * -- but trimmed to the budget, because the caller is building a mailto: and a
	 * URL past the client's limit loses the whole body.
	 */
	public function test_get_tail_trims_a_single_oversized_entry() {
		Client_Logger::clear();
		Client_Logger::append( [ 'type' => 'error', 'message' => 'failure number 1' ] );
		Client_Logger::append( [ 'type' => 'error', 'message' => str_repeat( 'x', 5000 ) ] );

		$tail = Client_Logger::get_tail( 400 );

		$this->assertSame( 1, $tail['shown'], 'Only the newest entry fits.' );
		$this->assertSame( 2, $tail['total'] );
		$this->assertLessThanOrEqual( 400, strlen( $tail['text'] ), 'The budget holds for a single entry too.' );
		$this->assertStringStartsWith( '{"type":"error"', $tail['text'], 'The start of the entry says what failed, so it is the part kept.' );
		$this->assertStringEndsWith( '[truncated]', $tail['text'] );

		Client_Logger::clear();
	}

	/**
	 * An empty or absent log must not produce a broken excerpt.
	 */
	public function test_get_tail_handles_an_empty_log() {
		Client_Logger::clear();

		$tail = Client_Logger::get_tail();

		$this->assertSame( '', $tail['text'] );
		$this->assertSame( 0, $tail['total'] );
	}

	// ---------------------------------------------------------------
	// Repeated-failure detection
	// ---------------------------------------------------------------

	/**
	 * The distinction the whole notice rests on. A mistyped email, an expired
	 * captcha, a declined card and a rejection naming the field to fix are the form
	 * working correctly. Counting them would tell healthy sites to contact support
	 * -- on every install, because logging is on by default.
	 */
	public function test_is_fault() {
		$visitor_correctable = [
			'field validation'   => [ 'type' => 'blocked', 'message' => 'field validation failed' ],
			'captcha'            => [ 'type' => 'blocked', 'message' => 'captcha validation failed' ],
			'declined card'      => [ 'type' => 'blocked', 'message' => 'payment. Card declined.' ],
			'rejected field'     => [ 'type' => 'blocked', 'status' => 200, 'field_keys' => [ 'srfm-email-lbl-x' ] ],
		];

		foreach ( $visitor_correctable as $label => $entry ) {
			$this->assertFalse( Client_Logger::is_fault( $entry ), $label . ' must not count as a fault.' );
		}

		$faults = [
			'transport failure' => [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ],
			'non-JSON response' => [ 'type' => 'response', 'status' => 500, 'body' => 'PHP Fatal error' ],
			'server error'      => [ 'type' => 'network', 'status' => 502 ],
			'token refused'     => [ 'type' => 'network', 'status' => 403 ],
			'email failure'     => [ 'type' => 'message', 'message' => 'Email notification failed to send.' ],
		];

		foreach ( $faults as $label => $entry ) {
			$this->assertTrue( Client_Logger::is_fault( $entry ), $label . ' must count as a fault.' );
		}
	}

	/**
	 * A 200 without field errors is the server failing rather than refusing, but a
	 * 200 is also how every ordinary rejection is returned -- so it must not count
	 * on its own.
	 */
	public function test_is_fault_ignores_an_ordinary_rejection() {
		$this->assertFalse( Client_Logger::is_fault( [ 'type' => 'network', 'status' => 200 ] ) );
	}

	/**
	 * The notice appears only after a run of faults, so one bad request on an
	 * otherwise healthy site says nothing.
	 */
	public function test_has_persistent_failures() {
		$this->assertFalse( Client_Logger::has_persistent_failures() );

		// However many visitor-correctable stops occur, none of them is a fault.
		// This is what stops the notice firing on a mistyped email now that a
		// single fault is enough to raise it.
		for ( $i = 0; $i < 10; $i++ ) {
			Client_Logger::append( [ 'type' => 'blocked', 'message' => 'field validation failed' ] );
		}

		$this->assertFalse( Client_Logger::has_persistent_failures(), 'Blocked entries must never raise it.' );

		Client_Logger::append( [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ] );

		$this->assertSame( Client_Logger::FAULT_THRESHOLD, Client_Logger::get_fault_streak() );
		$this->assertTrue( Client_Logger::has_persistent_failures(), 'A single fault is enough.' );
	}

	/**
	 * Visitor-correctable failures must never accumulate toward the notice, however
	 * many of them there are. A busy form produces these constantly.
	 */
	public function test_get_fault_streak_ignores_blocked_entries() {
		for ( $i = 0; $i < 20; $i++ ) {
			Client_Logger::append( [ 'type' => 'blocked', 'message' => 'field validation failed' ] );
		}

		$this->assertSame( 0, Client_Logger::get_fault_streak() );
		$this->assertFalse( Client_Logger::has_persistent_failures() );
	}

	/**
	 * When the newest fault happened. Not what the notice decides on -- the fault
	 * counter is, because both are written to the second -- but it is what tells
	 * support how recent the trouble is, so it has to actually advance.
	 */
	public function test_get_last_fault_time() {
		$this->assertSame( 0, Client_Logger::get_last_fault_time() );

		Client_Logger::append( [ 'type' => 'error', 'message' => 'failure' ] );

		$this->assertGreaterThan( 0, Client_Logger::get_last_fault_time() );

		// A visitor-correctable stop is not a fault and must not move it.
		$before = Client_Logger::get_last_fault_time();
		Client_Logger::append( [ 'type' => 'blocked', 'message' => 'field validation failed' ] );

		$this->assertSame( $before, Client_Logger::get_last_fault_time() );
	}

	/**
	 * Reporting the failures retires the notice, and a fault the site owner has
	 * not reported brings it straight back.
	 */
	public function test_acknowledge_failures() {
		for ( $i = 0; $i < Client_Logger::FAULT_THRESHOLD; $i++ ) {
			Client_Logger::append( [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ] );
		}

		$this->assertTrue( Client_Logger::has_persistent_failures() );

		Client_Logger::acknowledge_failures();

		$this->assertFalse( Client_Logger::has_persistent_failures(), 'Reported failures must go quiet.' );
		$this->assertFalse( Client_Logger::has_persistent_failures(), 'And stay quiet with nothing new.' );

		Client_Logger::append( [ 'type' => 'response', 'status' => 500, 'body' => 'a new fatal' ] );

		$this->assertTrue( Client_Logger::has_persistent_failures(), 'An unreported fault must bring it back.' );
	}

	/**
	 * Decided on the fault counter, not the clock.
	 *
	 * Both the acknowledgement and the fault are written to the second, so a fault
	 * landing in the same second as the click would compare as not-newer and stay
	 * hidden -- suppressing a failure nobody has reported. The counter is
	 * monotonic, so the comparison is exact regardless of timing.
	 */
	public function test_acknowledge_failures_is_not_decided_by_the_clock() {
		for ( $i = 0; $i < Client_Logger::FAULT_THRESHOLD; $i++ ) {
			Client_Logger::append( [ 'type' => 'error', 'message' => 'failure' ] );
		}

		Client_Logger::acknowledge_failures();
		$acknowledged = Client_Logger::get_acknowledgement();

		// Same-second: a timestamp comparison cannot separate these two.
		Client_Logger::append( [ 'type' => 'error', 'message' => 'same second failure' ] );

		$this->assertSame(
			$acknowledged['at'],
			Client_Logger::get_acknowledgement()['at'],
			'Fixture must exercise the same-second case.'
		);
		$this->assertTrue(
			Client_Logger::has_persistent_failures(),
			'A fault in the same second as the acknowledgement must still show.'
		);
	}

	/**
	 * The stored record carries the timestamp as well as the count -- the count
	 * decides, but the timestamp is what tells support when it was reported.
	 */
	public function test_get_acknowledgement() {
		$this->assertSame( [], Client_Logger::get_acknowledgement() );

		Client_Logger::append( [ 'type' => 'error', 'message' => 'failure' ] );
		Client_Logger::acknowledge_failures();

		$stored = Client_Logger::get_acknowledgement();

		$this->assertArrayHasKey( 'at', $stored );
		$this->assertArrayHasKey( 'streak', $stored );
		$this->assertGreaterThan( 0, $stored['at'] );
	}

	/**
	 * A working form clears the acknowledgement with the streak, so the next run
	 * of failures is judged on its own rather than against a stale report.
	 */
	public function test_reset_fault_streak_clears_the_acknowledgement() {
		Client_Logger::append( [ 'type' => 'error', 'message' => 'failure' ] );
		Client_Logger::acknowledge_failures();

		$this->assertNotSame( [], Client_Logger::get_acknowledgement() );

		Client_Logger::reset_fault_streak();

		$this->assertSame( [], Client_Logger::get_acknowledgement() );
	}

	/**
	 * One submission getting through is the best evidence the form is not broken,
	 * and retires the notice without anyone dismissing it.
	 */
	public function test_reset_fault_streak() {
		for ( $i = 0; $i < Client_Logger::FAULT_THRESHOLD; $i++ ) {
			Client_Logger::append( [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ] );
		}

		$this->assertTrue( Client_Logger::has_persistent_failures() );

		Client_Logger::reset_fault_streak();

		$this->assertSame( 0, Client_Logger::get_fault_streak() );
		$this->assertFalse( Client_Logger::has_persistent_failures() );
	}

	/**
	 * A full log or an unwritable uploads directory must not blind the notice --
	 * on a badly broken site those are exactly the conditions that occur.
	 */
	public function test_get_fault_streak_counts_even_when_the_log_is_full() {
		$this->fill_log();
		$this->assertTrue( Client_Logger::is_full() );

		$before = Client_Logger::get_fault_streak();
		Client_Logger::append( [ 'type' => 'error', 'message' => 'TypeError: Failed to fetch' ] );

		$this->assertSame( $before + 1, Client_Logger::get_fault_streak() );
	}

	// ---------------------------------------------------------------
	// Per-category failures
	// ---------------------------------------------------------------

	/**
	 * The three read completely differently to a site owner, so they are tracked
	 * apart: submissions failing means visitors cannot reach you, a notification
	 * failing means you are not hearing about entries that did save, an
	 * integration failing means a third party is not receiving them.
	 */
	public function test_record_failure() {
		$this->assertSame( [], Client_Logger::get_open_failures() );

		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );

		$open = Client_Logger::get_open_failures();

		$this->assertArrayHasKey( 'notification', $open );
		$this->assertArrayNotHasKey( 'submission', $open, 'Categories must not bleed into each other.' );
		$this->assertSame( 42, $open['notification']['form_id'] );
		$this->assertSame( 'Contact Form', $open['notification']['form_title'] );
	}

	/**
	 * The raw store, including categories already reported.
	 *
	 * get_open_failures() filters those out, so this is what shows a report was
	 * made rather than the failure having gone away.
	 */
	public function test_get_failures() {
		$this->assertSame( [], Client_Logger::get_failures() );

		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );
		Client_Logger::acknowledge_category( 'notification' );

		$all = Client_Logger::get_failures();

		$this->assertArrayHasKey( 'notification', $all, 'A reported failure is still on record.' );
		$this->assertSame( 1, $all['notification']['count'] );
		$this->assertSame( 1, $all['notification']['acked'] );
		$this->assertSame( [], Client_Logger::get_open_failures(), 'But it is no longer open.' );
	}

	/**
	 * Only categories with failures the owner has not reported, compared on the
	 * count rather than the clock -- both are written to the second, so a failure
	 * landing in the same second as the report would look not-newer and be hidden.
	 */
	public function test_get_open_failures() {
		$this->assertSame( [], Client_Logger::get_open_failures() );

		Client_Logger::record_failure( 'submission', 42, 'Contact Form' );

		$this->assertArrayHasKey( 'submission', Client_Logger::get_open_failures() );

		Client_Logger::acknowledge_category( 'submission' );

		$this->assertSame( [], Client_Logger::get_open_failures() );

		// Same second as the acknowledgement: the count still separates them.
		Client_Logger::record_failure( 'submission', 42, 'Contact Form' );

		$this->assertArrayHasKey(
			'submission',
			Client_Logger::get_open_failures(),
			'A failure in the same second as the report must still be open.'
		);
	}

	/**
	 * An unrecognised category is dropped rather than creating a notice nothing
	 * knows how to describe.
	 */
	public function test_record_failure_rejects_an_unknown_category() {
		Client_Logger::record_failure( 'not_a_category', 42, 'Contact Form' );

		$this->assertSame( [], Client_Logger::get_open_failures() );
	}

	/**
	 * Reporting one category must not silence the others.
	 */
	public function test_acknowledge_category() {
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );
		Client_Logger::record_failure( 'integration', 42, 'Contact Form' );

		Client_Logger::acknowledge_category( 'notification' );

		$open = Client_Logger::get_open_failures();

		$this->assertArrayNotHasKey( 'notification', $open );
		$this->assertArrayHasKey( 'integration', $open, 'Only the reported category goes quiet.' );

		// A further failure in the reported category brings it back.
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );

		$this->assertArrayHasKey( 'notification', Client_Logger::get_open_failures() );
	}

	/**
	 * A submission getting through says nothing about notifications or
	 * integrations, so it must not clear them.
	 */
	public function test_reset_fault_streak_clears_only_the_submission_category() {
		Client_Logger::record_failure( 'submission', 42, 'Contact Form' );
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );

		Client_Logger::reset_fault_streak();

		$open = Client_Logger::get_open_failures();

		$this->assertArrayNotHasKey( 'submission', $open );
		$this->assertArrayHasKey( 'notification', $open );
	}

	/**
	 * Forgetting a category removes it outright, unlike acknowledging it.
	 */
	public function test_clear_category() {
		Client_Logger::record_failure( 'integration', 42, 'Contact Form' );
		Client_Logger::clear_category( 'integration' );

		$this->assertSame( [], Client_Logger::get_failures() );
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * Switch the stored setting, stamping the start so the expiry logic behaves.
	 *
	 * @param bool $enabled Whether logging should be on.
	 * @return void
	 */
	private function set_logging( $enabled ) {
		$general                     = (array) get_option( 'srfm_general_settings_options', [] );
		$general['srfm_enable_logs'] = $enabled;
		update_option( 'srfm_general_settings_options', $general );

	}

	/**
	 * Append until the log refuses further writes.
	 *
	 * @return void
	 */
	private function fill_log() {
		$blob = str_repeat( 'a', Client_Logger::MAX_TEXT_LENGTH );

		for ( $i = 0; $i < 5000; $i++ ) {
			if ( ! Client_Logger::append( [ 'type' => 'error', 'message' => $blob ] ) ) {
				return;
			}
		}
	}
}
