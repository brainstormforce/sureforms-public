import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import {
	Table,
	Badge,
	Button,
	Tooltip,
	Select,
	Pagination,
	Skeleton,
} from '@bsf/force-ui';
import { ExternalLink, Trash as DeleteIcon } from 'lucide-react';
import {
	getStatusVariant,
	formatAmount,
	formatDateTime,
	getStatusLabel,
	formatOrderId,
	PartialAmount,
	getPaginationRange,
} from './utils';
import { cn } from '@Utils/Helpers';

/**
 * PaymentTable Component - Displays the payments table
 *
 * @param {Object}   props
 * @param {Array}    props.payments             - Array of payment objects
 * @param {Array}    props.selectedRows         - Array of selected payment IDs
 * @param {Function} props.onSelectRow          - Handler for row selection
 * @param {Function} props.onSelectAll          - Handler for select all
 * @param {Function} props.onView               - Handler for viewing payment details
 * @param {Function} props.onDelete             - Handler for deleting payment
 * @param {boolean}  props.isLoading            - Whether data is loading
 * @param {number}   props.currentPage          - Current page number
 * @param {number}   props.totalPages           - Total number of pages
 * @param {number}   props.itemsPerPage         - Items per page
 * @param {Function} props.onPageChange         - Page change handler
 * @param {Function} props.onItemsPerPageChange - Items per page change handler
 */
const PaymentTable = ( {
	payments = [],
	selectedRows = [],
	onSelectRow,
	onSelectAll,
	onView,
	onDelete,
	isLoading = false,
	currentPage = 1,
	totalPages = 0,
	itemsPerPage = 10,
	onPageChange,
	onItemsPerPageChange,
} ) => {
	// Table head definition
	const tableHead = () => {
		const tableHeadContent = applyFilters(
			'srfm_payment_admin_table_head_content',
			[
				{
					key: 'order_id',
					title: __( 'Order ID', 'sureforms' ),
					className: 'w-[22%]',
				},
				{
					key: 'customer_email',
					title: __( 'Customer Email', 'sureforms' ),
					className: 'w-[18%]',
				},
				{
					key: 'type',
					title: __( 'Type', 'sureforms' ),
				},
				{
					key: 'amountPaid',
					title: __( 'Amount', 'sureforms' ),
				},
				{
					key: 'status',
					title: __( 'Status', 'sureforms' ),
				},
				{
					key: 'dateTime',
					title: __( 'Transaction Date', 'sureforms' ),
				},
				{
					key: 'actions',
					title: __( 'Actions', 'sureforms' ),
					className: 'text-right',
				},
			]
		);

		return (
			<Table.Head
				onChangeSelection={ onSelectAll }
				indeterminate={
					selectedRows.length > 0 &&
					selectedRows.length < payments.length
				}
				selected={ selectedRows.length > 0 }
			>
				{ tableHeadContent.map( ( head ) => (
					<Table.HeadCell
						key={ head.key }
						className={ cn( head.className, 'whitespace-nowrap' ) }
					>
						{ head.title }
					</Table.HeadCell>
				) ) }
			</Table.Head>
		);
	};

	// Table row renderer
	const tableRow = ( payment ) => {
		const rowAction = (
			<div className="flex items-center justify-end gap-2">
				<Tooltip
					arrow
					content={ <span>{ __( 'View Form', 'sureforms' ) }</span> }
					placement="top"
					variant="dark"
					triggers={ [ 'hover', 'focus' ] }
					tooltipPortalId="srfm-settings-container"
					interactive
					className="z-999999"
				>
					<Button
						variant="ghost"
						size="sm"
						tag="a"
						href={ payment.form_url }
						target="_blank"
						rel="noopener noreferrer"
						className="p-0"
					>
						<ExternalLink className="w-4 h-4" />
					</Button>
				</Tooltip>
				<Tooltip
					arrow
					content={
						<span>{ __( 'Remove Transaction', 'sureforms' ) }</span>
					}
					placement="top"
					variant="dark"
					triggers={ [ 'hover', 'focus' ] }
					tooltipPortalId="srfm-settings-container"
					interactive
					className="z-999999"
				>
					<Button
						variant="ghost"
						size="sm"
						onClick={ () => onDelete( payment.id ) }
						className="p-0"
					>
						<DeleteIcon className="w-4 h-4" />
					</Button>
				</Tooltip>
			</div>
		);

		const paymentStatus =
			'active' === payment.status ? 'succeeded' : payment.status;

		const rowStatusBadge = (
			<Badge
				label={ getStatusLabel( paymentStatus ) }
				variant={ getStatusVariant( paymentStatus ) }
				size="xs"
				className="w-fit"
				disableHover
			/>
		);

		// Determine badge variant based on payment type
		let badgeVariant = 'neutral'; // Default for 'payment'
		if ( 'subscription' === payment.type ) {
			badgeVariant = 'blue';
		} else if ( 'renewal' === payment.type ) {
			badgeVariant = 'purple';
		}

		const paymentType = (
			<Badge
				label={ payment.payment_type }
				variant={ badgeVariant }
				size="xs"
				className="w-fit"
				disableHover
			/>
		);

		// Check if payment is partially refunded
		const isPartiallyRefunded =
			payment.status === 'partially_refunded' &&
			payment.refunded_amount &&
			parseFloat( payment.refunded_amount ) > 0;

		// Calculate remaining amount after refund
		const originalAmount = parseFloat(
			payment.total_amount || payment.amount
		);
		const refundedAmount = parseFloat( payment.refunded_amount || 0 );
		const remainingAmount = originalAmount - refundedAmount;

		// Format amount display based on refund status
		const rowAmountPaid = isPartiallyRefunded ? (
			<PartialAmount
				amount={ originalAmount }
				partialAmount={ remainingAmount }
				currency={ payment.currency }
			/>
		) : (
			formatAmount( originalAmount, payment.currency )
		);

		let orderId = formatOrderId( payment );
		orderId = (
			<Button
				variant="ghost"
				size="sm"
				className="p-0 text-text-secondary text-sm weight-medium font-normal hover:text-link-primary hover:underline"
				onClick={ () =>
					onView( {
						id: payment.id,
						type: payment.type,
					} )
				}
			>
				{ orderId }
			</Button>
		);

		const transactionDate = (
			<Tooltip
				arrow
				content={
					<span>{ formatDateTime( payment.datetime, true ) }</span>
				}
				placement="top"
				variant="dark"
				triggers={ [ 'hover', 'focus' ] }
				tooltipPortalId="srfm-settings-container"
				interactive
				className="z-999999"
			>
				<span>{ formatDateTime( payment.datetime ) }</span>
			</Tooltip>
		);

		const tableRowContent = applyFilters(
			'srfm_payment_admin_table_row_content',
			[
				{ key: 'order_id', content: orderId },
				{
					key: 'customer_email',
					content: payment.customer_email,
					className: 'text-wrap break-words',
				},
				{ key: 'type', content: paymentType },
				{ key: 'amountPaid', content: rowAmountPaid },
				{ key: 'status', content: rowStatusBadge },
				{
					key: 'dateTime',
					content: transactionDate,
				},
				{ key: 'actions', content: rowAction },
			],
			payment
		);

		return (
			<Table.Row
				key={ payment.id }
				selected={ selectedRows.includes( payment.id ) }
				onChangeSelection={ () => onSelectRow( payment.id ) }
				value={ payment.id }
			>
				{ tableRowContent.map( ( row ) => (
					<Table.Cell key={ row.key } className={ row.className }>
						{ row.content }
					</Table.Cell>
				) ) }
			</Table.Row>
		);
	};

	// Loading state - skeleton rows
	const tableLoading = () =>
		Array.from( { length: 10 }, ( _, index ) => (
			<Table.Row
				key={ `skeleton-${ index }` }
				className="[&_div:has(label)]:invisible"
			>
				{ Array.from( { length: 7 }, ( _2, colIndex ) => (
					<Table.Cell key={ colIndex }>
						{ colIndex === 0 && (
							<Skeleton className="absolute left-3.5 size-4 rounded-md" />
						) }
						<Skeleton
							variant="rectangular"
							className="h-4 rounded-md w-28"
						/>
					</Table.Cell>
				) ) }
			</Table.Row>
		) );

	return (
		<div className="overflow-x-auto">
			<Table className="w-full" checkboxSelection={ true }>
				{ tableHead() }
				<Table.Body>
					{ isLoading ? tableLoading() : payments.map( tableRow ) }
				</Table.Body>
				<Table.Footer className="flex items-center justify-between">
					{ isLoading ? (
						<>
							<Skeleton className="h-8 w-32 rounded-md" />
							<Skeleton className="h-8 w-40 rounded-md" />
						</>
					) : (
						<>
							{ /* Page Label */ }
							<div className="text-sm font-normal text-text-secondary whitespace-nowrap flex items-center gap-2">
								<div>
									{ __( 'Page', 'sureforms' ) }{ ' ' }
									{ currentPage }{ ' ' }
									{ __( 'out of', 'sureforms' ) }{ ' ' }
									{ totalPages }
								</div>
								<div>
									{ /* Items per page dropdown */ }
									<Select
										value={ itemsPerPage }
										onChange={ onItemsPerPageChange }
										size="sm"
									>
										<Select.Button className="w-16 h-[1.75rem]">
											{ ( { value: renderValue } ) =>
												renderValue || itemsPerPage
											}
										</Select.Button>
										<Select.Options>
											{ [ 10, 20, 50, 100 ].map(
												( count ) => (
													<Select.Option
														key={ count }
														value={ count }
													>
														{ count }
													</Select.Option>
												)
											) }
										</Select.Options>
									</Select>
								</div>
							</div>

							{ /* Pagination Controls */ }
							<div className="flex items-center space-x-2">
								<Pagination size="sm">
									<Pagination.Content className="[&>li]:m-0">
										<Pagination.Previous
											tag="button"
											onClick={ () =>
												onPageChange(
													Math.max(
														currentPage - 1,
														1
													)
												)
											}
											disabled={ currentPage === 1 }
											className={ cn(
												currentPage === 1 &&
													'opacity-50 text-text-tertiary cursor-not-allowed'
											) }
										/>
										{ getPaginationRange(
											currentPage,
											totalPages,
											1
										).map( ( item, index ) => {
											if ( item === 'ellipsis' ) {
												return (
													<Pagination.Ellipsis
														key={ `ellipsis-${ index }` }
													/>
												);
											}
											return (
												<Pagination.Item
													key={ item }
													isActive={
														currentPage === item
													}
													onClick={ () =>
														onPageChange( item )
													}
												>
													{ item }
												</Pagination.Item>
											);
										} ) }
										<Pagination.Next
											tag="button"
											onClick={ () =>
												onPageChange(
													Math.min(
														currentPage + 1,
														totalPages
													)
												)
											}
											disabled={
												currentPage === totalPages
											}
											className={ cn(
												currentPage === totalPages &&
													'opacity-50 text-text-tertiary cursor-not-allowed'
											) }
										/>
									</Pagination.Content>
								</Pagination>
							</div>
						</>
					) }
				</Table.Footer>
			</Table>
		</div>
	);
};

export default PaymentTable;
