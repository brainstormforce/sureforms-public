<?php
/**
 * Class Test_Background_Process
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Background_Process;

class Test_Background_Process extends TestCase {

	protected $background_process;

	protected function setUp(): void {
		$this->background_process = new Background_Process();
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/after-submission' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The after-submission endpoint should be registered' );
	}
}
