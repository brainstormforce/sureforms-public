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
		delete_option( Features::BLOCKS_OPTION );
		Features::reset();
	}

	protected function tearDown(): void {
		delete_option( Features::OPTION );
		delete_option( Features::BLOCKS_OPTION );
		Features::reset();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * A site that never saved the tab behaves exactly as before: every feature is on.
	 */
	public function test_every_feature_defaults_on() {
		foreach ( array_keys( Features::definitions() ) as $key ) {
			$this->assertTrue( Features::is_enabled( $key ), $key . ' must default on' );
		}
	}

	/**
	 * Asking about a feature nobody registered never switches it off.
	 */
	public function test_unknown_feature_reads_on() {
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
	public function test_filter_adds_rows_with_dependencies() {
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
	 * The Entries and Payments menus are not added while their features are off.
	 */
	public function test_menus_follow_their_features() {
		global $submenu;

		$admin_id = wp_insert_user(
			[
				'user_login' => 'srfm_features_admin',
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $admin_id );
		$slugs = static function () {
			global $submenu;
			$submenu = [];
			\SRFM\Admin\Admin::get_instance()->add_new_form();
			return wp_list_pluck( $submenu['sureforms_menu'] ?? [], 2 );
		};

		$on = $slugs();
		$this->assertContains( SRFM_ENTRIES, $on );
		$this->assertContains( SRFM_PAYMENTS, $on );

		Features::save(
			[
				'entries'  => false,
				'payments' => false,
			]
		);
		$off = $slugs();
		$this->assertNotContains( SRFM_ENTRIES, $off );
		$this->assertNotContains( SRFM_PAYMENTS, $off );
		$this->assertContains( 'sureforms_forms', $off, 'the Forms menu stays' );

		$submenu = [];
		wp_delete_user( $admin_id );
	}

	/**
	 * Saving keeps listed blocks with boolean values and drops unknown names,
	 * feature-owned blocks and non-booleans.
	 */
	public function test_save_blocks_keeps_only_switchable_blocks() {
		Features::save_blocks(
			[
				'srfm/url'      => false,
				'srfm/email'    => 'no',
				'srfm/payment'  => false,
				'srfm/form'     => false,
				'acme/anything' => false,
			]
		);

		$this->assertSame( [ 'srfm/url' ], get_option( Features::BLOCKS_OPTION ) );
		$this->assertFalse( Features::is_block_enabled( 'srfm/url' ) );
		$this->assertTrue( Features::is_block_enabled( 'srfm/email' ) );
		$this->assertTrue( Features::is_block_enabled( 'srfm/form' ), 'the Form block is never switchable' );
		$this->assertTrue( Features::is_block_enabled( 'acme/anything' ), 'a block nobody listed is on' );

		Features::save_blocks( [ 'srfm/url' => true ] );
		$this->assertTrue( Features::is_block_enabled( 'srfm/url' ) );
	}

	/**
	 * A block owned by a feature is on exactly while that feature is on.
	 */
	public function test_feature_owned_block_follows_its_feature() {
		$this->assertTrue( Features::is_block_enabled( 'srfm/payment' ) );
		Features::save( [ 'payments' => false ] );
		$this->assertFalse( Features::is_block_enabled( 'srfm/payment' ) );
	}

	/**
	 * A switched-off block is unregistered, so it renders nothing.
	 */
	public function test_disabled_block_is_unregistered() {
		$registry = WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'srfm/url' ) );
		$type = $registry->get_registered( 'srfm/url' );

		Features::save_blocks( [ 'srfm/url' => false ] );
		Features::unregister_disabled_blocks();

		$registered = $registry->is_registered( 'srfm/url' );
		register_block_type( $type );

		$this->assertFalse( $registered );
		$this->assertTrue( $registry->is_registered( 'srfm/email' ), 'other blocks stay' );
	}

	/**
	 * Switching off a block that published forms contain asks first.
	 */
	public function test_rest_save_lists_forms_using_a_block() {
		$form_id = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'URL Form',
				'post_content' => '<!-- wp:srfm/url {"block_id":"u1"} /-->',
			]
		);

		$data = Features::rest_save( [ 'blocks' => [ 'srfm/url' => false ] ] )->get_data();
		$this->assertTrue( $data['needs_confirmation'] );
		$this->assertSame( $form_id, $data['in_use']['srfm/url'][0]['id'] );
		$this->assertTrue( Features::is_block_enabled( 'srfm/url' ), 'nothing is saved before confirmation' );

		$data = Features::rest_save(
			[
				'blocks'  => [ 'srfm/url' => false ],
				'confirm' => true,
			]
		)->get_data();
		$this->assertFalse( $data['blocks']['srfm/url']['enabled'] );

		wp_delete_post( $form_id, true );
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
