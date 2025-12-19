/**
 * React Query hooks for forms
 * This file contains custom hooks that use TanStack Query for data fetching
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from '@bsf/force-ui';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	fetchFormsList,
	bulkFormsAction,
	exportForms as exportFormsApi,
	importForms as importFormsApi,
	duplicateForm as duplicateFormApi,
} from '../api/formsApi';

/**
 * Query key factory for forms
 * This helps maintain consistent query keys across the application
 *
 * @param {Object} filters - Filter parameters
 * @return {Array} Query key array
 */
export const formsKeys = {
	all: [ 'forms' ],
	lists: () => [ ...formsKeys.all, 'list' ],
	list: ( filters ) => [ ...formsKeys.lists(), filters ],
	details: () => [ ...formsKeys.all, 'detail' ],
	detail: ( id ) => [ ...formsKeys.details(), id ],
};

/**
 * Hook to fetch forms with filters
 *
 * @param {Object} params - Query parameters
 * @return {Object} Query result
 */
export const useForms = ( params ) => {
	return useQuery( {
		queryKey: formsKeys.list( params ),
		queryFn: () => fetchFormsList( params ),
		keepPreviousData: true,
		staleTime: 1000 * 60 * 5, // 5 minutes
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'An error occurred while fetching forms.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for bulk actions on forms
 *
 * @return {Object} Mutation object
 */
export const useBulkFormsAction = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: bulkFormsAction,
		onSuccess: ( _data, variables ) => {
			const { action, form_ids } = variables;
			const count = form_ids.length;

			let message = '';
			switch ( action ) {
				case 'trash':
					message = sprintf(
						/* translators: %d: number of forms */
						_n(
							'%d form moved to trash.',
							'%d forms moved to trash.',
							count,
							'sureforms'
						),
						count
					);
					break;
				case 'restore':
					message = sprintf(
						/* translators: %d: number of forms */
						_n(
							'%d form restored.',
							'%d forms restored.',
							count,
							'sureforms'
						),
						count
					);
					break;
				case 'delete':
					message = sprintf(
						/* translators: %d: number of forms */
						_n(
							'%d form permanently deleted.',
							'%d forms permanently deleted.',
							count,
							'sureforms'
						),
						count
					);
					break;
			}

			if ( message ) {
				toast.success( message );
			}

			// Invalidate and refetch forms list
			queryClient.invalidateQueries( { queryKey: formsKeys.lists() } );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__(
					'An error occurred while performing the action.',
					'sureforms'
				);
			toast.error( msg );
		},
	} );
};

/**
 * Hook for exporting forms
 *
 * @return {Object} Mutation object
 */
export const useExportForms = () => {
	return useMutation( {
		mutationFn: exportFormsApi,
		onSuccess: () => {
			toast.success( __( 'Forms exported successfully.', 'sureforms' ) );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'An error occurred while exporting forms.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for importing forms
 *
 * @return {Object} Mutation object
 */
export const useImportForms = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: importFormsApi,
		onSuccess: ( response ) => {
			if ( response.success ) {
				const count = response.count || 1;
				const message = sprintf(
					/* translators: %d: number of imported forms */
					_n(
						'%d form imported successfully.',
						'%d forms imported successfully.',
						count,
						'sureforms'
					),
					count
				);
				toast.success( message );

				// Invalidate and refetch forms list
				queryClient.invalidateQueries( {
					queryKey: formsKeys.lists(),
				} );
			}
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'An error occurred while importing forms.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for duplicating a form
 *
 * @return {Object} Mutation object
 */
export const useDuplicateForm = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: duplicateFormApi,
		onSuccess: ( data ) => {
			if ( data.success ) {
				const message = sprintf(
					/* translators: %s: new form title */
					__( 'Form duplicated successfully: %s', 'sureforms' ),
					data.new_form_title
				);
				toast.success( sprintf(
					/* translators: %s: new form title */
					__( 'Form duplicated successfully: %s', 'sureforms' ),
					data.new_form_title
				) );

				// Invalidate and refetch forms list
				queryClient.invalidateQueries( {
					queryKey: formsKeys.lists(),
				} );
			}
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__(
					'An error occurred while duplicating the form.',
					'sureforms'
				);
			toast.error( msg );
		},
	} );
};
