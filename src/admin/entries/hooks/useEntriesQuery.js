/**
 * React Query hooks for entries
 * This file contains custom hooks that use TanStack Query for data fetching
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from '@bsf/force-ui';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	fetchEntriesList,
	fetchFormsList,
	fetchEntryDetail,
	updateEntriesReadStatus,
	trashEntries,
	deleteEntries,
	exportEntries,
	fetchEntryLogs,
} from '../api/entriesApi';

/**
 * Query key factory for entries
 * This helps maintain consistent query keys across the application
 *
 * @param {Object} filters - Filter parameters
 * @return {Array} Query key array
 */
export const entriesKeys = {
	all: [ 'entries' ],
	lists: () => [ ...entriesKeys.all, 'list' ],
	list: ( filters ) => [ ...entriesKeys.lists(), filters ],
	details: () => [ ...entriesKeys.all, 'detail' ],
	detail: ( id ) => [ ...entriesKeys.details(), +id ],
};

/**
 * Query key factory for forms
 *
 * @return {Array} Query key array
 */
export const formsKeys = {
	all: [ 'forms' ],
	list: () => [ ...formsKeys.all, 'list' ],
};

/**
 * Query key factory for entry logs
 *
 * @param {number} entryId - Entry ID
 * @return {Object} Query key object
 */
export const entryLogsKeys = {
	all: [ 'entry-logs' ],
	lists: () => [ ...entryLogsKeys.all, 'list' ],
	list: ( entryId, pagination ) => [
		...entryLogsKeys.lists(),
		+entryId,
		pagination,
	],
};

/**
 * Hook to fetch entries with filters
 *
 * @param {Object} params - Query parameters
 * @return {Object} Query result
 */
export const useEntries = ( params ) => {
	return useQuery( {
		queryKey: entriesKeys.list( params ),
		queryFn: () => fetchEntriesList( params ),
		keepPreviousData: true, // Keep previous data while fetching new data
		staleTime: 1000 * 60 * 5, // Consider data fresh for 5 minutes
	} );
};

/**
 * Hook to fetch forms list
 * Returns a map of form IDs to form titles
 *
 * @return {Object} Query result
 */
export const useForms = () => {
	return useQuery( {
		queryKey: formsKeys.list(),
		queryFn: fetchFormsList,
		staleTime: 1000 * 60 * 10, // Consider data fresh for 10 minutes (forms don't change often)
	} );
};

/**
 * Hook to fetch single entry details
 *
 * @param {number} entryId - Entry ID to fetch
 * @return {Object} Query result
 */
export const useEntryDetail = ( entryId ) => {
	return useQuery( {
		queryKey: entriesKeys.detail( entryId ),
		queryFn: () => fetchEntryDetail( entryId ),
		enabled: !! entryId, // Only run query if entryId is provided
		staleTime: 1000 * 60 * 5, // Consider data fresh for 5 minutes
	} );
};

/**
 * Hook to update entries read status
 *
 * @return {Object} Mutation result
 */
export const useUpdateEntriesReadStatus = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: updateEntriesReadStatus,
		onSuccess: ( data, variables ) => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );

			// Invalidate entry detail queries if entry IDs are provided
			if ( variables?.entry_ids?.length > 0 ) {
				variables.entry_ids.forEach( ( entryId ) => {
					queryClient.invalidateQueries( {
						queryKey: entriesKeys.detail( entryId ),
					} );
				} );
			}

			// Skip showing toast if requested
			if ( variables?.skipToast ) {
				return;
			}

			// Show success message
			const action = variables?.action || '';
			const count = variables?.entry_ids?.length || 1;
			let message = '';
			if ( count === 1 ) {
				const entryId = variables.entry_ids[ 0 ];
				message = sprintf(
					// translators: %1$s is the entry ID, %2$s is the action (read/unread).
					__( 'Entry#%1$s marked as %2$s.', 'sureforms' ),
					entryId,
					action
				);
			} else {
				message = sprintf(
					// translators: %1$s is the number of entries marked as read, %2$s is the action.
					_n(
						'%1$s entry marked as %2$s.',
						'%1$s entries marked as %2$s.',
						count,
						'sureforms'
					),
					count,
					action
				);
			}
			toast.success( message );

			// Call custom onSuccess if provided
			if ( typeof variables?.onSuccess === 'function' ) {
				variables.onSuccess();
			}
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__(
					'An error occurred while updating read status. Please try again.',
					'sureforms'
				);
			toast.error( msg );
		},
	} );
};

/**
 * Hook to update entries trash status
 *
 * @return {Object} Mutation result
 */
export const useTrashEntries = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: trashEntries,
		onSuccess: ( data, variables ) => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );

			// Show a toast depending on the action (trash or restore)
			const action = variables?.action || '';
			const count = variables?.entry_ids?.length || 1;
			let message = '';
			if ( count === 1 ) {
				const entryId = variables.entry_ids[ 0 ];
				message =
					action === 'trash'
						? sprintf(
							// translators: %s is the entry ID.
							__( 'Entry#%s moved to trash.', 'sureforms' ),
							entryId
						  )
						: sprintf(
							// translators: %s is the entry ID.
							__(
								'Entry#%s restored successfully.',
								'sureforms'
							),
							entryId
						  );
			} else {
				message =
					action === 'trash'
						? sprintf(
							// translators: %s is the number of entries moved to trash.
							_n(
								'%1$s entry moved to trash.',
								'%1$s entries moved to trash.',
								count,
								'sureforms'
							),
							count
						  )
						: sprintf(
							// translators: %s is the number of entries restored.
							_n(
								'%1$s entry restored successfully.',
								'%1$s entries restored successfully.',
								count,
								'sureforms'
							),
							count
						  );
			}
			toast.success( message );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'An error occurred. Please try again.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook to permanently delete entries
 *
 * @return {Object} Mutation result
 */
export const useDeleteEntries = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: deleteEntries,
		onSuccess: ( data, variables ) => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );
			const count = variables?.entry_ids?.length || 1;
			let message = '';
			if ( count === 1 ) {
				const entryId = variables.entry_ids[ 0 ];
				message = sprintf(
					// translators: %s is the entry ID.
					__( 'Entry#%s deleted permanently.', 'sureforms' ),
					entryId
				);
			} else {
				message = sprintf(
					// translators: %s is the number of entries deleted.
					_n(
						'%s entry deleted permanently.',
						'%s entries deleted permanently.',
						count,
						'sureforms'
					),
					count
				);
			}
			toast.success( message );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'An error occurred. Please try again.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook to export entries
 *
 * @return {Object} Mutation result
 */
export const useExportEntries = () => {
	return useMutation( {
		mutationFn: exportEntries,
		onSuccess: ( data ) => {
			// Handle successful export
			// The response contains download_url which can be used to download the file
			if ( data.success && data.download_url ) {
				// Trigger download
				window.location.href = data.download_url;
			}
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__(
					'An error occurred during export. Please try again.',
					'sureforms'
				);
			toast.error( msg );
		},
	} );
};

/**
 * Hook to fetch entry logs with pagination
 *
 * @param {number} entryId    - Entry ID to fetch logs for
 * @param {Object} pagination - Object with page and per_page properties
 * @return {Object} Query result
 */
export const useEntryLogs = (
	entryId,
	pagination = { page: 1, per_page: 3 }
) => {
	return useQuery( {
		queryKey: entryLogsKeys.list( entryId, pagination ),
		queryFn: () => fetchEntryLogs( { id: entryId, ...pagination } ),
		enabled: !! entryId,
		staleTime: 1000 * 60 * 5, // Consider data fresh for 5 minutes
	} );
};
