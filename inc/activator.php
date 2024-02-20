<?php
/**
 * Sureforms flush rewrite rules.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

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

		/**
		 * Reset rewrite rules to avoid go to permalinks page
		 * through deleting the database options to force WP to do it
		 * because of on activation not work well flush_rewrite_rules()
		 */
		delete_option( 'rewrite_rules' );

		update_option( '__srfm_do_redirect', true );

	}
}
