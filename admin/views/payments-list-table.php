<?php
/**
 * SureForms Payments Table Class.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Admin\Views;

use SRFM\Inc\Database\Tables\Payments;
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
 * @since x.x.x
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create the payments table using WP_List_Table.
 */
class Payments_List_Table extends \WP_List_Table {
	use Get_Instance;

	/**
	 * Stores the count for the payments data fetched from the database according to the status.
	 * It will be used for pagination.
	 *
	 * @var int
	 * @since x.x.x
	 */
	public $payments_count;

	/**
	 * Stores the count for all payments regardless of status.
	 * It will be used for managing the no payments found page.
	 *
	 * @var int
	 * @since x.x.x
	 */
	public $all_payments_count;

	/**
	 * Stores the payments data fetched from database.
	 *
	 * @var array<mixed>
	 * @since x.x.x
	 */
	protected $data = [];

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->all_payments_count = Payments::get_total_payments_by_status( 'all' );
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb'              => '<input type="checkbox" />',
			'id'              => __( 'ID', 'sureforms' ),
			'date'            => __( 'Date', 'sureforms' ),
			'type'            => __( 'Type', 'sureforms' ),
			'form_id'         => __( 'Form', 'sureforms' ),
			'status'          => __( 'Status', 'sureforms' ),
			'amount'          => __( 'Amount', 'sureforms' ),
			'refunded_amount' => __( 'Refunded', 'sureforms' ),
			'gateway'         => __( 'Gateway', 'sureforms' ),
		];
	}

	/**
	 * Define the sortable columns.
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'id'              => [ 'id', false ],
			'date'            => [ 'created_at', false ],
			'status'          => [ 'status', false ],
			'amount'          => [ 'total_amount', false ],
			'refunded_amount' => [ 'refunded_amount', false ],
			'form_id'         => [ 'form_id', false ],
		];
	}

	/**
	 * Bulk action items.
	 *
	 * @since x.x.x
	 * @return array $actions Bulk actions.
	 */
	public function get_bulk_actions() {
		return [
			'delete' => __( 'Delete', 'sureforms' ),
			'export' => __( 'Export', 'sureforms' ),
		];
	}

	/**
	 * Message to be displayed when there are no payments.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function no_items() {
		printf(
			'<div class="sureforms-no-payments-found">%1$s</div>',
			esc_html__( 'No payments found.', 'sureforms' )
		);
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function prepare_items() {
		// If nonce verification fails, set the view to all else set the view to the view passed in the GET request.
		if ( ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_payments_action' ) ) ) {
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
				'total_items' => $this->payments_count,
				'total_pages' => ceil( $this->payments_count / $per_page ),
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
			case 'date':
				return $this->column_date( $item );
			case 'type':
				return $this->column_type( $item );
			case 'form_id':
				return $this->column_form_id( $item );
			case 'status':
				return $this->column_status( $item );
			case 'amount':
				return $this->column_amount( $item );
			case 'refunded_amount':
				return $this->column_refunded_amount( $item );
			case 'gateway':
				return $this->column_gateway( $item );
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
		$payment_id = esc_attr( $item['id'] );
		return sprintf(
			'<input type="checkbox" name="payment[]" value="%s" />',
			$payment_id
		);
	}

	/**
	 * Payments table form search input markup.
	 * Currently search is based on payment ID and transaction ID.
	 *
	 * @param string $text The 'submit' button label.
	 * @param int    $input_id ID attribute value for the search input field.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function search_box_markup( $text, $input_id ) {
		$input_id   .= '-search-input';
		$search_term = ! empty( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : ''; // phpcs:ignore -- Nonce verification is not required here as we are not doing any database work.
		?>
		<p class="search-box sureforms-payment-search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<input type="search" name="search_filter" value="<?php echo esc_attr( $search_term ); ?>" class="sureforms-payments-search-box" id="<?php echo esc_attr( $input_id ); ?>" placeholder="<?php esc_attr_e( 'Search by ID or Transaction ID', 'sureforms' ); ?>">
			<button type="submit" class="button" id="search-submit"><?php echo esc_html( $text ); ?></button>
		</p>
		<?php
	}

	/**
	 * Displays the table.
	 *
	 * @since x.x.x
	 */
	public function display() {
		$singular = $this->_args['singular'];
		$this->views();
		$this->search_box_markup( esc_html__( 'Search', 'sureforms' ), 'srfm-payments' );
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
	 * @since x.x.x
	 * @return void
	 */
	public static function process_bulk_actions() {
		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_payments_action' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( ( new self() )->current_action() ) );

		if ( ! $action ) {
			return;
		}

		// Get the selected payment IDs.
		$payment_ids = isset( $_GET['payment'] ) ? array_map( 'absint', wp_unslash( $_GET['payment'] ) ) : [];

		if ( 'export' === $action ) {
			// Handle export functionality (to be implemented)
			// This will be similar to entries export but for payments
			return;
		}

		// If there are payment IDs selected, process the bulk action.
		if ( ! empty( $payment_ids ) ) {
			// Update the status of each selected payment.
			foreach ( $payment_ids as $payment_id ) {
				self::handle_payment_action( Helper::get_integer_value( $payment_id ), $action );
			}

			set_transient(
				'srfm_bulk_action_message',
				[
					'action' => $action,
					'count'  => count( $payment_ids ),
				],
				30
			); // Transient expires in 30 seconds.
			// Redirect to prevent form resubmission.
			wp_safe_redirect( admin_url( 'admin.php?page=sureforms_payments' ) );
			exit;
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
		// Manually delete the transient after retrieval to prevent it from being displayed again after page reload.
		delete_transient( 'srfm_bulk_action_message' );
		$action  = $bulk_action_message['action'];
		$count   = $bulk_action_message['count'] ?? 0;
		$message = $bulk_action_message['message'] ?? '';
		$type    = $bulk_action_message['type'] ?? 'success';

		if ( ! $message && $count ) {
			// If we don't have $message added manually, and have $count then lets create message according to the action as default.
			switch ( $action ) {
				case 'delete':
					// translators: %1$d refers to the number of payments.
					$message = sprintf( _n( '%1$d payment was successfully deleted.', '%1$d payments were successfully deleted.', $count, 'sureforms' ), $count );
					break;
				default:
					return;
			}
		}

		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Common function to handle payment actions.
	 *
	 * @param int    $payment_id The ID of the payment.
	 * @param string $action The action to perform.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function handle_payment_action( $payment_id, $action ) {
		switch ( $action ) {
			case 'delete':
				Payments::delete( $payment_id );
				break;
			default:
				break;
		}
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
		$payment_id = esc_attr( $item['id'] );

		$view_url =
		wp_nonce_url(
			add_query_arg(
				[
					'payment_id' => $payment_id,
					'view'       => 'details',
				],
				admin_url( 'admin.php?page=sureforms_payments' )
			),
			'srfm_payments_action'
		);

		return sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s%3$s</a></strong>',
			$view_url,
			esc_html__( 'Payment #', 'sureforms' ),
			$payment_id
		) . $this->row_actions( $this->package_row_actions( $item ) );
	}

	/**
	 * Define the data for the "date" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_date( $item ) {
		$created_at = gmdate( 'Y/m/d \\a\\t g:i a', strtotime( $item['created_at'] ) );

		return sprintf(
			'<span>%1$s</span>',
			$created_at
		);
	}

	/**
	 * Define the data for the "type" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_type( $item ) {
		$type = ! empty( $item['type'] ) ? $item['type'] : __( 'One-time', 'sureforms' );
		return sprintf(
			'<span>%s</span>',
			esc_html( ucfirst( $type ) )
		);
	}

	/**
	 * Define the data for the "form_id" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_form_id( $item ) {
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
	 * @since x.x.x
	 * @return string
	 */
	protected function column_status( $item ) {
		$status_labels = [
			'pending'                 => __( 'Pending', 'sureforms' ),
			'succeeded'               => __( 'Succeeded', 'sureforms' ),
			'failed'                  => __( 'Failed', 'sureforms' ),
			'canceled'                => __( 'Canceled', 'sureforms' ),
			'requires_action'         => __( 'Requires Action', 'sureforms' ),
			'requires_payment_method' => __( 'Requires Payment Method', 'sureforms' ),
			'processing'              => __( 'Processing', 'sureforms' ),
		];

		$status_label = $status_labels[ $item['status'] ] ?? ucfirst( $item['status'] );

		return sprintf(
			'<span class="payment-status-%1$s">%2$s</span>',
			esc_attr( $item['status'] ),
			esc_html( $status_label )
		);
	}

	/**
	 * Define the data for the "amount" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_amount( $item ) {
		$amount          = ! empty( $item['total_amount'] ) ? floatval( $item['total_amount'] ) : 0;
		$refunded_amount = ! empty( $item['refunded_amount'] ) ? floatval( $item['refunded_amount'] ) : 0;
		$currency        = ! empty( $item['currency'] ) ? strtoupper( $item['currency'] ) : 'USD';

		// Show net amount if there are refunds
		if ( $refunded_amount > 0 ) {
			$net_amount = $amount - $refunded_amount;
			return sprintf(
				'<span>%1$s %2$s <small style="color: #6c757d;">(net)</small></span>',
				esc_html( $currency ),
				number_format( $net_amount, 2 )
			);
		}

		return sprintf(
			'<span>%1$s %2$s</span>',
			esc_html( $currency ),
			number_format( $amount, 2 )
		);
	}

	/**
	 * Define the data for the "refunded_amount" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_refunded_amount( $item ) {
		$refunded_amount = ! empty( $item['refunded_amount'] ) ? floatval( $item['refunded_amount'] ) : 0;
		$currency        = ! empty( $item['currency'] ) ? strtoupper( $item['currency'] ) : 'USD';

		if ( $refunded_amount > 0 ) {
			return sprintf(
				'<span style="color: #d63384;">%1$s %2$s</span>',
				esc_html( $currency ),
				number_format( $refunded_amount, 2 )
			);
		}

		return '<span style="color: #6c757d;">—</span>';
	}

	/**
	 * Define the data for the "gateway" column and return the markup.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function column_gateway( $item ) {
		$gateway = ! empty( $item['gateway'] ) ? $item['gateway'] : __( 'Unknown', 'sureforms' );
		return sprintf(
			'<span>%s</span>',
			esc_html( ucfirst( $gateway ) )
		);
	}

	/**
	 * Returns array of row actions for payments.
	 *
	 * @param array $item Column data.
	 *
	 * @since x.x.x
	 * @return array
	 */
	protected function package_row_actions( $item ) {
		$view_url   =
		wp_nonce_url(
			add_query_arg(
				[
					'payment_id' => esc_attr( $item['id'] ),
					'view'       => 'details',
				],
				admin_url( 'admin.php?page=sureforms_payments' )
			),
			'srfm_payments_action'
		);
		$delete_url =
		wp_nonce_url(
			add_query_arg(
				[
					'payment_id' => esc_attr( $item['id'] ),
					'action'     => 'delete',
				],
				admin_url( 'admin.php?page=sureforms_payments' )
			),
			'srfm_payments_action'
		);

		$row_actions = [
			'view'   => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $view_url ), esc_html__( 'View', 'sureforms' ) ),
			'delete' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $delete_url ), esc_html__( 'Delete', 'sureforms' ) ),
		];

		return apply_filters( 'sureforms_payments_table_row_actions', $row_actions, $item );
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
			?>
			<div class="alignleft actions">
				<?php
				$this->display_month_filter();
				$this->display_form_filter();
				$this->display_status_filter();

				if ( $this->is_filter_enabled() ) {
					?>
					<a href="<?php echo esc_url( add_query_arg( 'page', 'sureforms_payments', admin_url( 'admin.php' ) ) ); ?>" class="button button-link clear-filter"><?php esc_html_e( 'Clear Filter', 'sureforms' ); ?></a>
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
	 * @since x.x.x
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'srfm_payments_action' );
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
	 * Returns true if any filter is enabled.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	protected function is_filter_enabled() {
		$intersect = array_intersect(
			[
				'form_filter',
				'month_filter',
				'status_filter',
			],
			array_keys( wp_unslash( $_GET ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is fine because we are not using it to save in the database.
		);

		return ! empty( $intersect );
	}

	/**
	 * Display the available form name to filter payments.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_form_filter() {
		$forms = $this->get_available_forms();
		// Added the phpcs ignore nonce verification as no database operations are performed in this function.
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<input type="hidden" name="view" value="' . esc_attr( $view ) . '" />';
		echo '<select name="form_filter">';
		echo '<option value="all">' . esc_html__( 'All Forms', 'sureforms' ) . '</option>';
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
	 * Display the status filter.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_status_filter() {
		$statuses = [
			'all'                     => __( 'All Statuses', 'sureforms' ),
			'pending'                 => __( 'Pending', 'sureforms' ),
			'succeeded'               => __( 'Succeeded', 'sureforms' ),
			'failed'                  => __( 'Failed', 'sureforms' ),
			'canceled'                => __( 'Canceled', 'sureforms' ),
			'requires_action'         => __( 'Requires Action', 'sureforms' ),
			'requires_payment_method' => __( 'Requires Payment Method', 'sureforms' ),
			'processing'              => __( 'Processing', 'sureforms' ),
		];

		echo '<select name="status_filter">';
		foreach ( $statuses as $status_value => $status_label ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = isset( $_GET['status_filter'] ) && sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ) === $status_value ? ' selected="selected"' : '';
			printf( '<option value="%s"%s>%s</option>', esc_attr( $status_value ), esc_attr( $selected ), esc_html( $status_label ) );
		}
		echo '</select>';
	}

	/**
	 * Display the month and year from which the payments are present to filter payments according to time.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function display_month_filter() {
		if ( isset( $_GET['month_filter'] ) && ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_payments_action' ) ) ) {
			return;
		}
		$view    = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all';
		$form_id = isset( $_GET['form_filter'] ) && 'all' !== $_GET['form_filter'] ? absint( wp_unslash( $_GET['form_filter'] ) ) : 0;
		$months  = Payments::get_available_months( self::get_where_conditions( $form_id, $view, [ 'search_filter', 'month_filter' ] ) );

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
	 * Get the views for the payments table.
	 *
	 * @since x.x.x
	 * @return array<string,string>
	 */
	protected function get_views() {
		// Get the status count of the payments.
		$pending_payments_count   = Payments::get_total_payments_by_status( 'pending' );
		$succeeded_payments_count = Payments::get_total_payments_by_status( 'succeeded' );
		$failed_payments_count    = Payments::get_total_payments_by_status( 'failed' );

		// Get the current view to highlight the selected one.
		// Adding the phpcs ignore nonce verification as no complex operations are performed here only the count of the payments is required.
		$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Define the base URL for the views (without query parameters).
		$base_url = wp_nonce_url( admin_url( 'admin.php?page=sureforms_payments' ), 'srfm_payments_action' );

		// Create the array of view links.
		$views = [
			'all'       => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'all', $base_url ),
				'all' === $current_view ? 'current' : '',
				esc_html__( 'All', 'sureforms' ),
				$this->all_payments_count
			),
			'succeeded' => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'succeeded', $base_url ),
				'succeeded' === $current_view ? 'current' : '',
				esc_html__( 'Succeeded', 'sureforms' ),
				$succeeded_payments_count
			),
			'pending'   => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'pending', $base_url ),
				'pending' === $current_view ? 'current' : '',
				esc_html__( 'Pending', 'sureforms' ),
				$pending_payments_count
			),
		];

		// Only add the Failed view if the count is greater than 0.
		if ( $failed_payments_count > 0 ) {
			$views['failed'] = sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				add_query_arg( 'view', 'failed', $base_url ),
				'failed' === $current_view ? 'current' : '',
				esc_html__( 'Failed', 'sureforms' ),
				$failed_payments_count
			);
		}

		return $views;
	}

	/**
	 * Get the payments data.
	 *
	 * @param int      $per_page Number of payments to fetch per page.
	 * @param int      $current_page Current page number.
	 * @param string   $view The view to fetch the payments count from.
	 * @param int|null $form_id The ID of the form to fetch payments for.
	 *
	 * @since x.x.x
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

		$offset               = ( $current_page - 1 ) * $per_page;
		$where_condition      = self::get_where_conditions( $form_id, $view );
		$this->data           = Payments::get_all(
			[
				'limit'   => $per_page,
				'offset'  => $offset,
				'where'   => $where_condition,
				'orderby' => $orderby,
				'order'   => $order,
			]
		);
		$this->payments_count = Payments::get_total_payments_by_status( $view, $form_id, $where_condition );
		return $this->data;
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
	 * Return the where conditions to add to the query for filtering payments.
	 *
	 * @param int           $form_id The ID of the form to fetch payments for.
	 * @param string        $view The view to fetch payments for.
	 * @param array<string> $exclude_filters Added filters to exclude from where clause.
	 *
	 * @since x.x.x
	 * @return array<mixed>
	 */
	private static function get_where_conditions( $form_id = 0, $view = 'all', $exclude_filters = [] ) {
		if ( ! isset( $_GET['_wpnonce'] ) || ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_payments_action' ) ) ) {
			// Return the default condition to fetch all payments.
			return [];
		}

		$where_condition = [];

		// Set the where condition based on the view for populating filters.
		switch ( $view ) {
			case 'all':
				// No additional status condition for all view.
				break;
			case 'pending':
			case 'succeeded':
			case 'failed':
			case 'canceled':
			case 'requires_action':
			case 'requires_payment_method':
			case 'processing':
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

		// If form ID is set, then we need to add the form ID condition to the where clause to fetch payments only for that form.
		if ( 0 < $form_id ) {
			$where_condition[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		// Handle status filter.
		if ( ! in_array( 'status_filter', $exclude_filters, true ) ) {
			$status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ) : '';

			if ( ! empty( $status_filter ) && 'all' !== $status_filter ) {
				$where_condition[] = [
					[
						'key'     => 'status',
						'compare' => '=',
						'value'   => $status_filter,
					],
				];
			}
		}

		if ( ! in_array( 'search_filter', $exclude_filters, true ) ) {
			// Handle the search according to payment ID and transaction ID.
			$search_term = isset( $_GET['search_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['search_filter'] ) ) : '';

			// Apply search filter, currently search is based on payment ID and transaction ID.
			if ( ! empty( $search_term ) ) {
				$where_condition[] = [
					[
						'key'     => 'id',
						'compare' => 'LIKE',
						'value'   => $search_term,
					],
					[
						'key'     => 'transaction_id',
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
				// Using two conditions to filter the payments based on the start and end date.
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
}
