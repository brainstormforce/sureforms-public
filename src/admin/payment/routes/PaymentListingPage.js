import { useState, useEffect, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { toast, Button, Text } from '@bsf/force-ui';
import { useNavigate } from 'react-router-dom';
import PaymentFilters from '../components/PaymentFilters';
import PaymentTable from '../components/PaymentTable';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';
import PaymentListPlaceHolder from '../components/paymentListPlaceHolder';
import { usePaymentFilters } from '../hooks/usePaymentFilters';
import { usePaymentPagination } from '../hooks/usePaymentPagination';
import {
	usePayments,
	useForms,
	useBulkDeletePayments,
} from '../hooks/usePaymentQuery';
import { WebhookConfigure } from '../components/utils';

const PaymentListingPage = () => {
	// React Router navigation
	const navigate = useNavigate();

	// Custom hooks for state management
	const {
		searchTerm,
		setSearchTerm,
		statusFilter,
		setStatusFilter,
		formFilter,
		setFormFilter,
		paymentMode,
		setPaymentMode,
		dateRange,
		setDateRange,
		resetFilters,
		hasActiveFilters,
	} = usePaymentFilters();

	const {
		currentPage,
		itemsPerPage,
		setCurrentPage,
		setItemsPerPage,
		resetPage,
	} = usePaymentPagination();

	// Local state
	const [ selectedRows, setSelectedRows ] = useState( [] );

	// State for confirmation dialog
	const [ confirmationDialog, setConfirmationDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		confirmLabel: '',
		onConfirm: null,
		isLoading: false,
		destructive: true,
		enableVerification: false,
	} );

	// Query parameters for API
	const queryParams = useMemo(
		() => ( {
			searchTerm,
			filter: statusFilter,
			formFilter,
			paymentMode,
			selectedDates: dateRange,
			page: currentPage,
			itemsPerPage,
		} ),
		[
			searchTerm,
			statusFilter,
			formFilter,
			paymentMode,
			dateRange,
			currentPage,
			itemsPerPage,
		]
	);

	// Fetch data using React Query
	const { data: paymentsData, isLoading } = usePayments( queryParams );
	const { data: formsData } = useForms();
	const bulkDeleteMutation = useBulkDeletePayments();

	// Extract data
	const payments = paymentsData?.payments || [];
	const totalPayments = paymentsData?.total || 0;
	const totalPages = Math.ceil( totalPayments / itemsPerPage );
	const formsList = formsData || [];

	// Reset pagination when filters change
	useEffect( () => {
		if ( currentPage !== 1 ) {
			resetPage();
		}
	}, [
		searchTerm,
		statusFilter,
		formFilter,
		paymentMode,
		dateRange.from,
		dateRange.to,
	] );

	// Event handlers
	const handleSelectRow = ( rowId ) => {
		setSelectedRows( ( prev ) =>
			prev.includes( rowId )
				? prev.filter( ( id ) => id !== rowId )
				: [ ...prev, rowId ]
		);
	};

	const handleSelectAll = () => {
		setSelectedRows(
			selectedRows.length === payments.length
				? []
				: payments.map( ( row ) => row.id )
		);
	};

	const handleView = ( { id, type } ) => {
		// Navigate to payment detail page with type query parameter
		if ( type === 'subscription' ) {
			navigate( `/payment/${ id }?type=subscription` );
		} else {
			navigate( `/payment/${ id }` );
		}
	};

	const handleDeleteSingle = ( id ) => {
		setConfirmationDialog( {
			open: true,
			title: __( 'Delete Payment', 'sureforms' ),
			description: __(
				'Are you sure you want to delete this payment? This action cannot be undone and will permanently remove all associated data including notes, logs, and transaction information.',
				'sureforms'
			),
			confirmLabel: __( 'Delete Payment', 'sureforms' ),
			onConfirm: () => confirmDelete( [ id ] ),
			isLoading: bulkDeleteMutation.isPending,
			destructive: true,
			enableVerification: true,
		} );
	};

	const handleBulkDelete = () => {
		setConfirmationDialog( {
			open: true,
			title: __( 'Delete Payments', 'sureforms' ),
			description: sprintf(
				/* translators: %d: number of payments */
				__(
					'Are you sure you want to delete %d payments? This action cannot be undone and will permanently remove all associated data including notes, logs, and transaction information.',
					'sureforms'
				),
				selectedRows.length
			),
			confirmLabel: __( 'Delete Payments', 'sureforms' ),
			onConfirm: () => confirmDelete( selectedRows ),
			isLoading: bulkDeleteMutation.isPending,
			destructive: true,
			enableVerification: true,
		} );
	};

	const confirmDelete = ( paymentIds ) => {
		bulkDeleteMutation.mutate( paymentIds, {
			onSuccess: ( data ) => {
				// Handle success messages
				let message;

				if ( data.partial ) {
					message =
						data.message ||
						__( 'Some payments could not be deleted', 'sureforms' );
					toast.warning( message, {
						duration: 5000,
					} );
				} else {
					message =
						data.message ||
						__( 'Payments deleted successfully', 'sureforms' );
					toast.success( message, {
						duration: 4000,
					} );
				}

				setSelectedRows( [] );
				setConfirmationDialog( ( prev ) => ( {
					...prev,
					open: false,
				} ) );
			},
			onError: ( mutationError ) => {
				// Handle error messages
				let errorMessage;

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

				toast.error( errorMessage );
				setConfirmationDialog( ( prev ) => ( {
					...prev,
					open: false,
				} ) );
			},
		} );
	};

	const handlePageChange = ( newPage ) => {
		setCurrentPage( newPage );
		setSelectedRows( [] );
	};

	const handleItemsPerPageChange = ( newItemsPerPage ) => {
		setItemsPerPage( newItemsPerPage );
		setSelectedRows( [] );
	};

	const handleClearFilters = () => {
		resetFilters();
		setCurrentPage( 1 );
	};

	// Show placeholder if no transactions exist at all
	if (
		! isLoading &&
		( ! paymentsData ||
			paymentsData?.transactions_is_empty === 'with_no_filter' )
	) {
		return <PaymentListPlaceHolder paymentMode={ paymentMode } />;
	}

	return (
		<div className="min-h-screen px-8 py-8 bg-background-secondary flex flex-col gap-[24px]">
			<WebhookConfigure mode={ paymentMode } />
			<div className="p-4 border-0.5 border-solid shadow-sm bg-background-primary rounded-xl border-border-subtle">
				<div>
					{ /* Header with filters or batch actions */ }
					<div className="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 xl:gap-5">
						<div>
							<h1 className="text-xl whitespace-nowrap font-semibold text-text-primary leading-[30px] tracking-[-0.005em]">
								{ __( 'Payments', 'sureforms' ) }
							</h1>
						</div>
						<PaymentFilters
							searchTerm={ searchTerm }
							onSearchChange={ setSearchTerm }
							selectedPayments={ selectedRows }
							onBulkDelete={ handleBulkDelete }
							statusFilter={ statusFilter }
							onStatusFilterChange={ setStatusFilter }
							formFilter={ formFilter }
							onFormFilterChange={ setFormFilter }
							paymentMode={ paymentMode }
							onPaymentModeChange={ setPaymentMode }
							selectedDates={ dateRange }
							onDateChange={ setDateRange }
							formsList={ formsList }
							onClearFilters={ handleClearFilters }
							hasActiveFilters={ hasActiveFilters() }
						/>
					</div>
				</div>

				{ /* Empty state when no results found */ }
				{ payments.length === 0 && ! isLoading ? (
					<div className="space-y-3 py-8 flex flex-col items-center justify-center mx-auto max-w-md">
						<Text as="h3" color="primary" size={ 24 }>
							{ __( 'No transactions found', 'sureforms' ) }
						</Text>
						<Text color="secondary" className="text-center">
							{ hasActiveFilters()
								? __(
									'No transactions found. Try adjusting your search terms or clearing filters.',
									'sureforms'
								  )
								: __(
									'No transactions found for the selected mode.',
									'sureforms'
								  ) }
						</Text>
						{ hasActiveFilters() && (
							<Button
								size="sm"
								variant="outline"
								onClick={ handleClearFilters }
							>
								{ __( 'Clear Filters', 'sureforms' ) }
							</Button>
						) }
					</div>
				) : (
					<div className="bg-background-primary mt-4">
						<PaymentTable
							payments={ payments }
							selectedRows={ selectedRows }
							onSelectRow={ handleSelectRow }
							onSelectAll={ handleSelectAll }
							onView={ handleView }
							onDelete={ handleDeleteSingle }
							isLoading={ isLoading }
							currentPage={ currentPage }
							totalPages={ totalPages }
							itemsPerPage={ itemsPerPage }
							onPageChange={ handlePageChange }
							onItemsPerPageChange={ handleItemsPerPageChange }
						/>
					</div>
				) }
			</div>

			{ /* Delete Confirmation Dialog */ }
			<ConfirmationDialog
				isOpen={ confirmationDialog.open }
				onCancel={ () =>
					setConfirmationDialog( ( prev ) => ( {
						...prev,
						open: false,
					} ) )
				}
				onConfirm={ confirmationDialog.onConfirm }
				title={ confirmationDialog.title }
				description={ confirmationDialog.description }
				confirmButtonText={ confirmationDialog.confirmLabel }
				destructiveConfirmButton={ confirmationDialog.destructive }
				requireConfirmation={ confirmationDialog.enableVerification }
			/>
		</div>
	);
};

export default PaymentListingPage;
