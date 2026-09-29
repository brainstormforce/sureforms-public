<?php
/**
 * Class Test_Analytics_AI_Starter
 *
 * Tests for the ai_starter_usage analytics event.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Admin\Analytics;
use SRFM\Inc\Helper;

/**
 * Tests for Analytics::track_ai_starter_usage().
 */
class Test_Analytics_AI_Starter extends TestCase {

	/**
	 * Reset counters and pending events before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Helper::update_srfm_option( 'ai_starter_usage', [] );
		Helper::update_srfm_option( 'usage_events_pushed', [] );
		Helper::update_srfm_option( 'usage_events_pending', [] );
	}

	/**
	 * Get the pending ai_starter_usage event, if any.
	 *
	 * @return array|null
	 */
	private function get_pending_event() {
		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		foreach ( (array) $pending as $event ) {
			if ( isset( $event['event_name'] ) && 'ai_starter_usage' === $event['event_name'] ) {
				return $event;
			}
		}
		return null;
	}

	public function test_free_text_prompt_counts_toward_total_only() {
		Analytics::track_ai_starter_usage( '', false );

		$event = $this->get_pending_event();
		$this->assertNotNull( $event );
		$this->assertSame( '0', $event['event_value'] );
		$this->assertSame(
			[
				'total_ai_forms' => '1',
				'edited'         => '0',
			],
			$event['properties']
		);
	}

	public function test_starter_usage_is_counted_per_card_with_edits() {
		Analytics::track_ai_starter_usage( 'contact', false );
		Analytics::track_ai_starter_usage( 'contact', true );
		Analytics::track_ai_starter_usage( 'feedback', false );
		Analytics::track_ai_starter_usage( '', false );

		$event = $this->get_pending_event();
		$this->assertSame( '3', $event['event_value'] );
		$this->assertSame( '4', $event['properties']['total_ai_forms'] );
		$this->assertSame( '1', $event['properties']['edited'] );
		$this->assertSame( '2', $event['properties']['starter_contact'] );
		$this->assertSame( '1', $event['properties']['starter_feedback'] );
	}

	public function test_latest_totals_replace_the_pending_event() {
		Analytics::track_ai_starter_usage( 'contact', false );
		Analytics::track_ai_starter_usage( 'contact', false );

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$names   = array_column( (array) $pending, 'event_name' );
		$this->assertSame( 1, count( array_keys( $names, 'ai_starter_usage', true ) ) );
		$this->assertSame( '2', $this->get_pending_event()['event_value'] );
	}

	public function test_distinct_starter_keys_are_capped() {
		for ( $i = 0; $i < 25; $i++ ) {
			Analytics::track_ai_starter_usage( 'starter-' . $i, false );
		}

		$usage = Helper::get_srfm_option( 'ai_starter_usage', [] );
		$this->assertCount( 20, $usage['starters'] );
		$this->assertSame( 25, $usage['total_ai_forms'] );
	}
}
