<?php
/**
 * Class Test_Global_Settings
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Global_Settings\Global_Settings;

class Test_Global_Settings extends TestCase {

	protected $global_settings;

	protected function setUp(): void {
		$this->global_settings = new Global_Settings();
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/srfm-global-settings' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The srfm-global-settings endpoint should be registered' );
	}

	public function test_srfm_save_general_settings() {
		$setting_options = [
			'srfm_ip_log'             => true,
			'srfm_form_analytics'     => true,
			'srfm_bsf_analytics'      => false,
			'srfm_admin_notification' => true,
		];

		$reflection = new ReflectionClass( Global_Settings::class );
		$method = $reflection->getMethod( 'srfm_save_general_settings' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $setting_options );
		$this->assertTrue( $result );
	}
}
