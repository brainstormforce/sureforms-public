<?php
/**
 * SureForms Database Tables Register Class.
 *
 * @link       https://sureforms.com
 * @since      0.0.10
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database;

use SRFM\Inc\Database\Tables\Entries;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Tables Register Class
 *
 * @since 0.0.13
 */
class Register {
	/**
	 * Init database registration.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public static function init() {
		/*
		 * ### Here, order is important. ###
		 * 1. Start the DB upgrade which also manages the internal versioning of each tables.
		 * 2. Init create method, which create the table if the table does not exists.
		 * 3. Init maybe_add_new_columns, it only runs if we have new columns definition and DB is upgradable ( has new version ).
		 * 4. Init maybe_rename_columns, it only runs if got any columns to rename and DB is upgradable ( has new version ).
		 * 5. Finally, stop the DB upgrade and update the current version in option table.
		 */
		foreach ( self::get_db_tables() as $instance ) {
			$instance->start_db_upgrade();

			if ( $instance->is_db_upgradable() ) {
				// Only execute below methods if DB is upgradable.
				$instance->create( $instance->get_columns_definition() );
				$instance->maybe_add_new_columns( $instance->get_new_columns_definition() );
				$instance->maybe_rename_columns( $instance->get_columns_to_rename() );
			}

			// Stop the upgrade process of current table and move to next.
			$instance->stop_db_upgrade();
		}
	}

	/**
	 * Returns an array of instances/objects of our custom tables.
	 *
	 * @since 0.0.13
	 * @return array<string,\SRFM\Inc\Database\Base>
	 */
	public static function get_db_tables() {
		return [
			'entries' => Entries::get_instance(),
		];
	}
}
