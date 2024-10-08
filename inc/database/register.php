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
 * @since x.x.x
 */
class Register {

	/**
	 * Init database registration.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function init() {
		self::create_entries_table();
	}

	/**
	 * Create entries table.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected static function create_entries_table() {
		$entries = Entries::get_instance();

		$entries->start_db_upgrade();

		// Create new table if does not exists.
		$entries->create(
			[
				'ID BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY',
				'form_id BIGINT(20) UNSIGNED',
				'user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0',
				'form_data LONGTEXT',
				'logs LONGTEXT',
				'notes LONGTEXT',
				'submission_info LONGTEXT',
				'status VARCHAR(10)',
				'extras LONGTEXT',
				'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
				'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
				'INDEX idx_form_id (form_id)', // Indexing for the performance improvements.
				'INDEX idx_user_id (user_id)',
				'INDEX idx_form_id_created_at_status (form_id, created_at, status)', // Composite index for performance improvements.
			]
		);

		// Add new column to existing table.
		$entries->maybe_add_new_columns(
			[
				'extras LONGTEXT AFTER status',
				'user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER form_id',
				'INDEX idx_user_id (user_id)',
			]
		);

		// Rename columns of existing table.
		$entries->maybe_rename_columns(
			[
				[
					'from' => 'user_data',
					'to'   => 'form_data',
				],
			]
		);

		$entries->stop_db_upgrade();
	}
}
