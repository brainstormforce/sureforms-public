/**
 * React Query hooks for entries
 * This file contains custom hooks that use TanStack Query for data fetching
 */

// import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
// import {
// 	fetchEntries,
// 	deleteEntry,
// 	updateEntryStatus,
// 	bulkDeleteEntries,
// } from '../api/entriesApi';

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
 * TODO: Uncomment when TanStack Query is installed
 *
 * @param {Object} params - Query parameters
 * @return {Object} Query result
 */
export const useEntries = ( params ) => {
	// return useQuery({
	// 	queryKey: entriesKeys.list(params),
	// 	queryFn: () => fetchEntries(params),
	// 	keepPreviousData: true, // Keep previous data while fetching new data
	// 	staleTime: 1000 * 60 * 5, // Consider data fresh for 5 minutes
	// });

	// Temporary mock implementation
	console.log( 'useEntries called with params:', params );
	return {
		data: null,
		isLoading: false,
		isError: false,
		error: null,
	};
};

/**
 * Hook to delete an entry
 * TODO: Uncomment when TanStack Query is installed
 *
 * @return {Object} Mutation result
 */
export const useDeleteEntry = () => {
	// const queryClient = useQueryClient();
	//
	// return useMutation({
	// 	mutationFn: deleteEntry,
	// 	onSuccess: () => {
	// 		// Invalidate and refetch entries list
	// 		queryClient.invalidateQueries({ queryKey: entriesKeys.lists() });
	// 	},
	// });

	// Temporary mock implementation
	return {
		mutate: ( entryId ) => {
			console.log( 'Delete entry:', entryId );
		},
		isLoading: false,
	};
};

/**
 * Hook to update entry status
 * TODO: Uncomment when TanStack Query is installed
 *
 * @return {Object} Mutation result
 */
export const useUpdateEntryStatus = () => {
	// const queryClient = useQueryClient();
	//
	// return useMutation({
	// 	mutationFn: ({ entryId, status }) => updateEntryStatus(entryId, status),
	// 	onSuccess: () => {
	// 		queryClient.invalidateQueries({ queryKey: entriesKeys.lists() });
	// 	},
	// });

	// Temporary mock implementation
	return {
		mutate: ( { entryId, status } ) => {
			console.log( 'Update entry status:', entryId, status );
		},
		isLoading: false,
	};
};

/**
 * Hook to bulk delete entries
 * TODO: Uncomment when TanStack Query is installed
 *
 * @return {Object} Mutation result
 */
export const useBulkDeleteEntries = () => {
	// const queryClient = useQueryClient();
	//
	// return useMutation({
	// 	mutationFn: bulkDeleteEntries,
	// 	onSuccess: () => {
	// 		queryClient.invalidateQueries({ queryKey: entriesKeys.lists() });
	// 	},
	// });

	// Temporary mock implementation
	return {
		mutate: ( entryIds ) => {
			console.log( 'Bulk delete entries:', entryIds );
		},
		isLoading: false,
	};
};
