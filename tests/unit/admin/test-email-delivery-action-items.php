<?php
/**
 * Class Test_Email_Delivery_Action_Items
 *
 * @package sureforms
 */

use SRFM\Admin\Admin;
use SRFM\Inc\Client_Logger;
use SRFM\Inc\Email_Delivery\Status;
use SRFM\Inc\Email_Delivery\Suremail_Adapter;
use SRFM\Inc\Helper;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Email delivery in the Form Checks panel and the "Finish setting up" widget.
 */
class Test_Email_Delivery_Action_Items extends TestCase {

	/**
	 * Administrator created for the test.
	 *
	 * @var int
	 */
	private $admin_id = 0;

	/**
	 * Forms created for the test.
	 *
	 * @var int[]
	 */
	private $form_ids = [];

	/**
	 * srfm_options['dismissed_action_items'] before the test.
	 *
	 * @var mixed
	 */
	private $dismissed_backup;

	protected function setUp(): void {
		parent::setUp();

		$this->admin_id = (int) wp_insert_user(
			[
				'user_login' => 'srfm_email_items_' . wp_rand(),
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $this->admin_id );

		$this->dismissed_backup = Helper::get_srfm_option( 'dismissed_action_items', [] );
		Helper::update_srfm_option( 'dismissed_action_items', [] );

		$this->reset();
	}

	protected function tearDown(): void {
		foreach ( $this->form_ids as $form_id ) {
			wp_delete_post( $form_id, true );
		}
		$this->form_ids = [];

		Helper::update_srfm_option( 'dismissed_action_items', $this->dismissed_backup );
		$_GET     = [];
		$_REQUEST = [];

		wp_set_current_user( 0 );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $this->admin_id );

		$this->reset();
		parent::tearDown();
	}

	private function reset() {
		remove_all_filters( 'pre_option_active_plugins' );
		remove_all_filters( 'srfm_hide_promotions' );
		delete_option( Suremail_Adapter::CONNECTIONS_OPTION );
		delete_option( Status::OUTCOMES_OPTION );
		delete_option( Client_Logger::FAILURES_OPTION );
		delete_transient( Suremail_Adapter::QUOTA_TRANSIENT );
		Status::reset();
		Admin::reset_action_items_cache();
		Admin::reset_form_setup_card_cache();
	}

	private function use_plugins( array $plugins ) {
		remove_all_filters( 'pre_option_active_plugins' );
		add_filter(
			'pre_option_active_plugins',
			static function () use ( $plugins ) {
				return $plugins;
			}
		);
		Status::reset();
		Admin::reset_action_items_cache();
	}

	private function on_sureforms_dashboard( $on = true ) {
		if ( $on ) {
			$_GET['page']     = 'sureforms_menu';
			$_REQUEST['page'] = 'sureforms_menu';
		} else {
			unset( $_GET['page'], $_REQUEST['page'] );
		}
		Admin::reset_action_items_cache();
	}

	private function make_form( $notification_on = true ) {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Contact Form',
			]
		);
		update_post_meta(
			$form_id,
			'_srfm_email_notification',
			[
				[
					'id'     => 1,
					'status' => $notification_on,
				],
			]
		);
		$this->form_ids[] = $form_id;
		return $form_id;
	}

	private function items() {
		$items = [];
		foreach ( Admin::get_instance()->get_action_items() as $item ) {
			$items[ $item['id'] ] = $item;
		}
		return $items;
	}

	public function test_setup_item_offers_one_click_install_on_the_dashboard() {
		$this->use_plugins( [] );
		$this->make_form();
		$this->on_sureforms_dashboard();

		$items = $this->items();

		$this->assertArrayHasKey( 'email_delivery_setup', $items );
		$item = $items['email_delivery_setup'];
		$this->assertSame( 'warning', $item['status'] );
		$this->assertTrue( $item['dismissible'] );
		$this->assertSame( 'setup_email_delivery', $item['cta_action'] );
		$this->assertSame( 'sure_mails', $item['cta_install'] ?? '' );
		$this->assertStringContainsString( 'plugin-install.php', $item['cta_url'], 'Without JavaScript the link is the WordPress installer.' );
	}

	public function test_setup_item_stays_off_other_admin_screens() {
		$this->use_plugins( [] );
		$this->make_form();
		$this->on_sureforms_dashboard( false );

		$this->assertArrayNotHasKey( 'email_delivery_setup', $this->items() );
	}

	public function test_setup_item_needs_a_form_that_sends_notifications() {
		$this->use_plugins( [] );
		$this->make_form( false );
		$this->on_sureforms_dashboard();

		$this->assertArrayNotHasKey( 'email_delivery_setup', $this->items() );
	}

	public function test_setup_item_is_hidden_with_promotions() {
		$this->use_plugins( [] );
		$this->make_form();
		$this->on_sureforms_dashboard();
		add_filter( 'srfm_hide_promotions', '__return_true' );

		$this->assertArrayNotHasKey( 'email_delivery_setup', $this->items() );
	}

	public function test_setup_item_can_be_dismissed() {
		$this->use_plugins( [] );
		$this->make_form();
		$this->on_sureforms_dashboard();

		$dismiss = new ReflectionMethod( Admin::class, 'dismiss_action_item' );
		$dismiss->setAccessible( true );
		$this->assertTrue( $dismiss->invoke( Admin::get_instance(), 'email_delivery_setup' ) );

		Admin::reset_action_items_cache();
		$this->assertArrayNotHasKey( 'email_delivery_setup', $this->items() );
	}

	public function test_nothing_to_set_up_with_another_mail_plugin() {
		$this->use_plugins( [ 'wp-mail-smtp/wp_mail_smtp.php' ] );
		$this->make_form();
		$this->on_sureforms_dashboard();

		$items = $this->items();
		$this->assertArrayNotHasKey( 'email_delivery_setup', $items );
		$this->assertArrayNotHasKey( 'email_delivery_limited', $items );
	}

	public function test_suremail_without_connection_links_into_suremail() {
		$this->use_plugins( [ 'suremails/suremails.php' ] );
		$this->make_form();
		$this->on_sureforms_dashboard();

		$item = $this->items()['email_delivery_setup'] ?? [];

		$this->assertSame( 'open_suremail', $item['cta_action'] ?? '' );
		$this->assertStringContainsString( 'page=suremail', $item['cta_url'] ?? '' );
		$this->assertArrayNotHasKey( 'cta_install', $item );
	}

	public function test_unverified_surecontact_gets_the_limited_item() {
		$this->use_plugins( [ 'suremails/suremails.php' ] );
		update_option(
			Suremail_Adapter::CONNECTIONS_OPTION,
			[
				'connections'        => [
					'sc1' => [
						'type'           => 'SURECONTACT',
						'priority'       => 1,
						'email_verified' => false,
					],
				],
				'default_connection' => [ 'id' => 'sc1' ],
			]
		);
		$this->make_form();
		$this->on_sureforms_dashboard();

		$items = $this->items();
		$this->assertArrayHasKey( 'email_delivery_limited', $items );
		$this->assertArrayNotHasKey( 'email_delivery_setup', $items );
	}

	/**
	 * A fresh ZipWP site has no mailer and its first notification fails. The
	 * failure notice alone offered only a guide and support; the one-click fix
	 * must stay next to it, non-dismissible, and must not repeat the failure.
	 */
	public function test_setup_item_stays_next_to_a_failure_when_the_fix_is_suremail() {
		$this->use_plugins( [] );
		$form_id = $this->make_form();
		Client_Logger::record_failure( 'notification', $form_id, 'Contact Form' );
		Status::record_outcome( false, $form_id, 'mailer_error' );
		$this->on_sureforms_dashboard();

		$items = $this->items();

		$this->assertArrayHasKey( 'notification_error', $items );
		$this->assertArrayHasKey( 'email_delivery_setup', $items );
		$setup = $items['email_delivery_setup'];
		$this->assertSame( 'Email delivery is not set up', $setup['title'] );
		$this->assertFalse( $setup['dismissible'] );
		$this->assertSame( 'sure_mails', $setup['cta_install'] ?? '' );
	}

	/**
	 * Ignoring the advice earlier does not hide the fix once email actually fails.
	 */
	public function test_a_failure_brings_back_an_ignored_setup_item() {
		$this->use_plugins( [] );
		$form_id = $this->make_form();
		Helper::update_srfm_option( 'dismissed_action_items', [ 'email_delivery_setup' ] );
		$this->on_sureforms_dashboard();
		$this->assertArrayNotHasKey( 'email_delivery_setup', $this->items(), 'Precondition: ignored while nothing failed.' );

		Status::record_outcome( false, $form_id, 'mailer_error' );
		Status::reset();
		Admin::reset_action_items_cache();

		$this->assertArrayHasKey( 'email_delivery_setup', $this->items() );
	}

	public function test_notification_failure_points_at_suremail_logs_when_suremail_sends() {
		$this->use_plugins( [ 'suremails/suremails.php' ] );
		Client_Logger::record_failure( 'notification', 42, 'Contact Form' );

		$item = $this->items()['notification_error'] ?? [];

		$this->assertSame( 'view_logs', $item['guide_action'] ?? '' );
		$this->assertStringEndsWith( '#/logs', $item['guide_url'] ?? '' );
		$this->assertSame( 'contact_support', $item['cta_action'] ?? '', 'Contact Support stays.' );
	}

	public function test_setup_widget_shows_whether_emails_will_be_sent() {
		remove_all_actions( 'wp_insert_post_data' );
		$form_id = $this->make_form();
		update_post_meta( $form_id, Admin::ASTRA_SITES_IMPORT_META, 1 );
		delete_transient( Admin::NO_IMPORTED_FORMS_TRANSIENT );

		$this->use_plugins( [] );
		Admin::reset_form_setup_card_cache();
		ob_start();
		Admin::get_instance()->render_form_setup_widget();
		$not_set_up = (string) ob_get_clean();

		$this->assertStringContainsString( 'Email delivery is not set up', $not_set_up );
		$this->assertStringContainsString( 'data-srfm-event="email_delivery"', $not_set_up );
		$this->assertStringContainsString( 'page=sureforms_menu', $not_set_up );

		$this->use_plugins( [ 'fluent-smtp/fluent-smtp.php' ] );
		Admin::reset_form_setup_card_cache();
		ob_start();
		Admin::get_instance()->render_form_setup_widget();
		$handled = (string) ob_get_clean();

		$this->assertStringContainsString( 'Emails are sent through FluentSMTP', $handled );
		$this->assertStringNotContainsString( 'data-srfm-event="email_delivery"', $handled, 'Nothing to do, so no button.' );
	}

	/**
	 * SureMail sending through SureContact, with a cached quota.
	 *
	 * @param int    $used   Emails used.
	 * @param int    $limit  Emails in the plan.
	 * @param string $period SureContact's cap period.
	 */
	private function surecontact_with_quota( $used, $limit, $period = 'monthly' ) {
		$this->use_plugins( [ 'suremails/suremails.php' ] );
		update_option(
			Suremail_Adapter::CONNECTIONS_OPTION,
			[
				'connections'        => [
					'sc1' => [
						'type'           => 'SURECONTACT',
						'priority'       => 1,
						'email_verified' => true,
						'is_paid'        => false,
					],
				],
				'default_connection' => [ 'id' => 'sc1' ],
			]
		);
		set_transient(
			Suremail_Adapter::QUOTA_TRANSIENT,
			[
				'known'      => true,
				'used'       => $used,
				'limit'      => $limit,
				'remaining'  => max( 0, $limit - $used ),
				'period'     => $period,
				'checked_at' => time(),
			],
			HOUR_IN_SECONDS
		);
		Status::reset();
		Admin::reset_action_items_cache();
	}

	private function on_entries_screen() {
		$_GET['page']     = SRFM_ENTRIES;
		$_REQUEST['page'] = SRFM_ENTRIES;
		Admin::reset_action_items_cache();
	}

	public function test_quota_warning_when_free_emails_are_almost_used_up() {
		$this->surecontact_with_quota( 85, 100 );
		$this->on_sureforms_dashboard();

		$item = $this->items()['email_delivery_quota'] ?? [];

		$this->assertSame( 'warning', $item['status'] ?? '' );
		$this->assertTrue( $item['dismissible'] ?? false );
		$this->assertSame( 'Your free SureContact emails are almost used up', $item['title'] ?? '' );
		$this->assertStringContainsString( '85 of 100 emails used this month', $item['message'] ?? '' );
		$this->assertStringContainsString( 'source=sureforms', $item['cta_url'] ?? '' );
		$this->assertSame( 'open_suremail', $item['cta_action'] ?? '' );
	}

	public function test_quota_warning_is_also_on_entries_but_not_elsewhere() {
		$this->surecontact_with_quota( 90, 100 );

		\SRFM\Admin\Notice_Manager::clear_notices();
		$this->on_entries_screen();
		$this->assertArrayHasKey( 'email_delivery_quota', $this->items() );
		$notices = wp_list_pluck( \SRFM\Admin\Notice_Manager::get_notices(), 'pages', 'id' );
		$this->assertSame( [ SRFM_ENTRIES ], $notices['srfm-email_delivery_quota'] ?? null, 'Entries hides classic notices, so it gets the React notice.' );
		\SRFM\Admin\Notice_Manager::clear_notices();

		$this->on_sureforms_dashboard( false );
		$this->assertArrayNotHasKey( 'email_delivery_quota', $this->items() );
	}

	public function test_no_quota_warning_with_plenty_left() {
		$this->surecontact_with_quota( 40, 100 );
		$this->on_sureforms_dashboard();

		$this->assertArrayNotHasKey( 'email_delivery_quota', $this->items() );
	}

	public function test_used_up_quota_is_an_error_that_cannot_be_dismissed() {
		$this->surecontact_with_quota( 100, 100, 'lifetime' );
		Helper::update_srfm_option( 'dismissed_action_items', [ 'email_delivery_quota' ] );
		$this->on_sureforms_dashboard();

		$item = $this->items()['email_delivery_quota'] ?? [];

		$this->assertSame( 'error', $item['status'] ?? '' );
		$this->assertFalse( $item['dismissible'] ?? true );
		$this->assertStringContainsString( 'All 100 emails are used', $item['message'] ?? '' );
	}

	public function test_quota_dismissal_lasts_until_the_quota_recovers() {
		$this->surecontact_with_quota( 85, 100 );
		$this->on_sureforms_dashboard();

		$dismiss = new ReflectionMethod( Admin::class, 'dismiss_action_item' );
		$dismiss->setAccessible( true );
		$this->assertTrue( $dismiss->invoke( Admin::get_instance(), 'email_delivery_quota' ) );

		Admin::reset_action_items_cache();
		$this->assertArrayNotHasKey( 'email_delivery_quota', $this->items() );

		// A new month: plenty left again, so the dismissal is forgotten.
		$this->surecontact_with_quota( 5, 100 );
		$this->on_sureforms_dashboard();
		$this->items();
		$this->assertNotContains( 'email_delivery_quota', Helper::get_srfm_option( 'dismissed_action_items', [] ) );

		$this->surecontact_with_quota( 95, 100 );
		$this->on_sureforms_dashboard();
		$this->assertArrayHasKey( 'email_delivery_quota', $this->items() );
	}

	public function test_quota_warning_is_hidden_with_promotions() {
		$this->surecontact_with_quota( 95, 100 );
		$this->on_sureforms_dashboard();
		add_filter( 'srfm_hide_promotions', '__return_true' );

		$this->assertArrayNotHasKey( 'email_delivery_quota', $this->items() );
	}

	public function test_unverified_surecontact_failure_advises_verifying() {
		$this->use_plugins( [ 'suremails/suremails.php' ] );
		update_option(
			Suremail_Adapter::CONNECTIONS_OPTION,
			[
				'connections'        => [
					'sc1' => [
						'type'           => 'SURECONTACT',
						'priority'       => 1,
						'email_verified' => false,
					],
				],
				'default_connection' => [ 'id' => 'sc1' ],
			]
		);
		$form_id = $this->make_form();
		// Connected a minute before the failure, as on a real site.
		update_option( Status::OUTCOMES_OPTION, [ 'suremail_connected_at' => time() - 60 ] );
		Status::reset();
		Client_Logger::record_failure( 'notification', $form_id, 'Contact Form' );
		Status::record_outcome( false, $form_id, 'mailer_rejected' );
		Helper::update_srfm_option( 'dismissed_action_items', [ 'email_delivery_limited' ] );
		$this->on_sureforms_dashboard();

		$items = $this->items();
		$item  = $items['email_delivery_limited'] ?? [];

		$this->assertSame( 'Verify your SureContact email to keep sending', $item['title'] ?? '' );
		$this->assertSame( 'Verify in SureMail', $item['cta_label'] ?? '' );
		$this->assertFalse( $item['dismissible'] ?? true, 'Mail is failing, so it stays until fixed.' );
		$this->assertArrayHasKey( 'notification_error', $items, 'The failure itself is still reported.' );
		$this->assertArrayNotHasKey( 'email_delivery_setup', $items );
	}
}
