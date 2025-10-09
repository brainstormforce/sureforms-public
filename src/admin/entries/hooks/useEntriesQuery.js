/**
 * React Query hooks for entries
 * This file contains custom hooks that use TanStack Query for data fetching
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
	fetchEntriesList,
	updateEntriesReadStatus,
	updateEntriesTrashStatus,
	deleteEntries,
	exportEntries,
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
	detail: ( id ) => [ ...entriesKeys.details(), id ],
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
 * Hook to update entries read status
 *
 * @return {Object} Mutation result
 */
export const useUpdateEntriesReadStatus = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: updateEntriesReadStatus,
		onSuccess: () => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );
		},
	} );
};

/**
 * Hook to update entries trash status
 *
 * @return {Object} Mutation result
 */
export const useUpdateEntriesTrashStatus = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: updateEntriesTrashStatus,
		onSuccess: () => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );
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
		onSuccess: () => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );
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
	} );
};
