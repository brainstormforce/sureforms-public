import { useContext, useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import {
	Table,
	Badge,
	Button,
	Input,
	Select,
	Pagination,
	Container,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import {
	Eye as ViewIcon,
	Search,
	X,
	Trash as DeleteIcon,
	ExternalLink,
	ChevronsUpDown,
	ChevronUp,
	ChevronDown,
} from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { PaymentContext } from '../components/context';
import { fetchPayments, bulkDeletePayments, fetchForms } from '../components/apiCalls';
import {
	getPaginationRange,
	getStatusVariant,
	formatAmount,
	formatDateTime,
	getStatusLabel,
	formatOrderId,
} from '../components/utils';
import DeleteConfirmationDialog from '../components/DeleteConfirmationDialog';
import DateRangePicker from '../components/DateRangePicker';
import {
	getUrlParams,
	updateUrlParams,
	clearUrlParams,
} from '../components/urlUtils';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import PaymentListPlaceHolder from '../components/paymentListPlaceHolder';

// Payment status filters - mapped to database statuses
const STATUS_FILTERS = [
	{ value: 'succeeded', label: __( 'Paid', 'sureforms' ) },
	{
		value: 'partially_refunded',
		label: __( 'Partially Refunded', 'sureforms' ),
	},
	{ value: 'pending', label: __( 'Pending', 'sureforms' ) },
	{ value: 'failed', label: __( 'Failed', 'sureforms' ) },
	{ value: 'refunded', label: __( 'Refunded', 'sureforms' ) },
];

/**
 * Custom hook for debouncing a value
 *
 * @param {any}    value - The value to debounce
 * @param {number} delay - Delay in milliseconds
 * @return {any} Debounced value
 */
const useDebounce = ( value, delay = 500 ) => {
	const [ debouncedValue, setDebouncedValue ] = useState( value );

	useEffect( () => {
		const handler = setTimeout( () => {
			setDebouncedValue( value );
		}, delay );

		return () => clearTimeout( handler );
	}, [ value, delay ] );

	return debouncedValue;
};

const PaymentTable = () => {
	// Selection state management
	const [ selectedRows, setSelectedRows ] = useState( [] );

	// Delete confirmation dialog state
	const [ isDeleteDialogOpen, setIsDeleteDialogOpen ] = useState( false );
	const [ deletePaymentIds, setDeletePaymentIds ] = useState( [] );

	const { setViewSinglePayment, setSinglePaymentType } =
		useContext( PaymentContext );

	// React Query client for cache invalidation
	const queryClient = useQueryClient();

	// Filter and search state
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ filter, setFilter ] = useState( '' );
	const [ formFilter, setFormFilter ] = useState( '' );
	const [ selectedDates, setSelectedDates ] = useState( {
		from: null,
		to: null,
	} );
	const [ sortBy, setSortBy ] = useState( null ); // 'amount-asc' or 'amount-desc'

	// Debounce search term to avoid excessive API calls
	const debouncedSearchTerm = useDebounce( searchTerm, 500 );

	// Pagination state
	const [ page, setPage ] = useState( 1 );
	const [ itemsPerPage, setItemsPerPage ] = useState( 10 );

	// Fetch forms list using React Query
	const { data: formsData } = useQuery( {
		queryKey: [ 'forms' ],
		queryFn: fetchForms,
		staleTime: 10 * 60 * 1000, // 10 minutes - forms don't change often
		refetchOnWindowFocus: false,
	} );

	const formsList = formsData || [];

	// Fetch payments data using React Query
	const queryData = useQuery( {
		queryKey: [
			'payments',
			{ searchTerm: debouncedSearchTerm, filter, formFilter, selectedDates, page, itemsPerPage, sortBy },
		],
		queryFn: () =>
			fetchPayments( {
				searchTerm: debouncedSearchTerm,
				filter,
				formFilter,
				selectedDates,
				page,
				itemsPerPage,
				sortBy,
			} ),
		staleTime: 5 * 60 * 1000, // 5 minutes
		refetchOnWindowFocus: false,
	} );

	const { data: paymentsData, isLoading, error } = queryData;

	console.log( {
		queryData,
		data: paymentsData,
		update: '12',
	} );

	// Bulk delete mutation
	const bulkDeleteMutation = useMutation( {
		mutationFn: bulkDeletePayments,
		onSuccess: ( data ) => {
			// APP layer handles all message formatting
			let message;

			// Check if partial success (some failed, some succeeded)
			if ( data.partial ) {
				// Show warning toast for partial success
				message =
					data.message ||
					__( 'Some payments could not be deleted', 'sureforms' );
				toast.warning( message, {
					duration: 5000, // 5 seconds for warnings
				} );
			} else {
				// Show success toast for complete success
				message =
					data.message ||
					__( 'Payments deleted successfully', 'sureforms' );
				toast.success( message, {
					duration: 4000, // 4 seconds
				} );
			}

			// Clear selected rows
			setSelectedRows( [] );

			// Refresh payments list
			queryClient.invalidateQueries( [ 'payments' ] );

			// Close dialog
			setIsDeleteDialogOpen( false );
		},
		onError: ( mutationError ) => {
			console.error( 'Delete mutation error:', error );
			console.error( 'Error type:', {
				isApiError: mutationError.isApiError,
				isNetworkError: mutationError.isNetworkError,
				isValidationError: mutationError.isValidationError,
			} );
			console.error( 'Error data:', mutationError.data );

			// APP layer extracts and formats error messages
			let errorMessage;

			// Check for backend API error message
			if ( mutationError.data?.message ) {
				errorMessage = mutationError.data.message;
			} else if ( mutationError.isValidationError ) {
				errorMessage = __( 'No payment IDs provided', 'sureforms' );
			} else if ( mutationError.isNetworkError ) {
				errorMessage = __(
					'Network error. Please check your connection and try again.',
					'sureforms'
				);
			} else if (
				mutationError.message &&
				mutationError.message !== 'API request failed' &&
				mutationError.message !== 'Validation failed'
			) {
				errorMessage = mutationError.message;
			} else {
				errorMessage = __(
					'Failed to delete payments. Please try again.',
					'sureforms'
				);
			}

			// Show error toast with formatted message
			toast.error( errorMessage, {} );

			// Close dialog
			setIsDeleteDialogOpen( false );
		},
	} );

	// Use API data or fallback to dummy data
	const allPayments = paymentsData?.payments || [];
	const totalPayments = paymentsData?.total || allPayments.length;
	const totalPages = Math.ceil( totalPayments / itemsPerPage );

	// Get current page data (for client-side pagination fallback)
	const startIndex = ( page - 1 ) * itemsPerPage;
	const endIndex = startIndex + itemsPerPage;
	const payments = paymentsData?.payments
		? allPayments
		: allPayments.slice( startIndex, endIndex );

	// Initialize state from URL on mount
	useEffect( () => {
		const urlParams = getUrlParams();

		// Initialize from URL params
		if ( urlParams.srfm_payment_page ) {
			setPage( parseInt( urlParams.srfm_payment_page ) );
		}
		if ( urlParams.srfm_payment_search ) {
			setSearchTerm( urlParams.srfm_payment_search );
		}
		if ( urlParams.srfm_payment_status ) {
			setFilter( urlParams.srfm_payment_status );
		}
		if ( urlParams.srfm_payment_form ) {
			setFormFilter( urlParams.srfm_payment_form );
		}
		if ( urlParams.srfm_payment_per_page ) {
			setItemsPerPage( parseInt( urlParams.srfm_payment_per_page ) );
		}
		if ( urlParams.srfm_payment_sort ) {
			setSortBy( urlParams.srfm_payment_sort );
		}

		// Parse date range
		if (
			urlParams.srfm_payment_date_from &&
			urlParams.srfm_payment_date_to
		) {
			setSelectedDates( {
				from: new Date( urlParams.srfm_payment_date_from ),
				to: new Date( urlParams.srfm_payment_date_to ),
			} );
		}
	}, [] ); // Run once on mount

	// Sync page to URL
	useEffect( () => {
		if ( page > 1 ) {
			updateUrlParams( { srfm_payment_page: page } );
		} else {
			updateUrlParams( { srfm_payment_page: undefined } );
		}
	}, [ page ] );

	// Sync search to URL (use debounced value to avoid URL flickering)
	useEffect( () => {
		updateUrlParams( {
			srfm_payment_search: debouncedSearchTerm || undefined,
		} );
	}, [ debouncedSearchTerm ] );

	// Sync status filter to URL
	useEffect( () => {
		updateUrlParams( {
			srfm_payment_status: filter || undefined,
		} );
	}, [ filter ] );

	// Sync form filter to URL
	useEffect( () => {
		updateUrlParams( {
			srfm_payment_form: formFilter || undefined,
		} );
	}, [ formFilter ] );

	// Sync items per page to URL (only if not default)
	useEffect( () => {
		if ( itemsPerPage !== 10 ) {
			updateUrlParams( { srfm_payment_per_page: itemsPerPage } );
		} else {
			updateUrlParams( { srfm_payment_per_page: undefined } );
		}
	}, [ itemsPerPage ] );

	// Sync date range to URL
	useEffect( () => {
		const params = {};

		if ( selectedDates.from ) {
			params.srfm_payment_date_from = selectedDates.from
				.toISOString()
				.split( 'T' )[ 0 ];
		} else {
			params.srfm_payment_date_from = undefined;
		}

		if ( selectedDates.to ) {
			params.srfm_payment_date_to = selectedDates.to
				.toISOString()
				.split( 'T' )[ 0 ];
		} else {
			params.srfm_payment_date_to = undefined;
		}

		updateUrlParams( params );
	}, [ selectedDates ] );

	// Sync sortBy to URL
	useEffect( () => {
		updateUrlParams( {
			srfm_payment_sort: sortBy || undefined,
		} );
	}, [ sortBy ] );

	// Handle browser back/forward buttons
	useEffect( () => {
		const handlePopState = () => {
			const urlParams = getUrlParams();

			// Update state from URL when user uses back/forward buttons
			setPage( parseInt( urlParams.srfm_payment_page ) || 1 );
			setSearchTerm( urlParams.srfm_payment_search || '' );
			setFilter( urlParams.srfm_payment_status || '' );
			setFormFilter( urlParams.srfm_payment_form || '' );
			setItemsPerPage(
				parseInt( urlParams.srfm_payment_per_page ) || 10
			);

			if (
				urlParams.srfm_payment_date_from &&
				urlParams.srfm_payment_date_to
			) {
				setSelectedDates( {
					from: new Date( urlParams.srfm_payment_date_from ),
					to: new Date( urlParams.srfm_payment_date_to ),
				} );
			} else {
				setSelectedDates( { from: null, to: null } );
			}
		};

		window.addEventListener( 'popstate', handlePopState );

		return () => {
			window.removeEventListener( 'popstate', handlePopState );
		};
	}, [] );

	// Handle individual row selection
	const handleSelectRow = ( rowId ) => {
		setSelectedRows( ( prev ) =>
			prev.includes( rowId )
				? prev.filter( ( id ) => id !== rowId )
				: [ ...prev, rowId ]
		);
	};

	// Handle select all rows
	const handleSelectAll = () => {
		setSelectedRows(
			selectedRows.length === payments.length
				? []
				: payments.map( ( row ) => row.id )
		);
	};

	// Action handlers - replace with actual functionality in production
	const handleView = ( { id, type } ) => {
		setViewSinglePayment( id );
		setSinglePaymentType( type );
	};

	const handleDeleteSingle = ( id ) => {
		// Open confirmation dialog for single delete
		setDeletePaymentIds( [ id ] );
		setIsDeleteDialogOpen( true );
	};

	const handleDelete = ( paymentIds ) => {
		// Open confirmation dialog for bulk delete
		setDeletePaymentIds( paymentIds );
		setIsDeleteDialogOpen( true );
	};

	const confirmDelete = () => {
		// Execute deletion
		bulkDeleteMutation.mutate( deletePaymentIds );
	};

	const cancelDelete = () => {
		setIsDeleteDialogOpen( false );
		setDeletePaymentIds( [] );
	};

	// Date range filter handlers
	const handleDateApply = ( dates ) => {
		setSelectedDates( dates );
	};

	// Pagination event handlers
	const handlePageChange = ( newPage ) => {
		setPage( newPage );
		setSelectedRows( [] ); // Clear selections when changing pages
	};

	const handleItemsPerPageChange = ( newItemsPerPage ) => {
		setItemsPerPage( newItemsPerPage );
		setSelectedRows( [] ); // Clear selections when changing page size
	};

	// Handle amount sorting
	const handleAmountSort = () => {
		if ( sortBy === null ) {
			setSortBy( 'amount-asc' );
		} else if ( sortBy === 'amount-asc' ) {
			setSortBy( 'amount-desc' );
		} else {
			setSortBy( null );
		}
	};

	const tableLoading = () => (
		<Table.Row>
			<Table.Cell colSpan={ 7 } className="text-center py-8">
				{ __( 'Loading payments…', 'sureforms' ) }
			</Table.Cell>
		</Table.Row>
	);

	const tableError = () => (
		<Table.Row>
			<Table.Cell colSpan={ 7 } className="text-center py-8 text-red-600">
				{ __(
					'Error loading payments. Please try again.',
					'sureforms'
				) }
			</Table.Cell>
		</Table.Row>
	);

	const tableNoPayments = () => (
		<Table.Row>
			<Table.Cell colSpan={ 7 } className="text-center py-8">
				{ __( 'No payments found.', 'sureforms' ) }
			</Table.Cell>
		</Table.Row>
	);

	const tableHead = () => {
		const tableHeadContent = applyFilters(
			'srfm_payment_admin_table_head_content',
			[
				{
					key: 'order_id',
					title: __( 'Order Id', 'sureforms' ),
				},
				{
					key: 'customer_email',
					title: __( 'Customer Email', 'sureforms' ),
				},
				{
					key: 'type',
					title: __( 'Type', 'sureforms' ),
				},
				{
					key: 'amountPaid',
					title: (
						<div className="flex items-center gap-2">
							{ __( 'Amount Paid', 'sureforms' ) }
							<Button
								variant="ghost"
								size="sm"
								onClick={ handleAmountSort }
								icon={
									sortBy === 'amount-asc' ? (
										<ChevronUp className="w-4 h-4" />
									) : sortBy === 'amount-desc' ? (
										<ChevronDown className="w-4 h-4" />
									) : (
										<ChevronsUpDown className="w-4 h-4" />
									)
								}
							/>
						</div>
					),
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
				onChangeSelection={ handleSelectAll }
				indeterminate={
					selectedRows.length > 0 &&
					selectedRows.length < payments.length
				}
				selected={ selectedRows.length > 0 }
			>
				{ tableHeadContent.map( ( head ) => (
					<Table.HeadCell
						key={ head.key }
						className={ head.className }
					>
						{ head.title }
					</Table.HeadCell>
				) ) }
			</Table.Head>
		);
	};

	const tableRow = ( payment ) => {
		const rowAction = (
			<div className="flex items-center justify-end gap-2">
				<Tooltip
					arrow
					content={
						<span>{ __( 'View Transaction', 'sureforms' ) }</span>
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
						className="p-0"
						onClick={ () =>
							handleView( {
								id: payment.id,
								type: payment.type,
							} )
						}
					>
						<ViewIcon className="w-4 h-4" />
					</Button>
				</Tooltip>
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
						onClick={ () => handleDeleteSingle( payment.id ) }
						className="p-0"
					>
						<DeleteIcon className="w-4 h-4" />
					</Button>
				</Tooltip>
			</div>
		);

		const rawStatus = "subscription" === payment.type ? payment.subscription_status : payment.status;

		const rowStatusBadge = (
			<Badge
				label={ getStatusLabel( rawStatus ) }
				variant={ getStatusVariant( rawStatus ) }
				size="sm"
				className="max-w-fit"
				disableHover
			/>
		);

		const paymentType = (
			<Badge
				label={ payment.payment_type }
				variant={ 'subscription' === payment.type ? 'blue' : 'neutral' }
				size="sm"
				className="max-w-fit"
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
			<span style={ { display: 'flex', gap: '8px' } }>
				<span
					style={ {
						textDecoration: 'line-through',
						color: '#6c757d',
					} }
				>
					{ formatAmount( originalAmount, payment.currency ) }
				</span>
				<strong>
					{ formatAmount( remainingAmount, payment.currency ) }
				</strong>
			</span>
		) : (
			formatAmount( originalAmount, payment.currency )
		);

		const orderId = formatOrderId( payment );

		const tableRowContent = applyFilters(
			'srfm_payment_admin_table_row_content',
			[
				{ key: 'order_id', content: orderId },
				{ key: 'customer_email', content: payment.customer_email },
				{ key: 'type', content: paymentType },
				{ key: 'amountPaid', content: rowAmountPaid },
				{ key: 'status', content: rowStatusBadge },
				{ key: 'dateTime', content: formatDateTime( payment.datetime ) },
				{ key: 'actions', content: rowAction },
			],
			payment,
		);

		return (
			<Table.Row
				key={ payment.id }
				selected={ selectedRows.includes( payment.id ) }
				onChangeSelection={ () => handleSelectRow( payment.id ) }
			>
				{ tableRowContent.map( ( row ) => (
					<Table.Cell key={ row.key }>{ row.content }</Table.Cell>
				) ) }
			</Table.Row>
		);
	};

	if ( isLoading ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<Container
					containerType="flex"
					direction="column"
					gap="xs"
					className="w-full h-full"
				>
					<LoadingSkeleton count={ 10 } className="min-h-[44px]" />
				</Container>
			</div>
		);
	}

	// IF paymentsData.transactions_is_empty = "with_no_filter"
	if (
		! paymentsData ||
		paymentsData?.transactions_is_empty === 'with_no_filter'
	) {
		return <PaymentListPlaceHolder />;
	}

	// Delete confirmation dialog
	const deleteConfirmationDialog = (
		<DeleteConfirmationDialog
			isOpen={ isDeleteDialogOpen }
			setIsOpen={ setIsDeleteDialogOpen }
			deletePaymentIds={ deletePaymentIds }
			onConfirm={ confirmDelete }
			onCancel={ cancelDelete }
			isDeleting={ bulkDeleteMutation.isPending }
		/>
	);

	return (
		<div className="min-h-screen px-8 py-8 bg-background-secondary">
			<div className="p-4 border-0.5 border-solid shadow-sm bg-background-primary rounded-xl border-border-subtle">
				<div>
					{ /* Filters or Batch Actions */ }
					<div className="flex items-center justify-between p-1.25">
						<h1 className="text-xl font-semibold text-text-primary">
							{ __( 'Payment Logs', 'sureforms' ) }
						</h1>
						<div className="flex space-x-4">
							{ selectedRows.length > 0 ? (
								// Batch Action Buttons
								<>
									<Button
										variant="outline"
										icon={ <DeleteIcon /> }
										size="sm"
										onClick={ () =>
											handleDelete( selectedRows )
										}
										destructive
									>
										{ __( 'Delete', 'sureforms' ) }
									</Button>
								</>
							) : (
								// Filter Controls
								<>
									{ /* Conditionally Render Reset Filters Button */ }
									{ ( selectedDates.from ||
										selectedDates.to ||
										filter ||
										formFilter ||
										searchTerm ) && (
										<Button
											variant="link"
											size="sm"
											icon={ <X /> }
											onClick={ () => {
												setSelectedDates( {
													from: null,
													to: null,
												} );
												setFilter( '' );
												setFormFilter( '' );
												setSearchTerm( '' );
												setPage( 1 );
												setSortBy( null );

												// Clear URL params
												clearUrlParams( [
													'srfm_payment_search',
													'srfm_payment_status',
													'srfm_payment_form',
													'srfm_payment_date_from',
													'srfm_payment_date_to',
													'srfm_payment_page',
													'srfm_payment_per_page',
													'srfm_payment_sort',
												] );
											} }
											destructive
											className="leading-4 no-underline hover:no-underline min-w-fit focus:[box-shadow:none]"
										>
											{ __(
												'Clear Filters',
												'sureforms'
											) }
										</Button>
									) }

									<Input
										className="w-52"
										type="text"
										size="sm"
										onChange={ setSearchTerm }
										value={ searchTerm }
										placeholder={ __(
											'Search…',
											'sureforms'
										) }
										prefix={
											<Search className="text-icon-secondary" />
										}
									/>

									{ /* Form Filter */ }
									<div className="min-w-[200px]">
										<Select
											value={ formFilter }
											onChange={ setFormFilter }
											size="sm"
										>
											<Select.Button
												className="w-52 h-[2rem] [&_div]:text-xs"
												placeholder={ __(
													'Form',
													'sureforms'
												) }
											>
												{ ( { value: renderValue } ) => {
													if ( ! renderValue ) {
														return __(
															'Form',
															'sureforms'
														);
													}
													const selectedForm = formsList.find(
														( form ) => form.id === parseInt( renderValue )
													);
													return selectedForm
														? selectedForm.title
														: __( 'Form', 'sureforms' );
												} }
											</Select.Button>
											<Select.Options>
												{ formsList.map( ( form ) => (
													<Select.Option
														key={ form.id }
														value={ form.id }
														className="text-xs"
													>
														<span>{ form.title }</span>
													</Select.Option>
												) ) }
											</Select.Options>
										</Select>
									</div>

									{ /* Status Filter */ }
									<div className="min-w-[200px]">
										<Select
											value={ filter }
											onChange={ setFilter }
											size="sm"
										>
											<Select.Button
												className="w-52 h-[2rem] [&_div]:text-xs"
												placeholder={ __(
													'Status',
													'sureforms'
												) }
											>
												{ ( { value: renderValue } ) =>
													renderValue
														? getStatusLabel(
															renderValue
														  )
														: __(
															'Select Status',
															'sureforms'
														  )
												}
											</Select.Button>
											<Select.Options>
												{ STATUS_FILTERS.map(
													( option ) => (
														<Select.Option
															key={ option.value }
															value={
																option.value
															}
															className="text-xs"
														>
															<span>
																{ option.label }
															</span>
														</Select.Option>
													)
												) }
											</Select.Options>
										</Select>
									</div>
									{ /* Date Range Picker */ }
									<DateRangePicker
										selectedDates={ selectedDates }
										onApply={ handleDateApply }
									/>
								</>
							) }
						</div>
					</div>
				</div>

				{ /* Payment Table Content */ }
				<div className="bg-background-primary mt-4">
					<div className="overflow-x-auto">
						<Table className="w-full" checkboxSelection>
							{ tableHead() }
							<Table.Body>
								{ isLoading
									? tableLoading()
									: error
										? tableError()
										: payments.length === 0
											? tableNoPayments()
											: payments.map( tableRow ) }
							</Table.Body>
							<Table.Footer className="flex items-center justify-between">
								{ /* Page Label - aligned to the left */ }
								<div className="text-sm font-normal text-text-secondary whitespace-nowrap flex items-center gap-2">
									<div>
										{ __( 'Page', 'sureforms' ) } { page }{ ' ' }
										{ __( 'out of', 'sureforms' ) }{ ' ' }
										{ totalPages }
									</div>
									<div>
										{ /* Items per page dropdown */ }
										<Select
											value={ itemsPerPage }
											onChange={
												handleItemsPerPageChange
											}
											size="sm"
										>
											<Select.Button className="w-16 h-[1.75rem]">
												{ ( { value: renderValue } ) =>
													renderValue || itemsPerPage
												}
											</Select.Button>
											<Select.Options>
												{ [ 2, 5, 10, 20, 50, 100 ].map(
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

								{ /* Pagination Controls - aligned to the right */ }
								<div className="flex items-center space-x-2">
									<Pagination size="sm">
										<Pagination.Content className="[&>li]:m-0">
											<Pagination.Previous
												tag="button"
												onClick={ () =>
													setPage( ( prev ) =>
														Math.max( prev - 1, 1 )
													)
												}
												disabled={ page === 1 }
											/>
											{ getPaginationRange(
												page,
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
															page === item
														}
														onClick={ () =>
															handlePageChange(
																item
															)
														}
													>
														{ item }
													</Pagination.Item>
												);
											} ) }
											<Pagination.Next
												tag="button"
												onClick={ () =>
													setPage( ( prev ) =>
														Math.min(
															prev + 1,
															totalPages
														)
													)
												}
												disabled={ page === totalPages }
											/>
										</Pagination.Content>
									</Pagination>
								</div>
							</Table.Footer>
						</Table>
					</div>
				</div>
			</div>
			{ deleteConfirmationDialog }
		</div>
	);
};

export default PaymentTable;
