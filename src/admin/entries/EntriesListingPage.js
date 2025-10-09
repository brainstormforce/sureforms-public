import { useMemo } from '@wordpress/element';
import EntriesHeader from './components/EntriesHeader';
import EntriesFilters from './components/EntriesFilters';
import EntriesTable from './components/EntriesTable';
import EntriesPagination from './components/EntriesPagination';
import { useEntriesFilters } from './hooks/useEntriesFilters';
import { useEntriesSelection } from './hooks/useEntriesSelection';
import { usePagination } from './hooks/usePagination';
import {
	useEntries,
	useForms,
	useDeleteEntries,
} from './hooks/useEntriesQuery';
import { transformEntry } from './utils/entryHelpers';
import { getFormOptions } from './constants';

/**
 * EntriesListingPage Component
 * Main container for the entries listing page
 * Handles data fetching with TanStack Query
 */
const EntriesListingPage = () => {
	// Fetch forms data using React Query
	const { data: formsMap = {} } = useForms();
	// Custom hooks for state management
	const {
		statusFilter,
		setStatusFilter,
		formFilter,
		setFormFilter,
		searchQuery,
		setSearchQuery,
		dateRange,
		setDateRange,
	} = useEntriesFilters();

	const {
		currentPage,
		entriesPerPage,
		goToPage,
		nextPage,
		previousPage,
		changeEntriesPerPage,
	} = usePagination( 1, 20 );

	// Fetch entries using React Query
	const {
		data: entriesData,
		isLoading,
		isError,
		error,
	} = useEntries( {
		form_id: formFilter === 'all' ? 0 : parseInt( formFilter, 10 ),
		status: statusFilter,
		search: searchQuery,
		month: dateRange || '',
		orderby: 'created_at',
		order: 'DESC',
		per_page: entriesPerPage,
		page: currentPage,
	} );

	// Extract data from API response
	const rawEntries = entriesData?.entries || [];
	const totalPages = entriesData?.total_pages || 1;

	// Transform entries to component-friendly format
	const entries = useMemo( () => {
		return rawEntries.map( ( entry ) => transformEntry( entry, formsMap ) );
	}, [ rawEntries, formsMap ] );

	// Generate form options for filter
	const formOptions = useMemo( () => {
		return getFormOptions( formsMap );
	}, [ formsMap ] );

	// Delete mutation
	const { mutate: deleteEntriesMutation } = useDeleteEntries();

	const {
		selectedEntries,
		handleChangeRowCheckbox,
		handleToggleAll,
		indeterminate,
	} = useEntriesSelection( entries );

	// Action handlers
	const handleEdit = ( entry ) => {
		// TODO: Implement edit functionality
		console.log( 'Edit entry:', entry );
	};

	const handleDelete = ( entry ) => {
		// Delete single entry
		deleteEntriesMutation( { entry_ids: [ entry.id ] } );
	};

	// Show error state
	if ( isError ) {
		return (
			<div className="p-8 bg-gray-50 min-h-screen">
				<div className="max-w-[1374px] mx-auto">
					<div className="bg-white rounded-xl border-0.5 border-border-subtle shadow-sm p-8 text-center">
						<p className="text-red-600">
							Error loading entries:{ ' ' }
							{ error?.message || 'Unknown error' }
						</p>
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="p-8 bg-gray-50 min-h-screen">
			<div className="max-w-[1374px] mx-auto">
				<div className="bg-white rounded-xl border-0.5 border-border-subtle shadow-sm p-4 space-y-2">
					<div className="p-1">
						<div className="flex justify-between items-center gap-5">
							<EntriesHeader />
							<EntriesFilters
								statusFilter={ statusFilter }
								onStatusFilterChange={ setStatusFilter }
								formFilter={ formFilter }
								onFormFilterChange={ setFormFilter }
								searchQuery={ searchQuery }
								onSearchChange={ setSearchQuery }
								dateRange={ dateRange }
								onDateRangeChange={ setDateRange }
								formOptions={ formOptions }
							/>
						</div>
					</div>

					<EntriesTable
						entries={ entries }
						selectedEntries={ selectedEntries }
						onToggleAll={ handleToggleAll }
						onChangeRowSelection={ handleChangeRowCheckbox }
						indeterminate={ indeterminate }
						onEdit={ handleEdit }
						onDelete={ handleDelete }
						isLoading={ isLoading }
					>
						<EntriesPagination
							currentPage={ currentPage }
							totalPages={ totalPages }
							entriesPerPage={ entriesPerPage }
							onPageChange={ goToPage }
							onEntriesPerPageChange={ changeEntriesPerPage }
							onNextPage={ () => nextPage( totalPages ) }
							onPreviousPage={ previousPage }
						/>
					</EntriesTable>
				</div>
			</div>
		</div>
	);
};

export default EntriesListingPage;
