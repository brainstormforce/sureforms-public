<?php
/**
 * SureForms Entries Table Class.
 *
 * @package sureforms.
 * @since 0.0.13
 */

namespace SRFM\Admin\Views;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WP_List_Table class exists and if not, load it.
 *
 * @since 0.0.13
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create the entries table using WP_List_Table.
 */
class Entries_List_Table extends \WP_List_Table {
	use Get_Instance;

	/**
	 * Stores the count for the entries data fetched from the database according to the status.
	 * It will be used for pagination.
	 *
	 * @var int
	 * @since 0.0.13
	 */
	public $entries_count;

	/**
	 * Stores the count for all entries regardles of status.
	 * It will be used for managing the no entries found page.
	 *
	 * @var int
	 * @since 0.0.13
	 */
	public $all_entries_count;

	/**
	 * Stores the count for the trashed entries.
	 * Used for displaying the no entries found page.
	 *
	 * @var int
	 * @since 0.0.13
	 */
	public $trash_entries_count;
	/**
	 * Stores the entries data fetched from database.
	 *
	 * @var array<mixed>
	 * @since 0.0.13
	 */
	protected $data = [];

	/**
	 * Constructor.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->all_entries_count   = Entries::get_total_entries_by_status( 'all' );
		$this->trash_entries_count = Entries::get_total_entries_by_status( 'trash' );
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @since 0.0.13
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'ID', 'sureforms' ),
			'form_name'   => __( 'Form Name', 'sureforms' ),
			'status'      => __( 'Status', 'sureforms' ),
			'first_field' => __( 'First Field', 'sureforms' ),
			'created_at'  => __( 'Submitted On', 'sureforms' ),
		];
	}

	/**
	 * Define the sortable columns.
	 *
	 * @since 0.0.13
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'id'         => [ 'ID', false ],
			'status'     => [ 'status', false ],
			'created_at' => [ 'created_at', false ],
		];
	}

	/**
	 * Bulk action items.
	 *
	 * @since 0.0.13
	 * @return array $actions Bulk actions.
	 */
	public function get_bulk_actions() {
		$bulk_actions = [
			'trash'  => __( 'Move to Trash', 'sureforms' ),
			'read'   => __( 'Mark as Read', 'sureforms' ),
			'unread' => __( 'Mark as Unread', 'sureforms' ),
			'export' => __( 'Export', 'sureforms' ),
		];

		return $this->get_additional_bulk_actions( $bulk_actions );
	}

	/**
	 * Message to be displayed when there are no entries.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function no_items() {

		printf(
			'<div class="sureforms-no-entries-found">%1$s</div>',
			esc_html__( 'No entries found.', 'sureforms' )
		);
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function prepare_items() {
		// If nonce verification fails, set the view to all else set the view to the view passed in the GET request.
		if ( ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) ) {
			$view    = 'all';
			$form_id = 0;
		} else {
			$view    = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';
			$form_id = isset( $_GET['form_filter'] ) ? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) ) : 0;
		}
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden   = [];

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$data = $this->table_data( $per_page, $current_page, $view, $form_id );

		$this->set_pagination_args(
			[
				'total_items' => $this->entries_count,
				'total_pages' => ceil( $this->entries_count / $per_page ),
				'per_page'    => $per_page,
			]
		);

		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $data;
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @param array  $item Column data.
	 * @param string $column_name Current column name.
	 *
	 * @since 0.0.13
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $this->column_id( $item );
			case 'form_name':
				return $this->column_form_name( $item );
			case 'status':
				return $this->column_status( $item );
			case 'first_field':
				return $this->column_first_field( $item );
			case 'created_at':
				return $this->column_created_at( $item );
			default:
				return;
		}
	}

	/**
	 * Callback function for checkbox field.
	 *
	 * @param array $item Columns items.
	 * @return string
	 * @since 0.0.13
	 */
	public function column_cb( $item ) {
		$entry_id = esc_attr( $item['ID'] );
		return sprintf(
			'<input type="checkbox" name="entry[]" value="%s" />',
			$entry_id
		);
	}

	/**
	 * Entries table form search input markup.
	 * Currently search is based on entry ID only and not text.
	 *
	 * @param string $text The 'submit' button label.
	 * @param int    $input_id ID attribute value for the search input field.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function search_box_markup( $text, $input_id ) {
		$input_id   .= '-search-input';
		$search_term = ! empty( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : ''; // phpcs:ignore -- Nonce verification is not required here as we are not doing any database work.
		?>
		<p class="search-box sureforms-form-search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<input type="search" name="search_filter" value="<?php echo esc_attr( $search_term ); ?>" class="sureforms-entries-search-box" id="<?php echo esc_attr( $input_id ); ?>">
			<button type="submit" class="button" id="search-submit"><?php echo esc_html( $text ); ?></button>
		</p>
		<?php
	}

	/**
	 * Displays the table.
	 *
	 * @since 0.0.13
	 */
	public function display() {
		$singular = $this->_args['singular'];
		$this->views();
		$this->search_box_markup( esc_html__( 'Search', 'sureforms' ), 'srfm-entries' );
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<div class="sureforms-table-container">
			<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
				<?php $this->print_table_description(); ?>
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"
					<?php
					if ( $singular ) {
						echo ' data-wp-lists="list:' . esc_attr( $singular ) . '"';
					}
					?>
				>
				<?php $this->display_rows_or_placeholder(); ?>
				</tbody>

				<tfoot>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</tfoot>
			</table>
		</div>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public static function process_bulk_actions() {
		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( ( new self() )->current_action() ) );

		if ( ! $action ) {
			return;
		}

		// Get the selected entry IDs.
		$entry_ids = isset( $_GET['entry'] ) ? array_map( 'absint', wp_unslash( $_GET['entry'] ) ) : [];

		if ( 'export' === $action ) {
			if ( ! $entry_ids ) {
				// If we are here then user has not selected entries so handle all entries export accordingly.

				$filter_form_id = ! empty( $_GET['form_filter'] ) && 'all' !== $_GET['form_filter'] ? absint( wp_unslash( $_GET['form_filter'] ) ) : 0;

				if ( ! empty( $_GET['month_filter'] ) && 'all' !== $_GET['month_filter'] ) {
					/**
					 * If we are here then user has filtered the entries by month first
					 * then selected export as bulk action without selecting any entries manually.
					 */
					$where_condition = self::get_where_conditions( $filter_form_id );
					$all_entry_ids   = Entries::get_all(
						[
							'where'   => $where_condition,
							'columns' => 'ID',
						],
						false
					);
				} elseif ( $filter_form_id > 0 ) {
					/**
					 * If we are here then user has filtered the entries by form first
					 * then selected export as bulk action without selecting any entries manually.
					 */
					$all_entry_ids = Entries::get_all_entry_ids_for_form( $filter_form_id );
				} elseif ( ! empty( $_GET['search_filter'] ) ) {
					// Export entries based on the search filter.
					$all_entry_ids = self::get_entries_based_on_search( sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) );
				} else {
					// Export all the available ( but not trashed ) entries.
					$all_entry_ids = self::get_all_entries_except_trash();
				}
				$entry_ids = array_map( 'absint', array_column( $all_entry_ids, 'ID' ) );
			}

			// Get an array of form ids based on the entry ids.
			$form_ids       = Entries::get_form_ids_by_entries( $entry_ids );
			$is_single_form = count( $form_ids ) === 1;

			$temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) ); // Normalize the path to make it consistent between windows and linux system.

			if ( ! wp_is_writable( $temp_dir ) ) {
				set_transient(
					'srfm_bulk_action_message',
					[
						'action'  => $action,
						'message' => __( 'Entries export failed. You have file permission issue. Temporary directory is not writable.', 'sureforms' ),
						'type'    => 'error',
					],
					30 // Transient expires in 30 seconds.
				);
				// Redirect to prevent form resubmission.
				wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
				exit;
			}

			// Only process for ZIP files if we have multiple forms.
			if ( ! $is_single_form ) {
				// Check for ZipArchive class if we are processing multiple forms.
				if ( ! class_exists( 'ZipArchive' ) ) {
					set_transient(
						'srfm_bulk_action_message',
						[
							'action'  => $action,
							'message' => __( 'Entries export failed. Your server does not support the ZipArchive library.', 'sureforms' ),
							'type'    => 'error',
						],
						30 // Transient expires in 30 seconds.
					);
					// Redirect to prevent form resubmission.
					wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
					exit;
				}

				$temp_zip = $temp_dir . 'srfm-entries-export.zip'; // Create a temporary file for the ZIP archive.
				$zip      = new \ZipArchive();

				if ( file_exists( $temp_zip ) ) {
					unlink( $temp_zip ); // Remove if temp export zip file already exists.
				}

				if ( ! $zip->open( $temp_zip, \ZipArchive::CREATE ) ) {
					set_transient(
						'srfm_bulk_action_message',
						[
							'action'  => $action,
							'message' => __( 'Entries export failed. Unable to create the ZIP file.', 'sureforms' ),
							'type'    => 'error',
						],
						30 // Transient expires in 30 seconds.
					);
					// Redirect to prevent form resubmission.
					wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
					exit;
				}
			}

			$success   = 0;
			$csv_files = [];  // Array of csv files to delete after zip file is closed.

			foreach ( $form_ids as $form_id ) {
				$results = self::get_entries_data( $entry_ids, $form_id );

				if ( empty( $results ) ) {
					continue;
				}

				$sanitized_form_title = sanitize_title( get_the_title( $form_id ) );
				$sanitized_form_title = ! empty( $sanitized_form_title ) ? $sanitized_form_title : "srfm-entries-{$form_id}"; // Just incase if we have some unnamed forms.

				$csv_filename = 'srfm-entries-' . $sanitized_form_title . '.csv';
				$csv_filepath = $temp_dir . $csv_filename;

				if ( file_exists( $csv_filepath ) ) {
					// Remove if temp export csv file already exists.
					unlink( $csv_filepath );
				}

				$stream = fopen( $csv_filepath, 'wb' ); // phpcs:ignore -- Using fopen to decrease the memory use.

				if ( ! is_resource( $stream ) ) {
					continue;
				}

				$csv_files[] = $csv_filepath;

				// Step 1: Build consistent map of block_id => latest key and label.
				$block_key_map_and_labels = self::build_block_key_map_and_labels( $results );
				$block_key_map            = $block_key_map_and_labels['map'];
				$block_labels             = $block_key_map_and_labels['labels'];

				// Step 2: Build CSV header using block_labels.
				self::write_csv_header( $stream, $block_labels );

				// Step 3: Write each row using block_id -> matched key.
				self::write_csv_rows( $stream, $results, $block_key_map );

				fclose( $stream ); // phpcs:ignore -- Using fclose as we have used fopen above to decrease the memory use.

				// If we are only exporting single form, than create a single csv file rather than a zip file.
				if ( $is_single_form ) {
					// Set headers to download the single csv file.
					header( 'Content-Type: text/csv' );
					header( sprintf( 'Content-disposition: attachment; filename="%s"', $csv_filename ) ); // Set filename header.
					header( 'Content-Length: ' . filesize( $csv_filepath ) );

					// Output the zip file.
					readfile( $csv_filepath );  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile -- We are not using WP_Filesystem here as we need readfile functionality.
					unlink( $csv_filepath ); // Clean up the temporary zip file.
					exit; // Exit and don't proceed further because it is a single form.
				}

				// Make sure we don't create empty csv files inside zip.
				if ( ! filesize( $csv_filepath ) ) {
					$stream       = false; // Clean up memory.
					$csv_filepath = ''; // Reset path.
					$csv_filename = ''; // Reset filename.
					continue;
				}

				// Add file to the zip if we are processing more than one form.
				if ( ! $is_single_form && $zip->addFile( $csv_filepath, $csv_filename ) ) {
					$success++;
				}

				$stream       = false; // Clean up memory.
				$csv_filepath = ''; // Reset path.
				$csv_filename = ''; // Reset filename.
			}

			if ( 0 === $success ) {
				set_transient(
					'srfm_bulk_action_message',
					[
						'action'  => $action,
						'message' => __( 'Entries export failed.', 'sureforms' ),
						'type'    => 'error',
					],
					30 // Transient expires in 30 seconds.
				);
				// Redirect to prevent form resubmission.
				wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
				exit;
			}

			if ( ! $zip->close() ) {
				set_transient(
					'srfm_bulk_action_message',
					[
						'action'  => $action,
						'message' => __( 'Entries export failed. Problem occured while closing the ZIP file.', 'sureforms' ),
						'type'    => 'error',
					],
					30 // Transient expires in 30 seconds.
				);
				// Redirect to prevent form resubmission.
				wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
				exit;
			}

			// Set headers to download the zip.
			header( 'Content-Type: application/zip' );
			header( 'Content-disposition: attachment; filename="SureForms Entries.zip"' ); // Set filename header.
			header( 'Content-Length: ' . filesize( $temp_zip ) );

			// Output the zip file.
			readfile( $temp_zip );  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile -- We are not using WP_Filesystem here as we need readfile functionality.
			unlink( $temp_zip ); // Clean up the temporary zip file.

			if ( ! empty( $csv_files ) && is_array( $csv_files ) ) {
				foreach ( $csv_files as $csv_file ) {
					if ( file_exists( $csv_file ) ) {
						// Clean any remaining temporary csv files after zip is exported.
						// Doing this keeps us safe from taking unnecessary server space.
						unlink( $csv_file );
					}
				}
			}
			exit;
		}

		// If there are entry IDs selected, process the bulk action.
		if ( ! empty( $entry_ids ) ) {
			// Update the status of each selected entry.
			foreach ( $entry_ids as $entry_id ) {
				self::handle_entry_status( Helper::get_integer_value( $entry_id ), $action );
			}

			set_transient(
				'srfm_bulk_action_message',
				[
					'action' => $action,
					'count'  => count( $entry_ids ),
				],
				30
			); // Transient expires in 30 seconds.
			// Redirect to prevent form resubmission.
			wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
			exit;
		}
	}

	/**
	 * Display admin notice for bulk actions.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public static function display_bulk_action_notice() {
		$bulk_action_message = get_transient( 'srfm_bulk_action_message' );
		if ( ! $bulk_action_message ) {
			return;
		}
		// Manually delete the transient after retrieval to prevent it from being displayed again after page reload.
		delete_transient( 'srfm_bulk_action_message' );
		$action  = $bulk_action_message['action'];
		$count   = $bulk_action_message['count'] ?? 0;
		$message = $bulk_action_message['message'] ?? '';
		$type    = $bulk_action_message['type'] ?? 'success';

		if ( ! $message && $count ) {
			// If we don't have $message added manually, and have $count then lets create message according to the action as default.
			switch ( $action ) {
				case 'read':
				case 'unread':
					// translators: %1$d refers to the number of entries, %2$s refers to the status (read or unread).
					$message = sprintf( _n( '%1$d entry was successfully marked as %2$s.', '%1$d entries were successfully marked as %2$s.', $count, 'sureforms' ), $count, $action );
					break;
				case 'trash':
					// translators: %1$d refers to the number of entries, %2$s refers to the action (trash).
					$message = sprintf( _n( '%1$d entry was successfully moved to trash.', '%1$d entries were successfully moved to trash.', $count, 'sureforms' ), $count );
					break;
				case 'restore':
					// translators: %1$d refers to the number of entries, %2$s refers to the action (restore).
					$message = sprintf( _n( '%1$d entry was successfully restored.', '%1$d entries were successfully restored.', $count, 'sureforms' ), $count );
					break;
				case 'delete':
					// translators: %1$d refers to the number of entries, %2$s refers to the action (delete).
					$message = sprintf( _n( '%1$d entry was permanently deleted.', '%1$d entries were permanently deleted.', $count, 'sureforms' ), $count );
					break;
				default:
					return;
			}
		}

		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Check if the current page is a trash list.
	 *
	 * @since 0.0.13
	 * @return bool
	 */
	public static function is_trash_view() {
		return isset( $_GET['view'] ) && 'trash' === sanitize_text_field( wp_unslash( $_GET['view'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here as we are not doing any database work.
	}

	/**
	 * Common function to update the status of an entry.
	 *
	 * @param int    $entry_id The ID of the entry to update.
	 * @param string $action The action to perform.
	 * @param string $view The view to handle redirection.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public static function handle_entry_status( $entry_id, $action, $view = '' ) {
		switch ( $action ) {
			case 'restore':
				Entries::update( $entry_id, [ 'status' => 'unread' ] );
				break;
			case 'unread':
			case 'read':
			case 'trash':
				Entries::update( $entry_id, [ 'status' => $action ] );
				break;
			case 'delete':
				self::delete_entry_files( $entry_id );
				Entries::delete( $entry_id );
				break;
			default:
				break;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?page=sureforms_entries' ),
			'srfm_entries_action'
		);
		// Redirect to appropriate page after action is performed.
		if ( 'details' === $view ) {
			$url = add_query_arg(
				[
					'entry_id' => $entry_id,
					'view'     => 'details',
				],
				$url
			);
		}
		wp_safe_redirect( $url );
	}

	/**
	 * Delete the entry files when an entry is deleted.
	 *
	 * @param int $entry_id The ID of the entry to delete files for.
	 * @since 1.0.2
	 * @return void
	 */
	public static function delete_entry_files( $entry_id ) {
		if ( ! $entry_id ) {
			return;
		}
		// Get the entry data to get the file URLs.
		$form_data = Entries::get_form_data( $entry_id );
		if ( empty( $form_data ) ) {
			return;
		}
		foreach ( $form_data as $field_name => $value ) {
			// Continue to the next iteration if the field name does not contain 'srfm-upload' and value is not an array.
			if ( false === strpos( $field_name, 'srfm-upload' ) && ! is_array( $value ) ) {
				continue;
			}
			foreach ( $value as $file_url ) {
				// If the file URL is empty, skip to the next iteration.
				if ( empty( $file_url ) ) {
					continue;
				}
				$file_path = Helper::convert_fileurl_to_filepath( urldecode( $file_url ) );

				// Delete the file if it exists.
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
			}
		}

		// Action to run after deleting the entry files.
		do_action( 'srfm_after_deleting_entry_files', $form_data, $entry_id );
	}

	/**
	 * Define the data for the "id" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_id( $item ) {
		$entry_id = esc_attr( $item['ID'] );

		$view_url =
		wp_nonce_url(
			add_query_arg(
				[
					'entry_id' => $entry_id,
					'view'     => 'details',
					'action'   => 'read',
				],
				admin_url( 'admin.php?page=sureforms_entries' )
			),
			'srfm_entries_action'
		);

		return sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s%3$s</a></strong>',
			$view_url,
			esc_html__( 'Entry #', 'sureforms' ),
			$entry_id
		) . $this->row_actions( $this->package_row_actions( $item ) );
	}

	/**
	 * Define the data for the "form name" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_form_name( $item ) {
		$form_name = get_the_title( $item['form_id'] );
		// translators: %1$s is the word "form", %2$d is the form ID.
		$form_name = ! empty( $form_name ) ? $form_name : sprintf( 'SureForms %1$s #%2$d', esc_html__( 'Form', 'sureforms' ), Helper::get_integer_value( $item['form_id'] ) );
		return sprintf( '<strong><a class="row-title" href="%1$s" target="_blank">%2$s</a></strong>', get_the_permalink( $item['form_id'] ), esc_html( $form_name ) );
	}

	/**
	 * Define the data for the "status" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_status( $item ) {
		$translated_status = '';
		switch ( $item['status'] ) {
			case 'read':
				$translated_status = esc_html__( 'Read', 'sureforms' );
				break;
			case 'unread':
				$translated_status = esc_html__( 'Unread', 'sureforms' );
				break;
			case 'trash':
				$translated_status = esc_html__( 'Trash', 'sureforms' );
				break;
		}

		return sprintf(
			'<span class="status-%1$s">%2$s</span>',
			esc_attr( $item['status'] ),
			$translated_status
		);
	}

	/**
	 * Define the data for the "first field" column and return the markup.
	 *
	 * It excludes certain fields from the form data (honeypot, reCAPTCHA, sender email,
	 * and form ID) before determining the first non-excluded field value to display.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_first_field( $item ) {
		// Get the first field key.
		$first_key       = key( $item['form_data'] );
		$excluded_fields = Helper::get_excluded_fields();
		$form_data       = array_diff_key( $item['form_data'], array_flip( $excluded_fields ) );
		$first_field     = reset( $form_data );
		$field_name      = array_keys( $form_data )[0];

		if ( false !== strpos( $field_name, 'srfm-upload' ) ) {
			$filenames = [];
			if ( ! empty( $first_field ) && is_array( $first_field ) ) {
				foreach ( $first_field as $file ) {
					$file_url    = urldecode( strval( $file ) );
					$filenames[] = pathinfo( $file_url, PATHINFO_BASENAME );
				}
			}
			$first_field = implode( ', ', $filenames );
		}

		$max_length = 28;

		if ( strlen( $first_field ) > $max_length ) {
			$first_field = substr( $first_field, 0, $max_length - 3 ) . '...';
		}

		// Check if the first field is a textarea.
		if ( strpos( $first_key, 'srfm-textarea' ) !== false ) {
			// Strip HTML tags from the textarea value.
			// Regular expression to match HTML tags.
			$regex = '/<[^>]*>/';

			$decode_html_content = html_entity_decode( reset( $item['form_data'] ) );

			// Replace HTML tags with empty string.
			$first_field = preg_replace( $regex, '', $decode_html_content );

			// Truncate the textarea value to 15 characters if it's too long.
			if ( strlen( $first_field ) > 12 ) {
				$first_field = substr( $first_field, 0, 12 ) . '...';
			}
		} else {
			// Get the first field value directly.
			$first_field = reset( $item['form_data'] );
		}

		// Return the first field value in a paragraph element.
		return sprintf(
			'<p>%s</p>',
			$first_field
		);
	}

	/**
	 * Define the data for the "submitted on" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_created_at( $item ) {
		$created_at = gmdate( 'Y/m/d \a\t g:i a', strtotime( $item['created_at'] ) );

		return sprintf(
			'<span>%1$s<br>%2$s</span>',
			esc_html__( 'Published', 'sureforms' ),
			$created_at
		);
	}

	/**
	 * Returns array of row actions for packages.
	 *
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return array
	 */
	protected function package_row_actions( $item ) {
		$view_url  =
		wp_nonce_url(
			add_query_arg(
				[
					'entry_id' => esc_attr( $item['ID'] ),
					'view'     => 'details',
					'action'   => 'read',
				],
				admin_url( 'admin.php?page=sureforms_entries' )
			),
			'srfm_entries_action'
		);
		$trash_url =
		wp_nonce_url(
			add_query_arg(
				[
					'entry_id' => esc_attr( $item['ID'] ),
					'action'   => 'trash',
				],
				admin_url( 'admin.php?page=sureforms_entries' )
			),
			'srfm_entries_action'
		);

		$row_actions = [
			'view'  => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $view_url ), esc_html__( 'View', 'sureforms' ) ),
			'trash' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $trash_url ), esc_html__( 'Trash', 'sureforms' ) ),
		];

		if ( self::is_trash_view() ) {
			// Remove the Trash and View actions when entry is in trash.
			unset( $row_actions['trash'] );
			unset( $row_actions['view'] );

			// Add Restore and Delete actions.
			$restore_url =
			wp_nonce_url(
				add_query_arg(
					[
						'entry_id' => esc_attr( $item['ID'] ),
						'action'   => 'restore',
					],
					admin_url( 'admin.php?page=sureforms_entries' )
				),
				'srfm_entries_action'
			);

			$delete_url =
				wp_nonce_url(
					add_query_arg(
						[
							'entry_id' => esc_attr( $item['ID'] ),
							'action'   => 'delete',
						],
						admin_url( 'admin.php?page=sureforms_entries' )
					),
					'srfm_entries_action'
				);

			$row_actions['restore'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $restore_url ), esc_html__( 'Restore', 'sureforms' ) );
			$row_actions['delete']  = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $delete_url ), esc_html__( 'Delete Permanently', 'sureforms' ) );
		}

		return apply_filters( 'sureforms_entries_table_row_actions', $row_actions, $item );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Which table navigation is it... Is it top or bottom.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		if ( 'top' === $which ) {
			?>
			<div class="alignleft actions">
				<?php
				$this->display_month_filter();
				$this->display_form_filter();

				/**
				 * Action hook right after entry form opening tag.
				 *
				 * @since 1.3.0
				 */
				do_action( 'srfm_after_entry_postbox_title' );

				if ( $this->is_filter_enabled() ) {
					?>
					<a href="<?php echo esc_url( add_query_arg( 'page', 'sureforms_entries', admin_url( 'admin.php' ) ) ); ?>" class="button button-link clear-filter"><?php esc_html_e( 'Clear Filter', 'sureforms' ); ?></a>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Generates the table navigation above or below the table.
	 *
	 * @param string $which is it the top or bottom of the table.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'srfm_entries_action' );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( $this->has_items() ) { ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			}
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
		</div>
		<?php
		if ( 'bottom' === $which ) {
			?>
			<script>
				(function() {
					/**
					 * This is edge case JavaScript.
					 * This will help us to override the default bulk action
					 * behaviour of WordPress List Table and force the form
					 * to submit even if no item is selected for the Export.
					 *
					 * Note: Internal JS is needed in this scenario.
					 */
					const form = document.querySelector('form');
					form.addEventListener('submit', function(e) {
						const formData = new FormData(form);
						if ('export' === formData.get('action')) {
							// Add style in head tag to display:none the #no-items-selected
							const style = document.createElement('style');
							style.innerHTML = '#no-items-selected { display: none; }';
							document.head.appendChild(style);

							form.submit();
						}
					});
				}());
			</script>
			<?php
		}
	}

	/**
	 * Returns true if any filter is enabled.
	 *
	 * @since 1.2.1
	 * @return bool
	 */
	protected function is_filter_enabled() {
		$intersect = array_intersect(
			[
				'form_filter',
				'month_filter',
			],
			array_keys( wp_unslash( $_GET ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is fine because we are not using it to save in the database.
		);

		return ! empty( $intersect );
	}

	/**
	 * Display the available form name to filter entries.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	protected function display_form_filter() {
		$forms = $this->get_available_forms();
		// Added the phpcs ignore nonce verification as no database operations are performed in this function.
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<input type="hidden" name="view" value="' . esc_attr( $view ) . '" />';
		echo '<select name="form_filter">';
		echo '<option value="all">' . esc_html__( 'All Form Entries', 'sureforms' ) . '</option>';
		foreach ( $forms as $form_id => $form_name ) {
			$form_name = ! empty( $form_name ) ? $form_name : sprintf( 'SureForms %1$s #%2$d', esc_html__( 'Form', 'sureforms' ), esc_attr( $form_id ) );
			// Adding the phpcs ignore nonce verification as no database operations are performed in this function.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = isset( $_GET['form_filter'] ) && Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) ) === $form_id ? ' selected="selected"' : '';
			printf( '<option value="%s"%s>%s</option>', esc_attr( $form_id ), esc_attr( $selected ), esc_html( $form_name ) );
		}
		echo '</select>';
		echo '<input type="submit" name="filter_action" value="Filter" class="button" />';
	}

	/**
	 * Display the month and year from which the entries are present to filter entries according to time.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	protected function display_month_filter() {
		if ( isset( $_GET['month_filter'] ) && ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) ) {
			return;
		}
		$view    = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';
		$form_id = isset( $_GET['form_filter'] ) && 'all' !== $_GET['form_filter'] ? absint( wp_unslash( $_GET['form_filter'] ) ) : 0;
		$months  = Entries::get_available_months( self::get_where_conditions( $form_id, $view, [ 'search_filter', 'month_filter' ] ) );

		// Sort the months in descending order according to key.
		krsort( $months );

		echo '<select name="month_filter">';
		echo '<option value="all">' . esc_html__( 'All Dates', 'sureforms' ) . '</option>';
		foreach ( $months as $month_value => $month_label ) {
			$selected = isset( $_GET['month_filter'] ) && Helper::get_string_value( $month_value ) === sanitize_text_field( wp_unslash( $_GET['month_filter'] ) ) ? ' selected="selected"' : '';
			printf( '<option value="%s"%s>%s</option>', esc_attr( $month_value ), esc_attr( $selected ), esc_html( $month_label ) );
		}
		echo '</select>';
	}

	/**
	 * List of CSS classes for the "WP_List_Table" table element.
	 *
	 * @since 0.0.13
	 * @return array<string>
	 */
	protected function get_table_classes() {
		$mode       = get_user_setting( 'posts_list_mode', 'list' );
		$mode_class = esc_attr( 'table-view-' . $mode );
		$classes    = [
			'widefat',
			'striped',
			$mode_class,
		];

		$columns_class = $this->get_column_count() > 5 ? 'many' : 'few';
		$classes[]     = "has-{$columns_class}-columns";

		return $classes;
	}

	/**
	 * Get the views for the entries table.
	 *
	 * @since 0.0.13
	 * @return array<string,string>
	 */
	protected function get_views() {
		// Get the status count of the entries.
		$unread_entries_count = Entries::get_total_entries_by_status( 'unread' );

		// Get the current view (All, Read, Unread, Trash) to highlight the selected one.
		// Adding the phpcs ignore nonce verification as no complex operations are performed here only the count of the entries is required.
		$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not needed here and this is admin page request only.

		// Define the base URL for the views (without query parameters).
		$base_url = wp_nonce_url( admin_url( 'admin.php?page=sureforms_entries' ), 'srfm_entries_action' );

		// Create the array of view links.
		$views = [
			'all'    => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'all', $base_url ),
				'all' === $current_view ? 'current' : '',
				esc_html__( 'All', 'sureforms' ),
				$this->all_entries_count
			),
			'unread' => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'unread', $base_url ),
				'unread' === $current_view ? 'current' : '',
				esc_html__( 'Unread', 'sureforms' ),
				$unread_entries_count
			),
		];

		// Only add the Trash view if the count is greater than 0.
		if ( $this->trash_entries_count > 0 ) {
			$views['trash'] = sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'trash', $base_url ),
				'trash' === $current_view ? 'current' : '',
				esc_html__( 'Trash', 'sureforms' ),
				$this->trash_entries_count
			);
		}

		return $views;
	}

	/**
	 * Get the entries data.
	 *
	 * @param int      $per_page Number of entries to fetch per page.
	 * @param int      $current_page Current page number.
	 * @param string   $view The view to fetch the entries count from.
	 * @param int|null $form_id The ID of the form to fetch entries for.
	 *
	 * @since 0.0.13
	 * @return array
	 */
	private function table_data( $per_page, $current_page, $view, $form_id = 0 ) {
		$allowed_orderby_columns = $this->get_sortable_columns();

		// Disabled the nonce verification due to the sorting functionality, will need custom implementation to display the sortable columns to accommodate nonce check.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$orderby = isset( $allowed_orderby_columns[ $orderby ] ) ? $orderby : 'created_at'; // If the orderby column is not in the allowed columns, set it to default.
		$orderby = 'id' === $orderby ? strtoupper( $orderby ) : $orderby;
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
		$order   = 'asc' === $order ? 'asc' : 'desc'; // If the order is not asc, set it to desc.
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$offset              = ( $current_page - 1 ) * $per_page;
		$where_condition     = self::get_where_conditions( $form_id, $view );
		$this->data          = Entries::get_all(
			[
				'limit'   => $per_page,
				'offset'  => $offset,
				'where'   => $where_condition,
				'orderby' => $orderby,
				'order'   => $order,
			]
		);
		$this->entries_count = Entries::get_total_entries_by_status( $view, $form_id, $where_condition );
		return $this->data;
	}

	/**
	 * Populate the forms filter dropdown.
	 *
	 * @since 0.0.13
	 * @return array<string>
	 */
	private function get_available_forms() {
		$forms = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$available_forms = [];

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				// Populate the array with the form ID as key and form title as value.
				$available_forms[ $form->ID ] = $form->post_title;
			}
		}

		return $available_forms;
	}

	/**
	 * Return the where conditions to add to the query for filtering entries.
	 *
	 * @param int           $form_id The ID of the form to fetch entries for.
	 * @param string        $view The view to fetch entries for.
	 * @param array<string> $exclude_filters Added @since 1.2.1 and we pass filter keys to exclude from where clause.
	 *
	 * @since 1.2.1 Converted to static method.
	 * @since 0.0.13
	 * @return array<mixed>
	 */
	private static function get_where_conditions( $form_id = 0, $view = 'all', $exclude_filters = [] ) {
		if ( ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) ) {
			// Return the default condition to fetch all entries which are not in trash.
			return [
				[
					[
						'key'     => 'status',
						'compare' => '!=',
						'value'   => 'trash',
					],
				],
			];
		}

		$where_condition = [];

		// Set the where condition based on the view for populating the month filter dropdown.
		switch ( $view ) {
			case 'all':
				$where_condition[] = [
					[
						'key'     => 'status',
						'compare' => '!=',
						'value'   => 'trash',
					],
				];
				break;
			case 'trash':
			case 'unread':
				$where_condition[] = [
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => $view,
					],
				];
				break;
			default:
				break;
		}

		// If form ID is set, then we need to add the form ID condition to the where clause to fetch entries only for that form.
		if ( 0 < $form_id ) {
			$where_condition[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		if ( ! in_array( 'search_filter', $exclude_filters, true ) ) {
			// Handle the search according to entry ID.
			$search_term = isset( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : '';

			// Apply search filter, currently search is based on entry ID only and not text.
			if ( ! empty( $search_term ) ) {
				$where_condition[] = [
					[
						'key'     => 'ID',
						'compare' => 'LIKE',
						'value'   => $search_term,
					],
				];
			}
		}

		if ( ! in_array( 'month_filter', $exclude_filters, true ) ) {
			// Filter data based on the month and year selected.
			$month_filter = isset( $_GET['month_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['month_filter'] ) ) : '';

			// Apply month filter.
			if ( ! empty( $month_filter ) && 'all' !== $month_filter ) {
				$year       = substr( $month_filter, 0, 4 );
				$month      = substr( $month_filter, 4, 2 );
				$start_date = sprintf( '%s-%s-01', $year, $month );
				$end_date   = gmdate( 'Y-m-t', strtotime( $start_date ) );
				// Using two conditions to filter the entries based on the start and end date as the base class does not support BETWEEN operator.
				$where_condition[] = [
					[
						'key'     => 'created_at',
						'compare' => '>=',
						'value'   => $start_date,
					],
					[
						'key'     => 'created_at',
						'compare' => '<=',
						'value'   => $end_date,
					],
				];
			}
		}

		return $where_condition;
	}

	/**
	 * Get additional bulk actions like restore and delete for the trash view.
	 *
	 * @param array $bulk_actions The bulk actions array.
	 *
	 * @since 0.0.13
	 * @return array<string,string>
	 */
	private static function get_additional_bulk_actions( $bulk_actions ) {
		if ( ! self::is_trash_view() ) {
			return $bulk_actions;
		}
		return [
			'restore' => __( 'Restore', 'sureforms' ),
			'delete'  => __( 'Delete Permanently', 'sureforms' ),
		];
	}

	/**
	 * Get the entries data for export.
	 *
	 * @param array $entry_ids The entry IDs to fetch.
	 * @param int   $form_id The form ID to fetch entries for.
	 *
	 * @since 1.6.1
	 * @return array The entries data.
	 */
	private static function get_entries_data( $entry_ids, $form_id ) {
		if ( empty( $entry_ids ) || empty( $form_id ) ) {
			return [];
		}
		// SELECT * FROM %1s WHERE ID IN (%2s) AND form_id=%d.
		return Entries::get_all(
			[
				'where'   => [
					[
						[
							'key'     => 'ID',
							'compare' => 'IN',
							'value'   => $entry_ids,
						],
						[
							'key'     => 'form_id',
							'compare' => '=',
							'value'   => $form_id,
						],
					],
				],
				'columns' => 'ID, form_data', // Query only needed columns for the performance.
			],
			false
		);
	}

	/**
	 * Get all the entries data except the ones which are in trash.
	 *
	 * @since 1.6.1
	 * @return array The entries data.
	 */
	private static function get_all_entries_except_trash() {
		return Entries::get_all(
			[
				'where'   => [
					[
						[
							'key'     => 'status',
							'compare' => '!=',
							'value'   => 'trash',
						],
					],
				],
				'columns' => 'ID',
			],
			false
		);
	}

	/**
	 * Get the entries based on the search term.
	 *
	 * @param string $search_term The search term to filter entries.
	 *
	 * @since 1.6.1
	 * @return array The entries data.
	 */
	private static function get_entries_based_on_search( $search_term ) {
		return Entries::get_all(
			[
				'where'   => [
					[
						[
							'key'     => 'ID',
							'compare' => 'LIKE',
							'value'   => $search_term,
						],
					],
				],
				'columns' => 'ID',
			],
			false
		);
	}

	/**
	 * Step 1: Build consistent map of block_id => latest key and label.
	 *
	 * @param array $results The results to process.
	 *
	 * @since 1.6.1
	 * @return array the map of block_id => key and block labels.
	 */
	private static function build_block_key_map_and_labels( $results ) {
		// If there are no results, return empty map and labels.
		if ( empty( $results ) ) {
			return [
				'map'    => [],
				'labels' => [],
			];
		}

		$block_key_map = [];
		$block_labels  = [];
		foreach ( $results as $result ) {
			if ( empty( $result['form_data'] ) || ! is_array( $result['form_data'] ) ) {
				// Probably invalid submission.
				continue;
			}

			/**
			 * The structure of the keys is like srfm-[block_type]-[block_id]-lbl-[label].
			 * We need to get the block_id and label from the key and create a map of block_id => key.
			 * This will help us to create a consistent CSV file with the same order of columns.
			 */
			foreach ( $result['form_data'] as $key => $value ) {
				$block_id = Helper::get_block_id_from_key( $key );
				$label    = Helper::get_field_label_from_key( $key );
				// Only store latest key for each block_id.
				$block_key_map[ $block_id ] = $key;
				// Only update the label if it is not already set.
				if ( ! isset( $block_labels[ $block_id ] ) ) {
					$block_labels[ $block_id ] = $label;
				}
			}
		}
		return [
			'map'    => $block_key_map,
			'labels' => $block_labels,
		];
	}

	/**
	 * Step 2: Build CSV header using block_labels.
	 *
	 * @param resource $stream The file stream to write to.
	 * @param array    $block_labels The labels for the blocks.
	 *
	 * @since 1.6.1
	 * @return void
	 */
	private static function write_csv_header( $stream, $block_labels ) {
		if ( empty( $stream ) || empty( $block_labels ) ) {
			return;
		}
		$labels = array_merge(
			[ __( 'ID', 'sureforms' ) ],
			array_values( $block_labels )
		);

		fputcsv( $stream, $labels );
	}

	/**
	 * Step 3: Write each row using block_id -> matched key.
	 *
	 * @param resource $stream The file stream to write to.
	 * @param array    $results The results to write to the CSV.
	 * @param array    $block_key_map The map of block IDs to SureForms keys.
	 *
	 * @since 1.6.1
	 * @return void
	 */
	private static function write_csv_rows( $stream, $results, $block_key_map ) {
		if ( empty( $stream ) || empty( $results ) || empty( $block_key_map ) ) {
			return;
		}

		foreach ( $results as $result ) {
			if ( empty( $result['form_data'] ) || ! is_array( $result['form_data'] ) ) {
				continue;
			}

			$form_data = $result['form_data'];
			$values    = [ '#' . absint( $result['ID'] ) ];

			foreach ( $block_key_map as $block_id => $srfm_key ) {
				$field_value = '';

				// Try to find a key that matches this block ID in current form_data and use it's value.
				foreach ( $form_data as $key => $value ) {
					if ( strpos( $key, $block_id ) !== false ) {
						$field_value = $value;
						break;
					}
				}

				$_value = self::normalize_field_values( $srfm_key, $field_value );

				$values[] = $_value ? $_value : '';
			}

			fputcsv( $stream, $values );
		}
	}

	/**
	 * Normalize the field values for the CSV file.
	 * 1. If it is array then first check if it is from upload field value. Process the upload file urls and convert array into comma separated string.
	 * 2. If it is not upload field value then convert array into comma separated string.
	 *
	 * @param string $srfm_key The SureForms key for the field.
	 * @param mixed  $field_value The field value to normalize.
	 *
	 * @since 1.6.1
	 * @return string The normalized field value.
	 */
	private static function normalize_field_values( $srfm_key, $field_value ) {
		if ( empty( $srfm_key ) || empty( $field_value ) ) {
			return '';
		}

		// Check if the field value is an array and if it is from an upload field.
		if ( false !== strpos( $srfm_key, 'srfm-upload' ) && is_array( $field_value ) ) {
			// Decode the URLs, then create a comma separated string.
			return implode( ', ', array_map( 'urldecode', $field_value ) );
		}

		return is_array( $field_value ) ? implode( ', ', $field_value ) : $field_value;
	}
}
