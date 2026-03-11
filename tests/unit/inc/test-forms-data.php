<?php
/**
 * Class Test_Forms_Data
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Forms_Data;

class Test_Forms_Data extends TestCase {

	protected $forms_data;

	protected function setUp(): void {
		$this->forms_data = new Forms_Data();
	}

	public function test_get_form_permissions_check_admin() {
		$admin_user = wp_insert_user( [
			'user_login' => 'testadmin_formsdata_' . wp_generate_password( 4, false ),
			'user_pass'  => 'password',
			'role'       => 'administrator',
		] );
		wp_set_current_user( $admin_user );

		$result = $this->forms_data->get_form_permissions_check();
		$this->assertTrue( $result );

		wp_delete_user( $admin_user );
	}

	public function test_get_form_permissions_check_subscriber() {
		$subscriber = wp_insert_user( [
			'user_login' => 'testsub_formsdata_' . wp_generate_password( 4, false ),
			'user_pass'  => 'password',
			'role'       => 'subscriber',
		] );
		wp_set_current_user( $subscriber );

		$result = $this->forms_data->get_form_permissions_check();
		$this->assertInstanceOf( WP_Error::class, $result );

		wp_delete_user( $subscriber );
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/forms-data' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The forms-data endpoint should be registered' );
	}
}
