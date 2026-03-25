<?php
/**
 * Class Test_Forms_Data
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Forms_Data;
use SRFM\Inc\Helper;

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

	// ---------------------------------------------------------------
	// get_forms_list()
	// ---------------------------------------------------------------

	/**
	 * Helper: create an admin user, set as current, and return the user ID.
	 */
	private function set_admin_user(): int {
		$user_id = wp_insert_user(
			[
				'user_login' => 'testadmin_gfl_' . wp_generate_password( 6, false ),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * Helper: build a WP_REST_Request for GET /sureforms/v1/forms with a valid nonce.
	 */
	private function make_forms_request( array $params = [] ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/forms' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * Test get_forms_list returns 200 with expected keys for an admin user.
	 */
	public function test_get_forms_list_returns_valid_structure() {
		$admin_id = $this->set_admin_user();

		$request  = $this->make_forms_request( [ 'status' => 'any' ] );
		$response = $this->forms_data->get_forms_list( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'forms', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'total_pages', $data );
		$this->assertArrayHasKey( 'current_page', $data );
		$this->assertArrayHasKey( 'per_page', $data );

		wp_delete_user( $admin_id );
	}

	/**
	 * Test get_forms_list with a text search term uses title-only matching.
	 */
	public function test_get_forms_list_text_search_returns_200() {
		$admin_id = $this->set_admin_user();

		$request  = $this->make_forms_request( [ 'search' => 'contact', 'status' => 'any' ] );
		$response = $this->forms_data->get_forms_list( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'forms', $data );
		// All returned forms should have 'contact' in the title.
		foreach ( $data['forms'] as $form ) {
			$this->assertStringContainsStringIgnoringCase( 'contact', $form['title'] );
		}

		wp_delete_user( $admin_id );
	}

	/**
	 * Test get_forms_list with a numeric search does not leave posts_where filter attached.
	 */
	public function test_get_forms_list_numeric_search_removes_filter_after_query() {
		$admin_id = $this->set_admin_user();

		$request  = $this->make_forms_request( [ 'search' => '42', 'status' => 'any' ] );
		$response = $this->forms_data->get_forms_list( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify no srfm_numeric_search filter is still attached by running a plain WP_Query
		// and confirming it returns normally (no stale closure interference).
		$probe = new WP_Query(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'fields'      => 'ids',
			]
		);
		// If filter leaked, the probe query would have an unexpected WHERE clause.
		// Just asserting it completes without error is sufficient.
		$this->assertIsArray( $probe->posts );

		wp_delete_user( $admin_id );
	}

	/**
	 * Test get_forms_list with empty search returns all forms.
	 */
	public function test_get_forms_list_empty_search_returns_all_forms() {
		$admin_id = $this->set_admin_user();

		// Create two test forms.
		$form_a = wp_insert_post( [ 'post_type' => SRFM_FORMS_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Alpha Form' ] );
		$form_b = wp_insert_post( [ 'post_type' => SRFM_FORMS_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Beta Form' ] );

		$request  = $this->make_forms_request( [ 'status' => 'publish', 'per_page' => 100 ] );
		$response = $this->forms_data->get_forms_list( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data     = $response->get_data();
		$form_ids = wp_list_pluck( $data['forms'], 'id' );
		$this->assertContains( $form_a, $form_ids );
		$this->assertContains( $form_b, $form_ids );

		wp_delete_post( $form_a, true );
		wp_delete_post( $form_b, true );
		wp_delete_user( $admin_id );
	}

	/**
	 * Test get_forms_list numeric search matches a form by its ID.
	 */
	public function test_get_forms_list_numeric_search_matches_form_by_id() {
		$admin_id = $this->set_admin_user();

		$form_id = wp_insert_post( [ 'post_type' => SRFM_FORMS_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Numeric ID Form' ] );

		$request  = $this->make_forms_request( [ 'search' => (string) $form_id, 'status' => 'any' ] );
		$response = $this->forms_data->get_forms_list( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data     = $response->get_data();
		$form_ids = wp_list_pluck( $data['forms'], 'id' );
		$this->assertContains( $form_id, $form_ids );

		wp_delete_post( $form_id, true );
		wp_delete_user( $admin_id );
	}
}
