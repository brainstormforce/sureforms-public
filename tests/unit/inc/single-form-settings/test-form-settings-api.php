<?php
/**
 * Class Test_Form_Settings_Api
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Single_Form_Settings\Form_Settings_Api;

/**
 * Tests for `Form_Settings_Api`. Covers endpoint registration, the
 * permission gate (nonce + capability), and the save round-trip
 * (allowlist enforcement, non-form CPT rejection, and the sanitize
 * round-trip via `get_post_meta`).
 */
class Test_Form_Settings_Api extends TestCase {

	/**
	 * @var Form_Settings_Api
	 */
	protected $api;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var int
	 */
	protected $admin_id;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( Form_Settings_Api::class ) ) {
			$this->markTestSkipped( 'Form_Settings_Api class not available.' );
		}

		$this->api = Form_Settings_Api::get_instance();

		$this->admin_id = $this->factory()->user->create(
			[ 'role' => 'administrator' ]
		);
		wp_set_current_user( $this->admin_id );

		$this->form_id = $this->factory()->post->create(
			[ 'post_type' => defined( 'SRFM_FORMS_POST_TYPE' ) ? SRFM_FORMS_POST_TYPE : 'sureforms_form' ]
		);
	}

	protected function tearDown(): void {
		wp_delete_post( $this->form_id, true );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * Build a request with an admin-valid nonce header.
	 *
	 * @param array $params Request params.
	 * @return WP_REST_Request
	 */
	private function build_request( array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-settings' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * `register_endpoint` should inject a `form-settings` entry with
	 * the expected route shape — POST method, our callbacks, and the
	 * two required arg validators.
	 */
	public function test_register_endpoint() {
		$result = $this->api->register_endpoint( [] );

		$this->assertArrayHasKey( 'form-settings', $result );

		$endpoint = $result['form-settings'];
		$this->assertSame( 'POST', $endpoint['methods'] );
		$this->assertSame(
			[ $this->api, 'save_form_settings' ],
			$endpoint['callback']
		);
		$this->assertSame(
			[ $this->api, 'permission_check' ],
			$endpoint['permission_callback']
		);

		// Args.
		$this->assertArrayHasKey( 'post_id', $endpoint['args'] );
		$this->assertTrue( $endpoint['args']['post_id']['required'] );
		$this->assertSame( 'integer', $endpoint['args']['post_id']['type'] );

		$this->assertArrayHasKey( 'meta_data', $endpoint['args'] );
		$this->assertTrue( $endpoint['args']['meta_data']['required'] );
		$this->assertSame( 'object', $endpoint['args']['meta_data']['type'] );

		// `post_id` validate_callback rejects 0 / non-numeric, accepts > 0.
		$post_id_validator = $endpoint['args']['post_id']['validate_callback'];
		$this->assertTrue( $post_id_validator( 1 ) );
		$this->assertFalse( (bool) $post_id_validator( 0 ) );
		$this->assertFalse( (bool) $post_id_validator( 'abc' ) );

		// `meta_data` validate_callback rejects non-array / empty.
		$meta_validator = $endpoint['args']['meta_data']['validate_callback'];
		$this->assertTrue( $meta_validator( [ 'k' => 'v' ] ) );
		$this->assertFalse( (bool) $meta_validator( [] ) );
		$this->assertFalse( (bool) $meta_validator( 'not-an-array' ) );

		// Preserves existing entries.
		$result = $this->api->register_endpoint( [ 'other' => [ 'methods' => 'GET' ] ] );
		$this->assertArrayHasKey( 'other', $result );
		$this->assertArrayHasKey( 'form-settings', $result );
	}

	/**
	 * `permission_check` covers three branches:
	 *   - invalid / missing nonce → WP_Error 403
	 *   - missing capability → WP_Error 403
	 *   - valid nonce + admin → true
	 */
	public function test_permission_check() {
		// 1. Invalid nonce — explicit bad value.
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-settings' );
		$request->set_header( 'X-WP-Nonce', 'bogus-nonce' );
		$request->set_param( 'post_id', $this->form_id );
		$result = $this->api->permission_check( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_invalid_nonce', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );

		// 2. No capability — subscriber can't `edit_post` on a form CPT.
		$subscriber_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$request = $this->build_request( [ 'post_id' => $this->form_id ] );
		$result  = $this->api->permission_check( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_cannot_edit_post', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );
		wp_delete_user( $subscriber_id );

		// 3. Admin + valid nonce → true.
		wp_set_current_user( $this->admin_id );
		$request = $this->build_request( [ 'post_id' => $this->form_id ] );
		$this->assertTrue( $this->api->permission_check( $request ) );

		// 4. `post_id` = 0 (e.g. brand-new unsaved post) → cannot edit → 403.
		$request = $this->build_request( [ 'post_id' => 0 ] );
		$result  = $this->api->permission_check( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_cannot_edit_post', $result->get_error_code() );
	}

	/**
	 * `save_form_settings` covers:
	 *   - non-existent / wrong CPT → WP_Error 404
	 *   - allowlisted keys persist via `update_post_meta`
	 *   - non-allowlisted keys are skipped silently
	 *   - response.meta echoes the persisted value (post-sanitize)
	 *   - pro extension via `srfm_form_settings_allowed_meta_keys` filter
	 */
	public function test_save_form_settings() {
		// 1. Wrong CPT → 404.
		$plain_post_id = $this->factory()->post->create( [ 'post_type' => 'post' ] );
		$request       = $this->build_request(
			[
				'post_id'   => $plain_post_id,
				'meta_data' => [ '_srfm_form_confirmation' => 'value' ],
			]
		);
		$result        = $this->api->save_form_settings( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_invalid_form_id', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
		wp_delete_post( $plain_post_id, true );

		// 2. Non-existent post id → 404.
		$request = $this->build_request(
			[
				'post_id'   => 999999,
				'meta_data' => [ '_srfm_form_confirmation' => 'value' ],
			]
		);
		$result  = $this->api->save_form_settings( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_invalid_form_id', $result->get_error_code() );

		// 3. Allowlisted keys persist; rejected keys are silently skipped.
		$payload = [
			'_srfm_form_confirmation' => 'allowed-value',
			'_srfm_evil_key'          => 'should-be-rejected',
		];
		$request = $this->build_request(
			[
				'post_id'   => $this->form_id,
				'meta_data' => $payload,
			]
		);
		$result  = $this->api->save_form_settings( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( '_srfm_form_confirmation', $data['meta'] );
		$this->assertArrayNotHasKey( '_srfm_evil_key', $data['meta'] );
		$this->assertSame(
			'allowed-value',
			get_post_meta( $this->form_id, '_srfm_form_confirmation', true )
		);
		$this->assertSame(
			'',
			get_post_meta( $this->form_id, '_srfm_evil_key', true )
		);

		// 4. Pro-style allowlist extension via filter — a key added by
		// the filter callback should be accepted on subsequent calls.
		$filter = static function ( $keys ) {
			$keys[] = '_srfm_test_pro_extension_key';
			return $keys;
		};
		add_filter( 'srfm_form_settings_allowed_meta_keys', $filter );

		$request = $this->build_request(
			[
				'post_id'   => $this->form_id,
				'meta_data' => [ '_srfm_test_pro_extension_key' => 'pro-value' ],
			]
		);
		$result  = $this->api->save_form_settings( $request );
		$data    = $result->get_data();
		$this->assertArrayHasKey( '_srfm_test_pro_extension_key', $data['meta'] );
		$this->assertSame(
			'pro-value',
			get_post_meta( $this->form_id, '_srfm_test_pro_extension_key', true )
		);

		remove_filter( 'srfm_form_settings_allowed_meta_keys', $filter );

		// 5. Empty meta_data after sanitize_key drops empty keys → still
		// returns a 200 response with an empty meta array.
		$request = $this->build_request(
			[
				'post_id'   => $this->form_id,
				'meta_data' => [ '' => 'orphan-empty-key' ],
			]
		);
		$result  = $this->api->save_form_settings( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( [], $result->get_data()['meta'] );
	}
}
