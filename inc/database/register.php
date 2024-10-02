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
 * @since 0.0.10
 */
class Register {

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
				'INDEX idx_form_id (form_id)', // Indexing for the performance improvements.
				'INDEX idx_form_id_created_at_status (form_id, created_at, status)', // Composite index for performance improvements.
			]
		);

		Entries::get_instance()->maybe_add_new_columns(
			[
				'user_id BIGINT(20) UNSIGNED AFTER form_id',
				'INDEX idx_user_id (user_id)'
			]
		);
	}
}
