<?php
/**
 * Class Test_Features
 *
 * @package sureforms
 */

use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Features;
use SRFM\Inc\Form_Restriction;
use SRFM\Inc\Form_Submit;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Features extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		delete_option( Features::OPTION );
		Features::reset();
	}

	protected function tearDown(): void {
		delete_option( Features::OPTION );
		Features::reset();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * A site that never saved the tab behaves exactly as before: every feature is on.
	 */
	public function test_is_enabled_defaults_on() {
		foreach ( array_keys( Features::definitions() ) as $key ) {
			$this->assertTrue( Features::is_enabled( $key ), $key . ' must default on' );
		}
	}

	/**
	 * Asking about a feature nobody registered never switches it off.
	 */
	public function test_is_enabled_unknown_key_reads_on() {
		update_option( Features::OPTION, [ 'not_a_feature' => false ] );
		Features::reset();
		$this->assertTrue( Features::is_enabled( 'not_a_feature' ) );
	}

	/**
	 * Save keeps registered keys with a boolean value and drops everything else.
	 */
	public function test_save_drops_unknown_keys_and_non_booleans() {
		Features::save(
			[
				'payments'      => 'no',
				'entries'       => 'maybe',
				'not_a_feature' => false,
				'ai_builder'    => [ 'x' ],
			]
		);

		$this->assertSame( [ 'payments' => false ], get_option( Features::OPTION ) );
	}

	/**
	 * Pro (or anyone) adds rows through srfm_features, and they gate the same way.
	 */
	public function test_definitions_filter_adds_rows_with_dependencies() {
		$add = static function ( $rows ) {
			$rows['pdf'] = [
				'label'      => 'PDF',
				'group'      => 'pro',
				'depends_on' => [ 'entries' ],
			];
			return $rows;
		};
		add_filter( 'srfm_features', $add );
		Features::reset();

		$this->assertArrayHasKey( 'pdf', Features::definitions() );
		$this->assertTrue( Features::is_enabled( 'pdf' ) );

		Features::save( [ 'entries' => false ] );
		$this->assertFalse( Features::is_enabled( 'pdf' ), 'a dependent is off while its parent is off' );
		$this->assertTrue( Features::is_enabled( 'ai_builder' ), 'unrelated features stay on' );

		Features::save( [ 'entries' => true ] );
		$this->assertTrue( Features::is_enabled( 'pdf' ), 'turning the parent back on restores it' );

		remove_filter( 'srfm_features', $add );
	}

	/**
	 * The REST routes of a feature that is off are not registered.
	 */
	public function test_filter_endpoints_drops_routes_of_disabled_features() {
		$endpoints = [
			'entries/list'  => [],
			'generate-form' => [],
			'forms'         => [],
		];

		$this->assertSame( $endpoints, Features::filter_endpoints( $endpoints ), 'all on: nothing dropped' );

		Features::save( [ 'entries' => false ] );
		$this->assertSame(
			[
				'generate-form' => [],
				'forms'         => [],
			],
			Features::filter_endpoints( $endpoints ),
			'only the entry routes are dropped'
		);
	}

	/**
	 * The filter is wired: entry routes vanish from the live REST server.
	 */
	public function test_entry_routes_are_not_registered_when_entries_is_off() {
		global $wp_rest_server;

		$wp_rest_server = null;
		$this->assertArrayHasKey( '/sureforms/v1/entries/list', rest_get_server()->get_routes() );

		Features::save( [ 'entries' => false ] );
		$wp_rest_server = null;
		$routes         = rest_get_server()->get_routes();
		$this->assertArrayNotHasKey( '/sureforms/v1/entries/list', $routes );
		$this->assertArrayHasKey( '/sureforms/v1/forms', $routes, 'core routes stay' );

		Features::save( [ 'entries' => true ] );
		$wp_rest_server = null;
	}

	/**
	 * Turning a feature off asks first when a published form uses it, and saves on confirm.
	 */
	public function test_rest_save_lists_forms_in_use_before_turning_off() {
		$form_id = $this->make_payment_form();

		$response = Features::rest_save( [ 'features' => [ 'payments' => false ] ] );
		$data     = $response->get_data();

		$this->assertTrue( $data['needs_confirmation'] );
		$this->assertSame( $form_id, $data['in_use']['payments'][0]['id'] );
		$this->assertTrue( Features::is_enabled( 'payments' ), 'nothing is saved before confirmation' );
		$this->assertSame( '', $data['in_use']['payments'][0]['edit_url'], 'no editor link for a user who cannot edit the form' );

		$admin = wp_insert_user(
			[
				'user_login' => 'features_editor_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $admin );
		$listed = Features::forms_using( 'payments' );
		$this->assertStringContainsString( 'post.php?post=' . $form_id . '&action=edit', $listed[0]['edit_url'] );
		wp_set_current_user( 0 );
		wp_delete_user( $admin );

		$data = Features::rest_save(
			[
				'features' => [ 'payments' => false ],
				'confirm'  => true,
			]
		)->get_data();

		$this->assertArrayNotHasKey( 'needs_confirmation', $data );
		$this->assertFalse( Features::is_enabled( 'payments' ) );

		wp_delete_post( $form_id, true );
	}

	/**
	 * A form that takes payments is never accepted while Payments is off.
	 */
	public function test_payment_form_is_restricted_while_payments_is_off() {
		$paid  = $this->make_payment_form();
		$plain = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/input {"block_id":"in1"} /-->',
			]
		);

		$this->assertFalse( Form_Restriction::is_form_restricted( $paid ), 'payments on: the form is open' );

		Features::save( [ 'payments' => false ] );

		$this->assertTrue( Form_Restriction::is_form_restricted( $paid ) );
		$this->assertFalse( Form_Restriction::is_form_restricted( $plain ), 'forms without a payment are unaffected' );
		$this->assertStringContainsString(
			Form_Restriction::payments_off_message(),
			(string) Form_Restriction::display_form_restriction_message( $paid )
		);

		wp_delete_post( $paid, true );
		wp_delete_post( $plain, true );
	}

	/**
	 * With Entries off, a submission is accepted and mailed but not stored,
	 * whatever the form's own compliance setting says.
	 */
	public function test_submission_is_not_stored_while_entries_is_off() {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_title'  => 'Entries Off Form',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $form_id, '_srfm_email_notification', [ [ 'id' => 1, 'status' => true ] ] );

		$mailed  = false;
		$parsed  = static function () {
			return [
				'to'      => 'owner@example.com',
				'subject' => 'New entry',
				'message' => 'An entry arrived.',
				'headers' => [],
			];
		};
		$sending = static function () use ( &$mailed ) {
			$mailed = true;
			return true;
		};
		add_filter( 'srfm_email_notification', $parsed, 99 );
		add_filter( 'pre_wp_mail', $sending, 99 );

		Features::save( [ 'entries' => false ] );
		$before   = EntriesTable::get_total_entries_by_status( 'all', $form_id );
		$response = Form_Submit::get_instance()->handle_form_entry( [ 'form-id' => (string) $form_id ] );
		$after    = EntriesTable::get_total_entries_by_status( 'all', $form_id );

		remove_filter( 'pre_wp_mail', $sending, 99 );
		remove_filter( 'srfm_email_notification', $parsed, 99 );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $mailed, 'the notification is still sent' );
		$this->assertSame( $before, $after, 'no entry row is written' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Turning Entries off clears the weekly summary it feeds.
	 */
	public function test_turning_entries_off_unschedules_the_email_summary() {
		as_schedule_recurring_action( time() + DAY_IN_SECONDS, WEEK_IN_SECONDS, 'srfm_weekly_scheduled_events', [], 'sureforms' );
		$this->assertNotFalse( as_next_scheduled_action( 'srfm_weekly_scheduled_events' ) );

		Features::save( [ 'entries' => false ] );

		$this->assertFalse( as_next_scheduled_action( 'srfm_weekly_scheduled_events' ) );
	}

	/**
	 * Gating runs on plugins_loaded, before translations may load, so reading the
	 * rows and resolving a state must translate nothing. Text lives in labels().
	 */
	public function test_definitions_translate_nothing() {
		$calls = 0;
		$count = static function ( $text ) use ( &$calls ) {
			++$calls;
			return $text;
		};
		add_filter( 'gettext_sureforms', $count );
		Features::reset();
		Features::definitions();
		Features::is_enabled( 'payments' );
		remove_filter( 'gettext_sureforms', $count );

		$this->assertSame( 0, $calls );
	}

	/**
	 * Every feature row has a label and description, and the JS rows carry them.
	 */
	public function test_labels() {
		$labels = Features::labels();
		foreach ( array_keys( Features::definitions() ) as $key ) {
			$this->assertNotEmpty( $labels[ $key ]['label'] ?? '', $key . ' has a label' );
		}
		$this->assertSame( $labels['payments']['label'], Features::for_js()['payments']['label'] );
	}

	/**
	 * Only string keys with boolean values survive a read of the option.
	 */
	public function test_get_saved() {
		update_option(
			Features::OPTION,
			[
				'payments' => false,
				'entries'  => 'no',
				7          => true,
			]
		);
		$this->assertSame( [ 'payments' => false ], Features::get_saved() );

		update_option( Features::OPTION, 'garbage' );
		$this->assertSame( [], Features::get_saved() );
	}

	/**
	 * States are resolved, so a dependent reads off while its parent is off.
	 */
	public function test_states() {
		$add = static function ( $rows ) {
			$rows['child'] = [
				'label'      => 'Child',
				'depends_on' => [ 'entries' ],
			];
			return $rows;
		};
		add_filter( 'srfm_features', $add );
		Features::save( [ 'entries' => false ] );

		$states = Features::states();
		remove_filter( 'srfm_features', $add );

		$this->assertFalse( $states['entries'] );
		$this->assertFalse( $states['child'] );
		$this->assertTrue( $states['payments'] );
	}

	/**
	 * The JS rows keep the saved switch apart from the resolved state, and name the blocker.
	 */
	public function test_for_js() {
		$add = static function ( $rows ) {
			$rows['child'] = [
				'label'      => 'Child',
				'depends_on' => [ 'entries' ],
			];
			return $rows;
		};
		add_filter( 'srfm_features', $add );
		Features::save( [ 'entries' => false ] );

		$rows = Features::for_js();
		remove_filter( 'srfm_features', $add );

		$this->assertTrue( $rows['child']['switch'], 'its own switch stays on' );
		$this->assertFalse( $rows['child']['enabled'], 'but it is off through its parent' );
		$this->assertSame( [ 'entries' ], $rows['child']['blocked_by'] );
		$this->assertSame( 'core', $rows['entries']['group'] );
	}

	/**
	 * A feature lists the published forms that use its blocks.
	 */
	public function test_forms_using() {
		$paid = $this->make_payment_form();

		$ids = wp_list_pluck( Features::forms_using( 'payments' ), 'id' );
		$this->assertContains( $paid, $ids );
		$this->assertSame( [], Features::forms_using( 'not_a_feature' ) );
		$this->assertSame( [], Features::forms_using( 'ai_builder' ), 'a row with no usage rules lists nothing' );

		wp_delete_post( $paid, true );
	}

	/**
	 * Published forms match by block or by a switched-on meta; drafts never match.
	 */
	public function test_forms_matching() {
		$published = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Meta Form',
			]
		);
		$draft     = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'draft',
				'post_content' => '<!-- wp:srfm/url {"block_id":"d1"} /-->',
			]
		);
		update_post_meta( $published, '_srfm_test_meta', wp_json_encode( [ 'status' => true ] ) );

		$this->assertSame( [], Features::forms_matching( [], [] ) );
		$this->assertContains( $published, wp_list_pluck( Features::forms_matching( [], [ '_srfm_test_meta' ] ), 'id' ) );
		$this->assertNotContains( $draft, wp_list_pluck( Features::forms_matching( [ 'srfm/url' ], [] ), 'id' ), 'drafts are not listed' );

		wp_delete_post( $published, true );
		wp_delete_post( $draft, true );
	}

	/**
	 * Meta counts only when its on/off flag is on, as an array, JSON or a list of items.
	 */
	public function test_form_uses() {
		$form = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/email {"block_id":"e1"} /-->',
			]
		);

		$this->assertTrue( Features::form_uses( $form, [ 'srfm/email' ], [] ) );
		$this->assertFalse( Features::form_uses( $form, [ 'srfm/url' ], [] ) );

		update_post_meta( $form, '_srfm_flag', wp_json_encode( [ 'enabled' => false ] ) );
		$this->assertFalse( Features::form_uses( $form, [], [ '_srfm_flag' ] ), 'a flag that is off does not count' );

		update_post_meta( $form, '_srfm_flag', [ 'is_cf_enabled' => 'true' ] );
		$this->assertTrue( Features::form_uses( $form, [], [ '_srfm_flag' ] ) );

		update_post_meta( $form, '_srfm_flag', wp_json_encode( [ [ 'status' => false ], [ 'status' => true ] ] ) );
		$this->assertTrue( Features::form_uses( $form, [], [ '_srfm_flag' ] ), 'any item of a list that is on counts' );

		wp_delete_post( $form, true );
	}

	/**
	 * reset() drops the cached rows, so a filter added later is picked up.
	 */
	public function test_reset() {
		Features::definitions();
		$add = static function ( $rows ) {
			$rows['late'] = [ 'label' => 'Late' ];
			return $rows;
		};
		add_filter( 'srfm_features', $add );

		$cached = array_key_exists( 'late', Features::definitions() );
		Features::reset();
		$fresh = array_key_exists( 'late', Features::definitions() );
		remove_filter( 'srfm_features', $add );

		$this->assertFalse( $cached, 'rows are cached until reset' );
		$this->assertTrue( $fresh );
	}

	/**
	 * Create a published form with a payment block.
	 *
	 * @return int
	 */
	private function make_payment_form() {
		return wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Paid Form',
				'post_content' => '<!-- wp:srfm/payment {"block_id":"pay1"} /-->',
			]
		);
	}
}
