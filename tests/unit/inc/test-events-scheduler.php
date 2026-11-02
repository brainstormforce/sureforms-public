<?php
/**
 * Class Test_Events_Scheduler
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Entries;
use SRFM\Inc\Events_Scheduler;

class Test_Events_Scheduler extends TestCase {

	public function test_unschedule_events_does_not_error() {
		// Unschedule a non-existent hook should not throw errors
		Events_Scheduler::unschedule_events( 'srfm_test_nonexistent_hook' );
		$this->assertTrue( true );
	}

	/**
	 * The daily action removes export files whose download never happened, so
	 * they do not wait for the next export to be swept.
	 */
	public function test_constructor_sweeps_stale_export_files_daily() {
		new Events_Scheduler();

		$stale = Entries::get_export_temp_filepath( 'srfm-entries-contact', 'csv' );
		file_put_contents( $stale, 'x' );
		touch( $stale, time() - DAY_IN_SECONDS - 60 );

		do_action( 'srfm_daily_scheduled_action' );

		$this->assertFileDoesNotExist( $stale );
	}
}
