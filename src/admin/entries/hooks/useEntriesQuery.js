/**
 * React Query hooks for entries
 * This file contains custom hooks that use TanStack Query for data fetching
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import {
	fetchEntriesList,
	fetchFormsList,
	updateEntriesReadStatus,
	trashEntries,
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
 * Query key factory for forms
 *
 * @return {Array} Query key array
 */
export const formsKeys = {
	all: [ 'forms' ],
	list: () => [ ...formsKeys.all, 'list' ],
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
			if ( action === 'trash' ) {
				toast.success( __( 'Entry(s) moved to Trash.', 'sureforms' ) );
			} else if ( action === 'restore' ) {
				toast.success(
					__( 'Entry(s) restored successfully.', 'sureforms' )
				);
			} else {
				// generic success
				toast.success(
					data?.message || __( 'Action completed.', 'sureforms' )
				);
			}
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
		onSuccess: () => {
			// Invalidate and refetch entries list
			queryClient.invalidateQueries( { queryKey: entriesKeys.lists() } );
			toast.success( __( 'Entry deleted permanently.', 'sureforms' ) );
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
