<?php
/**
 * Onboarding Class
 *
 * Handles the onboarding process for the SureForms plugin.
 *
 * @package SRFM\Inc
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
 *
 * @package SRFM\Inc\Onboarding
 */
class Onboarding {
	use Get_Instance;

	/**
	 * Onboarding completion setting.
	 *
	 * @var string
	 */
	private $onboarding_status_option = 'sureforms_onboarding_completed';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Constructor can be used for future initialization if needed
	}

	/**
	 * Set onboarding completion status.
	 *
	 * @since 1.7.0
	 * @param string $completed Whether the onboarding is completed.
	 * @return bool
	 */
	public function set_onboarding_status( $completed = 'no' ) {
		return update_option( $this->onboarding_status_option, $completed );
	}

	/**
	 * Get onboarding completion status.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function get_onboarding_status() {
		return get_option( $this->onboarding_status_option, 'no' ) === 'yes';
	}
}
