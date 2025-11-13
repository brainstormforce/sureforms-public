import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import { toast } from '@bsf/force-ui';
import EntriesHeader from '../components/EntriesHeader';
import EntriesFilters from '../components/EntriesFilters';
import EntriesTable from '../components/EntriesTable';
import EmptyState from '../components/EmptyState';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';
import { useEntriesFilters } from '../hooks/useEntriesFilters';
import { useEntriesSelection } from '../hooks/useEntriesSelection';
import { usePagination } from '../hooks/usePagination';
import { useEntriesSort } from '../hooks/useEntriesSort';
import {
	useEntries,
	useForms,
	useDeleteEntries,
	useTrashEntries,
	useUpdateEntriesReadStatus,
	useExportEntries,
} from '../hooks/useEntriesQuery';
import { transformEntry } from '../utils/entryHelpers';
import { getFormOptions } from '../constants';
import NoResultsFound from '@Admin/common/listing/components/NoResultsFound';

/**
 * EntriesListingPage Component
 * Main container for the entries listing page
 * Handles data fetching with TanStack Query
 */
const EntriesListingPage = () => {
	// Router navigation hook
	const navigate = useNavigate();

	// Fetch forms data using React Query
	const { data: formsMap = {}, isLoading: isLoadingForms } = useForms();
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

	// Filter change handlers with page reset
	const handleStatusFilterChange = ( value ) => {
		setStatusFilter( value );
		goToPage( 1 );
	};

	const handleFormFilterChange = ( value ) => {
		setFormFilter( value );
		goToPage( 1 );
	};

	const handleSearchChange = ( value ) => {
		setSearchQuery( value );
		goToPage( 1 );
	};

	const handleDateRangeChange = ( value ) => {
		setDateRange( value );
		goToPage( 1 );
	};

	const {
		currentPage,
		entriesPerPage,
		goToPage,
		silentGoToPage,
		nextPage,
		previousPage,
		changeEntriesPerPage,
	} = usePagination( 1, 10 );

	const { sortBy, order, handleSort, getSortDirection } = useEntriesSort();

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
	const {
		data: entriesData,
		isLoading,
		error,
		isError,
	} = useEntries( {
		form_id: formFilter === 'all' ? 0 : parseInt( formFilter, 10 ),
		status: statusFilter,
		search: searchQuery,
		date_from: dateFilters.date_from,
		date_to: dateFilters.date_to,
		orderby: sortBy,
		order,
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

	useEffect( () => {
		if ( currentPage <= 1 || entries.length > 0 || isLoading ) {
			return;
		}
		if ( currentPage > totalPages && entries?.length === 0 ) {
			goToPage( totalPages );
		}
	}, [ entries, isLoading ] );

	// Generate form options for filter
	const formOptions = useMemo( () => {
		return getFormOptions( formsMap );
	}, [ formsMap ] );

	const hasActiveFilters = useMemo( () => {
		return (
			( statusFilter !== '' && statusFilter !== 'all' ) ||
			( formFilter !== '' && formFilter !== 'all' ) ||
			searchQuery.trim() !== '' ||
			( dateRange?.from !== null && dateRange?.to !== null )
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
		destructive: true,
	} );

	const {
		selectedEntries,
		handleChangeRowCheckbox,
		handleToggleAll,
		clearSelection,
		indeterminate,
	} = useEntriesSelection( entries );

	// Compute if selected entries include unread or read entries
	const hasUnreadSelected = useMemo( () => {
		return selectedEntries?.some( ( entryId ) => {
			const entry = entries.find(
				( e ) => parseInt( e.id ) === parseInt( entryId )
			);
			return entry && entry.status === 'unread';
		} );
	}, [ selectedEntries, entries ] );

	const hasReadSelected = useMemo( () => {
		return selectedEntries?.some( ( entryId ) => {
			const entry = entries.find(
				( e ) => parseInt( e.id ) === parseInt( entryId )
			);
			return entry && entry.status === 'read';
		} );
	}, [ selectedEntries, entries ] );

	// Show an error toast if fetching entries fails
	useEffect( () => {
		if ( ! isError ) {
			return;
		}
		toast.error(
			error?.message ||
				__( 'An error occurred while fetching entries.', 'sureforms' )
		);
	}, [ error ] );

	// Action handlers
	const handleEdit = ( entry ) => {
		// If entry is unread, add "read" query param to mark it as read
		if ( entry.status === 'unread' ) {
			navigate( `/entry/${ entry.id }?read=true` );
		} else {
			navigate( `/entry/${ entry.id }` );
		}
	};

	const handleDelete = ( entry ) => {
		// If entry is already in trash, show confirmation dialog for permanent delete
		if ( entry.status === 'trash' ) {
			setConfirmationDialog( {
				open: true,
				title: __( 'Delete Entry', 'sureforms' ),
				description: __(
					'Are you sure you want to permanently delete this entry? This action cannot be undone.',
					'sureforms'
				),
				confirmLabel: __( 'Delete Permanently', 'sureforms' ),
				onConfirm: () => handlePermanentDelete( entry ),
				isLoading: isDeleting,
				destructive: true,
				enableVerification: true,
				verificationText: 'delete',
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
				? _n(
					'Delete Entry',
					'Delete Entries',
					selectedEntries.length,
					'sureforms'
				  )
				: _n(
					'Move Entry to Trash?',
					'Move Entries to Trash?',
					selectedEntries.length,
					'sureforms'
				  ),
			description: allInTrash
				? sprintf(
					// translators: %s is the number of entries to be deleted.
					_n(
						'Are you sure you want to permanently delete %s entry? This action cannot be undone.',
						'Are you sure you want to permanently delete %s entries? This action cannot be undone.',
						selectedEntries.length,
						'sureforms'
					),
					selectedEntries.length
				  )
				: sprintf(
					// translators: %s is the number of entries to be moved to trash.
					_n(
						'%s entry will be moved to trash and can be restored later.',
						'%s entries will be moved to trash and can be restored later.',
						selectedEntries.length,
						'sureforms'
					),
					selectedEntries.length
				  ),
			confirmLabel: allInTrash
				? __( 'Delete Permanently', 'sureforms' )
				: __( 'Move to Trash', 'sureforms' ),
			onConfirm: handleBulkDeleteConfirm,
			isLoading: isDeleting,
			destructive: true,
			enableVerification: allInTrash,
			verificationText: 'delete',
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
		exportEntriesMutation(
			{
				entry_ids: selectedEntries,
				form_id: formFilter === 'all' ? 0 : parseInt( formFilter, 10 ),
				status: statusFilter,
				search: searchQuery,
				date_from: dateFilters.date_from,
				date_to: dateFilters.date_to,
			},
			{
				onSuccess: () => {
					clearSelection();
					toast.success(
						__( 'Entries exported successfully.', 'sureforms' )
					);
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
					silentGoToPage( 1 );
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
					silentGoToPage( 1 );
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
			title: _n(
				'Restore Entry',
				'Restore Entries',
				selectedEntries.length,
				'sureforms'
			),
			description: sprintf(
				// translators: %s is the number of entries to be restored.
				_n(
					'%s entry will be restored from trash.',
					'%s entries will be restored from trash.',
					selectedEntries.length,
					'sureforms'
				),
				selectedEntries.length
			),
			confirmLabel: __( 'Restore', 'sureforms' ),
			onConfirm: handleBulkRestoreConfirm,
			isLoading: false,
			destructive: false,
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

	const handleClearFilters = () => {
		setStatusFilter( '' );
		setFormFilter( '' );
		setSearchQuery( '' );
		setDateRange( { from: null, to: null } );
		goToPage( 1 );
	};

	if (
		! hasActiveFilters &&
		entries.length === 0 &&
		! isLoading &&
		!! entriesData?.emptyTrash
	) {
		return (
			<div className="p-8 bg-background-secondary min-h-screen">
				<div className="mx-auto">
					<div className="bg-white rounded-lg border-0.5 border-solid border-border-subtle shadow-sm p-4 space-y-2">
						<EmptyState />
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="p-8 bg-background-secondary min-h-screen">
			<div className="mx-auto">
				<div className="bg-white rounded-xl border-0.5 border-solid border-border-subtle shadow-sm p-4 space-y-2">
					<div className="p-1">
						<div className="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 xl:gap-5">
							<EntriesHeader />
							<EntriesFilters
								statusFilter={ statusFilter }
								onStatusFilterChange={
									handleStatusFilterChange
								}
								formFilter={ formFilter }
								onFormFilterChange={ handleFormFilterChange }
								searchQuery={ searchQuery }
								onSearchChange={ handleSearchChange }
								dateRange={ dateRange }
								onDateRangeChange={ handleDateRangeChange }
								formOptions={ formOptions }
								isLoadingForms={ isLoadingForms }
								selectedEntries={ selectedEntries }
								onBulkDelete={ handleBulkDelete }
								onBulkExport={ handleBulkExport }
								onMarkAsRead={ handleMarkAsRead }
								onMarkAsUnread={ handleMarkAsUnread }
								onBulkRestore={ handleBulkRestore }
								onClearFilters={ handleClearFilters }
								hasActiveFilters={ hasActiveFilters }
								hasUnreadSelected={ hasUnreadSelected }
								hasReadSelected={ hasReadSelected }
							/>
						</div>
					</div>

					{ hasActiveFilters &&
					entries.length === 0 &&
					! isLoading ? (
							<NoResultsFound
								handleClearFilters={ handleClearFilters }
							/>
						) : (
							<EntriesTable
								data={ entries }
								selectedItems={ selectedEntries }
								onToggleAll={ handleToggleAll }
								onChangeRowSelection={ handleChangeRowCheckbox }
								indeterminate={ indeterminate }
								isLoading={ isLoading }
								onSort={ handleSort }
								getSortDirection={ getSortDirection }
								onEdit={ handleEdit }
								onDelete={ handleDelete }
								onRestore={ handleRestore }
								paginationProps={ {
									currentPage,
									totalPages,
									perPage: entriesPerPage,
									onPageChange: goToPage,
									onPerPageChange: changeEntriesPerPage,
									onNextPage: () => nextPage( totalPages ),
									onPreviousPage: previousPage,
								} }
							/>
						) }
				</div>
			</div>

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
				destructiveConfirmButton={ confirmationDialog?.destructive }
				requireConfirmation={ confirmationDialog?.enableVerification }
			/>
		</div>
	);
};

export default EntriesListingPage;
