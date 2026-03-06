<?php
/**
 * Class Test_Events_Scheduler
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Events_Scheduler;

class Test_Events_Scheduler extends TestCase {

	public function test_unschedule_events_does_not_error() {
		// Unschedule a non-existent hook should not throw errors
		Events_Scheduler::unschedule_events( 'srfm_test_nonexistent_hook' );
		$this->assertTrue( true );
	}
}
