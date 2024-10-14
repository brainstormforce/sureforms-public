<?php
/**
 * SureForms Entries Table Class.
 *
 * @package sureforms.
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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create the entries table using WP_List_Table.
 */
class Entries_List_Table extends \WP_List_Table {

	/**
	 * Stores the entries data fetched from database.
	 *
	 * @var array<mixed>
	 * @since x.x.x
	 */
	protected $data = [];

	/**
	 * Stores the count for the entries data fetched from the database.
	 *
	 * @var int
	 * @since x.x.x
	 */
	public $entries_count;

	/**
	 * Remove unnecessary query arguments from the URL.
	 * WIP: This is currently work in progress and will be improved later.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function remove_query_args() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$remove_args = [
			'action',
			'action2',
			'search_filter',
			'filter_action',
			'_wpnonce',
			'_wp_http_referer',
			'paged',
		];

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			// Adding the phpcs ignore to avoid removing slashes from the URL.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$_SERVER['REQUEST_URI'] = remove_query_arg( $remove_args, esc_url_raw( $_SERVER['REQUEST_URI'] ) );
		}
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'ID', 'sureforms' ),
			'form_name'   => __( 'Form Name', 'sureforms' ),
			'status'      => __( 'Status', 'sureforms' ),
			'first_field' => __( 'First Field', 'sureforms' ),
			'created_at'  => __( 'Submitted On', 'sureforms' ),
		];

		return $columns;
	}

	/**
	 * Define the sortable columns.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return array $actions Bulk actions.
	 */
	public function get_bulk_actions() {
		$bulk_actions = [
			'edit'   => __( 'Edit', 'sureforms' ),
			'trash'  => __( 'Move to Trash', 'sureforms' ),
			'read'   => __( 'Mark as Read', 'sureforms' ),
			'unread' => __( 'Mark as Unread', 'sureforms' ),
		];

		$bulk_actions = $this->get_additional_bulk_actions( $bulk_actions );

		return $bulk_actions;
	}

	/**
	 * Message to be displayed when there are no entries.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function no_items() {

		printf(
			'<div class="sureforms-no-entries-found">%1$s</div>',
			esc_html__( 'No entries found.', 'sureforms' )
		);
	}

	/**
	 * Get the entries data.
	 *
	 * @param int      $per_page Number of entries to fetch per page.
	 * @param int      $current_page Current page number.
	 * @param string   $view The view to fetch the entries count from.
	 * @param int|null $form_id The ID of the form to fetch entries for.
	 *
	 * @since x.x.x
	 * @return array
	 */
	private function table_data( $per_page, $current_page, $view, $form_id = 0 ) {
		$offset = ( $current_page - 1 ) * $per_page;
		// If view is all, then we need to fetch all entries except the trash.
		$compare = 'all' === $view ? '!=' : '=';
		$value   = 'all' === $view ? 'trash' : $view;
		// Default where clause for all views.
		$where_condition = [
			[
				[
					'key'     => 'status',
					'compare' => $compare,
					'value'   => $value,
				],
			],
		];
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
		$this->data          = Entries::get_all(
			[
				'limit'  => $per_page,
				'offset' => $offset,
				'where'  => $where_condition,
			]
		);
		$this->entries_count = Entries::get_total_entries_by_status( $view, $form_id );
		return $this->data;
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function prepare_items() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden   = [];

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$view         = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';
		$form_id      = isset( $_GET['form_filter'] ) ? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) ) : 0;

		$data = $this->table_data( $per_page, $current_page, $view, $form_id );
		$data = $this->filter_entries_data( $data );

		usort( $data, [ $this, 'sort_data' ] );

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
	 * @since x.x.x
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
	 * @since x.x.x
	 */
	public function column_cb( $item ) {
		$entry_id = esc_attr( $item['ID'] );
		return sprintf(
			'<input type="checkbox" name="entry[]" value="%s" />',
			$entry_id
		);
	}

	/**
	 * Define the data for the "id" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_id( $item ) {
		$entry_id = esc_attr( $item['ID'] );
		$view_url = esc_url(
			add_query_arg(
				[
					'entry_id' => esc_attr( $item['ID'] ),
					'view'     => 'details',
				],
				admin_url( 'admin.php?page=sureforms_entries' )
			)
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
	 * @since x.x.x
	 * @return string
	 */
	protected function column_form_name( $item ) {
		$form_name = get_the_title( $item['form_id'] );
		// translators: %1$s is the word "form", %2$d is the form ID.
		$form_name = ! empty( $form_name ) ? $form_name : sprintf( 'SureForms %1$s #%2$d', esc_html__( 'Form', 'sureforms' ), Helper::get_integer_value( $item['form_id'] ) );
		return sprintf( '<strong><a class="row-title" href="%1$s" target="_blank">%2$s</a></strong>', get_edit_post_link( $item['form_id'] ), esc_html( $form_name ) );
	}

	/**
	 * Define the data for the "status" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return array
	 */
	protected function package_row_actions( $item ) {
		$view_url    = esc_url(
			add_query_arg(
				[
					'entry_id' => esc_attr( $item['ID'] ),
					'view'     => 'details',
				],
				admin_url( 'admin.php?page=sureforms_entries' )
			)
		);
		$trash_url   = esc_url(
			wp_nonce_url(
				add_query_arg(
					[
						'entry_id' => esc_attr( $item['ID'] ),
						'action'   => 'trash',
					],
					admin_url( 'admin.php?page=sureforms_entries' )
				),
				'srfm_entries_action'
			)
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
			$restore_url = esc_url(
				wp_nonce_url(
					add_query_arg(
						[
							'entry_id' => esc_attr( $item['ID'] ),
							'action'   => 'restore',
						],
						admin_url( 'admin.php?page=sureforms_entries' )
					),
					'srfm_entries_action'
				)
			);

			$delete_url             = esc_url(
				wp_nonce_url(
					add_query_arg(
						[
							'entry_id' => esc_attr( $item['ID'] ),
							'action'   => 'delete',
						],
						admin_url( 'admin.php?page=sureforms_entries' )
					),
					'srfm_entries_action'
				)
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
	 * @since x.x.x
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		if ( 'top' === $which ) {
			$this->display_month_filter();
			$this->display_form_filter();
		}
	}

	/**
	 * Generates the table navigation above or below the table.
	 *
	 * @param string $which is it the top or bottom of the table.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'srfm_entries_action' );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
		</div>
		<?php
	}

	/**
	 * Display the available form name to filter entries.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_form_filter() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$forms = $this->get_available_forms();

		echo '<select name="form_filter">';
		echo '<option value="all">' . esc_html__( 'All Form Entries', 'sureforms' ) . '</option>';
		foreach ( $forms as $form_id => $form_name ) {
			$selected = ( isset( $_GET['form_filter'] ) && Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) ) === $form_id ) ? ' selected="selected"' : '';
			printf( '<option value="%s"%s>%s</option>', esc_attr( $form_id ), esc_attr( $selected ), esc_html( $form_name ) );
		}
		echo '</select>';
		echo '<input type="submit" name="filter_action" value="Filter" class="button" />';
	}

	/**
	 * Display the month and year from which the entries are present to filter entries according to time.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_month_filter() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$months = $this->get_available_months();

		// Sort the months in descending order according to key.
		krsort( $months );

		echo '<select name="month_filter">';
		echo '<option value="all">' . esc_html__( 'All Dates', 'sureforms' ) . '</option>';
		foreach ( $months as $month_value => $month_label ) {
			$selected = ( isset( $_GET['month_filter'] ) && Helper::get_string_value( $month_value ) === sanitize_text_field( $_GET['month_filter'] ) ) ? ' selected="selected"' : '';
			printf( '<option value="%s"%s>%s</option>', esc_attr( $month_value ), esc_attr( $selected ), esc_html( $month_label ) );
		}
		echo '</select>';
	}

	/**
	 * Entries table form search input markup.
	 * Currently search is based on entry ID only and not text.
	 *
	 * @param string $text The 'submit' button label.
	 * @param int    $input_id ID attribute value for the search input field.
	 *
	 * @since x.x.x
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
	 * Allows you to sort the data by the variables set in the $_GET superglobal.
	 *
	 * @param array $data1 Data one to compare to.
	 * @param array $data2 Data two to compare with.
	 *
	 * @since x.x.x
	 * @return mixed
	 */
	protected function sort_data( $data1, $data2 ) {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$orderby = 'ID';
		$order   = 'desc';

		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			$orderby = 'id' === $orderby ? strtoupper( $orderby ) : $orderby;
		}

		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}

		if ( 'ID' === $orderby ) {
			$result = Helper::get_integer_value( $data1[ $orderby ] ) - Helper::get_integer_value( $data2[ $orderby ] );
		} else {
			$result = strcmp( $data1[ $orderby ], $data2[ $orderby ] );
		}
		if ( 'asc' === $order ) {
			return $result;
		}

		return -$result;
	}

	/**
	 * Displays the table.
	 *
	 * @since x.x.x
	 */
	public function display() {
		$singular = $this->_args['singular'];
		$this->views();
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
	 * List of CSS classes for the "WP_List_Table" table element.
	 *
	 * @since x.x.x
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
	 * Populate the month/year filter dropdown.
	 *
	 * @since x.x.x
	 * @return array<string>
	 */
	private function get_available_months() {
		$months = [];
		foreach ( $this->data as $entry ) {
			if ( isset( $entry['created_at'] ) ) {
				// Convert created_at to a DateTime object.
				$date = new \DateTime( $entry['created_at'] );

				// Get month in 'YYYYMM' format for value and 'Month Year' for display.
				$month_value = $date->format( 'Ym' );   // Example: 202408.
				$month_label = $date->format( 'F Y' );  // Example: August 2024.

				// Add to months array if not already present.
				if ( ! isset( $months[ $month_value ] ) ) {
					$months[ $month_value ] = $month_label;
				}
			}
		}

		return $months;
	}

	/**
	 * Populate the forms filter dropdown.
	 *
	 * @since x.x.x
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
	 * Filter the data according to options applied.
	 *
	 * @param array $data The form entries which will be displayed in the table.
	 *
	 * @since x.x.x
	 * @return array<mixed>
	 */
	private function filter_entries_data( $data ) {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		// Handle the search according to entry ID.
		$search_term = isset( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : '';

		// Filter the data based on the form name selected.
		$form_filter = isset( $_GET['form_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['form_filter'] ) ) : '';

		// Filter data based on the month and year selected.
		$month_filter = isset( $_GET['month_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['month_filter'] ) ) : '';

		// Filter data based on the status (All, Unread, Trash).
		$status_filter = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';

		// Apply search filter, currently search is based on entry ID only and not text.
		if ( ! empty( $search_term ) ) {
			$data = array_filter(
				$data,
				function( $entry ) use ( $search_term ) {
					return isset( $entry['ID'] ) && strpos( Helper::get_string_value( $entry['ID'] ), $search_term ) !== false;
				}
			);
		}

		// Apply form filter.
		if ( ! empty( $form_filter ) && 'all' !== $form_filter ) {
			$data = array_filter(
				$data,
				function( $entry ) use ( $form_filter ) {
					return isset( $entry['form_id'] ) && $entry['form_id'] === $form_filter;
				}
			);
		}

		// Apply month filter.
		if ( ! empty( $month_filter ) && 'all' !== $month_filter ) {
			$data = array_filter(
				$data,
				function( $entry ) use ( $month_filter ) {
					$entry_month = ( new \DateTime( $entry['created_at'] ) )->format( 'Ym' ); // Format as 'YYYYMM'.
					return $entry_month === $month_filter;
				}
			);
		}

		// Apply status filter.
		if ( ! empty( $status_filter ) && 'all' !== $status_filter ) {
			$data = array_filter(
				$data,
				function( $entry ) use ( $status_filter ) {
					if ( 'unread' === $status_filter ) {
						return isset( $entry['status'] ) && 'unread' === $entry['status'];
					} elseif ( 'trash' === $status_filter ) {
						return isset( $entry['status'] ) && 'trash' === $entry['status'];
					}
					return true; // Return true for all other cases.
				}
			);
		}

		// Filter out trash entries if the status is 'all'.
		if ( 'all' === $status_filter ) {
			$data = array_filter(
				$data,
				function( $entry ) {
					return ! isset( $entry['status'] ) || 'trash' !== $entry['status'];
				}
			);
		}

		return $data;
	}

	/**
	 * Get the views for the entries table.
	 *
	 * @since x.x.x
	 * @return array<string,string>
	 */
	protected function get_views() {
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		$status_count = [
			'all'    => Entries::get_total_entries_by_status( 'all' ),
			'unread' => Entries::get_total_entries_by_status( 'unread' ),
			'trash'  => Entries::get_total_entries_by_status( 'trash' ),
		];

		// Get the current view (All, Read, Unread, Trash) to highlight the selected one.
		$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';

		// Define the base URL for the views (without query parameters).
		$base_url = esc_url( admin_url( 'admin.php?page=sureforms_entries' ) );

		// Create the array of view links.
		$views = [
			'all'    => sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				add_query_arg( 'view', 'all', $base_url ),
				( 'all' === $current_view ) ? 'current' : '',
				/* translators: %d refers to the number of entries in 'All' view. */
				sprintf( __( 'All <span class="count">(%d)</span>', 'sureforms' ), $status_count['all'] )
			),
			'unread' => sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				add_query_arg( 'view', 'unread', $base_url ),
				( 'unread' === $current_view ) ? 'current' : '',
				/* translators: %d refers to the number of unread entries. */
				sprintf( __( 'Unread <span class="count">(%d)</span>', 'sureforms' ), $status_count['unread'] )
			),
		];

		// Only add the Trash view if the count is greater than 0.
		if ( $status_count['trash'] > 0 ) {
			$views['trash'] = sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				add_query_arg( 'view', 'trash', $base_url ),
				( 'trash' === $current_view ) ? 'current' : '',
				/* translators: %d refers to the number of entries in the trash. */
				sprintf( __( 'Trash <span class="count">(%d)</span>', 'sureforms' ), $status_count['trash'] )
			);
		}

		return $views;
	}

	/**
	 * Process bulk actions.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function process_bulk_actions() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
			return;
		}
		// Check if the form is submitted with bulk action using GET.
		if ( isset( $_GET['action'] ) && isset( $_GET['entry'] ) ) {

			// Get the selected entry IDs.
			$entry_ids = isset( $_GET['entry'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['entry'] ) ) : [];

			// If there are entry IDs selected, process the bulk action.
			if ( ! empty( $entry_ids ) ) {
				$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

				// Update the status of each selected entry.
				foreach ( $entry_ids as $entry_id ) {
					self::handle_entry_status( Helper::get_integer_value( $entry_id ), $action );
				}

				set_transient( 'srfm_bulk_action_message', [ 'action' => $action, 'count' => count( $entry_ids ) ], 10 ); // Transient expires in 10 seconds.
				// Redirect to prevent form resubmission.
				wp_safe_redirect( admin_url( 'admin.php?page=sureforms_entries' ) );
				exit;
			}
		}
	}

	/**
	 * Get additional bulk actions like restore and delete for the trash view.
	 *
	 * @param array $bulk_actions The bulk actions array.
	 *
	 * @since x.x.x
	 * @return array<string,string>
	 */
	private static function get_additional_bulk_actions( $bulk_actions ) {
		if ( ! self::is_trash_view() ) {
			return $bulk_actions;
		}
		$bulk_actions = [
			'restore' => __( 'Restore', 'sureforms' ),
			'delete'  => __( 'Delete Permanently', 'sureforms' ),
		];
		return $bulk_actions;
	}

	/**
	 * Check if the current page is a trash list.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_trash_view() {
		return isset( $_GET['view'] ) && 'trash' === sanitize_text_field( $_GET['view'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Common function to update the status of an entry.
	 *
	 * @param int    $entry_id The ID of the entry to update.
	 * @param string $action The action to perform.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function handle_entry_status( $entry_id, $action ) {
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
				Entries::delete( $entry_id );
				break;
			default:
				break;
		}
	}

	/**
	 * Display admin notice for bulk actions.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function display_bulk_action_notice() {
		$bulk_action_message = get_transient( 'srfm_bulk_action_message' );
		if ( ! $bulk_action_message ) {
			return;
		}
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'srfm_entries_action' ) ) {
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
				break;
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
