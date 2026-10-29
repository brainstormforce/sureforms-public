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
	 * The Stripe webhook stays registered while Payments is off, so renewals,
	 * refunds and disputes on existing subscriptions are still recorded.
	 */
	public function test_stripe_webhook_is_registered_while_payments_is_off() {
		global $wp_rest_server;

		// The test bootstrap already booted it with Payments on; start from nothing.
		$prop = new ReflectionProperty( \SRFM\Inc\Payments\Stripe\Stripe_Webhook::class, 'instance' );
		$prop->setAccessible( true );
		$original = $prop->getValue();
		if ( $original ) {
			remove_action( 'rest_api_init', [ $original, 'register_endpoints' ] );
		}
		$prop->setValue( null, null );

		Features::save( [ 'payments' => false ] );
		\SRFM\Plugin_Loader::get_instance()->load_classes();
		$booted         = $prop->getValue();
		$wp_rest_server = null;
		$routes         = rest_get_server()->get_routes();
		$wp_rest_server = null;

		if ( $booted && $original ) {
			remove_action( 'rest_api_init', [ $booted, 'register_endpoints' ] );
		}
		$prop->setValue( null, $original );
		if ( $original ) {
			add_action( 'rest_api_init', [ $original, 'register_endpoints' ] );
		}

		$this->assertNotNull( $booted, 'the webhook boots whatever the Payments switch says' );
		$this->assertArrayHasKey( '/sureforms/webhook_test', $routes );
		$this->assertArrayHasKey( '/sureforms/webhook_live', $routes );
	}

	/**
	 * A payment block that renders from a synced pattern counts as the form's own:
	 * the form is restricted while Payments is off and listed before it is turned off.
	 */
	public function test_payment_block_inside_a_synced_pattern_counts() {
		$pattern = wp_insert_post(
			[
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/payment {"block_id":"pay1"} /-->',
			]
		);
		$form    = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/input {"block_id":"in1"} /--><!-- wp:block {"ref":' . $pattern . '} /-->',
			]
		);

		$this->assertTrue( Features::form_uses( $form, [ 'srfm/payment' ], [] ) );
		$this->assertContains( $form, wp_list_pluck( Features::forms_using( 'payments' ), 'id' ) );

		Features::save( [ 'payments' => false ] );
		$this->assertTrue( Form_Restriction::is_form_restricted( $form ), 'never accepted without its payment' );

		wp_delete_post( $form, true );
		wp_delete_post( $pattern, true );
	}

	/**
	 * A synced pattern core does not render (not published, or behind a password)
	 * adds nothing to the form, so its payment block does not count.
	 */
	public function test_payment_block_inside_an_unrendered_synced_pattern_does_not_count() {
		$patterns = [
			'draft'    => [ 'post_status' => 'draft' ],
			'pending'  => [ 'post_status' => 'pending' ],
			'private'  => [ 'post_status' => 'private' ],
			'trash'    => [ 'post_status' => 'trash' ],
			'password' => [
				'post_status'   => 'publish',
				'post_password' => 'secret',
			],
		];
		foreach ( $patterns as $case => $args ) {
			$pattern = wp_insert_post(
				array_merge(
					[
						'post_type'    => 'wp_block',
						'post_content' => '<!-- wp:srfm/payment {"block_id":"pay1"} /-->',
					],
					$args
				)
			);
			$form    = wp_insert_post(
				[
					'post_type'    => SRFM_FORMS_POST_TYPE,
					'post_status'  => 'publish',
					'post_content' => '<!-- wp:block {"ref":' . $pattern . '} /-->',
				]
			);

			$this->assertFalse( Features::form_uses( $form, [ 'srfm/payment' ], [] ), $case );

			wp_delete_post( $form, true );
			wp_delete_post( $pattern, true );
		}
	}

	/**
	 * Forms that are not published yet still use the feature: a draft, scheduled,
	 * pending or private payment form is listed; a trashed one is not.
	 */
	public function test_unpublished_forms_are_listed_as_in_use() {
		$ids = [];
		foreach ( [ 'draft', 'future', 'pending', 'private', 'trash' ] as $status ) {
			$ids[ $status ] = wp_insert_post(
				[
					'post_type'    => SRFM_FORMS_POST_TYPE,
					'post_status'  => $status,
					'post_title'   => 'Payment form ' . $status,
					'post_date'    => 'future' === $status ? gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) : '',
					'post_content' => '<!-- wp:srfm/payment {"block_id":"p' . $status . '"} /-->',
				]
			);
		}

		$listed = wp_list_pluck( Features::forms_using( 'payments' ), 'id' );

		foreach ( [ 'draft', 'future', 'pending', 'private' ] as $status ) {
			$this->assertContains( $ids[ $status ], $listed, $status . ' forms are listed' );
		}
		$this->assertNotContains( $ids['trash'], $listed, 'trashed forms are not' );

		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
	}

	/**
	 * A pattern that references itself is expanded once and does not loop.
	 */
	public function test_form_uses_stops_on_a_pattern_cycle() {
		$pattern = wp_insert_post(
			[
				'post_type'   => 'wp_block',
				'post_status' => 'publish',
				'post_title'  => 'Self-referencing pattern',
			]
		);
		wp_update_post(
			[
				'ID'           => $pattern,
				'post_content' => '<!-- wp:block {"ref":' . $pattern . '} /-->',
			]
		);
		$form = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:block {"ref":' . $pattern . '} /-->',
			]
		);

		$this->assertFalse( Features::form_uses( $form, [ 'srfm/payment' ], [] ) );

		wp_delete_post( $form, true );
		wp_delete_post( $pattern, true );
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
	 * A submission that is not stored still passes through srfm_form_submission_response,
	 * so listeners that shape the response (e.g. Pro's conditional confirmations) run on
	 * both paths: with Entries off and on a form whose compliance setting skips storage.
	 */
	public function test_submission_response_filter_runs_when_not_stored() {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
			]
		);
		$seen    = 0;
		$shape   = static function ( $response ) use ( &$seen ) {
			++$seen;
			$response['data']['submission_settings']['submission_mode'] = 'same page';
			return $response;
		};
		add_filter( 'srfm_form_submission_response', $shape );

		Features::save( [ 'entries' => false ] );
		$response = Form_Submit::get_instance()->handle_form_entry( [ 'form-id' => (string) $form_id ] );
		$this->assertSame( 1, $seen, 'the filter runs once with Entries off' );
		$this->assertSame( 'same page', $response['data']['submission_settings']['submission_mode'] );
		$this->assertTrue( $response['success'] );

		Features::save( [ 'entries' => true ] );
		update_post_meta(
			$form_id,
			'_srfm_compliance',
			[
				[
					'gdpr'                 => true,
					'do_not_store_entries' => true,
				],
			]
		);
		$response = Form_Submit::get_instance()->handle_form_entry( [ 'form-id' => (string) $form_id ] );
		$this->assertSame( 2, $seen, 'the filter runs on a form that does not store entries' );
		$this->assertSame( 'same page', $response['data']['submission_settings']['submission_mode'] );

		remove_filter( 'srfm_form_submission_response', $shape );
		wp_delete_post( $form_id, true );
	}

	/**
	 * While Entries is off, saving the email summary settings schedules nothing,
	 * because the summary's handler is not booted.
	 */
	public function test_email_summary_is_not_scheduled_while_entries_is_off() {
		Features::save( [ 'entries' => false ] );
		\SRFM\Inc\Global_Settings\Global_Settings::srfm_save_email_summary_settings( [ 'srfm_email_summary' => true ] );

		$this->assertFalse( as_next_scheduled_action( 'srfm_weekly_scheduled_events' ) );
	}

	/**
	 * The dashboard chart reads the entries table, so its route goes with Entries.
	 */
	public function test_entries_chart_route_is_dropped_while_entries_is_off() {
		Features::save( [ 'entries' => false ] );

		$this->assertArrayNotHasKey( 'entries-chart-data', Features::filter_endpoints( [ 'entries-chart-data' => [] ] ) );
	}

	/**
	 * Entry limits count stored entries, so a form with one is listed before
	 * Entries is turned off.
	 */
	public function test_forms_with_an_entry_limit_are_listed_before_entries_is_turned_off() {
		$limited = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
			]
		);
		$open    = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
			]
		);
		update_post_meta( $limited, '_srfm_form_restriction', wp_json_encode( [ 'status' => true, 'maxEntries' => 5 ] ) );
		update_post_meta( $open, '_srfm_form_restriction', wp_json_encode( [ 'status' => false, 'maxEntries' => 5 ] ) );

		$listed = wp_list_pluck( Features::forms_using( 'entries' ), 'id' );

		$this->assertContains( $limited, $listed );
		$this->assertNotContains( $open, $listed, 'a limit that is switched off is not listed' );

		wp_delete_post( $limited, true );
		wp_delete_post( $open, true );
	}

	/**
	 * The OttoKit integration AJAX handler is not registered while OttoKit is off.
	 */
	public function test_ottokit_ajax_is_not_registered_while_ottokit_is_off() {
		global $wp_filter;
		$saved = $wp_filter['wp_ajax_sureforms_integration'] ?? null;
		unset( $wp_filter['wp_ajax_sureforms_integration'] );

		Features::save( [ 'ottokit' => false ] );
		new \SRFM\Inc\Admin_Ajax();
		$registered = has_action( 'wp_ajax_sureforms_integration' );

		unset( $wp_filter['wp_ajax_sureforms_integration'] );
		if ( null !== $saved ) {
			$wp_filter['wp_ajax_sureforms_integration'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restores the hook removed above.
		}

		$this->assertFalse( $registered );
	}

	/**
	 * With the AI form builder off, New Form still opens its own screen (the start
	 * screen without the AI card), not a redirect to a blank form.
	 */
	public function test_new_form_screen_is_not_redirected_while_ai_builder_is_off() {
		global $wp_filter;

		$admin = wp_insert_user(
			[
				'user_login' => 'features_newform_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $admin );
		Features::save( [ 'ai_builder' => false ] );

		$load_hooks = static function () use ( &$wp_filter ) {
			return array_filter(
				array_keys( $wp_filter ),
				static fn( $hook ) => 0 === strpos( (string) $hook, 'load-' ) && false !== strpos( (string) $hook, 'add-new-form' )
			);
		};
		$before = $load_hooks();
		\SRFM\Admin\Admin::get_instance()->add_new_form();
		$added = array_diff( $load_hooks(), $before );

		wp_set_current_user( 0 );
		wp_delete_user( $admin );

		$this->assertSame( [], array_values( $added ) );
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
	 * Forms match by block or by a switched-on meta, drafts included: a draft
	 * breaks the same way once it is published.
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
		$this->assertContains( $draft, wp_list_pluck( Features::forms_matching( [ 'srfm/url' ], [] ), 'id' ), 'drafts are listed' );

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
