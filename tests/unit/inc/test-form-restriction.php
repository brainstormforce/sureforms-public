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
			'status'     => true,
			'maxEntries' => 9999,
			'date'       => "2025-08-01", // Past date
			'hours'      => '12',
			'minutes'    => '00',
			'meridiem'   => 'AM',
			'message'    => 'Time limit reached.',
		]));

		$this->assertTrue(Form_Restriction::is_form_restricted(self::$form_id));
	}

}
