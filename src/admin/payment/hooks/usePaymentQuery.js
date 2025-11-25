/**
 * React Query hooks for payments
 * This file contains custom hooks that use TanStack Query for data fetching
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import {
	fetchPaymentsList,
	fetchSinglePayment,
	fetchSubscription,
	fetchForms,
	bulkDeletePayments,
	refundPayment,
	cancelSubscription,
	pauseSubscription,
	addPaymentNote,
	deletePaymentNote,
	deletePaymentLog,
} from '../api/paymentApi';

/**
 * Query key factory for payments
 * This helps maintain consistent query keys across the application
 */
export const paymentKeys = {
	all: [ 'payments' ],
	lists: () => [ ...paymentKeys.all, 'list' ],
	list: ( filters ) => [ ...paymentKeys.lists(), filters ],
	details: () => [ ...paymentKeys.all, 'detail' ],
	detail: ( id ) => [ ...paymentKeys.details(), id ],
	subscriptions: () => [ ...paymentKeys.all, 'subscription' ],
	subscription: ( id ) => [ ...paymentKeys.subscriptions(), id ],
	forms: () => [ 'forms', 'payment' ],
};

/**
 * Hook to fetch payments with filters
 *
 * @param {Object} params - Query parameters
 * @return {Object} Query result
 */
export const usePayments = ( params ) => {
	return useQuery( {
		queryKey: paymentKeys.list( params ),
		queryFn: () => fetchPaymentsList( params ),
		staleTime: 5 * 60 * 1000, // 5 minutes
		refetchOnWindowFocus: false,
		keepPreviousData: true,
	} );
};

/**
 * Hook to fetch single payment
 *
 * @param {number} paymentId - Payment ID
 * @param {Object} options   - Additional options
 * @return {Object} Query result
 */
export const usePayment = ( paymentId, options = {} ) => {
	return useQuery( {
		queryKey: paymentKeys.detail( paymentId ),
		queryFn: () => fetchSinglePayment( paymentId ),
		enabled: !! paymentId,
		staleTime: 2 * 60 * 1000, // 2 minutes
		refetchOnWindowFocus: false,
		...options,
	} );
};

/**
 * Hook to fetch subscription details
 *
 * @param {string|number} subscriptionId - Subscription ID
 * @param {Object}        options        - Additional options
 * @return {Object} Query result
 */
export const useSubscription = ( subscriptionId, options = {} ) => {
	return useQuery( {
		queryKey: paymentKeys.subscription( subscriptionId ),
		queryFn: () => fetchSubscription( subscriptionId ),
		enabled: !! subscriptionId,
		staleTime: 2 * 60 * 1000, // 2 minutes
		refetchOnWindowFocus: false,
		...options,
	} );
};

/**
 * Hook to fetch forms list
 *
 * @return {Object} Query result
 */
export const useForms = () => {
	return useQuery( {
		queryKey: paymentKeys.forms(),
		queryFn: fetchForms,
		staleTime: 10 * 60 * 1000, // 10 minutes - forms don't change often
		refetchOnWindowFocus: false,
	} );
};

/**
 * Hook for bulk deleting payments
 *
 * @return {Object} Mutation object
 */
export const useBulkDeletePayments = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: bulkDeletePayments,
		onSuccess: () => {
			// Handle messages in component
			queryClient.invalidateQueries( { queryKey: paymentKeys.lists() } );
		},
		// Error handling done in component
	} );
};

/**
 * Hook for refunding a payment
 *
 * @return {Object} Mutation object
 */
export const useRefundPayment = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: refundPayment,
		onSuccess: ( _data, variables ) => {
			toast.success(
				__( 'Payment refunded successfully.', 'sureforms' )
			);
			// Invalidate payment details and list
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( variables.paymentId ),
			} );
			queryClient.invalidateQueries( { queryKey: paymentKeys.lists() } );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'Failed to refund payment.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for canceling a subscription
 *
 * @return {Object} Mutation object
 */
export const useCancelSubscription = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: cancelSubscription,
		onSuccess: ( _data, paymentId ) => {
			toast.success(
				__( 'Subscription canceled successfully.', 'sureforms' )
			);
			// Invalidate subscription and payment details
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( paymentId ),
			} );
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.subscriptions(),
			} );
			queryClient.invalidateQueries( { queryKey: paymentKeys.lists() } );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'Failed to cancel subscription.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for pausing a subscription
 *
 * @return {Object} Mutation object
 */
export const usePauseSubscription = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: pauseSubscription,
		onSuccess: ( _data, paymentId ) => {
			toast.success(
				__( 'Subscription paused successfully.', 'sureforms' )
			);
			// Invalidate subscription and payment details
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( paymentId ),
			} );
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.subscriptions(),
			} );
			queryClient.invalidateQueries( { queryKey: paymentKeys.lists() } );
		},
		onError: ( error ) => {
			const msg =
				error?.message ||
				__( 'Failed to pause subscription.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for adding a payment note
 *
 * @return {Object} Mutation object
 */
export const useAddPaymentNote = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: ( { paymentId, noteText } ) =>
			addPaymentNote( paymentId, noteText ),
		onSuccess: ( _data, variables ) => {
			toast.success( __( 'Note added successfully.', 'sureforms' ) );
			// Invalidate payment details
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( variables.paymentId ),
			} );
		},
		onError: ( error ) => {
			const msg =
				error?.message || __( 'Failed to add note.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for deleting a payment note
 *
 * @return {Object} Mutation object
 */
export const useDeletePaymentNote = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: ( { paymentId, noteIndex } ) =>
			deletePaymentNote( paymentId, noteIndex ),
		onSuccess: ( _data, variables ) => {
			toast.success( __( 'Note deleted successfully.', 'sureforms' ) );
			// Invalidate payment details
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( variables.paymentId ),
			} );
		},
		onError: ( error ) => {
			const msg =
				error?.message || __( 'Failed to delete note.', 'sureforms' );
			toast.error( msg );
		},
	} );
};

/**
 * Hook for deleting a payment log
 *
 * @return {Object} Mutation object
 */
export const useDeletePaymentLog = () => {
	const queryClient = useQueryClient();

	return useMutation( {
		mutationFn: ( { paymentId, logIndex } ) =>
			deletePaymentLog( paymentId, logIndex ),
		onSuccess: ( _data, variables ) => {
			toast.success( __( 'Log deleted successfully.', 'sureforms' ) );
			// Invalidate payment details
			queryClient.invalidateQueries( {
				queryKey: paymentKeys.detail( variables.paymentId ),
			} );
		},
		onError: ( error ) => {
			const msg =
				error?.message || __( 'Failed to delete log.', 'sureforms' );
			toast.error( msg );
		},
	} );
};
