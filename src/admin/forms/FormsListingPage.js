import { __, _n, sprintf } from '@wordpress/i18n';
import { useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import { Container, toast } from '@bsf/force-ui';
import { useQueryClient } from '@tanstack/react-query';
import { exportForms } from './utils';
import Header from '../components/Header';
import FormsHeader from './components/FormsHeader';
import FormsTable from './components/FormsTable';
import Pagination from '@Admin/common/listing/components/Pagination';
import EmptyState from './components/EmptyState';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';
import { useForms, useBulkFormsAction, formsKeys } from './hooks/useFormsQuery';
import { useFormsFilters } from './hooks/useFormsFilters';
import { useFormsSort } from './hooks/useFormsSort';
import { useFormsPagination } from './hooks/useFormsPagination';

const FormsListingPage = () => {
	// React Query client for cache invalidation
	const queryClient = useQueryClient();

	// URL-based state management
	const {
		statusFilter,
		setStatusFilter,
		searchQuery,
		setSearchQuery,
		dateRange,
		setDateRange,
		resetFilters: resetUrlFilters,
	} = useFormsFilters();

	const { sortBy, sortOrder, handleSort, getSortDirection } = useFormsSort();

	const { currentPage, perPage, setCurrentPage, setPerPage } =
		useFormsPagination();

	// Local state management
	const [ selectedForms, setSelectedForms ] = useState( [] );

	// Dialog state
	const [ confirmDialog, setConfirmDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		action: null,
		confirmButtonText: null,
		destructive: true,
		requireConfirmation: false,
	} );

	// Query parameters for API
	const queryParams = useMemo(
		() => ( {
			page: currentPage,
			...( perPage && { per_page: perPage } ),
			status: statusFilter,
			orderby: sortBy,
			order: sortOrder,
			...( searchQuery && { search: searchQuery } ),
			...( dateRange.from && {
				after: new Date( dateRange.from ).toISOString(),
			} ),
			...( dateRange.to && {
				before: new Date( dateRange.to ).toISOString(),
			} ),
		} ),
		[
			statusFilter,
			searchQuery,
			dateRange,
			sortBy,
			sortOrder,
			currentPage,
			perPage,
		]
	);

	// Fetch forms using React Query
	const {
		data: formsData,
		isLoading,
		isError,
		error,
	} = useForms( queryParams );

	// Extract data from API response
	const forms = formsData?.forms || [];
	const paginationData = {
		total: formsData?.total || 0,
		totalPages: Math.max( 1, formsData?.total_pages || 0 ),
		currentPage: formsData?.current_page || currentPage,
		perPage: formsData?.per_page || perPage,
	};

	// Handle pagination validation and redirect when needed
	useEffect( () => {
		if ( ! isLoading && formsData ) {
			const apiTotalPages = formsData.total_pages || 0;

			// Validate URL-based page parameter
			// If current page exceeds available pages, redirect to last valid page
			if ( currentPage > apiTotalPages && apiTotalPages > 0 ) {
				setCurrentPage( apiTotalPages );
				return;
			}

			// Handle case when all forms on current page are deleted
			// Only redirect if:
			// 1. Current page > 1 (don't redirect from page 1)
			// 2. No forms on current page
			// 3. Total pages from API is less than current page
			if (
				currentPage > 1 &&
				forms.length === 0 &&
				apiTotalPages < currentPage
			) {
				const targetPage = Math.max( 1, apiTotalPages );
				setCurrentPage( targetPage );
			}
		}
	}, [ isLoading, currentPage, forms.length, formsData, setCurrentPage ] );

	// Check for trash forms when "All Forms" is empty to detect if all forms are trashed
	const trashQueryParams = useMemo(
		() => ( {
			page: 1,
			per_page: 1, // Just need to check if any exist
			status: 'trash',
		} ),
		[]
	);

	const { data: trashData, isLoading: isTrashLoading } = useForms(
		trashQueryParams,
		{
			enabled:
				statusFilter === 'any' &&
				forms.length === 0 &&
				! isLoading &&
				! searchQuery.trim() &&
				! dateRange.from &&
				! dateRange.to,
		}
	);

	// Mutations
	const { mutate: bulkActionMutation } = useBulkFormsAction();

	// Event handlers
	const handleSearch = ( searchTerm ) => {
		setSearchQuery( searchTerm );
		setCurrentPage( 1 );
	};

	const handleStatusFilter = ( status ) => {
		setStatusFilter( status );
		setCurrentPage( 1 );
	};

	const handleDateChange = ( dates ) => {
		setDateRange( dates );
		setCurrentPage( 1 );
	};

	const handleBulkExport = async () => {
		if ( selectedForms.length === 0 ) {
			return;
		}

		try {
			await exportForms( selectedForms );
			// Clear selected forms after successful export
			setSelectedForms( [] );
		} catch ( err ) {
			console.error( 'Bulk export error:', err );
		}
	};

	// Handle import success
	const handleImportSuccess = ( response ) => {
		if ( response.success ) {
			// Show success toast message
			const count = response.imported_count || 1;
			const message = sprintf(
				/* translators: %d: number of imported forms */
				_n(
					'%d form imported successfully.',
					'%d forms imported successfully.',
					count,
					'sureforms'
				),
				count
			);
			toast.success( message );

			// Invalidate cache to refresh the table
			queryClient.invalidateQueries( { queryKey: formsKeys.lists() } );
		}
	};

	// Pagination handlers
	const handlePageChange = ( page ) => {
		setCurrentPage( page );
	};

	const handlePerPageChange = ( perPageValue ) => {
		setPerPage( perPageValue );
	};

	const nextPage = useCallback( ( totalPages ) => {
		setCurrentPage( ( prev ) => Math.min( totalPages, prev + 1 ) );
	}, [] );

	const previousPage = useCallback( () => {
		setCurrentPage( ( prev ) => Math.max( 1, prev - 1 ) );
	}, [] );

	// Selection handlers
	const handleToggleAll = ( checked ) => {
		setSelectedForms( checked ? forms.map( ( form ) => form.id ) : [] );
	};

	const handleRowSelection = ( selected, item ) => {
		setSelectedForms( ( prev ) =>
			selected
				? [ ...prev, item.id ]
				: prev.filter( ( id ) => id !== item.id )
		);
	};

	// Bulk action handlers
	const handleBulkAction = ( action, formIds ) => {
		bulkActionMutation(
			{ action, form_ids: formIds },
			{
				onSuccess: () => {
					setSelectedForms( [] );
				},
			}
		);
	};

	const handleBulkTrash = () => {
		setConfirmDialog( {
			open: true,
			title: _n(
				'Move Form to Trash?',
				'Move Forms to Trash?',
				selectedForms.length,
				'sureforms'
			),
			description: sprintf(
				/* translators: %d: number of forms */
				_n(
					'%d form will be moved to trash and can be restored later.',
					'%d forms will be moved to trash and can be restored later.',
					selectedForms.length,
					'sureforms'
				),
				selectedForms.length
			),
			action: async () => {
				await new Promise( ( resolve ) => {
					handleBulkAction( 'trash', selectedForms );
					resolve();
				} );
				setConfirmDialog( ( prev ) => ( { ...prev, open: false } ) );
			},
			confirmButtonText: __( 'Move to Trash', 'sureforms' ),
			destructive: true,
			requireConfirmation: false,
		} );
	};

	const handleBulkRestore = () => {
		handleBulkAction( 'restore', selectedForms );
	};

	const handleBulkDelete = () => {
		setConfirmDialog( {
			open: true,
			title: __( 'Delete Permanently', 'sureforms' ),
			description: sprintf(
				/* translators: %d: number of forms */
				_n(
					'Are you sure you want to permanently delete %d form? This action cannot be undone.',
					'Are you sure you want to permanently delete %d forms? This action cannot be undone.',
					selectedForms.length,
					'sureforms'
				),
				selectedForms.length
			),
			action: async () => {
				await new Promise( ( resolve ) => {
					handleBulkAction( 'delete', selectedForms );
					resolve();
				} );
				setConfirmDialog( ( prev ) => ( { ...prev, open: false } ) );
			},
			confirmButtonText: __( 'Delete Permanently', 'sureforms' ),
			destructive: true,
			requireConfirmation: true,
		} );
	};

	// Individual form actions
	const handleFormEdit = ( form ) => {
		window.location.href = form.edit_url;
	};

	const handleFormTrash = ( form ) => {
		handleBulkAction( 'trash', [ form.id ] );
	};

	const handleFormRestore = ( form ) => {
		handleBulkAction( 'restore', [ form.id ] );
	};

	const handleFormDelete = ( form ) => {
		setConfirmDialog( {
			open: true,
			title: __( 'Delete Form', 'sureforms' ),
			description: __(
				'Are you sure you want to permanently delete this form?',
				'sureforms'
			),
			action: async () => {
				await new Promise( ( resolve ) => {
					handleBulkAction( 'delete', [ form.id ] );
					resolve();
				} );
				setConfirmDialog( ( prev ) => ( { ...prev, open: false } ) );
			},
			confirmButtonText: __( 'Permanently Delete', 'sureforms' ),
			destructive: true,
			requireConfirmation: true,
		} );
	};

	const handleClearFilters = () => {
		resetUrlFilters();
		setCurrentPage( 1 );
	};

	// Computed values
	const hasActiveFilters = useMemo( () => {
		return (
			( statusFilter !== '' && statusFilter !== 'any' ) ||
			searchQuery.trim() !== '' ||
			dateRange.from ||
			dateRange.to
		);
	}, [ statusFilter, searchQuery, dateRange ] );

	// Check if there are any forms in trash
	const hasTrashForms = trashData?.total > 0;

	// Only show initial empty state when truly no forms exist anywhere
	const shouldShowInitialEmptyState = useMemo( () => {
		return (
			forms.length === 0 &&
			statusFilter === 'any' &&
			! searchQuery.trim() &&
			! dateRange.from &&
			! dateRange.to &&
			! isLoading &&
			! isTrashLoading &&
			! hasTrashForms // Only show initial state if no trash forms exist
		);
	}, [
		forms.length,
		statusFilter,
		searchQuery,
		dateRange,
		isLoading,
		isTrashLoading,
		hasTrashForms,
	] );

	// Show filtered empty state when filters are active but no results
	const shouldShowFilteredEmptyState = useMemo( () => {
		return hasActiveFilters && forms.length === 0 && ! isLoading;
	}, [ hasActiveFilters, forms.length, isLoading ] );

	const isIndeterminate =
		selectedForms.length > 0 && selectedForms.length < forms.length;

	// Error state
	if ( isError && forms.length === 0 ) {
		return (
			<Container className="p-6 bg-background-secondary rounded-lg">
				<FormsHeader />
				<div className="mt-6 text-text-error">
					{ __( 'Error loading forms: ', 'sureforms' ) }
					{ error?.message }
				</div>
			</Container>
		);
	}

	return (
		<Container className="h-full" direction="column" gap={ 0 }>
			{ /* Header */ }
			<Header />

			<Container.Item>
				<Container
					className="p-5 pb-8 xl:p-8 w-full bg-background-secondary"
					direction="column"
					gap="2xl"
				>
					{ /* Content */ }
					{ shouldShowInitialEmptyState ? (
						<div className="p-6 bg-background-secondary rounded-lg">
							<EmptyState
								onImportSuccess={ handleImportSuccess }
							/>
						</div>
					) : (
						<Container
							direction="column"
							className="w-full rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 overflow-hidden p-4 gap-2"
						>
							<Container.Item className="p-1">
								<FormsHeader
									searchQuery={ searchQuery }
									onSearchChange={ handleSearch }
									selectedForms={ selectedForms }
									onBulkTrash={ handleBulkTrash }
									onBulkDelete={ handleBulkDelete }
									onBulkExport={ handleBulkExport }
									onBulkRestore={ handleBulkRestore }
									onImportSuccess={ handleImportSuccess }
									statusFilter={ statusFilter }
									onStatusFilterChange={ handleStatusFilter }
									selectedDates={ dateRange }
									onDateChange={ handleDateChange }
									hasActiveFilters={ hasActiveFilters }
									onClearFilters={ handleClearFilters }
								/>
							</Container.Item>

							<Container.Item className="border-t border-border-subtle">
								{ shouldShowFilteredEmptyState ? (
									<EmptyState
										hasActiveFilters={ true }
										onClearFilters={ handleClearFilters }
										onImportSuccess={ handleImportSuccess }
									/>
								) : (
									<FormsTable
										data={ forms }
										selectedItems={ selectedForms }
										onToggleAll={ handleToggleAll }
										onChangeRowSelection={
											handleRowSelection
										}
										indeterminate={ isIndeterminate }
										onEdit={ handleFormEdit }
										onTrash={ handleFormTrash }
										onRestore={ handleFormRestore }
										onDelete={ handleFormDelete }
										isLoading={ isLoading }
										onSort={ handleSort }
										getSortDirection={ getSortDirection }
									>
										<Pagination
											currentPage={
												paginationData.currentPage
											}
											totalPages={
												paginationData.totalPages
											}
											perPage={ paginationData.perPage }
											onPageChange={ handlePageChange }
											onPerPageChange={
												handlePerPageChange
											}
											onNextPage={ () =>
												nextPage(
													paginationData.totalPages
												)
											}
											onPreviousPage={ previousPage }
										/>
									</FormsTable>
								) }
							</Container.Item>
						</Container>
					) }
				</Container>
			</Container.Item>

			<ConfirmationDialog
				isOpen={ confirmDialog.open }
				onCancel={ () =>
					setConfirmDialog( ( prev ) => ( { ...prev, open: false } ) )
				}
				title={ confirmDialog.title }
				description={ confirmDialog.description }
				onConfirm={ confirmDialog.action }
				confirmButtonText={
					confirmDialog.confirmButtonText ||
					__( 'Confirm', 'sureforms' )
				}
				cancelButtonText={ __( 'Cancel', 'sureforms' ) }
				destructiveConfirmButton={ confirmDialog.destructive !== false }
				requireConfirmation={
					confirmDialog.requireConfirmation || false
				}
			/>
		</Container>
	);
};

export default FormsListingPage;
