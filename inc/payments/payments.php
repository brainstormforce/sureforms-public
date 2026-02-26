<?php
/**
 * SureForms Payments Main Class.
 *
 * @package sureforms
 * @since 2.0.0
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
 * @since 2.0.0
 */
class Payments {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			Admin_Handler::get_instance();
			Admin_Stripe_Handler::get_instance();
		}

		// Initialize Payments_Settings for both admin and REST API contexts.
		Payments_Settings::get_instance();

		Front_End::get_instance();
		Stripe_Webhook::get_instance();

		add_filter( 'srfm_ai_form_generator_body', [ $this, 'add_payment_version_to_ai_body' ], 10, 2 );
	}

	/**
	 * Add payment version to AI form generator body.
	 *
	 * @param array<mixed> $body   The body array for AI request.
	 * @param array<mixed> $params The request parameters.
	 * @since 2.0.0
	 * @return array<mixed> Modified body array.
	 */
	public function add_payment_version_to_ai_body( $body, $params ) {
		// Get the form type from params.
		$form_type = ! empty( $params['form_type'] ) && is_string( $params['form_type'] ) ? sanitize_text_field( $params['form_type'] ) : '';

		// If form type is payment, add version to body.
		if ( 'payment' === $form_type ) {
			$body['version'] = 'payment';
		}

		return $body;
	}
}
