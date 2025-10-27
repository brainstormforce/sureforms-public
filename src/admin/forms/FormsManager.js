import { __, _n, sprintf } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import { Container } from '@bsf/force-ui';
import { exportForms } from './utils';
import Header from '../components/Header';
import FormsHeader from './components/FormsHeader';
import FormsTable from './components/FormsTable';
import FormsPagination from './components/FormsPagination';
import EmptyState from './components/EmptyState';
import ConfirmationDialog from './components/ConfirmationDialog';
import {
	useForms,
	useBulkFormsAction,
	useImportForms,
} from './hooks/useFormsQuery';

const FormsManager = () => {
	// State management
	const [ selectedForms, setSelectedForms ] = useState( [] );

	// Filters state
	const [ filters, setFilters ] = useState( {
		search: '',
		status: 'any',
		orderby: 'date',
		order: 'desc',
	} );

	// Date filter state
	const [ selectedDates, setSelectedDates ] = useState( {
		from: null,
		to: null,
	} );

	// Pagination state
	const [ pagination, setPagination ] = useState( {
		currentPage: 1,
		perPage: 20,
	} );

	// Dialog state
	const [ confirmDialog, setConfirmDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		action: null,
	} );

	// Query parameters for API
	const queryParams = useMemo(
		() => ( {
			page: pagination.currentPage,
			per_page: pagination.perPage,
			status: filters.status,
			orderby: filters.orderby,
			order: filters.order,
			...( filters.search && { search: filters.search } ),
			...( selectedDates.from && {
				after: new Date( selectedDates.from ).toISOString(),
			} ),
			...( selectedDates.to && {
				before: new Date( selectedDates.to ).toISOString(),
			} ),
		} ),
		[ filters, selectedDates, pagination ]
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
		totalPages: formsData?.total_pages || 0,
		currentPage: formsData?.current_page || pagination.currentPage,
		perPage: formsData?.per_page || pagination.perPage,
	};

	// Mutations
	const { mutate: bulkActionMutation, isPending: isBulkActionPending } =
		useBulkFormsAction();
	const { mutate: importFormsMutation } = useImportForms();

	// Event handlers
	const handleSearch = ( searchTerm ) => {
		setFilters( ( prev ) => ( { ...prev, search: searchTerm } ) );
		setPagination( ( prev ) => ( { ...prev, currentPage: 1 } ) );
	};

	const handleStatusFilter = ( status ) => {
		setFilters( ( prev ) => ( { ...prev, status } ) );
		setPagination( ( prev ) => ( { ...prev, currentPage: 1 } ) );
	};

	const handleDateChange = ( dates ) => {
		setSelectedDates( dates );
		setPagination( ( prev ) => ( { ...prev, currentPage: 1 } ) );
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
			// Import mutation will handle refetching and showing success message
			importFormsMutation( response );
		}
	};

	const handleSort = ( column ) => {
		const newOrder =
			filters.orderby === column && filters.order === 'desc'
				? 'asc'
				: 'desc';
		setFilters( ( prev ) => ( {
			...prev,
			orderby: column,
			order: newOrder,
		} ) );
	};

	const getSortDirection = ( column ) => {
		return filters.orderby === column ? filters.order : null;
	};

	// Pagination handlers
	const handlePageChange = ( page ) => {
		setPagination( ( prev ) => ( { ...prev, currentPage: page } ) );
	};

	const handlePerPageChange = ( perPage ) => {
		setPagination( ( prev ) => ( { ...prev, perPage, currentPage: 1 } ) );
	};

	// Selection handlers
	const handleToggleAll = ( checked ) => {
		setSelectedForms( checked ? forms.map( ( form ) => form.id ) : [] );
	};

	const handleRowSelection = ( formId, selected ) => {
		setSelectedForms( ( prev ) =>
			selected
				? [ ...prev, formId ]
				: prev.filter( ( id ) => id !== formId )
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
			title: __( 'Move to Trash', 'sureforms' ),
			description: sprintf(
				/* translators: %d: number of forms */
				_n(
					'Are you sure you want to move %d form to trash?',
					'Are you sure you want to move %d forms to trash?',
					selectedForms.length,
					'sureforms'
				),
				selectedForms.length
			),
			action: () => handleBulkAction( 'trash', selectedForms ),
		} );
	};

	// Individual form actions
	const handleFormEdit = ( form ) => {
		window.location.href = form.edit_url;
	};

	const handleFormTrash = ( form ) => {
		setConfirmDialog( {
			open: true,
			title: __( 'Move to Trash', 'sureforms' ),
			description: sprintf(
				/* translators: %s: form title */
				__(
					'Are you sure you want to move "%s" to trash?',
					'sureforms'
				),
				form.title
			),
			action: () => handleBulkAction( 'trash', [ form.id ] ),
		} );
	};

	const handleFormRestore = ( form ) => {
		handleBulkAction( 'restore', [ form.id ] );
	};

	const handleFormDelete = ( form ) => {
		setConfirmDialog( {
			open: true,
			title: __( 'Delete Permanently', 'sureforms' ),
			description: sprintf(
				/* translators: %s: form title */
				__(
					'Are you sure you want to permanently delete "%s"? This action cannot be undone.',
					'sureforms'
				),
				form.title
			),
			action: () => handleBulkAction( 'delete', [ form.id ] ),
		} );
	};

	// Computed values
	const hasActiveFilters = useMemo( () => {
		return (
			( filters.status !== '' && filters.status !== 'any' ) ||
			filters.search.trim() !== '' ||
			selectedDates.from ||
			selectedDates.to
		);
	}, [ filters, selectedDates ] );

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

	// Empty state for no forms (only when not loading)
	if ( forms.length === 0 && ! hasActiveFilters && ! isLoading ) {
		return (
			<Container className="p-6 bg-background-secondary rounded-lg">
				<FormsHeader />
				<div className="mt-6">
					<EmptyState onImportSuccess={ handleImportSuccess } />
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
					{ forms.length === 0 && hasActiveFilters && ! isLoading ? (
						<Container
							direction="column"
							className="w-full p-6 gap-4 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2"
						>
							<FormsHeader
								searchQuery={ filters.search }
								onSearchChange={ handleSearch }
								selectedForms={ selectedForms }
								onBulkTrash={ handleBulkTrash }
								onBulkExport={ handleBulkExport }
								statusFilter={ filters.status }
								onStatusFilterChange={ handleStatusFilter }
								selectedDates={ selectedDates }
								onDateChange={ handleDateChange }
							/>
							<div className="border-t border-border-subtle pt-6">
								<EmptyState
									hasActiveFilters={ true }
									onClearFilters={ () => {
										setFilters( {
											search: '',
											status: 'any',
											orderby: 'date',
											order: 'desc',
										} );
										setSelectedDates( {
											from: null,
											to: null,
										} );
										setPagination( ( prev ) => ( {
											...prev,
											currentPage: 1,
										} ) );
									} }
								/>
							</div>
						</Container>
					) : (
						<Container
							direction="column"
							className="w-full rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 overflow-hidden p-4 gap-2"
						>
							<Container.Item className="p-1">
								<FormsHeader
									searchQuery={ filters.search }
									onSearchChange={ handleSearch }
									selectedForms={ selectedForms }
									onBulkTrash={ handleBulkTrash }
									onBulkExport={ handleBulkExport }
									onImportSuccess={ handleImportSuccess }
									statusFilter={ filters.status }
									onStatusFilterChange={ handleStatusFilter }
									selectedDates={ selectedDates }
									onDateChange={ handleDateChange }
								/>
							</Container.Item>

							<Container.Item className="border-t border-border-subtle">
								<FormsTable
									forms={ forms }
									selectedForms={ selectedForms }
									onToggleAll={ handleToggleAll }
									onChangeRowSelection={ handleRowSelection }
									indeterminate={ isIndeterminate }
									onEdit={ handleFormEdit }
									onTrash={ handleFormTrash }
									onRestore={ handleFormRestore }
									onDelete={ handleFormDelete }
									isLoading={ isLoading }
									onSort={ handleSort }
									getSortDirection={ getSortDirection }
								/>
							</Container.Item>

							{ paginationData.totalPages > 1 && (
								<Container.Item className="border-t border-border-subtle px-6 py-4">
									<FormsPagination
										currentPage={
											paginationData.currentPage
										}
										totalPages={ paginationData.totalPages }
										entriesPerPage={
											paginationData.perPage
										}
										onPageChange={ handlePageChange }
										onEntriesPerPageChange={
											handlePerPageChange
										}
										onNextPage={ () =>
											handlePageChange(
												paginationData.currentPage + 1
											)
										}
										onPreviousPage={ () =>
											handlePageChange(
												paginationData.currentPage - 1
											)
										}
									/>
								</Container.Item>
							) }
						</Container>
					) }
				</Container>
			</Container.Item>

			<ConfirmationDialog
				open={ confirmDialog.open }
				setOpen={ ( open ) =>
					setConfirmDialog( ( prev ) => ( { ...prev, open } ) )
				}
				title={ confirmDialog.title }
				description={ confirmDialog.description }
				onConfirm={ confirmDialog.action }
				isLoading={ isBulkActionPending }
			/>
		</Container>
	);
};

export default FormsManager;
