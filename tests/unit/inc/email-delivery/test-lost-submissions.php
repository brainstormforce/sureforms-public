<?php
/**
 * Class Test_Lost_Submissions
 *
 * @package sureforms
 */

use SRFM\Admin\Admin;
use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Email_Delivery\Lost_Submissions;
use SRFM\Inc\Email_Delivery\Status;
use SRFM\Inc\Form_Submit;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Submissions lost on forms that never store entries.
 */
class Test_Lost_Submissions extends TestCase {

	/**
	 * Administrator for the Form Checks assertions.
	 *
	 * @var int
	 */
	private $admin_id = 0;

	protected function setUp(): void {
		parent::setUp();
		Lost_Submissions::acknowledge();
		delete_option( Status::OUTCOMES_OPTION );
		Status::reset();
		$this->admin_id = (int) wp_insert_user(
			[
				'user_login' => 'srfm_lost_' . wp_rand(),
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			]
		);
	}

	protected function tearDown(): void {
		Lost_Submissions::acknowledge();
		delete_option( Status::OUTCOMES_OPTION );
		remove_all_filters( 'srfm_enable_logs' );
		Status::reset();
		Admin::reset_action_items_cache();
		wp_set_current_user( 0 );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * Send the form's notification as a never-store submission.
	 *
	 * @param int      $form_id     Form id.
	 * @param callable $pre_wp_mail pre_wp_mail result.
	 */
	private function send_never_stored( $form_id, $pre_wp_mail ) {
		$parsed = static function () {
			return [
				'to'      => 'owner@example.com',
				'subject' => 'New entry',
				'message' => 'An entry arrived.',
				'headers' => [],
			];
		};
		add_filter( 'srfm_email_notification', $parsed, 99 );
		add_filter( 'pre_wp_mail', $pre_wp_mail, 99 );
		EntriesTable::get_instance()->reset_logs();
		Form_Submit::send_email( $form_id, [], [], [ 'entry_saved' => false ] );
		remove_filter( 'pre_wp_mail', $pre_wp_mail, 99 );
		remove_filter( 'srfm_email_notification', $parsed, 99 );
	}

	private function make_form() {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_title'  => 'No Storage Form',
				'post_status' => 'publish',
			]
		);
		update_post_meta(
			$form_id,
			'_srfm_email_notification',
			[
				[
					'id'     => 1,
					'status' => true,
				],
			]
		);
		return $form_id;
	}

	public function test_record() {
		Lost_Submissions::record( 4 );
		Lost_Submissions::record( 9 );
		Lost_Submissions::record( 4 );

		$lost = Lost_Submissions::get();
		$this->assertSame( 3, $lost['count'] );
		$this->assertSame( [ 9, 4 ], $lost['form_ids'], 'Each form once, most recent last.' );
		$this->assertGreaterThan( 0, $lost['last_at'] );
	}

	public function test_get() {
		$this->assertSame( 0, Lost_Submissions::get()['count'] );

		update_option( Lost_Submissions::OPTION, 'garbage' );
		$this->assertSame( 0, Lost_Submissions::get()['count'], 'Malformed storage reads as nothing lost.' );
	}

	public function test_acknowledge() {
		Lost_Submissions::record( 4 );
		Lost_Submissions::acknowledge();
		$this->assertSame( 0, Lost_Submissions::get()['count'] );
	}

	/**
	 * Vansh's case: SMTP is down, a never-store form loses a submission, then
	 * the next email goes through. The loss must still be reported.
	 */
	public function test_a_later_success_does_not_clear_a_lost_submission() {
		$form_id = $this->make_form();

		$this->send_never_stored( $form_id, '__return_false' );
		$this->send_never_stored( $form_id, '__return_true' );

		$this->assertSame( 1, Lost_Submissions::get()['count'] );

		wp_set_current_user( $this->admin_id );
		Admin::reset_action_items_cache();
		$ids = wp_list_pluck( Admin::get_instance()->get_action_items(), 'id' );
		$this->assertContains( 'lost_submissions', $ids );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Distraction Free turns logging off; a lost submission is still recorded
	 * and still shown.
	 */
	public function test_lost_submissions_are_recorded_with_logging_off() {
		add_filter( 'srfm_enable_logs', '__return_false' );
		$form_id = $this->make_form();

		$this->send_never_stored( $form_id, '__return_false' );
		$this->assertSame( 1, Lost_Submissions::get()['count'] );

		wp_set_current_user( $this->admin_id );
		Admin::reset_action_items_cache();
		$this->assertContains( 'lost_submissions', wp_list_pluck( Admin::get_instance()->get_action_items(), 'id' ) );

		wp_delete_post( $form_id, true );
	}

	/**
	 * A submission whose notification went out is not lost, even on a
	 * never-store form.
	 */
	public function test_a_sent_notification_is_not_a_lost_submission() {
		$form_id = $this->make_form();
		$this->send_never_stored( $form_id, '__return_true' );
		$this->assertSame( 0, Lost_Submissions::get()['count'] );
		wp_delete_post( $form_id, true );
	}

	/**
	 * "Ignore" acknowledges: the record is cleared, so the next loss shows.
	 */
	public function test_ignoring_the_notice_acknowledges_it() {
		Lost_Submissions::record( 4 );

		$dismiss = new ReflectionMethod( Admin::class, 'dismiss_action_item' );
		$dismiss->setAccessible( true );
		$this->assertTrue( $dismiss->invoke( Admin::get_instance(), 'lost_submissions' ) );
		$this->assertSame( 0, Lost_Submissions::get()['count'] );

		Lost_Submissions::record( 4 );
		wp_set_current_user( $this->admin_id );
		Admin::reset_action_items_cache();
		$this->assertContains( 'lost_submissions', wp_list_pluck( Admin::get_instance()->get_action_items(), 'id' ) );
	}
}
