<?php
/**
 * Onboarding Class
 *
 * Handles the onboarding process for the SureForms plugin.
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Onboarding Class
 *
 * Handles the onboarding process for the SureForms plugin.
 */
class Onboarding {
	use Get_Instance;

	/**
	 * Onboarding completion setting.
	 *
	 * @var string
	 */
	private $onboarding_status_option = 'srfm_onboarding_completed';

	/**
	 * Set onboarding completion status.
	 *
	 * @since x.x.x
	 * @param string $completed Whether the onboarding is completed.
	 * @return bool
	 */
	public function set_onboarding_status( $completed = 'no' ) {
		return update_option( $this->onboarding_status_option, $completed );
	}

	/**
	 * Get onboarding completion status.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function get_onboarding_status() {
		return get_option( $this->onboarding_status_option, 'no' ) === 'yes';
	}
}
