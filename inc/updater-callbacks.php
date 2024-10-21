<?php
/**
 * SureForms Updater Callbacks.
 * Provides static methods for the updater class.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updater class.
 *
 * @since x.x.x
 */
class Updater_Callbacks {

	/**
	 * Update callback method to handle the entries migration admin notice for existing users.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function manage_entries_migrate_admin_notice() {
		$has_legacy_entries = ! empty( get_posts( [ 'post_type' => 'sureforms_entry' ] ) );

		// If we have don't have legacy entries then save it as 'hide' to hide the admin notice automatically.
		update_option( 'srfm_dismiss_entries_migration_notice', $has_legacy_entries ? 'show' : 'hide' );
	}
}
