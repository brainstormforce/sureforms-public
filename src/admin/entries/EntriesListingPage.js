// import { useQuery } from '@tanstack/react-query';
import EntriesHeader from './components/EntriesHeader';
import EntriesFilters from './components/EntriesFilters';
import EntriesTable from './components/EntriesTable';
import EntriesPagination from './components/EntriesPagination';
import { useEntriesFilters } from './hooks/useEntriesFilters';
import { useEntriesSelection } from './hooks/useEntriesSelection';
import { usePagination } from './hooks/usePagination';

/**
 * EntriesListingPage Component
 * Main container for the entries listing page
 * This component will handle data fetching with TanStack Query
 */
const EntriesListingPage = () => {
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
	} = usePagination( 2, 10 );

	// TODO: Replace with TanStack Query
	// const { data, isLoading, isError } = useQuery({
	//   queryKey: ['entries', { currentPage, entriesPerPage, statusFilter, formFilter, searchQuery, dateRange }],
	//   queryFn: () => fetchEntries({ currentPage, entriesPerPage, statusFilter, formFilter, searchQuery, dateRange }),
	// });

	// Mock data - will be replaced by TanStack Query
	const entries = [
		{
			id: 1,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 2,
			entryId: 'Entry #4',
			formName: 'Newsletter',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 3,
			entryId: 'Entry #4',
			formName: 'Job Application Form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jun 23, 2023 10:30 am',
		},
		{
			id: 4,
			entryId: 'Entry #4',
			formName: 'Support Request',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Aug 3, 2023 10:30 am',
		},
		{
			id: 5,
			entryId: 'Entry #4',
			formName: 'Calculation Form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 6,
			entryId: 'Entry #4',
			formName: 'Login Form',
			status: 'Trash',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 7,
			entryId: 'Entry #4',
			formName: 'Registration form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Oct 3, 2024 10:30 am',
		},
		{
			id: 8,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 9,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 10,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
	];

	const totalPages = 10; // TODO: Get from API response

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
		// TODO: Implement delete functionality
		console.log( 'Delete entry:', entry );
	};

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
						isLoading={ false } // TODO: Replace with isLoading from useQuery
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
