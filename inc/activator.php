<?php
/**
 * Sureforms flush rewrite rules.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Global_Settings\Email_Summary;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Activation Class.
 *
 * @since 0.0.1
 */
class Activator {
	use Get_Instance;

	/**
	 * Activation handler function.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public static function activate() {
		self::create_tables();

		$email_summary_options = get_option( 'srfm_email_summary_settings_options' );
		$enable_email_summary  = is_array( $email_summary_options ) ? $email_summary_options['srfm_email_summary'] : '';

		if ( $enable_email_summary ) {
			Email_Summary::schedule_weekly_entries_email();
		}

		/**
		 * Reset rewrite rules to avoid go to permalinks page
		 * through deleting the database options to force WP to do it
		 * because of on activation not work well flush_rewrite_rules()
		 */
		delete_option( 'rewrite_rules' );

		update_option( '__srfm_do_redirect', true );

	}

	/**
	 * Create necessary table tables on plugin activation.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected static function create_tables() {
		Entries::get_instance()->create(
			[
				'ID BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY',
				'form_id BIGINT(20) UNSIGNED',
				'user_data LONGTEXT',
				'logs LONGTEXT',
				'notes LONGTEXT',
				'submission_info LONGTEXT',
				'status VARCHAR(10)',
				'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
				'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			]
		);
	}
}
