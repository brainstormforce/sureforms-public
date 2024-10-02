<?php
/**
 * SureForms Database Entires Table Class.
 *
 * @link       https://sureforms.com
 * @since      0.0.10
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database\Tables;

use SRFM\Inc\Database\Base;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Entires Table Class.
 *
 * @since 0.0.10
 */
class Entries extends Base {
	use Get_Instance;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $table_suffix = 'entries';

	/**
	 * {@inheritDoc}
	 *
	 * @var int
	 */
	protected $table_version = 1;

	/**
	 * Current logs.
	 *
	 * @var array<array<string,mixed>> $logs
	 * The structure of each log entry is:
	 * [
	 *     'title'    => string,
	 *     'messages' => array<string>,
	 *     'timestamp' => int
	 * ]
	 *
	 * @since 0.0.10
	 */
	private $logs = [];

	/**
	 * {@inheritDoc}
	 */
	public function get_schema() {
		return [
			// Entry ID.
			'ID'              => [
				'type' => 'number',
			],
			// Submitted form ID.
			'form_id'         => [
				'type' => 'number',
			],
			// Current entry status: ['read', 'unread'].
			'status'          => [
				'type'    => 'string',
				'default' => 'unread',
			],
			// Submitted form data by user.
			'user_data'       => [
				'type'    => 'array',
				'default' => [],
			],
			// Additional information about the current submitted data: i.e: Browser type, device etc.
			'submission_info' => [
				'type'    => 'array',
				'default' => [],
			],
			// Any additional notes that is added by the admin.
			'notes'           => [
				'type'    => 'array',
				'default' => [],
			],
			// Entry activities logs.
			'logs'            => [
				'type'    => 'array',
				'default' => [],
			],
		];
	}

	/**
	 * Retrieve the key of the last log entry.
	 *
	 * @since 0.0.10
	 * @return int|null The key of the last log entry if logs exist, or null if no logs are present.
	 */
	public function get_last_log_key() {
		$key = array_key_last( $this->logs );
		return is_int( $key ) ? $key : null;
	}

	/**
	 * Add a new log entry.
	 *
	 * @param string   $title The title of the log entry.
	 * @param string[] $messages Optional. An array of messages to include in the log entry. Default is an empty array.
	 * @since 0.0.10
	 * @return int|null The key of the newly added log entry, or null if the log could not be added.
	 */
	public function add_log( $title, $messages = [] ) {
		$this->logs[] = [
			'title'     => Helper::get_string_value( trim( $title ) ),
			'messages'  => Helper::get_array_value( $messages ),
			'timestamp' => time(),
		];

		return $this->get_last_log_key();
	}

	/**
	 * Update an existing log entry.
	 *
	 * @param int         $log_key The key of the log entry to update.
	 * @param string|null $title Optional. The new title for the log entry. If null, the title will not be changed.
	 * @param string[]    $messages Optional. An array of new messages to add to the log entry.
	 * @since 0.0.10
	 * @return int|null The key of the updated log entry, or null if the log entry does not exist.
	 */
	public function update_log( $log_key, $title = null, $messages = [] ) {
		if ( empty( $this->logs[ $log_key ] ) ) {
			return null;
		}

		$logs = $this->logs;

		$logs[ $log_key ]['title']    = ! is_null( $title ) ? Helper::get_string_value( trim( $title ) ) : $logs[ $log_key ]['title'];
		$logs[ $log_key ]['messages'] = array_merge( Helper::get_array_value( $logs[ $log_key ]['messages'] ), Helper::get_array_value( $messages ) );

		$this->logs = $logs;
		return $log_key;
	}

	/**
	 * Retrieve all log entries.
	 *
	 * @since 0.0.10
	 * @return array<array<string,mixed>>
	 */
	public function get_logs() {
		return $this->logs;
	}

	/**
	 * Add a new entry to the database.
	 *
	 * @param array<mixed> $data An associative array of data for the new entry. Must include 'form_id'.
	 *                    If 'ID' is set, it will be removed before inserting.
	 * @since 0.0.10
	 * @return int|false The number of rows inserted, or false if the insertion fails.
	 */
	public static function add( $data ) {
		if ( empty( $data['form_id'] ) ) {
			return false;
		}

		if ( isset( $data['ID'] ) ) {
			// Unset ID if exists because we are creating a new entry, not updating.
			unset( $data['ID'] );
		}

		$instance = self::get_instance();

		if ( ! isset( $data['logs'] ) ) {
			// Add default logs if no logs provided.
			$data['logs'] = $instance->get_logs();
		}

		return $instance->insert( $data );
	}

	/**
	 * Retrieve a specific entry from the database.
	 *
	 * @param int $entry_id The ID of the entry to retrieve.
	 * @since 0.0.10
	 * @return array<mixed> An associative array representing the entry, or an empty array if no entry is found.
	 */
	public static function get( $entry_id ) {
		$results = self::get_instance()->get_results(
			[
				'ID' => $entry_id,
			]
		);

		return isset( $results[0] ) ? Helper::get_array_value( $results[0] ) : [];
	}
}
