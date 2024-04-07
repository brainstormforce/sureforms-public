<?php
/**
 * SureForms events scheduler class.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

/**
 * SureForms events scheduler class.
 *
 * @since x.x.x
 */
class Events_Scheduler {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'srfm_schedule_daily_action' ] );
	}

	/**
	 * Schedules a action that runs every 24 hours for SureForms.
	 *
	 * @hooked - init
	 * @since x.x.x
	 * @return void
	 */
	public function srfm_schedule_daily_action() {
		// Check if the action is not scheduled. Then schedule a new action.
		if ( false === as_has_scheduled_action( 'srfm_daily_scheduled_action' ) ) {
			// Shedule a recurring action srfm_daily_scheduled_events that runs every 24 hours.
			as_schedule_recurring_action( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'srfm_daily_scheduled_action', [], 'sureforms', true );
		}
	}

}
