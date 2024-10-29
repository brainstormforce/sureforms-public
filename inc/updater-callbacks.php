<?php
/**
 * SureForms Updater Callbacks.
 * Provides static methods for the updater class.
 *
 * @package sureforms.
 * @since 1.0.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updater class.
 *
 * @since 1.0.0
 */
class Updater_Callbacks {

	/**
	 * Update callback method to handle the entries migration admin notice for existing users.
	 *
	 * @param string $old_version SureForms Old saved version.
	 * @since 1.0.0
	 * @return void
	 */
	public static function manage_entries_migrate_admin_notice( $old_version ) {
		$has_legacy_entries = ! empty( get_posts( [ 'post_type' => 'sureforms_entry' ] ) );

		// If we have don't have legacy entries then save it as 'hide' to hide the admin notice automatically.
		update_option( 'srfm_dismiss_entries_migration_notice', empty( $old_version ) && $has_legacy_entries ? 'show' : 'hide' );
	}

	/**
	 * Update callback method to handle the default dynamic block options in the global settings.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function manage_default_dynamic_options() {

		$previous_options = get_option( 'get_default_dynamic_block_option' );

		if ( ! empty( $previous_options ) && is_array( $previous_options ) ) {
			update_option( 'srfm_default_dynamic_block_option', $previous_options );
			delete_option( 'get_default_dynamic_block_option' );
		}
	}

}
