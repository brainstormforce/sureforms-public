<?php
/**
 * SureForms Entries Table Class.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Inc\Database\Tables\Entries;

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
class SRFM_Entries_Table extends \WP_List_Table {
	/**
	 * Display content when no entries are found in the database.
	 *
	 * @return void
	 */
	public function no_items() {
		printf( esc_html__( 'No entries found.', 'sureforms' ) );
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
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
	 *
	 * @return array $actions Bulk actions.
	 */
	public function get_bulk_actions() {
		$actions = [
			'edit'   => __( 'Edit', 'sureforms' ),
			'trash'  => __( 'Move to Trash', 'sureforms' ),
			'read'   => __( 'Mark as Read', 'sureforms' ),
			'unread' => __( 'Mark as Unread', 'sureforms' ),
		];

		return $actions;
	}

	/**
	 * Get the entries data.
	 *
	 * @return array
	 */
	private function table_data() {
		return Entries::get_all();
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();
		$data     = $this->table_data();
		usort( $data, [ $this, 'sort_data' ] );
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

		$data                  = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $data;
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @param array  $item Column data.
	 * @param string $column_name Current column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $this->column_id( $item );
			case 'form_name':
				$form_name = ! empty( get_the_title( $item['form_id'] ) ) ? get_the_title( $item['form_id'] ) : 'SureForms Form #' . intval( $item['form_id'] );
				return $form_name;
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
	 * @since 1.0.0
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
	 * @return string
	 */
	protected function column_id( $item ) {
		$entry_id = esc_attr( $item['ID'] );
		$url      = esc_url( add_query_arg( 'entry_id', $entry_id, admin_url( 'admin.php?page=entries' ) ) );

		return sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s%3$s</a></strong>' . $this->row_actions( $this->package_row_actions( $item ) ),
			$url,
			esc_html__( 'Entry #', 'sureforms' ),
			$entry_id
		);
	}

	/**
	 * Define the data for the "status" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @return string
	 */
	protected function column_status( $item ) {
		$status = esc_attr( $item['status'] );

		return sprintf(
			'<span class="%1$s">%2$s</span>',
			'read' === $status ? 'status-read' : 'status-unread',
			$status
		);
	}

	/**
	 * Define the data for the "first field" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @return string
	 */
	protected function column_first_field( $item ) {
		$first_field = reset( $item['user_data'] );

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
	 * @since x.x.x
	 *
	 * @return array
	 */
	protected function package_row_actions( $item ) {
		$url         = esc_url( add_query_arg( 'entry_id', esc_attr( $item['ID'] ), admin_url( 'admin.php?page=entries' ) ) );
		$row_actions = [
			'view'  => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'View', 'sureforms' ) ),
			'trash' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'Trash', 'sureforms' ) ),
		];

		return array_filter( $row_actions );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Which table navigation is it... Is it top or bottom.
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
		// TODO: set the $forms variable with available forms and add nonce verification check.
		$forms = [];

		echo '<select name="form_filter">';
		echo '<option value="all_entries">' . esc_html__( 'All Form Entries', 'sureforms' ) . '</option>';
		foreach ( $forms as $form_id => $form_name ) {
			printf( '<option value="%s">%s</option>', esc_attr( $form_id ), esc_html( $form_name ) );
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
		// TODO: set the $months variable with available months and add nonce verification check.
		$months = [];

		echo '<select name="month_filter">';
		echo '<option value="all_dates">' . esc_html__( 'All Dates', 'sureforms' ) . '</option>';
		foreach ( $months as $month_value => $month_label ) {
			printf( '<option value="%s">%s</option>', esc_attr( $month_value ), esc_html( $month_label ) );
		}
		echo '</select>';
	}

	/**
	 * Entries table form search.
	 *
	 * @param string $text The "search entries" button label.
	 * @param string $input_id ID attribute for the search input field.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		$input_id .= '-search-input';
		// TODO: Search logic implementation.
		$this->search_box_output( $text, $input_id );
	}

	/**
	 * Entries table form search input markup.
	 *
	 * @param string $text The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function search_box_output( $text, $input_id ) {
		?>
		<p class="search-box sureforms-form-search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<input type="search" class="sureforms-form-search-box-term" id="<?php echo esc_attr( $input_id ); ?>">
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
		$orderby = 'ID';
		$order   = 'desc';

		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_key( wp_unslash( $_GET['orderby'] ) );
		}

		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_key( wp_unslash( $_GET['order'] ) );
		}

		$result = strcmp( $data1[ $orderby ], $data2[ $orderby ] );
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
					<?php $this->print_column_headers( false ); ?>
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
}


