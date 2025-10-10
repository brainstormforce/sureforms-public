import { useMemo, useState } from '@wordpress/element';
import EntriesHeader from './components/EntriesHeader';
import EntriesFilters from './components/EntriesFilters';
import EntriesTable from './components/EntriesTable';
import EntriesPagination from './components/EntriesPagination';
import EmptyState from './components/EmptyState';
import ConfirmationDialog from './components/ConfirmationDialog';
import { useEntriesFilters } from './hooks/useEntriesFilters';
import { useEntriesSelection } from './hooks/useEntriesSelection';
import { usePagination } from './hooks/usePagination';
import {
	useEntries,
	useForms,
	useDeleteEntries,
	useTrashEntries,
	useUpdateEntriesReadStatus,
	useExportEntries,
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
	} = usePagination( 1, 10 );

	// Convert dateRange to formatted date strings for API
	const dateFilters = useMemo( () => {
		if ( ! dateRange || ! dateRange.from ) {
			return { date_from: '', date_to: '' };
		}

		const formatDate = ( date ) => {
			const year = date.getFullYear();
			const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
			const day = String( date.getDate() ).padStart( 2, '0' );
			return `${ year }-${ month }-${ day }`;
		};

		const fromDate = new Date( dateRange.from );
		const toDate = dateRange.to ? new Date( dateRange.to ) : fromDate;

		return {
			date_from: `${ formatDate( fromDate ) } 00:00:00`,
			date_to: `${ formatDate( toDate ) } 23:59:59`,
		};
	}, [ dateRange ] );

	// Fetch entries using React Query
	const { data: entriesData, isLoading } = useEntries( {
		form_id: formFilter === 'all' ? 0 : parseInt( formFilter, 10 ),
		status: statusFilter,
		search: searchQuery,
		date_from: dateFilters.date_from,
		date_to: dateFilters.date_to,
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

	// Check if any filters are active
	const hasActiveFilters = useMemo( () => {
		return (
			statusFilter !== 'all' ||
			formFilter !== 'all' ||
			searchQuery.trim() !== '' ||
			dateRange !== null
		);
	}, [ statusFilter, formFilter, searchQuery, dateRange ] );

	// Mutations
	const { mutate: deleteEntriesMutation, isPending: isDeleting } =
		useDeleteEntries();
	const { mutate: trashEntriesMutation } = useTrashEntries();
	const { mutate: updateReadStatusMutation } = useUpdateEntriesReadStatus();
	const { mutate: exportEntriesMutation } = useExportEntries();

	// State for confirmation dialogs
	const [ confirmationDialog, setConfirmationDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		confirmLabel: '',
		onConfirm: null,
		isLoading: false,
	} );

	const {
		selectedEntries,
		handleChangeRowCheckbox,
		handleToggleAll,
		clearSelection,
		indeterminate,
	} = useEntriesSelection( entries );

	// Action handlers
	const handleEdit = ( entry ) => {
		// TODO: Implement edit functionality
		console.log( 'Edit entry:', entry );
	};

	const handleDelete = ( entry ) => {
		// If entry is already in trash, show confirmation dialog for permanent delete
		if ( entry.status === 'trash' ) {
			setConfirmationDialog( {
				open: true,
				title: 'Delete Entry Permanently?',
				description:
					'This action cannot be undone. The entry will be permanently deleted from the database.',
				confirmLabel: 'Delete Permanently',
				onConfirm: () => handlePermanentDelete( entry ),
				isLoading: isDeleting,
			} );
		} else {
			// Otherwise, move to trash
			trashEntriesMutation( {
				entry_ids: [ entry.id ],
				action: 'trash',
			} );
		}
	};

	const handleRestore = ( entry ) => {
		// Restore entry from trash
		trashEntriesMutation( {
			entry_ids: [ entry.id ],
			action: 'restore',
		} );
	};

	const handlePermanentDelete = ( entry ) => {
		deleteEntriesMutation( {
			entry_ids: [ entry.id ],
		} );
		setConfirmationDialog( ( prev ) => ( { ...prev, open: false } ) );
	};

	// Bulk action handlers
	const handleBulkDelete = () => {
		if ( selectedEntries.length === 0 ) {
			return;
		}

		const allInTrash = selectedEntries.every(
			( entryId ) =>
				entries.find( ( item ) => item.id === entryId )?.status ===
				'trash'
		);

		setConfirmationDialog( {
			open: true,
			title: allInTrash
				? 'Delete Entries Permanently?'
				: 'Move Entries to Trash?',
			description: allInTrash
				? `This action cannot be undone. ${ selectedEntries.length } entries will be permanently deleted from the database.`
				: `${ selectedEntries.length } entries will be moved to trash. You can restore them later.`,
			confirmLabel: allInTrash ? 'Delete Permanently' : 'Move to Trash',
			onConfirm: handleBulkDeleteConfirm,
			isLoading: isDeleting,
		} );
	};

	const handleBulkDeleteConfirm = () => {
		// Check if all selected entries are in trash
		const allInTrash = selectedEntries.every(
			( entryId ) =>
				entries.find( ( e ) => e.id === entryId )?.status === 'trash'
		);

		if ( allInTrash ) {
			// If all are in trash, permanently delete them
			deleteEntriesMutation(
				{
					entry_ids: selectedEntries,
				},
				{
					onSuccess: () => {
						clearSelection();
						setConfirmationDialog( ( prev ) => ( {
							...prev,
							open: false,
						} ) );
					},
				}
			);
		} else {
			// Otherwise, move to trash
			trashEntriesMutation(
				{
					entry_ids: selectedEntries,
					action: 'trash',
				},
				{
					onSuccess: () => {
						clearSelection();
						setConfirmationDialog( ( prev ) => ( {
							...prev,
							open: false,
						} ) );
					},
				}
			);
		}
	};

	const handleBulkExport = () => {
		if ( selectedEntries.length === 0 ) {
			return;
		}

		exportEntriesMutation(
			{
				entry_ids: selectedEntries,
			},
			{
				onSuccess: () => {
					clearSelection();
				},
			}
		);
	};

	const handleMarkAsRead = () => {
		if ( selectedEntries.length === 0 ) {
			return;
		}

		updateReadStatusMutation(
			{
				entry_ids: selectedEntries,
				action: 'read',
			},
			{
				onSuccess: () => {
					clearSelection();
				},
			}
		);
	};

	const handleMarkAsUnread = () => {
		if ( selectedEntries.length === 0 ) {
			return;
		}

		updateReadStatusMutation(
			{
				entry_ids: selectedEntries,
				action: 'unread',
			},
			{
				onSuccess: () => {
					clearSelection();
				},
			}
		);
	};

	const handleBulkRestore = () => {
		if ( selectedEntries.length === 0 ) {
			return;
		}

		setConfirmationDialog( {
			open: true,
			title: 'Restore Entries?',
			description: `${ selectedEntries.length } entries will be restored from trash and will be visible in the entries list again.`,
			confirmLabel: 'Restore Entries',
			onConfirm: handleBulkRestoreConfirm,
			isLoading: false,
		} );
	};

	const handleBulkRestoreConfirm = () => {
		trashEntriesMutation(
			{
				entry_ids: selectedEntries,
				action: 'restore',
			},
			{
				onSuccess: () => {
					clearSelection();
					setConfirmationDialog( ( prev ) => ( {
						...prev,
						open: false,
					} ) );
				},
			}
		);
	};

	if (
		! hasActiveFilters &&
		entries.length === 0 &&
		! isLoading &&
		!! entriesData.emptyTrash
	) {
		return (
			<div className="p-8 bg-background-secondary min-h-screen">
				<div className="max-w-[1374px] mx-auto">
					<div className="bg-white rounded-xl border-0.5 border-border-subtle shadow-sm p-2 space-y-2">
						<EmptyState />
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="p-8 bg-background-secondary min-h-screen">
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
								selectedEntries={ selectedEntries }
								onBulkDelete={ handleBulkDelete }
								onBulkExport={ handleBulkExport }
								onMarkAsRead={ handleMarkAsRead }
								onMarkAsUnread={ handleMarkAsUnread }
								onBulkRestore={ handleBulkRestore }
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
						onRestore={ handleRestore }
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

			<ConfirmationDialog
				open={ confirmationDialog.open }
				setOpen={ ( open ) =>
					setConfirmationDialog( ( prev ) => ( { ...prev, open } ) )
				}
				onConfirm={ confirmationDialog.onConfirm }
				title={ confirmationDialog.title }
				description={ confirmationDialog.description }
				confirmLabel={ confirmationDialog.confirmLabel }
				isLoading={ confirmationDialog.isLoading }
			/>
		</div>
	);
};

export default EntriesListingPage;
