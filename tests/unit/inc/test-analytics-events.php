<?php
/**
 * Class Test_Analytics_Events
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Helper;

class Test_Analytics_Events extends TestCase {

	/**
	 * Clean up analytics options before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Helper::update_srfm_option( 'usage_events_pending', [] );
		Helper::update_srfm_option( 'usage_events_pushed', [] );
	}

	/**
	 * Get a BSF_Analytics_Events instance configured for SureForms.
	 *
	 * @return \BSF_Analytics_Events
	 */
	private function get_events_instance() {
		if ( ! class_exists( 'BSF_Analytics_Events' ) ) {
			require_once SRFM_DIR . 'inc/lib/bsf-analytics/class-bsf-analytics-events.php';
		}

		return new \BSF_Analytics_Events(
			'sureforms',
			[
				'get'    => [ Helper::class, 'get_srfm_option' ],
				'update' => [ Helper::class, 'update_srfm_option' ],
			]
		);
	}

	/**
	 * Test flush_pending returns empty array when no events are pending.
	 */
	public function test_flush_pending_returns_empty_when_no_events() {
		$events = $this->get_events_instance();
		$result = $events->flush_pending();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test flush_pending returns pending events and clears the queue.
	 */
	public function test_flush_pending_returns_and_clears_pending_events() {
		$events = $this->get_events_instance();
		$events->track( 'test_event_1', 'v1' );
		$events->track( 'test_event_2', 'v2' );

		$result = $events->flush_pending();

		$this->assertCount( 2, $result );
		$this->assertEquals( 'test_event_1', $result[0]['event_name'] );
		$this->assertEquals( 'test_event_2', $result[1]['event_name'] );

		// Pending queue should be empty after flush.
		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$this->assertEmpty( $pending );
	}

	/**
	 * Test flush_pending adds event names to pushed dedup list.
	 */
	public function test_flush_pending_adds_to_pushed_dedup() {
		$events = $this->get_events_instance();
		$events->track( 'dedup_event', 'val' );

		$events->flush_pending();

		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$this->assertContains( 'dedup_event', $pushed );
	}

	/**
	 * Test flush_pending prevents re-tracking of flushed events.
	 */
	public function test_flush_pending_prevents_retracking() {
		$events = $this->get_events_instance();
		$events->track( 'once_event', 'v1' );
		$events->flush_pending();

		// Try tracking the same event again.
		$events->track( 'once_event', 'v2' );

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$this->assertEmpty( $pending );
	}

	/**
	 * Test flush_pushed clears all pushed events when called with no arguments.
	 */
	public function test_flush_pushed_clears_all_when_no_args() {
		$events = $this->get_events_instance();
		$events->track( 'ev1', 'v1' );
		$events->track( 'ev2', 'v2' );
		$events->flush_pending();

		// Verify pushed has entries.
		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$this->assertNotEmpty( $pushed );

		$events->flush_pushed();

		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$this->assertEmpty( $pushed );
	}

	/**
	 * Test flush_pushed removes only specified event names.
	 */
	public function test_flush_pushed_removes_specific_events() {
		$events = $this->get_events_instance();
		$events->track( 'keep_event', 'v1' );
		$events->track( 'remove_event', 'v2' );
		$events->flush_pending();

		$events->flush_pushed( [ 'remove_event' ] );

		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$this->assertContains( 'keep_event', $pushed );
		$this->assertNotContains( 'remove_event', $pushed );
	}

	/**
	 * Test flush_pushed allows re-tracking of cleared events.
	 */
	public function test_flush_pushed_allows_retracking() {
		$events = $this->get_events_instance();
		$events->track( 'retry_event', 'v1' );
		$events->flush_pending();

		// Clear the pushed dedup for this event.
		$events->flush_pushed( [ 'retry_event' ] );

		// Should be trackable again.
		$events->track( 'retry_event', 'v2' );

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$this->assertCount( 1, $pending );
		$this->assertEquals( 'retry_event', $pending[0]['event_name'] );
		$this->assertEquals( 'v2', $pending[0]['event_value'] );
	}

	/**
	 * Test flush_pushed handles empty pushed list gracefully.
	 */
	public function test_flush_pushed_handles_empty_list() {
		$events = $this->get_events_instance();
		$events->flush_pushed();

		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$this->assertIsArray( $pushed );
		$this->assertEmpty( $pushed );
	}
}
