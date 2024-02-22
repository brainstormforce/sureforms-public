<?php
/**
 * Sureforms flush rewrite rules.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

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

		$email_summary_options = get_option( 'srfm_email_summary_options' );
		$enable_email_summary  = is_array( $email_summary_options ) ? $email_summary_options['enable_email_summary'] : '';

		if ( $enable_email_summary ) {
			$email_summaries = new Email_Summaries();
			$email_summaries->schedule_weekly_entries_email();
		}

		/**
		 * Reset rewrite rules to avoid go to permalinks page
		 * through deleting the database options to force WP to do it
		 * because of on activation not work well flush_rewrite_rules()
		 */
		delete_option( 'rewrite_rules' );

		update_option( '__sureforms_do_redirect', true );

	}
}
