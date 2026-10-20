<?php
/**
 * Class Test_Form_Start
 *
 * @package sureforms
 */

use SRFM\Admin\Analytics;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Duplicate_Form;
use SRFM\Inc\Form_Start;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Helper;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Form_Start extends TestCase {

	/**
	 * Forms created by a test, deleted in tear_down().
	 *
	 * @var array<int, int>
	 */
	private $form_ids = [];


	public function set_up() {
		parent::set_up();
		delete_option( Analytics::FORM_START_FUNNEL_OPTION );
		Analytics::events()->flush_pending();
		add_filter( 'sureforms_tracking_enabled', '__return_true' );
	}

	public function tear_down() {
		global $pagenow, $wpdb;
		foreach ( $this->form_ids as $form_id ) {
			wp_delete_post( $form_id, true );
			$wpdb->delete( Entries::get_instance()->get_tablename(), [ 'form_id' => $form_id ] );
		}
		$this->form_ids = [];
		$pagenow = '';
		unset( $_GET[ Form_Start::SCRATCH_QUERY_ARG ] );
		remove_filter( 'sureforms_tracking_enabled', '__return_true' );
		remove_filter( 'sureforms_tracking_enabled', '__return_false' );
		remove_all_filters( 'srfm_show_form_start_screen' );
		delete_option( Analytics::FORM_START_FUNNEL_OPTION );
		Analytics::events()->flush_pending();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	private function login_admin() {
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_start_admin_' . uniqid(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_start_' . uniqid() . '@example.com',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $user_id );
	}

	private function track( $action, $choice = '', $position = '' ) {
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-start/track' );
		$request->set_param( 'action', $action );
		if ( '' !== $choice ) {
			$request->set_param( 'choice', $choice );
		}
		if ( '' !== $position ) {
			$request->set_param( 'position', $position );
		}
		return rest_do_request( $request );
	}

	/**
	 * Properties of the queued `form_start_funnel` event, or null.
	 */
	private function funnel_event() {
		foreach ( array_reverse( Analytics::events()->flush_pending() ) as $event ) {
			if ( 'form_start_funnel' === ( $event['event_name'] ?? '' ) ) {
				return $event;
			}
		}
		return null;
	}

	private function insert_form( $status = 'draft', $title = 'Start test' ) {
		$post_id          = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => $status,
			]
		);
		$this->form_ids[] = $post_id;
		return $post_id;
	}

	private function create_form( $status, $choice, $via ) {
		$post_id = $this->insert_form( $status );
		update_post_meta( $post_id, Form_Start::START_CHOICE_META, $choice );
		update_post_meta( $post_id, Form_Templates::CREATED_VIA_META, $via );
		return $post_id;
	}

	public function test_start_screen_is_on_by_default_and_can_be_filtered_off() {
		$this->assertTrue( Form_Start::is_enabled() );
		$this->assertSame(
			[
				'enabled'  => true,
				'tracking' => true,
			],
			Form_Start::get_settings_for_js()
		);

		add_filter( 'srfm_show_form_start_screen', '__return_false' );
		$this->assertFalse( Form_Start::get_settings_for_js()['enabled'] );
	}

	public function test_sanitize_choice_only_keeps_known_choices() {
		$this->assertSame( 'ai', Form_Start::sanitize_choice( 'ai' ) );
		$this->assertSame( 'template', Form_Start::sanitize_choice( 'Template' ) );
		$this->assertSame( '', Form_Start::sanitize_choice( 'import' ) );
		$this->assertSame( '', Form_Start::sanitize_choice( [ 'ai' ] ) );
	}

	public function test_views_choices_positions_and_steps_are_counted() {
		$this->login_admin();

		$this->assertTrue( $this->track( 'view' )->get_data()['recorded'] );
		$this->track( 'view' );
		$this->track( 'choose', 'template', '2' );
		$this->track( 'choose', 'ai', '1' );
		$this->track( 'choose', 'scratch', 'link' );
		$this->track( 'choose', 'ai', 'sidebar' );
		$this->track( 'ai_generate', 'ai' );
		$this->track( 'template_preview', 'template' );

		$this->assertSame( 400, $this->track( 'delete_everything', 'ai' )->get_status() );
		$this->assertFalse( $this->track( 'choose', 'import' )->get_data()['recorded'], 'Unknown choices are not counted.' );

		$funnel = get_option( Analytics::FORM_START_FUNNEL_OPTION, [] );
		$this->assertSame( 2, $funnel['views'] );
		$this->assertSame( 2, $funnel['choice_ai'] );
		$this->assertSame( 1, $funnel['choice_ai_pos_1'] );
		$this->assertArrayNotHasKey( 'choice_ai_pos_sidebar', $funnel, 'Only known positions are counted per position.' );
		$this->assertSame( 1, $funnel['choice_template_pos_2'] );
		$this->assertSame( 1, $funnel['choice_scratch_pos_link'] );
		$this->assertSame( 'template', $funnel['first_choice'], 'The first choice ever made sticks.' );
		$this->assertSame( 1, $funnel['step_ai_generate'] );
		$this->assertSame( 1, $funnel['step_template_preview'] );

		$event = $this->funnel_event();
		$this->assertSame( '4', $event['event_value'], 'The event value is the number of choices.' );
		$this->assertSame( 'free', $event['properties']['plan'] );
		$this->assertSame( '2', $event['properties']['choice_ai'] );
		$this->assertSame( 'template', $event['properties']['first_choice'] );
	}

	public function test_nothing_is_counted_without_usage_sharing() {
		$this->login_admin();
		remove_filter( 'sureforms_tracking_enabled', '__return_true' );
		add_filter( 'sureforms_tracking_enabled', '__return_false' );

		$this->assertFalse( $this->track( 'view' )->get_data()['recorded'] );
		Analytics::track_form_start_created( 'ai', 'ai' );

		$this->assertEmpty( get_option( Analytics::FORM_START_FUNNEL_OPTION, [] ) );
		$this->assertFalse( Form_Start::get_settings_for_js()['tracking'] );
	}

	public function test_track_route_needs_a_user_who_can_manage_forms() {
		$this->assertSame( 401, $this->track( 'view' )->get_status() );
	}

	public function test_record_created_tags_the_form_and_counts_switches() {
		$ai_form       = $this->insert_form();
		$template_form = $this->insert_form();
		$plain_form    = $this->insert_form();

		Form_Start::record_created( $ai_form, 'ai', 'ai' );
		// Picked AI, ran out of credits and used a template instead.
		Form_Start::record_created( $template_form, 'ai', 'template' );
		// Not from the start screen (e.g. the dashboard AI prompt).
		Form_Start::record_created( $plain_form, '', 'ai' );

		$this->assertSame( 'ai', get_post_meta( $template_form, Form_Start::START_CHOICE_META, true ) );
		$this->assertSame( '', get_post_meta( $plain_form, Form_Start::START_CHOICE_META, true ) );

		$funnel = get_option( Analytics::FORM_START_FUNNEL_OPTION, [] );
		$this->assertSame( 2, $funnel['created_ai'] );
		$this->assertSame( 1, $funnel['switch_ai_to_template'] );
		$this->assertArrayNotHasKey( 'created_template', $funnel );
	}

	public function test_blank_form_from_post_new_is_tagged_and_counted_once_saved() {
		global $pagenow;
		$pagenow = 'post-new.php';
		$_GET[ Form_Start::SCRATCH_QUERY_ARG ] = 'scratch';

		// What post-new.php does when the editor opens.
		$post_id = $this->insert_form( 'auto-draft', 'Auto Draft' );

		$this->assertSame( 'scratch', get_post_meta( $post_id, Form_Templates::CREATED_VIA_META, true ) );
		$this->assertSame( 'scratch', get_post_meta( $post_id, Form_Start::START_CHOICE_META, true ) );
		$this->assertArrayNotHasKey( 'created_scratch', get_option( Analytics::FORM_START_FUNNEL_OPTION, [] ), 'An unsaved auto-draft is not a created form.' );

		$pagenow = 'post.php';
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'publish',
			]
		);

		$this->assertSame( 1, get_option( Analytics::FORM_START_FUNNEL_OPTION, [] )['created_scratch'], 'Counted on the first save only.' );
	}

	public function test_blank_form_switch_from_ai_is_counted_as_a_switch() {
		global $pagenow;
		$pagenow = 'post-new.php';
		$_GET[ Form_Start::SCRATCH_QUERY_ARG ] = 'ai';

		$post_id = $this->insert_form( 'auto-draft', 'Auto Draft' );
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$funnel = get_option( Analytics::FORM_START_FUNNEL_OPTION, [] );
		$this->assertSame( 1, $funnel['created_ai'] );
		$this->assertSame( 1, $funnel['switch_ai_to_scratch'] );
	}

	public function test_blank_form_without_a_choice_is_tagged_but_not_counted() {
		global $pagenow;
		$pagenow = 'post-new.php';
		$_GET[ Form_Start::SCRATCH_QUERY_ARG ] = 'everything';

		$post_id = $this->insert_form( 'auto-draft', 'Auto Draft' );
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->assertSame( 'scratch', get_post_meta( $post_id, Form_Templates::CREATED_VIA_META, true ) );
		$this->assertSame( '', get_post_meta( $post_id, Form_Start::START_CHOICE_META, true ) );
		$this->assertEmpty( get_option( Analytics::FORM_START_FUNNEL_OPTION, [] ) );
	}

	public function test_forms_created_outside_post_new_are_not_tagged_as_scratch() {
		$post_id = $this->insert_form( 'auto-draft', 'Imported' );

		$this->assertSame( '', get_post_meta( $post_id, Form_Templates::CREATED_VIA_META, true ) );
	}

	public function test_outcomes_count_drafts_published_and_forms_with_entries() {
		$this->create_form( 'draft', 'ai', 'ai' );
		$this->create_form( 'publish', 'ai', 'template' );
		$template_live = $this->create_form( 'publish', 'template', 'template' );
		$this->create_form( 'trash', 'template', 'template' );
		$this->create_form( 'publish', 'nonsense', 'ai' );

		Entries::add(
			[
				'form_id'   => $template_live,
				'user_id'   => 0,
				'status'    => 'unread',
				'form_data' => [ 'name' => 'Jane' ],
			]
		);

		$outcomes = Analytics::form_start_outcomes();
		$this->assertSame(
			[
				'draft'        => 1,
				'published'    => 1,
				'with_entries' => 0,
			],
			$outcomes['ai']
		);
		$this->assertSame(
			[
				'draft'        => 0,
				'published'    => 1,
				'with_entries' => 1,
			],
			$outcomes['template']
		);
		$this->assertSame( 0, $outcomes['scratch']['published'] );
	}

	public function test_refresh_sends_outcomes_only_for_sites_that_used_the_start_screen() {
		$this->create_form( 'publish', 'template', 'template' );

		Analytics::refresh_form_start_funnel();
		$this->assertNull( $this->funnel_event(), 'A site that never saw the start screen sends nothing.' );

		Analytics::track_form_start_action( 'view' );
		Analytics::events()->flush_pending();
		Analytics::refresh_form_start_funnel();

		$event = $this->funnel_event();
		$this->assertSame( '1', $event['properties']['views'] );
		$this->assertSame( '1', $event['properties']['published_template'] );
		$this->assertSame( '0', $event['properties']['draft_scratch'] );
	}

	public function test_duplicates_do_not_count_for_the_start_screen_or_templates() {
		$source = $this->create_form( 'publish', 'template', 'template' );
		update_post_meta( $source, Form_Templates::TEMPLATE_ID_META, 'contact-us' );

		$result           = Duplicate_Form::get_instance()->duplicate_form( $source );
		$copy             = $result['new_form_id'];
		$this->form_ids[] = $copy;

		$this->assertSame( '', get_post_meta( $copy, Form_Start::START_CHOICE_META, true ) );
		$this->assertSame( 'duplicate', get_post_meta( $copy, Form_Templates::CREATED_VIA_META, true ) );
	}
}
