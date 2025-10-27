import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { toast, Container } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { exportForms } from './utils';
import Header from '../components/Header';
import FormsHeader from './components/FormsHeader';
import FormsTable from './components/FormsTable';
import FormsPagination from './components/FormsPagination';
import EmptyState from './components/EmptyState';
import ConfirmationDialog from './components/ConfirmationDialog';

const FormsManager = () => {
	// State management
	const [forms, setForms] = useState( [] );
	const [loading, setLoading] = useState( true );
	const [error, setError] = useState( null );
	const [selectedForms, setSelectedForms] = useState( [] );
	
	// Filters state
	const [filters, setFilters] = useState( {
		search: '',
		status: 'any',
		orderby: 'date',
		order: 'desc'
	} );

	// Date filter state
	const [selectedDates, setSelectedDates] = useState( {
		from: null,
		to: null,
	} );

	// Status counts
	const [statusCounts, setStatusCounts] = useState( {
		total: 0,
		publish: 0,
		draft: 0,
		trash: 0
	} );
	
	// Pagination state
	const [pagination, setPagination] = useState( {
		total: 0,
		totalPages: 0,
		currentPage: 1,
		perPage: 20
	} );
	
	
	// Dialog state
	const [confirmDialog, setConfirmDialog] = useState( {
		open: false,
		title: '',
		description: '',
		action: null
	} );

	// API functions
	const fetchForms = async ( params = {} ) => {
		try {
			setLoading( true );
			setError( null );

			const currentFilters = { ...filters, ...params };
			const queryParams = new URLSearchParams( {
				page: currentFilters.page || pagination.currentPage || 1,
				per_page: currentFilters.per_page || pagination.perPage || 20,
				status: currentFilters.status || 'any',
				orderby: currentFilters.orderby || 'date',
				order: currentFilters.order || 'desc',
				...( currentFilters.search && { search: currentFilters.search } ),
				...( selectedDates.from && { after: new Date( selectedDates.from ).toISOString() } ),
				...( selectedDates.to && { before: new Date( selectedDates.to ).toISOString() } )
			} );

			const response = await apiFetch( {
				path: `/sureforms/v1/forms?${ queryParams.toString() }`
			} );

			setForms( response.forms || [] );
			setPagination( {
				total: response.total || 0,
				totalPages: response.total_pages || 0,
				currentPage: response.current_page || 1,
				perPage: response.per_page || 20
			} );
			setFilters( currentFilters );

		} catch ( err ) {
			setError( err.message || __( 'Failed to fetch forms', 'sureforms' ) );
			toast.error( err.message || __( 'Failed to fetch forms', 'sureforms' ) );
		} finally {
			setLoading( false );
		}
	};

	const fetchStatusCounts = async () => {
		try {
			const statuses = [ 'publish', 'draft', 'trash' ];
			const promises = statuses.map( async ( status ) => {
				const response = await apiFetch( {
					path: `/sureforms/v1/forms?status=${ status }&per_page=1`
				} );
				return { status, count: response.total || 0 };
			} );

			const results = await Promise.all( promises );
			const counts = { total: 0 };
			
			results.forEach( ( { status, count } ) => {
				counts[ status ] = count;
				counts.total += count;
			} );

			setStatusCounts( counts );
		} catch ( err ) {
			console.error( 'Error fetching status counts:', err );
		}
	};

	// Event handlers
	const handleSearch = ( searchTerm ) => {
		fetchForms( { search: searchTerm, page: 1 } );
	};

	const handleStatusFilter = ( status ) => {
		fetchForms( { status, page: 1 } );
	};

	const handleDateChange = ( dates ) => {
		setSelectedDates( dates );
		fetchForms( { page: 1 } );
	};

	const handleBulkExport = async () => {
		if ( selectedForms.length === 0 ) {
			return;
		}

		try {
			await exportForms( selectedForms );
			// Clear selected forms after successful export
			setSelectedForms( [] );
		} catch ( error ) {
			console.error( 'Bulk export error:', error );
			// TODO: Show user-friendly error message
		}
	};

	// Handle import success
	const handleImportSuccess = ( response ) => {
		if ( response.success ) {
			// Refresh forms list to show imported forms
			fetchForms();
			// Show success message
			toast.add( {
				type: 'success',
				message: sprintf(
					/* translators: %d: number of imported forms */
					_n( '%d form imported successfully.', '%d forms imported successfully.', response.count || 1, 'sureforms' ),
					response.count || 1
				),
			} );
		}
	};

	const handleSort = ( column ) => {
		const newOrder = filters.orderby === column && filters.order === 'desc' ? 'asc' : 'desc';
		fetchForms( { orderby: column, order: newOrder } );
	};

	const getSortDirection = ( column ) => {
		return filters.orderby === column ? filters.order : null;
	};

	// Pagination handlers
	const handlePageChange = ( page ) => {
		fetchForms( { page } );
	};

	const handlePerPageChange = ( perPage ) => {
		fetchForms( { per_page: perPage, page: 1 } );
	};

	// Selection handlers
	const handleToggleAll = ( checked ) => {
		setSelectedForms( checked ? forms.map( form => form.id ) : [] );
	};

	const handleRowSelection = ( formId, selected ) => {
		setSelectedForms( prev => 
			selected 
				? [ ...prev, formId ]
				: prev.filter( id => id !== formId )
		);
	};

	// Bulk action handlers
	const handleBulkAction = async ( action, formIds ) => {
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/forms/manage',
				method: 'POST',
				data: {
					form_ids: formIds,
					action: action
				}
			} );

			if ( response.success ) {
				const count = formIds.length;
				let message = '';
				
				switch ( action ) {
					case 'trash':
						message = sprintf( 
							_n( '%d form moved to trash.', '%d forms moved to trash.', count, 'sureforms' ), 
							count 
						);
						break;
					case 'restore':
						message = sprintf( 
							_n( '%d form restored.', '%d forms restored.', count, 'sureforms' ), 
							count 
						);
						break;
					case 'delete':
						message = sprintf( 
							_n( '%d form permanently deleted.', '%d forms permanently deleted.', count, 'sureforms' ), 
							count 
						);
						break;
				}
				
				toast.success( message );
				setSelectedForms( [] );
				fetchForms();
			}
		} catch ( err ) {
			toast.error( err.message || __( 'Action failed', 'sureforms' ) );
		}
	};

	const handleBulkTrash = () => {
		setConfirmDialog( {
			open: true,
			title: __( 'Move to Trash', 'sureforms' ),
			description: sprintf( 
				_n( 
					'Are you sure you want to move %d form to trash?', 
					'Are you sure you want to move %d forms to trash?', 
					selectedForms.length, 
					'sureforms' 
				), 
				selectedForms.length 
			),
			action: () => handleBulkAction( 'trash', selectedForms )
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
			description: sprintf( __( 'Are you sure you want to move "%s" to trash?', 'sureforms' ), form.title ),
			action: () => handleBulkAction( 'trash', [ form.id ] )
		} );
	};

	const handleFormRestore = ( form ) => {
		handleBulkAction( 'restore', [ form.id ] );
	};

	const handleFormDelete = ( form ) => {
		setConfirmDialog( {
			open: true,
			title: __( 'Delete Permanently', 'sureforms' ),
			description: sprintf( __( 'Are you sure you want to permanently delete "%s"? This action cannot be undone.', 'sureforms' ), form.title ),
			action: () => handleBulkAction( 'delete', [ form.id ] )
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

	const isIndeterminate = selectedForms.length > 0 && selectedForms.length < forms.length;

	useEffect( () => {
		fetchForms();
		fetchStatusCounts();
	}, [] );

	// Loading state
	if ( loading && forms.length === 0 ) {
		return (
			<Container className="p-6 bg-background-secondary rounded-lg">
				<FormsHeader />
				<div className="mt-6">
					{ __( 'Loading forms...', 'sureforms' ) }
				</div>
			</Container>
		);
	}

	// Error state
	if ( error && forms.length === 0 ) {
		return (
			<Container className="p-6 bg-background-secondary rounded-lg">
				<FormsHeader />
				<div className="mt-6 text-text-error">
					{ __( 'Error loading forms: ', 'sureforms' ) }{ error }
				</div>
			</Container>
		);
	}

	// Empty state for no forms
	if ( forms.length === 0 && ! hasActiveFilters ) {
		return (
			<Container className="p-6 bg-background-secondary rounded-lg">
				<FormsHeader />
				<div className="mt-6">
					<EmptyState />
				</div>
			</Container>
		);
	}

	return (
		<Container className="h-full" direction="column" gap={ 0 }>
			{/* Header */}
			<Header />
			
			<Container.Item>
				<Container
					className="p-5 pb-8 xl:p-8 w-full bg-background-secondary"
					direction="column"
					gap="2xl"
				>
					{/* Content */}
					{ forms.length === 0 && hasActiveFilters ? (
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
								statusCounts={ statusCounts }
								selectedDates={ selectedDates }
								onDateChange={ handleDateChange }
							/>
							<div className="border-t border-border-subtle pt-6">
								<EmptyState 
									hasActiveFilters={ true }
									onClearFilters={ () => {
										setFilters( { search: '', status: 'any', orderby: 'date', order: 'desc' } );
										setSelectedDates( { from: null, to: null } );
										fetchForms( { search: '', status: 'any', page: 1 } );
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
									statusCounts={ statusCounts }
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
									isLoading={ loading }
									onSort={ handleSort }
									getSortDirection={ getSortDirection }
								/>
							</Container.Item>
							
							{ pagination.totalPages > 1 && (
								<Container.Item className="border-t border-border-subtle px-6 py-4">
									<FormsPagination
										currentPage={ pagination.currentPage }
										totalPages={ pagination.totalPages }
										entriesPerPage={ pagination.perPage }
										onPageChange={ handlePageChange }
										onEntriesPerPageChange={ handlePerPageChange }
										onNextPage={ () => handlePageChange( pagination.currentPage + 1 ) }
										onPreviousPage={ () => handlePageChange( pagination.currentPage - 1 ) }
									/>
								</Container.Item>
							) }
						</Container>
					) }
				</Container>
			</Container.Item>

			<ConfirmationDialog
				open={ confirmDialog.open }
				setOpen={ ( open ) => setConfirmDialog( prev => ( { ...prev, open } ) ) }
				title={ confirmDialog.title }
				description={ confirmDialog.description }
				onConfirm={ confirmDialog.action }
				isLoading={ loading }
			/>
		</Container>
	);
};

export default FormsManager;