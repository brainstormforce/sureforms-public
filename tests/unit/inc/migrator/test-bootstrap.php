<?php
/**
 * Class Test_Bootstrap
 *
 * Smoke tests for each public method on the migrator's REST bootstrap. The
 * REST callbacks are exercised through real WP_REST_Request instances with a
 * valid cookie nonce; sanitisers are exercised directly. Function names
 * follow the project's coverage-naming convention so `check-test-coverage`
 * recognises them.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Bootstrap;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Bootstrap extends TestCase {

	/**
	 * Build a Bootstrap instance — it's a plain class with a `Get_Instance`
	 * trait that side-effects on construction. Using `new` directly avoids
	 * polluting the singleton.
	 *
	 * @return Bootstrap
	 */
	private function bootstrap() {
		return new Bootstrap();
	}

	/**
	 * Build a REST request seeded with a valid X-WP-Nonce header. Lets us
	 * drive each REST callback past its nonce check.
	 *
	 * @return WP_REST_Request
	 */
	private function authed_request() {
		wp_set_current_user( 1 );
		$nonce   = wp_create_nonce( 'wp_rest' );
		$request = new WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', $nonce );
		return $request;
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Bootstrap::register_routes() — adds the three migrator endpoints to the
	 * existing SureForms REST registry.
	 *
	 * @return void
	 */
	public function test_register_routes() {
		$endpoints = $this->bootstrap()->register_routes( [] );
		$this->assertArrayHasKey( 'migrator/sources', $endpoints );
		$this->assertArrayHasKey( 'migrator/sources/(?P<key>[a-z0-9]+)/forms', $endpoints );
		$this->assertArrayHasKey( 'migrator/sources/(?P<key>[a-z0-9]+)/import', $endpoints );
	}

	/**
	 * Bootstrap::rest_list_sources() — always returns a `sources` array.
	 *
	 * @return void
	 */
	public function test_rest_list_sources() {
		$response = $this->bootstrap()->rest_list_sources( $this->authed_request() );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'sources', $data );
		$this->assertIsArray( $data['sources'] );
		// Both CF7 and WPForms importers are registered; when the underlying
		// plugins are active, both appear in the source list.
		$keys = array_column( $data['sources'], 'key' );
		$this->assertContains( 'cf7', $keys );
		$this->assertContains( 'wpforms', $keys );
	}

	/**
	 * Bootstrap::rest_list_forms() — returns 404 for an unknown source key.
	 *
	 * @return void
	 */
	public function test_rest_list_forms() {
		$request = $this->authed_request();
		$request->set_param( 'key', 'nonexistent' );
		$response = $this->bootstrap()->rest_list_forms( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Bootstrap::rest_import_forms() — returns 404 for an unknown source key.
	 *
	 * @return void
	 */
	public function test_rest_import_forms() {
		$request = $this->authed_request();
		$request->set_param( 'key', 'nonexistent' );
		$request->set_param( 'form_ids', [] );
		$request->set_param( 'dry_run', true );
		$request->set_param( 'behavior', [] );
		$response = $this->bootstrap()->rest_import_forms( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Bootstrap::sanitize_behavior() — keeps only known actions; drops bogus values.
	 *
	 * @return void
	 */
	public function test_sanitize_behavior() {
		$b = $this->bootstrap();
		$this->assertSame( [], $b->sanitize_behavior( 'not-an-array' ) );
		$out = $b->sanitize_behavior(
			[
				'12'  => 'update',
				'34'  => 'SKIP',
				'56'  => 'unknown',
				'78'  => 'create',
				''    => 'update',
			]
		);
		// Allowed lower-cased values pass; '78'=>'create' kept; unknown dropped.
		$this->assertArrayHasKey( '12', $out );
		$this->assertSame( 'update', $out['12'] );
		$this->assertSame( 'skip', $out['34'] );
		$this->assertSame( 'create', $out['78'] );
		$this->assertArrayNotHasKey( '56', $out );
	}

	/**
	 * Bootstrap::sanitize_form_ids() — coerces ints + strings, drops the rest.
	 *
	 * @return void
	 */
	public function test_sanitize_form_ids() {
		$b = $this->bootstrap();
		$this->assertSame( [], $b->sanitize_form_ids( 'not-an-array' ) );
		$out = $b->sanitize_form_ids( [ 12, '34', 'abc-99', null, true ] );
		$this->assertContains( '12', $out );
		$this->assertContains( '34', $out );
		$this->assertContains( 'abc-99', $out );
	}
}
