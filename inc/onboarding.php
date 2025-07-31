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
	 * Onboarding completion setting key.
	 *
	 * @var string
	 */
	private $onboarding_status_key = 'onboarding_completed';

	/**
	 * Set onboarding completion status.
	 *
	 * @since 1.9.1
	 * @param string $completed Whether the onboarding is completed.
	 * @return void
	 */
	public function set_onboarding_status( $completed = 'no' ) {
		Helper::update_srfm_option( $this->onboarding_status_key, $completed );
	}

	/**
	 * Get onboarding completion status.
	 *
	 * @since 1.9.1
	 * @return bool
	 */
	public function get_onboarding_status() {
		return Helper::get_srfm_option( $this->onboarding_status_key, 'no' ) === 'yes';
	}
}
