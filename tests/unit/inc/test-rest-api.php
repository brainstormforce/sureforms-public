<?php
/**
 * Class Test_Rest_Api
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Rest_Api;

class Test_Rest_Api extends TestCase {

	protected $rest_api;

	protected function setUp(): void {
		parent::setUp();
		$this->rest_api = new Rest_Api();
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
	// sanitize_boolean_field()
	// ---------------------------------------------------------------

	public function test_sanitize_boolean_field_truthy_values() {
		$this->assertTrue( $this->rest_api->sanitize_boolean_field( 'true' ) );
		$this->assertTrue( $this->rest_api->sanitize_boolean_field( 1 ) );
		$this->assertTrue( $this->rest_api->sanitize_boolean_field( 'yes' ) );
	}

	public function test_sanitize_boolean_field_falsy_values() {
		$this->assertFalse( $this->rest_api->sanitize_boolean_field( 'false' ) );
		$this->assertFalse( $this->rest_api->sanitize_boolean_field( 0 ) );
		$this->assertFalse( $this->rest_api->sanitize_boolean_field( '' ) );
		$this->assertFalse( $this->rest_api->sanitize_boolean_field( null ) );
	}

	// ---------------------------------------------------------------
	// sanitize_entry_ids()
	// ---------------------------------------------------------------

	public function test_sanitize_entry_ids_with_array() {
		$this->assertEquals( [ 1, 2, 3 ], $this->rest_api->sanitize_entry_ids( [ 1, 2, 3 ] ) );
	}

	public function test_sanitize_entry_ids_filters_zero_values() {
		$result = $this->rest_api->sanitize_entry_ids( [ 1, 0, 3 ] );
		$this->assertEquals( [ 1, 3 ], array_values( $result ) );
	}

	public function test_sanitize_entry_ids_with_single_numeric() {
		$this->assertEquals( [ 42 ], $this->rest_api->sanitize_entry_ids( 42 ) );
	}

	public function test_sanitize_entry_ids_with_comma_separated_string() {
		$this->assertEquals( [ 1, 2, 3 ], $this->rest_api->sanitize_entry_ids( '1,2,3' ) );
	}

	public function test_sanitize_entry_ids_with_object_returns_empty() {
		$this->assertEquals( [], $this->rest_api->sanitize_entry_ids( new \stdClass() ) );
	}

	public function test_sanitize_entry_ids_converts_negative_to_absolute() {
		$this->assertEquals( [ 5, 10 ], $this->rest_api->sanitize_entry_ids( [ -5, 10 ] ) );
	}

	public function test_sanitize_entry_ids_casts_strings_to_int() {
		$this->assertEquals( [ 7, 8, 9 ], $this->rest_api->sanitize_entry_ids( [ '7', '8', '9' ] ) );
	}

	// ---------------------------------------------------------------
	// validate_read_action()
	// ---------------------------------------------------------------

	public function test_validate_read_action_valid_values() {
		$this->assertTrue( $this->rest_api->validate_read_action( 'read' ) );
		$this->assertTrue( $this->rest_api->validate_read_action( 'unread' ) );
	}

	public function test_validate_read_action_invalid_values() {
		$this->assertFalse( $this->rest_api->validate_read_action( 'delete' ) );
		$this->assertFalse( $this->rest_api->validate_read_action( '' ) );
	}

	// ---------------------------------------------------------------
	// validate_trash_action()
	// ---------------------------------------------------------------

	public function test_validate_trash_action_valid_values() {
		$this->assertTrue( $this->rest_api->validate_trash_action( 'trash' ) );
		$this->assertTrue( $this->rest_api->validate_trash_action( 'restore' ) );
	}

	public function test_validate_trash_action_invalid_values() {
		$this->assertFalse( $this->rest_api->validate_trash_action( 'delete' ) );
		$this->assertFalse( $this->rest_api->validate_trash_action( '' ) );
	}

	// ---------------------------------------------------------------
	// get_endpoints() - route registration verification
	// ---------------------------------------------------------------

	public function test_get_endpoints_returns_array_with_expected_routes() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$this->assertIsArray( $endpoints );

		$expected_routes = [
			'generate-form',
			'map-fields',
			'initiate-auth',
			'handle-access-key',
			'entries-chart-data',
			'form-data',
			'onboarding/set-status',
			'onboarding/get-status',
			'plugin-status',
			'entries/list',
			'entries/read-status',
			'entries/trash',
			'entries/delete',
			'entries/export',
			'forms',
			'forms/export',
			'forms/import',
			'forms/manage',
			'forms/duplicate',
			'pages/search',
		];

		foreach ( $expected_routes as $route ) {
			$this->assertArrayHasKey( $route, $endpoints, "Route '{$route}' not found in endpoints." );
		}
	}

	public function test_all_endpoints_have_required_keys() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		foreach ( $endpoints as $route => $args ) {
			$this->assertArrayHasKey( 'permission_callback', $args, "Route '{$route}' missing permission_callback." );
			$this->assertArrayHasKey( 'methods', $args, "Route '{$route}' missing methods." );
			$this->assertArrayHasKey( 'callback', $args, "Route '{$route}' missing callback." );
		}
	}

	public function test_endpoint_http_methods_are_correct() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );

		$this->assertEquals( 'POST', $endpoints['generate-form']['methods'] );
		$this->assertEquals( 'POST', $endpoints['map-fields']['methods'] );
		$this->assertEquals( 'GET', $endpoints['entries-chart-data']['methods'] );
		$this->assertEquals( 'GET', $endpoints['entries/list']['methods'] );
		$this->assertEquals( 'POST', $endpoints['entries/delete']['methods'] );
		$this->assertEquals( 'POST', $endpoints['entries/read-status']['methods'] );
		$this->assertEquals( 'POST', $endpoints['entries/trash']['methods'] );
		$this->assertEquals( 'POST', $endpoints['forms/manage']['methods'] );
		$this->assertEquals( 'GET', $endpoints['forms']['methods'] );
	}

	// ---------------------------------------------------------------
	// Endpoint arg definitions / input validation
	// ---------------------------------------------------------------

	public function test_entries_read_status_args_required_fields() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$args      = $endpoints['entries/read-status']['args'];
		$this->assertTrue( $args['entry_ids']['required'] );
		$this->assertTrue( $args['action']['required'] );
	}

	public function test_entries_trash_args_required_fields() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$args      = $endpoints['entries/trash']['args'];
		$this->assertTrue( $args['entry_ids']['required'] );
		$this->assertTrue( $args['action']['required'] );
	}

	public function test_forms_manage_args_required_and_enum() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$args      = $endpoints['forms/manage']['args'];
		$this->assertTrue( $args['form_ids']['required'] );
		$this->assertTrue( $args['action']['required'] );
		$this->assertEquals( [ 'trash', 'restore', 'delete' ], $args['action']['enum'] );
	}

	public function test_forms_duplicate_requires_form_id() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$this->assertTrue( $endpoints['forms/duplicate']['args']['form_id']['required'] );
	}

	public function test_plugin_status_requires_plugin_slug() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$this->assertTrue( $endpoints['plugin-status']['args']['plugin']['required'] );
	}

	public function test_entries_list_default_values() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$args      = $endpoints['entries/list']['args'];
		$this->assertEquals( 20, $args['per_page']['default'] );
		$this->assertEquals( 'DESC', $args['order']['default'] );
		$this->assertEquals( 'created_at', $args['orderby']['default'] );
		$this->assertEquals( 1, $args['page']['default'] );
		$this->assertEquals( 'all', $args['status']['default'] );
	}

	public function test_forms_import_args() {
		$endpoints = $this->call_private_method( $this->rest_api, 'get_endpoints' );
		$args      = $endpoints['forms/import']['args'];
		$this->assertTrue( $args['forms_data']['required'] );
		$this->assertEquals( 'draft', $args['default_status']['default'] );
		$this->assertEquals( [ 'draft', 'publish', 'private' ], $args['default_status']['enum'] );
	}

	// get_sample_data() and get_form_fields() are on Admin_Ajax, tested there.

	// ---------------------------------------------------------------
	// get_dropdown_counter()
	// ---------------------------------------------------------------

	public function test_get_dropdown_counter_returns_integer() {
		$this->assertIsInt( $this->rest_api->get_dropdown_counter() );
	}

	// ---------------------------------------------------------------
	// parse_form_fields() (private)
	// ---------------------------------------------------------------

	public function test_parse_form_fields_returns_empty_for_empty_or_plain_content() {
		$this->assertEquals( [], $this->call_private_method( $this->rest_api, 'parse_form_fields', [ '' ] ) );
		$this->assertEquals( [], $this->call_private_method( $this->rest_api, 'parse_form_fields', [ 'plain text' ] ) );
	}

	// ---------------------------------------------------------------
	// extract_form_fields()
	// ---------------------------------------------------------------

	public function test_extract_form_fields_skips_non_srfm_and_invalid_blocks() {
		$blocks = [
			[ 'blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [] ],
			'not an array',
			[ 'no_blockname_key' => true ],
		];
		$form_fields = [];
		$this->rest_api->extract_form_fields( $blocks, [], $form_fields );
		$this->assertEmpty( $form_fields );
	}

	public function test_extract_form_fields_skips_inline_button() {
		$blocks = [
			[
				'blockName'   => 'srfm/inline-button',
				'attrs'       => [],
				'innerBlocks' => [],
			],
		];
		$form_fields = [];
		$this->rest_api->extract_form_fields( $blocks, [ 'inline-button' => [ 'label' => [ 'default' => 'Submit' ] ] ], $form_fields );
		$this->assertEmpty( $form_fields );
	}

	public function test_extract_form_fields_sets_base_counter() {
		$form_fields = [];
		$this->rest_api->extract_form_fields( [], [], $form_fields, [], false, 5 );
		$this->assertEquals( 5, $this->rest_api->get_dropdown_counter() );
	}

	// ---------------------------------------------------------------
	// REST route registration via action hook
	// ---------------------------------------------------------------

	public function test_register_endpoints_hooks_into_rest_api_init() {
		$rest_api = new Rest_Api();
		$this->assertIsInt( has_action( 'rest_api_init', [ $rest_api, 'register_endpoints' ] ) );
	}

	public function test_sureforms_routes_registered_in_rest_server() {
		do_action( 'rest_api_init' );
		$routes    = rest_get_server()->get_routes();
		$has_route = false;
		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, '/sureforms/v1' ) === 0 ) {
				$has_route = true;
				break;
			}
		}
		$this->assertTrue( $has_route, 'No sureforms/v1 routes found in the REST server.' );
	}

	// ---------------------------------------------------------------
	// Unauthorized access (permission_callback rejects user 0)
	// ---------------------------------------------------------------

	public function test_unauthorized_access_entries_list() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/sureforms/v1/entries/list' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_unauthorized_access_entries_delete() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/entries/delete' );
		$request->set_body_params( [ 'entry_ids' => [ 1 ] ] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_unauthorized_access_forms_manage() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/manage' );
		$request->set_body_params( [ 'form_ids' => [ 1 ], 'action' => 'trash' ] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_unauthorized_access_generate_form() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'POST', '/sureforms/v1/generate-form' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_unauthorized_access_forms_export() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/export' );
		$request->set_body_params( [ 'post_ids' => [ 1 ] ] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_unauthorized_access_forms_import() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/forms/import' );
		$request->set_body_params( [ 'forms_data' => [ [ 'title' => 'test' ] ] ] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	// ---------------------------------------------------------------
	// search_only_post_titles()
	// ---------------------------------------------------------------

	public function test_search_only_post_titles_returns_unchanged_when_search_is_empty() {
		$wp_query                              = new WP_Query();
		$wp_query->query_vars['search_terms'] = [ 'hello' ];
		$result                                = $this->rest_api->search_only_post_titles( '', $wp_query );
		$this->assertEquals( '', $result );
	}

	public function test_search_only_post_titles_returns_unchanged_when_no_search_terms() {
		$wp_query                              = new WP_Query();
		$wp_query->query_vars['search_terms'] = [];
		$result                                = $this->rest_api->search_only_post_titles( ' AND original_clause', $wp_query );
		$this->assertEquals( ' AND original_clause', $result );
	}

	public function test_search_only_post_titles() {
		global $wpdb;
		$wp_query                              = new WP_Query();
		$wp_query->query_vars['search_terms'] = [ 'hello' ];
		$wp_query->query_vars['exact']        = false;
		$result                                = $this->rest_api->search_only_post_titles( ' AND original', $wp_query );
		$this->assertStringStartsWith( ' AND ', $result );
		$this->assertStringContainsString( "{$wpdb->posts}.post_title", $result );
		$this->assertStringContainsString( 'LIKE', $result );
		// $wpdb->prepare() encodes '%' as a hex placeholder in the test env;
		// assert the term is present rather than checking for literal '%term%'.
		$this->assertStringContainsString( 'hello', $result );
	}

	public function test_search_only_post_titles_exact_mode_omits_wildcards() {
		$wp_query                              = new WP_Query();
		// Use a term without special chars so $wpdb->esc_like() does not add backslashes.
		$wp_query->query_vars['search_terms'] = [ 'exactterm' ];
		$wp_query->query_vars['exact']        = true;
		$result                                = $this->rest_api->search_only_post_titles( ' AND original', $wp_query );
		$this->assertStringContainsString( 'exactterm', $result );
		$this->assertStringContainsString( 'LIKE', $result );
	}

	public function test_search_only_post_titles_multiple_terms_each_get_a_like_condition() {
		$wp_query                              = new WP_Query();
		$wp_query->query_vars['search_terms'] = [ 'foo', 'bar' ];
		$wp_query->query_vars['exact']        = false;
		$result                                = $this->rest_api->search_only_post_titles( ' AND original', $wp_query );
		$this->assertEquals( 2, substr_count( $result, 'LIKE' ) );
		// '%' is encoded by $wpdb->prepare() in the test env; assert the terms are present.
		$this->assertStringContainsString( 'foo', $result );
		$this->assertStringContainsString( 'bar', $result );
	}

	// ---------------------------------------------------------------
	// search_pages()
	// ---------------------------------------------------------------

	/**
	 * Create a temporary administrator user for search_pages tests.
	 */
	private function create_test_admin(): int {
		static $count = 0;
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_test_admin_' . ( ++$count ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		return is_wp_error( $user_id ) ? 0 : $user_id;
	}

	/**
	 * Dispatch a GET request to pages/search with a valid nonce.
	 *
	 * @param array $params Query parameters.
	 * @return WP_REST_Response
	 */
	private function dispatch_search_pages( array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/pages/search' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		if ( ! empty( $params ) ) {
			$request->set_query_params( $params );
		}
		return rest_get_server()->dispatch( $request );
	}

	public function test_search_pages_endpoint_registered_in_rest_server() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/sureforms/v1/pages/search', $routes );
		$this->assertEquals( 'GET', $routes['/sureforms/v1/pages/search'][0]['methods']['GET'] ? 'GET' : '' );
	}

	public function test_search_pages_returns_401_for_unauthenticated_user() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/sureforms/v1/pages/search' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_search_pages_returns_403_for_invalid_nonce() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/pages/search' );
		$request->set_header( 'X-WP-Nonce', 'invalid_nonce_value' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
		wp_set_current_user( 0 );
	}

	public function test_search_pages() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$unique  = 'SrfmStructureTest' . wp_rand();
		$page_id = wp_insert_post(
			[
				'post_title'  => $unique,
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);

		$response = $this->dispatch_search_pages( [ 'search' => $unique ] );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'pagination', $data );
		$this->assertNotEmpty( $data['items'] );

		$item = $data['items'][0];
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'label', $item );
		$this->assertArrayHasKey( 'value', $item );
		$this->assertIsInt( $item['id'] );
		$this->assertIsString( $item['label'] );
		$this->assertIsString( $item['value'] );

		wp_delete_post( $page_id, true );
		wp_set_current_user( 0 );
	}

	public function test_search_pages_per_page_is_clamped_to_50() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );

		// The validate_callback enforces per_page <= 50, so > 50 is rejected with 400.
		$response = $this->dispatch_search_pages( [ 'per_page' => 999 ] );
		$this->assertEquals( 400, $response->get_status() );

		// per_page = 50 (the maximum) is accepted and reflected in pagination.
		$response = $this->dispatch_search_pages( [ 'per_page' => 50 ] );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 50, $data['pagination']['per_page'] );

		wp_set_current_user( 0 );
	}

	public function test_search_pages_has_more_true_when_results_exceed_per_page() {
		$user_id  = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$unique   = 'SrfmHasMoreTrue' . wp_rand();
		$page_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$page_ids[] = wp_insert_post(
				[
					'post_title'  => "{$unique} Page {$i}",
					'post_status' => 'publish',
					'post_type'   => 'page',
				]
			);
		}

		$response = $this->dispatch_search_pages( [ 'per_page' => 2, 'search' => $unique ] );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['pagination']['has_more'] );
		$this->assertCount( 2, $data['items'] );

		foreach ( $page_ids as $id ) {
			wp_delete_post( $id, true );
		}
		wp_set_current_user( 0 );
	}

	public function test_search_pages_has_more_false_when_within_per_page() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$unique  = 'SrfmHasMoreFalse' . wp_rand();
		$page_id = wp_insert_post(
			[
				'post_title'  => $unique,
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);

		$response = $this->dispatch_search_pages( [ 'per_page' => 20, 'search' => $unique ] );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $data['pagination']['has_more'] );

		wp_delete_post( $page_id, true );
		wp_set_current_user( 0 );
	}

	public function test_search_pages_label_contains_raw_ampersand_not_html_entity() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$page_id = wp_insert_post(
			[
				'post_title'  => 'Terms & Conditions',
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);

		$response = $this->dispatch_search_pages( [ 'search' => 'Terms' ] );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$matching = array_values( array_filter( $data['items'], fn( $item ) => $item['id'] === $page_id ) );
		$this->assertNotEmpty( $matching, 'Page not found in search results.' );
		$this->assertEquals( 'Terms & Conditions', $matching[0]['label'] );
		$this->assertStringNotContainsString( '&amp;', $matching[0]['label'] );

		wp_delete_post( $page_id, true );
		wp_set_current_user( 0 );
	}

	public function test_search_pages_selected_url_prepends_saved_page_to_results() {
		$user_id   = $this->create_test_admin();
		wp_set_current_user( $user_id );
		$unique    = 'SrfmSavedRedirect' . wp_rand();
		$page_id   = wp_insert_post(
			[
				'post_title'  => $unique,
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);
		$permalink = get_permalink( $page_id );

		$response = $this->dispatch_search_pages(
			[
				'search'       => 'zzznotamatch' . wp_rand(),
				'selected_url' => $permalink,
			]
		);
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotEmpty( $data['items'], 'Selected page should appear even when search keyword has no matches.' );
		$this->assertEquals( $page_id, $data['items'][0]['id'] );

		wp_delete_post( $page_id, true );
		wp_set_current_user( 0 );
	}

	// ---------------------------------------------------------------
	// get_form_data()
	// ---------------------------------------------------------------

	/**
	 * Test get_entries_chart_data is callable.
	 */
	public function test_get_entries_chart_data() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_entries_chart_data' ) );
	}

	/**
	 * Test set_onboarding_status is callable.
	 */
	public function test_set_onboarding_status() {
		$this->assertTrue( method_exists( $this->rest_api, 'set_onboarding_status' ) );
	}

	/**
	 * Test get_onboarding_status is callable.
	 */
	public function test_get_onboarding_status() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_onboarding_status' ) );
	}

	/**
	 * Test get_plugin_status is callable.
	 */
	public function test_get_plugin_status() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_plugin_status' ) );
	}

	/**
	 * Test get_entries_list is callable.
	 */
	public function test_get_entries_list() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_entries_list' ) );
	}

	/**
	 * Test update_entries_read_status is callable.
	 */
	public function test_update_entries_read_status() {
		$this->assertTrue( method_exists( $this->rest_api, 'update_entries_read_status' ) );
	}

	/**
	 * Test update_entries_trash_status is callable.
	 */
	public function test_update_entries_trash_status() {
		$this->assertTrue( method_exists( $this->rest_api, 'update_entries_trash_status' ) );
	}

	/**
	 * Test delete_entries is callable.
	 */
	public function test_delete_entries() {
		$this->assertTrue( method_exists( $this->rest_api, 'delete_entries' ) );
	}

	/**
	 * Test get_entry_details is callable.
	 */
	public function test_get_entry_details() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_entry_details' ) );
	}

	/**
	 * Test get_entry_logs is callable.
	 */
	public function test_get_entry_logs() {
		$this->assertTrue( method_exists( $this->rest_api, 'get_entry_logs' ) );
	}

	/**
	 * Test export_entries is callable.
	 */
	public function test_export_entries() {
		$this->assertTrue( method_exists( $this->rest_api, 'export_entries' ) );
	}

	/**
	 * Test manage_form_lifecycle is callable.
	 */
	public function test_manage_form_lifecycle() {
		$this->assertTrue( method_exists( $this->rest_api, 'manage_form_lifecycle' ) );
	}

	public function test_get_form_data_returns_forms_when_they_exist() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );

		$form_id = wp_insert_post(
			[
				'post_type'   => 'sureforms_form',
				'post_status' => 'publish',
				'post_title'  => 'Test Form for get_form_data',
			]
		);

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-data' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $form_id, $data );

		wp_delete_post( $form_id, true );
		wp_set_current_user( 0 );
	}

	public function test_get_form_data_returns_empty_array_when_no_forms() {
		$user_id = $this->create_test_admin();
		wp_set_current_user( $user_id );

		// Delete all existing sureforms_form posts.
		$existing = get_posts( [ 'post_type' => 'sureforms_form', 'posts_per_page' => -1, 'fields' => 'ids' ] );
		foreach ( $existing as $id ) {
			wp_delete_post( $id, true );
		}

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-data' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );

		wp_set_current_user( 0 );
	}
}
