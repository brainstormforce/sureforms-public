<?php
/**
 * Class Test_Global_Settings_Defaults
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Global_Settings\Global_Settings_Defaults;

class Test_Global_Settings_Defaults extends TestCase {

	/**
	 * @var Global_Settings_Defaults
	 */
	protected $defaults;

	protected function setUp(): void {
		// The class registers its `default_post_metadata` filter in the
		// constructor. We use the singleton to make sure the filter is
		// already wired up when these tests fire `get_post_meta`.
		$this->defaults = Global_Settings_Defaults::get_instance();
	}

	protected function tearDown(): void {
		// Clean up any forms created during tests.
		$forms = get_posts(
			[
				'post_type'   => 'sureforms_form',
				'numberposts' => -1,
				'post_status' => 'any',
				'fields'      => 'ids',
			]
		);
		foreach ( $forms as $form_id ) {
			wp_delete_post( $form_id, true );
		}

		delete_option( 'srfm_email_notification_settings_options' );
		delete_option( 'srfm_form_confirmation_settings_options' );
		delete_option( 'srfm_compliance_settings_options' );
		delete_option( 'srfm_form_restriction_settings_options' );
	}

	/**
	 * Test get_global_defaults_as_form_meta returns an empty array when no global settings are configured.
	 */
	public function test_get_global_defaults_as_form_meta_empty_when_no_settings() {
		$meta = Global_Settings_Defaults::get_global_defaults_as_form_meta();

		$this->assertIsArray( $meta );
		$this->assertEmpty( $meta );
	}

	/**
	 * Test get_global_defaults_as_form_meta returns mapped form meta for configured global settings.
	 */
	public function test_get_global_defaults_as_form_meta() {
		update_option(
			'srfm_email_notification_settings_options',
			[
				'email_to'       => 'test@example.com',
				'subject'        => 'Test Subject',
				'email_body'     => '{all_data}',
				'from_name'      => 'Test Site',
				'from_email'     => 'noreply@example.com',
				'email_cc'       => '',
				'email_bcc'      => '',
				'email_reply_to' => '',
			]
		);

		update_option(
			'srfm_compliance_settings_options',
			[
				'gdpr'                 => true,
				'do_not_store_entries' => false,
				'auto_delete_entries'  => false,
				'auto_delete_days'     => 30,
			]
		);

		update_option(
			'srfm_form_confirmation_settings_options',
			[
				'confirmation_type' => 'same page',
				'message'           => 'Thanks!',
				'submission_action' => 'hide form',
			]
		);

		update_option(
			'srfm_form_restriction_settings_options',
			[
				'max_entries' => [
					'status'     => true,
					'maxEntries' => 10,
					'message'    => 'Closed.',
				],
			]
		);

		$meta = Global_Settings_Defaults::get_global_defaults_as_form_meta();

		// Email notification meta shape.
		$this->assertArrayHasKey( '_srfm_email_notification', $meta );
		$this->assertIsArray( $meta['_srfm_email_notification'] );
		$this->assertSame( 'test@example.com', $meta['_srfm_email_notification'][0]['email_to'] );

		// Compliance meta shape.
		$this->assertArrayHasKey( '_srfm_compliance', $meta );
		$this->assertIsArray( $meta['_srfm_compliance'] );
		$this->assertTrue( $meta['_srfm_compliance'][0]['gdpr'] );

		// Form confirmation meta shape.
		$this->assertArrayHasKey( '_srfm_form_confirmation', $meta );
		$this->assertIsArray( $meta['_srfm_form_confirmation'] );
		$this->assertSame( 'same page', $meta['_srfm_form_confirmation'][0]['confirmation_type'] );

		// Form restriction is stored as a JSON string.
		$this->assertArrayHasKey( '_srfm_form_restriction', $meta );
		$this->assertIsString( $meta['_srfm_form_restriction'] );
		$decoded = json_decode( $meta['_srfm_form_restriction'], true );
		$this->assertIsArray( $decoded );
		$this->assertSame( 10, $decoded['maxEntries'] );
	}

	/**
	 * Test inject_global_defaults returns the configured global compliance value
	 * when no row exists for the form yet (live-link inheritance).
	 *
	 * Calls get_post_meta directly so the WP metadata filter chain runs end-to-end —
	 * this is the same path the editor REST and the frontend submit use.
	 */
	public function test_inject_global_defaults() {
		update_option(
			'srfm_compliance_settings_options',
			[
				'gdpr'                 => true,
				'do_not_store_entries' => false,
				'auto_delete_entries'  => true,
				'auto_delete_days'     => 60,
			]
		);

		$form_id = wp_insert_post(
			[
				'post_type'   => 'sureforms_form',
				'post_status' => 'draft',
				'post_title'  => 'Inheriting Form',
			]
		);

		// $single = true → frontend submit path. Filter must return the
		// JSON-friendly value directly (an array of meta-shape entries).
		$compliance_single = get_post_meta( $form_id, '_srfm_compliance', true );
		$this->assertIsArray( $compliance_single, 'Expected compliance defaults injected for inheriting form.' );
		$this->assertSame( true, $compliance_single[0]['gdpr'] );
		$this->assertSame( true, $compliance_single[0]['auto_delete_entries'] );
		$this->assertSame( '60', $compliance_single[0]['auto_delete_days'] );

		// $single = false → REST path. Filter must wrap the return in an
		// outer array so $all_values[0] in WP's REST meta handler matches
		// the schema (otherwise the editor renders an empty list).
		$compliance_all = get_post_meta( $form_id, '_srfm_compliance', false );
		$this->assertIsArray( $compliance_all );
		$this->assertCount( 1, $compliance_all );
		$this->assertIsArray( $compliance_all[0] );
		$this->assertSame( true, $compliance_all[0][0]['gdpr'] );
	}

	/**
	 * Inheritance must stop firing the moment a real meta row exists. Detach-on-edit
	 * falls out of "row exists or not" — once the user saves, the form is detached
	 * and subsequent global changes do not bleed in.
	 */
	public function test_inject_global_defaults_detaches_when_row_exists() {
		update_option(
			'srfm_compliance_settings_options',
			[
				'gdpr'                 => true,
				'do_not_store_entries' => false,
				'auto_delete_entries'  => false,
				'auto_delete_days'     => 30,
			]
		);

		$form_id = wp_insert_post(
			[
				'post_type'   => 'sureforms_form',
				'post_status' => 'draft',
				'post_title'  => 'Detached Form',
			]
		);

		// Simulate the user having saved a customised compliance row.
		$customised = [
			[
				'id'                   => 'gdpr',
				'gdpr'                 => false,
				'do_not_store_entries' => true,
				'auto_delete_entries'  => false,
				'auto_delete_days'     => '7',
			],
		];
		update_post_meta( $form_id, '_srfm_compliance', $customised );

		// Now flip the global to something different — the form must NOT
		// pick up the change because a row exists in postmeta.
		update_option(
			'srfm_compliance_settings_options',
			[
				'gdpr'                 => true,
				'do_not_store_entries' => false,
				'auto_delete_entries'  => true,
				'auto_delete_days'     => 90,
			]
		);

		$compliance = get_post_meta( $form_id, '_srfm_compliance', true );
		$this->assertIsArray( $compliance );
		$this->assertSame( false, $compliance[0]['gdpr'] );
		$this->assertSame( true, $compliance[0]['do_not_store_entries'] );
		$this->assertSame( '7', $compliance[0]['auto_delete_days'] );
	}

	/**
	 * Form Restriction is stored as a JSON-encoded string (register_meta type
	 * 'string'), unlike the other three groups which are array-typed. Confirm
	 * the filter matches that shape so both the REST schema and the frontend
	 * decode path stay consistent.
	 */
	public function test_inject_global_defaults_form_restriction_returns_json_string() {
		update_option(
			'srfm_form_restriction_settings_options',
			[
				'max_entries' => [
					'status'     => true,
					'maxEntries' => 25,
					'message'    => 'No more entries please.',
				],
			]
		);

		$form_id = wp_insert_post(
			[
				'post_type'   => 'sureforms_form',
				'post_status' => 'draft',
				'post_title'  => 'Restriction Form',
			]
		);

		$restriction_single = get_post_meta( $form_id, '_srfm_form_restriction', true );
		$this->assertIsString( $restriction_single );

		$decoded = json_decode( $restriction_single, true );
		$this->assertIsArray( $decoded );
		$this->assertTrue( $decoded['status'] );
		$this->assertSame( 25, $decoded['maxEntries'] );
		$this->assertSame( 'No more entries please.', $decoded['message'] );
	}

	/**
	 * When the admin has not configured a global for a group, the filter must
	 * return $value unchanged so the hardcoded register_post_meta default still
	 * applies — we never want to spoof a "configured" state on top of an empty
	 * option.
	 */
	public function test_inject_global_defaults_passthrough_when_global_unset() {
		// No options configured.
		$form_id = wp_insert_post(
			[
				'post_type'   => 'sureforms_form',
				'post_status' => 'draft',
				'post_title'  => 'No Global Form',
			]
		);

		$result = $this->defaults->inject_global_defaults( 'sentinel-value', $form_id, '_srfm_compliance', true );
		$this->assertSame( 'sentinel-value', $result );
	}

	/**
	 * The filter must only fire on sureforms_form posts. A regular post asking
	 * for the same meta key (an unlikely but possible collision) should fall
	 * through unchanged.
	 */
	public function test_inject_global_defaults_skips_non_sureforms_posts() {
		update_option(
			'srfm_compliance_settings_options',
			[
				'gdpr'                 => true,
				'do_not_store_entries' => false,
				'auto_delete_entries'  => false,
				'auto_delete_days'     => 30,
			]
		);

		$post_id = wp_insert_post(
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_title'  => 'Regular Post',
			]
		);

		$result = $this->defaults->inject_global_defaults( 'untouched', $post_id, '_srfm_compliance', true );
		$this->assertSame( 'untouched', $result );
	}
}
