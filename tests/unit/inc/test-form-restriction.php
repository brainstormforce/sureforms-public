<?php
/**
 * Class Test_Form_Restriction
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Restriction;

class Test_Form_Restriction extends TestCase {

	protected static $form_id = 1;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// Create a form post.
		self::$form_id = wp_insert_post([
			'post_type'   => 'sureform', // Adjust if different
			'post_title'  => 'Test Restriction Form',
			'post_status' => 'publish',
		]);

		// Set restriction meta
		update_post_meta(self::$form_id, '_srfm_form_restriction', wp_json_encode([
			'status'     => true,
			'maxEntries' => 9999,
			'date'       => "2025-08-01", // Past date
			'hours'      => '12',
			'minutes'    => '00',
			'meridiem'   => 'AM',
			'message'    => 'Time limit reached.',
		]));
	}

	public function test_has_entries_limit_reached_returns_false_by_default() {
		$restriction = Form_Restriction::get_form_restriction_setting(self::$form_id);
		$result = Form_Restriction::has_entries_limit_reached(self::$form_id, $restriction);

		$this->assertFalse($result);
	}

	public function test_is_form_restricted_returns_true_when_time_limit_passed() {
		// Override the meta with past date
		update_post_meta(self::$form_id, '_srfm_form_restriction', wp_json_encode([
			'status'           => true,
			'schedulingStatus' => true,
			'maxEntries'       => 9999,
			'date'             => "2025-08-01", // Past date
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'AM',
			'message'          => 'Time limit reached.',
		]));

		$this->assertTrue(Form_Restriction::is_form_restricted(self::$form_id));
	}

	/**
	 * Test get_form_scheduling_state() when scheduling is disabled.
	 */
	public function test_get_form_scheduling_state_returns_disabled_when_scheduling_not_enabled() {
		$form_restriction = [
			'schedulingStatus' => false,
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'disabled', $result );
	}

	/**
	 * Test get_form_scheduling_state() when scheduling is disabled but end date passed.
	 * When schedulingStatus is false, end date is not checked, so it returns 'disabled'.
	 */
	public function test_get_form_scheduling_state_returns_disabled_when_scheduling_disabled_even_with_end_date_passed() {
		$form_restriction = [
			'schedulingStatus' => false,
			'date'             => '2020-01-01', // Past date
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'AM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'disabled', $result );
	}

	/**
	 * Test get_form_scheduling_state() when current time is before start time.
	 */
	public function test_get_form_scheduling_state_returns_not_started_before_start_time() {
		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => '2030-01-01', // Future date
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => '2030-12-31', // Future end date
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'not_started', $result );
	}

	/**
	 * Test get_form_scheduling_state() when current time is after end time.
	 */
	public function test_get_form_scheduling_state_returns_ended_after_end_time() {
		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => '2020-01-01', // Past date
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => '2020-12-31', // Past end date
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'ended', $result );
	}

	/**
	 * Test get_form_scheduling_state() when current time is within the schedule.
	 */
	public function test_get_form_scheduling_state_returns_active_within_schedule() {
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$tomorrow  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => $yesterday,
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => $tomorrow,
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'active', $result );
	}

	/**
	 * Test get_form_scheduling_state() with only end date (no start date).
	 */
	public function test_get_form_scheduling_state_with_only_end_date_in_future() {
		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$form_restriction = [
			'schedulingStatus' => true,
			'date'             => $tomorrow,
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'active', $result );
	}

	/**
	 * Test get_form_scheduling_state() handles 12 AM correctly.
	 */
	public function test_get_form_scheduling_state_handles_12am_correctly() {
		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$form_restriction = [
			'schedulingStatus' => true,
			'date'             => $tomorrow,
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'AM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'active', $result );
	}

	/**
	 * Test get_form_scheduling_state() handles 12 PM correctly.
	 */
	public function test_get_form_scheduling_state_handles_12pm_correctly() {
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$form_restriction = [
			'schedulingStatus' => true,
			'date'             => $yesterday,
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'ended', $result );
	}

	/**
	 * Test get_form_scheduling_state() with empty end date.
	 */
	public function test_get_form_scheduling_state_with_empty_end_date() {
		$form_restriction = [
			'schedulingStatus' => true,
			'date'             => '',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		$this->assertEquals( 'active', $result );
	}

	/**
	 * Test get_form_scheduling_state() with invalid date format.
	 */
	public function test_get_form_scheduling_state_with_invalid_date_format() {
		$form_restriction = [
			'schedulingStatus' => true,
			'date'             => 'invalid-date',
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::get_form_scheduling_state( $form_restriction );

		// Should return 'active' if date parsing fails
		$this->assertEquals( 'active', $result );
	}

	/**
	 * Test get_restriction_message_by_state returns a message string.
	 */
	public function test_get_restriction_message_by_state() {
		$form_restriction = [
			'message'         => 'Custom message.',
			'notStartMessage' => 'Not started yet.',
		];
		$result = Form_Restriction::get_restriction_message_by_state( 'ended', $form_restriction );
		$this->assertIsString( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns false when scheduling is disabled.
	 */
	public function test_is_form_outside_schedule_returns_false_when_disabled() {
		$form_restriction = [
			'schedulingStatus' => false,
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns true when not started.
	 */
	public function test_is_form_outside_schedule_returns_true_when_not_started() {
		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => '2030-01-01', // Future date
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => '2030-12-31',
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns true when ended.
	 */
	public function test_is_form_outside_schedule_returns_true_when_ended() {
		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => '2020-01-01', // Past date
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => '2020-12-31', // Past end date
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns false when active.
	 */
	public function test_is_form_outside_schedule_returns_false_when_active() {
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$tomorrow  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$form_restriction = [
			'schedulingStatus' => true,
			'startDate'        => $yesterday,
			'startHours'       => '10',
			'startMinutes'     => '00',
			'startMeridiem'    => 'AM',
			'date'             => $tomorrow,
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns false when scheduling is disabled.
	 * When schedulingStatus is false, end date is not checked for scheduling purposes.
	 */
	public function test_is_form_outside_schedule_returns_false_when_scheduling_disabled_even_with_past_date() {
		$form_restriction = [
			'schedulingStatus' => false,
			'date'             => '2020-01-01', // Past date
			'hours'            => '12',
			'minutes'          => '00',
			'meridiem'         => 'AM',
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_form_outside_schedule() returns false when simple time limit not reached.
	 */
	public function test_is_form_outside_schedule_returns_false_when_simple_time_limit_not_reached() {
		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$form_restriction = [
			'schedulingStatus' => false,
			'date'             => $tomorrow,
			'hours'            => '11',
			'minutes'          => '59',
			'meridiem'         => 'PM',
		];

		$result = Form_Restriction::is_form_outside_schedule( $form_restriction );

		$this->assertFalse( $result );
	}

}
