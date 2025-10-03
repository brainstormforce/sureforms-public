<?php
/**
 * SureForms Payments Main Class.
 *
 * @package sureforms
 * @since 1.0.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Payments\Admin\Admin_Handler;
use SRFM\Inc\Payments\Stripe\Admin_Stripe_Handler;
use SRFM\Inc\Payments\Stripe\Payments_Settings;
use SRFM\Inc\Payments\Stripe\Stripe_Webhook;
use SRFM\Inc\Traits\Get_Instance;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payments Main Class.
 *
 * @since 1.0.0
 */
class Payments {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			Admin_Handler::get_instance();
			Admin_Stripe_Handler::get_instance();
			Payments_Settings::get_instance();
		}

		Front_End::get_instance();
		Stripe_Webhook::get_instance();
	}
}
