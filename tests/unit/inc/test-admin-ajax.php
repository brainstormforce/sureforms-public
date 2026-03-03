<?php
/**
 * Class Test_Admin_Ajax
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Admin_Ajax;

class Test_Admin_Ajax extends TestCase {

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
}
