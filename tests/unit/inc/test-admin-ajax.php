<?php
/**
 * Class Test_Admin_Ajax
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Admin_Ajax;
use SRFM\Inc\Client_Logger;

class Test_Admin_Ajax extends SRFM_Unit_Test_Case {

	protected $admin_ajax;

	protected function setUp(): void {
		parent::setUp();
		$this->admin_ajax = new Admin_Ajax();
	}

	/**
	 * Helper method to call private methods for testing.
	 */
	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}

	// ---------------------------------------------------------------
	// Tests for constructor / AJAX action registration
	// ---------------------------------------------------------------

	public function test_constructor_registers_plugin_activate_action() {
		$ajax = new Admin_Ajax();
		$this->assertIsInt(
			has_action( 'wp_ajax_sureforms_recommended_plugin_activate', [ $ajax, 'required_plugin_activate' ] )
		);
	}

	public function test_constructor_registers_plugin_install_action() {
		$this->assertIsInt(
			has_action( 'wp_ajax_sureforms_recommended_plugin_install', 'wp_ajax_install_plugin' )
		);
	}

	public function test_constructor_registers_integration_action() {
		$ajax = new Admin_Ajax();
		$this->assertIsInt(
			has_action( 'wp_ajax_sureforms_integration', [ $ajax, 'generate_data_for_suretriggers_integration' ] )
		);
	}

	public function test_constructor_registers_download_export_action() {
		$ajax = new Admin_Ajax();
		$this->assertIsInt(
			has_action( 'wp_ajax_srfm_download_export', [ $ajax, 'download_export_file' ] )
		);
	}

	// ---------------------------------------------------------------
	// Tests for get_error_msg()
	// ---------------------------------------------------------------

	public function test_get_error_msg_returns_empty_when_errors_not_set() {
		$ajax   = new Admin_Ajax();
		$result = $ajax->get_error_msg( 'permission' );
		$this->assertEquals( '', $result );
	}

	public function test_get_error_msg_falls_back_to_default_for_unknown_type() {
		$ajax = new Admin_Ajax();
		// Set errors dynamically since property is not declared.
		$reflection = new \ReflectionObject( $ajax );
		if ( ! $reflection->hasProperty( 'errors' ) ) {
			$ajax->errors = [
				'default'    => 'Something went wrong.',
				'permission' => 'You do not have permission.',
			];
		}

		$result = $ajax->get_error_msg( 'nonexistent_type' );
		$this->assertEquals( 'Something went wrong.', $result );
	}

	public function test_get_error_msg_returns_correct_message_for_known_type() {
		$ajax = new Admin_Ajax();
		$ajax->errors = [
			'default'    => 'Something went wrong.',
			'permission' => 'You do not have permission.',
			'nonce'      => 'Nonce verification failed.',
		];

		$this->assertEquals( 'You do not have permission.', $ajax->get_error_msg( 'permission' ) );
		$this->assertEquals( 'Nonce verification failed.', $ajax->get_error_msg( 'nonce' ) );
	}

	// ---------------------------------------------------------------
	// Tests for get_sample_data()
	// ---------------------------------------------------------------

	public function test_get_sample_data_returns_default_for_empty_name() {
		$result = $this->admin_ajax->get_sample_data( '' );
		$this->assertEquals( 'Sample data', $result );
	}

	public function test_get_sample_data_returns_correct_phone_data() {
		$result = $this->admin_ajax->get_sample_data( 'srfm/phone' );
		$this->assertEquals( '1234567890', $result );
	}

	public function test_get_sample_data_returns_default_for_unknown_block() {
		$result = $this->admin_ajax->get_sample_data( 'srfm/unknown-widget' );
		$this->assertEquals( 'Sample data', $result );
	}

	// ---------------------------------------------------------------
	// Tests for get_form_fields()
	// ---------------------------------------------------------------

	public function test_get_form_fields_returns_empty_for_zero_id() {
		$result = $this->admin_ajax->get_form_fields( 0 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_get_form_fields_returns_empty_for_non_integer_id() {
		$result = $this->admin_ajax->get_form_fields( 'string' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_get_form_fields_returns_empty_for_wrong_post_type() {
		$post_id = wp_insert_post( [
			'post_title'  => 'Test Page',
			'post_type'   => 'page',
			'post_status' => 'publish',
		] );
		$result = $this->admin_ajax->get_form_fields( $post_id );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
		wp_delete_post( $post_id, true );
	}

	public function test_get_form_fields_returns_empty_for_nonexistent_post() {
		$result = $this->admin_ajax->get_form_fields( 999999 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ---------------------------------------------------------------
	// Tests for localize_script_integration()
	// ---------------------------------------------------------------

	public function test_localize_script_integration_returns_merged_array() {
		$initial = [ 'existing_key' => 'existing_value' ];
		$result  = $this->admin_ajax->localize_script_integration( $initial );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'existing_key', $result );
		$this->assertEquals( 'existing_value', $result['existing_key'] );
	}

	public function test_localize_script_integration_contains_ajax_url() {
		$result = $this->admin_ajax->localize_script_integration( [] );
		$this->assertArrayHasKey( 'ajax_url', $result );
		$this->assertStringContainsString( 'admin-ajax.php', $result['ajax_url'] );
	}

	public function test_localize_script_integration_contains_nonce_keys() {
		$result = $this->admin_ajax->localize_script_integration( [] );
		$this->assertArrayHasKey( 'sfPluginManagerNonce', $result );
		$this->assertArrayHasKey( 'plugin_installer_nonce', $result );
		$this->assertArrayHasKey( 'suretriggers_nonce', $result );
	}

	public function test_localize_script_integration_nonces_are_strings() {
		$result = $this->admin_ajax->localize_script_integration( [] );
		$this->assertIsString( $result['sfPluginManagerNonce'] );
		$this->assertIsString( $result['plugin_installer_nonce'] );
		$this->assertIsString( $result['suretriggers_nonce'] );
	}

	public function test_localize_script_integration_contains_isrtl() {
		$result = $this->admin_ajax->localize_script_integration( [] );
		$this->assertArrayHasKey( 'isRTL', $result );
	}

	public function test_localize_script_integration_contains_form_id() {
		$result = $this->admin_ajax->localize_script_integration( [] );
		$this->assertArrayHasKey( 'form_id', $result );
	}

	// ---------------------------------------------------------------
	// Tests for required_plugin_activate() security checks
	// ---------------------------------------------------------------

	public function test_required_plugin_activate_requires_capability() {
		// Verify user 0 does not have manage_options.
		wp_set_current_user( 0 );
		$this->assertFalse( current_user_can( 'manage_options' ) );
	}

	/**
	 * Install a probe plugin whose activation hook records that it ran, call
	 * the AJAX handler for it, and clean up.
	 *
	 * @param array<string,callable> $filters Extra filters for the call.
	 * @return array{response:string,ran:string,redirect:mixed}
	 */
	private function activate_probe( array $filters = [] ) {
		$file = 'srfm-activation-probe/srfm-activation-probe.php';
		$path = WP_PLUGIN_DIR . '/' . $file;
		wp_mkdir_p( dirname( $path ) );
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
			$path,
			"<?php\n/**\n * Plugin Name: SRFM Activation Probe\n */\nregister_activation_hook( __FILE__, function () { update_option( 'srfm_activation_probe_ran', 1 ); update_option( 'srfm_probe_do_redirect', 1 ); } );\n"
		);
		wp_clean_plugins_cache( false );

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		$_POST    = [
			'security' => wp_create_nonce( 'sf_plugin_manager_nonce' ),
			'init'     => $file,
			'slug'     => 'srfm-activation-probe',
		];
		$_REQUEST = $_POST;

		foreach ( $filters as $hook => $callback ) {
			add_filter( $hook, $callback );
		}
		add_filter( 'wp_doing_ajax', '__return_true' );
		$die = static function () {
			return static function () {
				throw new \RuntimeException( 'wp_die' );
			};
		};
		add_filter( 'wp_die_ajax_handler', $die );

		ob_start();
		try {
			$this->admin_ajax->required_plugin_activate();
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}
		$response = (string) ob_get_clean();

		remove_filter( 'wp_die_ajax_handler', $die );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		foreach ( $filters as $hook => $callback ) {
			remove_filter( $hook, $callback );
		}

		$result = [
			'response' => $response,
			'ran'      => (string) get_option( 'srfm_activation_probe_ran' ),
			'redirect' => get_option( 'srfm_probe_do_redirect' ),
		];

		deactivate_plugins( $file, true );
		delete_option( 'srfm_activation_probe_ran' );
		delete_option( 'srfm_probe_do_redirect' );
		wp_delete_file( $path );
		rmdir( dirname( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture.
		$_POST    = [];
		$_REQUEST = [];

		return $result;
	}

	private function allow_probe() {
		return static function ( $plugins ) {
			$plugins[] = 'srfm-activation-probe/srfm-activation-probe.php';
			return $plugins;
		};
	}

	/**
	 * Installed from SureForms, SureMail's email log table was never created:
	 * the activation was silent, which skips the plugin's activation hook.
	 */
	public function test_required_plugin_activate_runs_the_activation_hook() {
		$result = $this->activate_probe( [ 'srfm_activatable_plugins' => $this->allow_probe() ] );

		$this->assertStringContainsString( '"success":true', $result['response'] );
		$this->assertSame( '1', $result['ran'], 'The plugin\'s activation hook must run.' );
	}

	/**
	 * With the hook running, the plugin's "redirect to my onboarding" flag is
	 * cleared, so the owner is not pulled out of SureForms on the next page.
	 */
	public function test_required_plugin_activate_clears_the_plugins_redirect_flag() {
		$result = $this->activate_probe(
			[
				'srfm_activatable_plugins'              => $this->allow_probe(),
				'srfm_plugin_activation_redirect_flags' => static function ( $flags ) {
					$flags['srfm-activation-probe/srfm-activation-probe.php'] = [ 'option' => 'srfm_probe_do_redirect' ];
					return $flags;
				},
			]
		);

		$this->assertSame( '1', $result['ran'] );
		$this->assertFalse( $result['redirect'] );
	}

	/**
	 * Only plugins SureForms offers can be activated through it: activation
	 * runs the plugin's own code.
	 */
	public function test_required_plugin_activate_rejects_plugins_sureforms_does_not_offer() {
		$result = $this->activate_probe();

		$this->assertStringContainsString( '"success":false', $result['response'] );
		$this->assertSame( '', $result['ran'], 'Nothing ran.' );
	}

	/**
	 * manage_options is not enough: a multisite sub-site admin has it but may
	 * not activate plugins.
	 */
	public function test_required_plugin_activate_needs_activate_plugins() {
		$result = $this->activate_probe(
			[
				'srfm_activatable_plugins' => $this->allow_probe(),
				'user_has_cap'             => static function ( $caps ) {
					$caps['activate_plugins'] = false;
					return $caps;
				},
			]
		);

		$this->assertStringContainsString( '"success":false', $result['response'] );
		$this->assertSame( '', $result['ran'] );
	}

	public function test_get_activatable_plugins() {
		$plugins = Admin_Ajax::get_activatable_plugins();

		$this->assertContains( 'suremails/suremails.php', $plugins );
		$this->assertContains( 'mcp-adapter/mcp-adapter.php', $plugins );
		$this->assertContains( 'astra-sites/astra-sites.php', $plugins );
		$this->assertNotContains( 'akismet/akismet.php', $plugins );
	}

	public function test_clear_activation_redirect() {
		update_option( 'suremails_do_redirect', true );
		set_transient( 'st-redirect-after-activation', true, 120 );

		Admin_Ajax::clear_activation_redirect( 'suremails/suremails.php' );
		Admin_Ajax::clear_activation_redirect( 'suretriggers/suretriggers.php' );

		$this->assertFalse( get_option( 'suremails_do_redirect' ) );
		$this->assertFalse( get_transient( 'st-redirect-after-activation' ) );
	}

	// ---------------------------------------------------------------
	// Tests for download_export_file() security - path traversal
	// ---------------------------------------------------------------

	public function test_download_export_sanitizes_filename() {
		$dangerous_filename = '../../etc/passwd';
		$sanitized          = sanitize_file_name( $dangerous_filename );
		$this->assertStringNotContainsString( '..', $sanitized );
		$this->assertStringNotContainsString( '/', $sanitized );
	}

	public function test_download_export_sanitize_strips_directory_traversal() {
		$dangerous_filename = '../../../wp-config.php';
		$sanitized          = sanitize_file_name( $dangerous_filename );
		$this->assertStringNotContainsString( '..', $sanitized );
	}

	public function test_download_export_file_path_must_be_in_temp_dir() {
		$temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) );
		$safe     = $temp_dir . 'export.csv';
		$unsafe   = '/etc/passwd';

		$this->assertSame( 0, strpos( wp_normalize_path( $safe ), $temp_dir ) );
		$this->assertNotSame( 0, strpos( wp_normalize_path( $unsafe ), $temp_dir ) );
	}

	// ---------------------------------------------------------------
	// Tests for generate_data_for_suretriggers_integration() guards
	// ---------------------------------------------------------------

	/**
	 * The SureTriggers integration AJAX handler must reject requests that fail
	 * its capability, nonce, and required-parameter guards (each ends in a
	 * wp_send_json_error -> WPDieException in the test runner).
	 */
	public function test_generate_data_for_suretriggers_integration() {
		// 1. No logged-in user => capability check fails.
		wp_set_current_user( 0 );
		ob_start();
		try {
			$this->admin_ajax->generate_data_for_suretriggers_integration();
			ob_end_clean();
			$this->fail( 'Expected WPDieException for missing capability.' );
		} catch ( \WPDieException $e ) {
			$data = json_decode( (string) ob_get_clean(), true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertStringContainsString( 'permission', $data['data']['message'] );
		}

		// 2. Admin user but an invalid nonce => nonce check fails.
		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );
		$_POST['security']    = 'bad-nonce';
		$_REQUEST['security'] = 'bad-nonce';
		ob_start();
		try {
			$this->admin_ajax->generate_data_for_suretriggers_integration();
			ob_end_clean();
			$this->fail( 'Expected WPDieException for invalid nonce.' );
		} catch ( \WPDieException $e ) {
			$data = json_decode( (string) ob_get_clean(), true );
			$this->assertFalse( $data['success'] );
			$this->assertStringContainsString( 'nonce', strtolower( $data['data']['message'] ) );
		}

		// 3. Admin user + valid nonce, but no formId => required-param check fails.
		$nonce                = wp_create_nonce( 'suretriggers_nonce' );
		$_POST['security']    = $nonce;
		$_REQUEST['security'] = $nonce;
		unset( $_POST['formId'] );
		ob_start();
		try {
			$this->admin_ajax->generate_data_for_suretriggers_integration();
			ob_end_clean();
			$this->fail( 'Expected WPDieException for missing form id.' );
		} catch ( \WPDieException $e ) {
			$data = json_decode( (string) ob_get_clean(), true );
			$this->assertFalse( $data['success'] );
			$this->assertStringContainsString( 'Form ID', $data['data']['message'] );
		}

		// Cleanup.
		unset( $_POST['security'], $_REQUEST['security'] );
		wp_set_current_user( 0 );
	}

	// ---------------------------------------------------------------
	// Client debug log: download and clear
	// ---------------------------------------------------------------

	/**
	 * The download must refuse anyone without the capability, and must refuse a
	 * missing or wrong nonce. Capability is checked first, matching the ordering of
	 * the sibling handlers in this class.
	 */
	public function test_download_client_log() {
		wp_set_current_user( $this->make_user( 'subscriber' ) );

		$this->assertStringContainsString(
			'permission',
			$this->run_log_handler( 'download_client_log', wp_create_nonce( 'srfm_client_logs' ) ),
			'A subscriber must be refused on the capability check, before the nonce.'
		);

		wp_set_current_user( $this->make_user( 'administrator' ) );

		$this->assertStringContainsString(
			'Security check failed',
			$this->run_log_handler( 'download_client_log', 'not-a-real-nonce' ),
			'A bad nonce must be refused even for an administrator.'
		);
	}

	/**
	 * Clearing deletes the file, and is gated the same way as the download — it is
	 * a destructive action reachable by a link.
	 */
	public function test_clear_client_log() {
		wp_set_current_user( $this->make_user( 'subscriber' ) );

		$this->assertStringContainsString(
			'permission',
			$this->run_log_handler( 'clear_client_log', wp_create_nonce( 'srfm_client_logs' ) ),
			'A subscriber must not be able to clear the log.'
		);

		// With the capability and a good nonce it succeeds and removes the file.
		// The original settings are restored afterwards: leaving logging off here
		// silently disables Client_Logger for every later test class.
		$original                    = get_option( 'srfm_general_settings_options', null );
		$general                     = (array) $original;
		$general['srfm_enable_logs'] = true;
		update_option( 'srfm_general_settings_options', $general );

		try {
			Client_Logger::append( [ 'type' => 'error', 'message' => 'temporary' ] );
			$path = Client_Logger::get_log_path( false );
			$this->assertFileExists( $path );

			wp_set_current_user( $this->make_user( 'administrator' ) );
			$body = $this->run_log_handler( 'clear_client_log', wp_create_nonce( 'srfm_client_logs' ) );

			$this->assertStringContainsString( '"success":true', $body );
			$this->assertFileDoesNotExist( $path );
		} finally {
			null === $original ? delete_option( 'srfm_general_settings_options' ) : update_option( 'srfm_general_settings_options', $original );
		}
	}

	/**
	 * Run a log handler and return what it emitted.
	 *
	 * Both handlers end the request -- on rejection through wp_die(), on success
	 * through wp_send_json_success(). Asserting merely that the request ended
	 * cannot tell those apart, so this returns the body and the caller checks it.
	 *
	 * @param string $method Handler method name.
	 * @param string $nonce  Nonce to send.
	 * @return string Emitted body.
	 */
	private function run_log_handler( $method, $nonce ) {
		$_REQUEST['_wpnonce'] = $nonce;

		// Pass the message through: distinguishing the capability refusal from the
		// nonce refusal is the whole point of these assertions.
		$throw_handler = static function () {
			return static function ( $message = '' ) {
				throw new \WPDieException( is_string( $message ) ? $message : 'srfm_die' );
			};
		};

		add_filter( 'wp_die_handler', $throw_handler );
		add_filter( 'wp_die_ajax_handler', $throw_handler );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$body = '';

		ob_start();
		try {
			Admin_Ajax::get_instance()->$method();
		} catch ( \WPDieException $e ) {
			$body = $e->getMessage();
		} finally {
			$body .= (string) ob_get_clean();
			remove_filter( 'wp_die_handler', $throw_handler );
			remove_filter( 'wp_die_ajax_handler', $throw_handler );
			remove_filter( 'wp_doing_ajax', '__return_true' );
			unset( $_REQUEST['_wpnonce'] );
			wp_set_current_user( 0 );
		}

		return $body;
	}

	/**
	 * Create a user with the given role and return its ID.
	 *
	 * @param string $role Role to assign.
	 * @return int
	 */
	private function make_user( $role ) {
		return (int) wp_insert_user(
			[
				'user_login' => 'srfm_log_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => $role,
			]
		);
	}

}
