<?php
/**
 * Class Test_Onboarding_Migration_Banner_Rest
 *
 * Covers the dismiss-migration-banner REST endpoint registered by the
 * Onboarding class. The endpoint persists a per-user flag the Forms
 * listing page reads to keep the migration banner hidden after dismissal.
 *
 * @package sureforms
 */

use SRFM\Inc\Onboarding;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Onboarding_Migration_Banner_Rest extends TestCase {
	/**
	 * @return void
	 */
	public function tear_down() {
		delete_user_meta( 1, Onboarding::MIGRATION_BANNER_DISMISSED_META_KEY );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * register_routes() appends the dismiss-migration-banner route to the
	 * SureForms REST endpoint registry passed in via the filter.
	 *
	 * @return void
	 */
	public function test_register_routes() {
		$endpoints = $this->instance()->register_routes( [] );
		$this->assertArrayHasKey(
			'user-meta/dismiss-migration-banner',
			$endpoints
		);
		$this->assertSame(
			'POST',
			$endpoints['user-meta/dismiss-migration-banner']['methods']
		);
	}

	/**
	 * register_routes() preserves any pre-existing endpoints — it must be
	 * additive so other modules wired to the same filter keep working.
	 *
	 * @return void
	 */
	public function test_register_routes_preserves_existing_endpoints() {
		$endpoints = $this->instance()->register_routes(
			[
				'existing/endpoint' => [
					'methods'             => 'GET',
					'callback'            => '__return_true',
					'permission_callback' => '__return_true',
				],
			]
		);
		$this->assertArrayHasKey( 'existing/endpoint', $endpoints );
		$this->assertArrayHasKey(
			'user-meta/dismiss-migration-banner',
			$endpoints
		);
	}

	/**
	 * rest_dismiss_migration_banner() persists the flag for the logged-in
	 * user and returns a 200 response with `dismissed: true`.
	 *
	 * @return void
	 */
	public function test_rest_dismiss_migration_banner_persists_flag() {
		$response = $this->instance()->rest_dismiss_migration_banner(
			$this->authed_request()
		);
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['dismissed'] );
		$this->assertTrue(
			$this->instance()->is_migration_banner_dismissed()
		);
	}

	/**
	 * A request without a valid REST nonce is rejected with a 403 — the
	 * endpoint verifies X-WP-Nonce like its sibling migrator callbacks.
	 * (Anonymous callers are blocked earlier by the route's
	 * is_user_logged_in permission_callback.)
	 *
	 * @return void
	 */
	public function test_rest_dismiss_migration_banner_requires_nonce() {
		wp_set_current_user( 1 );
		$request  = new WP_REST_Request(); // No X-WP-Nonce header.
		$response = $this->instance()->rest_dismiss_migration_banner( $request );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame(
			403,
			$response->get_error_data()['status']
		);
	}

	/**
	 * is_migration_banner_dismissed() returns false when the meta is
	 * absent and true after it has been set.
	 *
	 * @return void
	 */
	public function test_is_migration_banner_dismissed_reflects_meta() {
		wp_set_current_user( 1 );
		$this->assertFalse( $this->instance()->is_migration_banner_dismissed() );
		update_user_meta(
			1,
			Onboarding::MIGRATION_BANNER_DISMISSED_META_KEY,
			'1'
		);
		$this->assertTrue( $this->instance()->is_migration_banner_dismissed() );
	}

	/**
	 * @return Onboarding
	 */
	private function instance() {
		return Onboarding::get_instance();
	}

	/**
	 * @return WP_REST_Request
	 */
	private function authed_request() {
		wp_set_current_user( 1 );
		$nonce   = wp_create_nonce( 'wp_rest' );
		$request = new WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', $nonce );
		return $request;
	}
}
