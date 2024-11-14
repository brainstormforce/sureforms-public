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
		$input_id .= '-search-input';
		?>
		<p class="search-box sureforms-form-search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<input type="search" name="search_filter" class="sureforms-entries-search-box" id="<?php echo esc_attr( $input_id ); ?>">
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
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) {
			return;
		}

		// Get the selected entry IDs.
		$entry_ids = isset( $_GET['entry'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['entry'] ) ) : [];

		// If there are entry IDs selected, process the bulk action.
		if ( ! empty( $entry_ids ) ) {
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

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
		$action = $bulk_action_message['action'];
		$count  = $bulk_action_message['count'];
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
			case 'export':
				// translators: %1$d refers to the number of entries, %2$s refers to the action (export).
				$message = sprintf( _n( '%1$d entry was successfully exported.', '%1$d entries were successfully exported.', $count, 'sureforms' ), $count );
				break;
			default:
				return;
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Check if the current page is a trash list.
	 *
	 * @since 0.0.13
	 * @return bool
	 */
	public static function is_trash_view() {
		return isset( $_GET['view'] ) && 'trash' === sanitize_text_field( wp_unslash( $_GET['view'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		$upload_dir = wp_get_upload_dir();
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
				// Get the file path from the file URL.
				$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], urldecode( $file_url ) );

				// Delete the file if it exists.
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
			}
		}
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
	 * @param array $item Column data.
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function column_first_field( $item ) {
		$first_field = reset( $item['form_data'] );

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
				<?php $this->display_month_filter(); ?>
				<?php $this->display_form_filter(); ?>
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
		$form_id = isset( $_GET['form_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) : 0;
		$months  = Entries::get_available_months( $this->get_where_conditions( $form_id, $view ) );

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
		$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
		// Disabled the nonce verification due to the sorting functionality, will need custom implementation to display the sortable columns to accommodate nonce check.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$orderby = 'id' === $orderby ? strtoupper( $orderby ) : $orderby;
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$offset              = ( $current_page - 1 ) * $per_page;
		$where_condition     = $this->get_where_conditions( $form_id, $view );
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
	 * @param int    $form_id The ID of the form to fetch entries for.
	 * @param string $view The view to fetch entries for.
	 *
	 * @since 0.0.13
	 * @return array<mixed>
	 */
	private function get_where_conditions( $form_id = 0, $view = 'all' ) {
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
		// Handle the search according to entry ID.
		$search_term = isset( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : '';

		// Filter data based on the month and year selected.
		$month_filter = isset( $_GET['month_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['month_filter'] ) ) : '';

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
}
