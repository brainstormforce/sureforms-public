<?php
/**
 * SureForms events scheduler class.
 *
 * @package sureforms.
 * @since 0.0.2
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

/**
 * SureForms events scheduler class.
 *
 * @since 0.0.2
 */
class Events_Scheduler {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.2
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'srfm_schedule_daily_action' ] );
	}

	/**
	 * Schedules a action that runs every 24 hours for SureForms.
	 *
	 * @hooked - init
	 * @uses as_has_scheduled_action() To check if the action is already scheduled.
	 * @uses as_schedule_recurring_action() To schedule a recurring action.
	 * @link https://actionscheduler.org/api/
	 * @since 0.0.2
	 * @return void
	 */
	public function srfm_schedule_daily_action() {
		// Check if the action is not scheduled. Then schedule a new action.
		if ( false === as_has_scheduled_action( 'srfm_daily_scheduled_action' ) ) {
			// Shedule a recurring action srfm_daily_scheduled_events that runs every 24 hours.
			as_schedule_recurring_action( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'srfm_daily_scheduled_action', [], 'sureforms', true );
		}
	}

	/**
	 * Unschedule any action.
	 *
	 * @param string $hook Event hook name.
	 * @since 0.0.2
	 * @return void
	 */
	public static function unschedule_events( $hook ) {
		as_unschedule_all_actions( $hook );
	}

}
