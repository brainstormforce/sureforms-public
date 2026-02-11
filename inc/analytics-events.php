<?php
/**
 * Analytics Events helper for one-time milestone tracking.
 *
 * Tracks events temporarily, sends them once via BSF Analytics,
 * then cleans up. Only a minimal dedup flag remains.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Analytics Events Class.
 *
 * @since x.x.x
 */
class Analytics_Events {

	/**
	 * Track a one-time event. Skips if already tracked or pending.
	 * Only stores temporary data — cleaned up after analytics send.
	 *
	 * @param string $event_name  Event identifier.
	 * @param string $event_value Primary value (version, form ID, mode, etc.).
	 * @param array  $properties  Additional context as key-value pairs.
	 * @since x.x.x
	 * @return void
	 */
	public static function track( $event_name, $event_value = '', $properties = [] ) {
		// Check dedup flag — already sent in a previous cycle.
		$tracked = Helper::get_srfm_option( 'newsage_events_tracked', [] );
		if ( in_array( $event_name, $tracked, true ) ) {
			return;
		}

		// Check if already queued in current cycle.
		$pending = Helper::get_srfm_option( 'newsage_events_pending', [] );
		if ( in_array( $event_name, array_column( $pending, 'event_name' ), true ) ) {
			return;
		}

		// Add to pending queue.
		$pending[] = [
			'event_name'  => sanitize_text_field( $event_name ),
			'event_value' => sanitize_text_field( (string) $event_value ),
			'properties'  => wp_json_encode( $properties ),
			'date'        => current_time( 'mysql' ),
		];
		Helper::update_srfm_option( 'newsage_events_pending', $pending );
	}

	/**
	 * Flush pending events: returns them for the payload, then cleans up.
	 *
	 * After this call:
	 * - newsage_events_pending is EMPTY (full event data deleted).
	 * - newsage_events_tracked has event_name strings added (minimal dedup).
	 *
	 * @since x.x.x
	 * @return array Pending events to include in payload. Empty if none.
	 */
	public static function flush_pending() {
		$pending = Helper::get_srfm_option( 'newsage_events_pending', [] );
		if ( empty( $pending ) || ! is_array( $pending ) ) {
			return [];
		}

		// Add event names to dedup flag (minimal — just strings).
		$tracked = Helper::get_srfm_option( 'newsage_events_tracked', [] );
		$tracked = array_unique(
			array_merge( $tracked, array_column( $pending, 'event_name' ) )
		);
		Helper::update_srfm_option( 'newsage_events_tracked', $tracked );

		// DELETE all temporary event data.
		Helper::update_srfm_option( 'newsage_events_pending', [] );

		return $pending;
	}

	/**
	 * Check if an event has already been tracked (sent or pending).
	 *
	 * @param string $event_name Event identifier.
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_tracked( $event_name ) {
		$tracked = Helper::get_srfm_option( 'newsage_events_tracked', [] );
		if ( in_array( $event_name, $tracked, true ) ) {
			return true;
		}

		$pending = Helper::get_srfm_option( 'newsage_events_pending', [] );
		return in_array( $event_name, array_column( $pending, 'event_name' ), true );
	}
}
