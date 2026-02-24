<?php
/**
 * Analytics Events helper for one-time milestone tracking.
 *
 * Tracks events temporarily, sends them once via BSF Analytics,
 * then cleans up. Only a minimal dedup flag remains.
 *
 * @package sureforms.
 * @since 2.5.1
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Analytics Events Class.
 *
 * @since 2.5.1
 */
class Analytics_Events {
	/**
	 * Track a one-time event. Skips if already tracked or pending.
	 * Only stores temporary data — cleaned up after analytics send.
	 *
	 * @param string               $event_name  Event identifier.
	 * @param string               $event_value Primary value (version, form ID, mode, etc.).
	 * @param array<string, mixed> $properties Additional context as key-value pairs.
	 * @since 2.5.1
	 * @return void
	 */
	public static function track( $event_name, $event_value = '', $properties = [] ) {
		// Check dedup flag — already sent in a previous cycle.
		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$pushed = is_array( $pushed ) ? $pushed : [];
		if ( in_array( $event_name, $pushed, true ) ) {
			return;
		}

		// Check if already queued in current cycle.
		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$pending = is_array( $pending ) ? $pending : [];
		if ( in_array( $event_name, array_column( $pending, 'event_name' ), true ) ) {
			return;
		}

		// Add to pending queue.
		$pending[] = [
			'event_name'  => sanitize_text_field( $event_name ),
			'event_value' => sanitize_text_field( (string) $event_value ),
			'properties'  => ! empty( $properties ) ? $properties : new \stdClass(),
			'date'        => current_time( 'mysql' ),
		];
		Helper::update_srfm_option( 'usage_events_pending', $pending );
	}

	/**
	 * Flush pending events: returns them for the payload, then cleans up.
	 *
	 * After this call:
	 * - usage_events_pending is EMPTY (full event data deleted).
	 * - usage_events_pushed has event_name strings added (minimal dedup).
	 *
	 * @since 2.5.1
	 * @return array Pending events to include in payload. Empty if none.
	 */
	public static function flush_pending() {
		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		if ( empty( $pending ) || ! is_array( $pending ) ) {
			return [];
		}

		// Add event names to dedup flag (minimal — just strings).
		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$pushed = is_array( $pushed ) ? $pushed : [];
		$pushed = array_unique(
			array_merge( $pushed, array_column( $pending, 'event_name' ) )
		);
		Helper::update_srfm_option( 'usage_events_pushed', $pushed );

		// DELETE all temporary event data.
		Helper::update_srfm_option( 'usage_events_pending', [] );

		return $pending;
	}

	/**
	 * Remove specific event names from the pushed dedup flag, allowing them to be re-tracked.
	 *
	 * Pass an array of event names to remove only those entries.
	 * Pass an empty array (or omit) to clear all pushed events.
	 *
	 * @param array<string> $event_names Event names to remove. Empty = clear all.
	 * @since x.x.x
	 * @return void
	 */
	public static function flush_pushed( $event_names = [] ) {
		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$pushed = is_array( $pushed ) ? $pushed : [];

		if ( empty( $event_names ) ) {
			Helper::update_srfm_option( 'usage_events_pushed', [] );
			return;
		}

		$pushed = array_values( array_diff( $pushed, $event_names ) );
		Helper::update_srfm_option( 'usage_events_pushed', $pushed );
	}

	/**
	 * Check if an event has already been tracked (sent or pending).
	 *
	 * @param string $event_name Event identifier.
	 * @since 2.5.1
	 * @return bool
	 */
	public static function is_tracked( $event_name ) {
		$pushed = Helper::get_srfm_option( 'usage_events_pushed', [] );
		$pushed = is_array( $pushed ) ? $pushed : [];
		if ( in_array( $event_name, $pushed, true ) ) {
			return true;
		}

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$pending = is_array( $pending ) ? $pending : [];
		return in_array( $event_name, array_column( $pending, 'event_name' ), true );
	}
}
