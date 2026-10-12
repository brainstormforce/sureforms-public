<?php
/**
 * Shared fixtures for the email delivery tests.
 *
 * SureMail is never loaded in the suite, so it is "activated" through the
 * active_plugins option and its storage is written directly, in the shape
 * SureMail 2.0.x writes.
 *
 * @package sureforms
 */

use SRFM\Inc\Client_Logger;
use SRFM\Inc\Email_Delivery\Status;
use SRFM\Inc\Email_Delivery\Suremail_Adapter;

trait SRFM_Email_Delivery_Fixtures {

	protected function reset_email_delivery_state() {
		remove_all_filters( 'pre_option_active_plugins' );
		remove_all_filters( 'srfm_smtp_plugins' );
		remove_all_filters( 'srfm_active_mailer' );
		remove_all_filters( 'srfm_email_delivery_status' );
		remove_all_filters( 'suremails_delivery_readiness' );
		remove_all_filters( 'srfm_hide_promotions' );
		delete_option( Status::OUTCOMES_OPTION );
		delete_option( Suremail_Adapter::CONNECTIONS_OPTION );
		delete_option( Suremail_Adapter::ONBOARDING_OPTION );
		delete_option( Client_Logger::FAILURES_OPTION );
		delete_transient( Suremail_Adapter::QUOTA_TRANSIENT );
		wp_clear_scheduled_hook( Suremail_Adapter::QUOTA_REFRESH_HOOK );
		Status::reset();
	}

	protected function activate_plugins( array $plugins ) {
		remove_all_filters( 'pre_option_active_plugins' );
		add_filter(
			'pre_option_active_plugins',
			static function () use ( $plugins ) {
				return $plugins;
			}
		);
		Status::reset();
	}

	protected function set_suremail_connections( array $connections, $default_id = '' ) {
		update_option(
			Suremail_Adapter::CONNECTIONS_OPTION,
			[
				'connections'        => $connections,
				'default_connection' => [
					'type'             => '',
					'email'            => '',
					'id'               => $default_id,
					'connection_title' => '',
				],
				'log_emails'         => 'yes',
			]
		);
		Status::reset();
	}

	protected function surecontact_row( $verified ) {
		$row = [
			'type'             => 'SURECONTACT',
			'connection_title' => 'SureContact',
			'from_email'       => 'owner@example.com',
			'priority'         => 1,
			'id'               => 'sc1',
		];
		if ( null !== $verified ) {
			$row['email_verified'] = $verified;
		}
		return $row;
	}
}
