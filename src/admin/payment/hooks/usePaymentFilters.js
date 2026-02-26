import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook to manage payment filters state with URL synchronization
 * Manages: search, status, form filter, payment mode, and date range
 * Uses React Router's useSearchParams for hash routing compatibility
 *
 * @return {Object} Filter states and setters
 */
export const usePaymentFilters = () => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Get default payment mode from localized settings
	const defaultPaymentMode =
		window.srfm_admin?.payments?.stripe_mode || 'test';

	// Initialize state from URL params
	const [ searchTerm, setSearchTermState ] = useState( () => {
		return searchParams.get( 'search' ) || '';
	} );

	const [ statusFilter, setStatusFilterState ] = useState( () => {
		return searchParams.get( 'status' ) || '';
	} );

	const [ formFilter, setFormFilterState ] = useState( () => {
		return searchParams.get( 'form' ) || '';
	} );

	const [ paymentMode, setPaymentModeState ] = useState( () => {
		// Use URL param if available, otherwise use default mode
		return searchParams.get( 'mode' ) || defaultPaymentMode;
	} );

	const [ dateRange, setDateRangeState ] = useState( () => {
		const from = searchParams.get( 'date_from' );
		const to = searchParams.get( 'date_to' );

		if ( from && to ) {
			try {
				return {
					from: new Date( from ),
					to: new Date( to ),
				};
			} catch {
				return { from: null, to: null };
			}
		}
		return { from: null, to: null };
	} );

	// Sync all filters to URL
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		// Update search
		if ( searchTerm ) {
			params.set( 'search', searchTerm );
		} else {
			params.delete( 'search' );
		}

		// Update status
		if ( statusFilter ) {
			params.set( 'status', statusFilter );
		} else {
			params.delete( 'status' );
		}

		// Update form filter
		if ( formFilter ) {
			params.set( 'form', formFilter );
		} else {
			params.delete( 'form' );
		}

		// Update payment mode - only add to URL if different from default
		if ( paymentMode && paymentMode !== defaultPaymentMode ) {
			params.set( 'mode', paymentMode );
		} else {
			params.delete( 'mode' );
		}

		// Update date range
		if ( dateRange.from ) {
			params.set(
				'date_from',
				dateRange.from.toISOString().split( 'T' )[ 0 ]
			);
		} else {
			params.delete( 'date_from' );
		}

		if ( dateRange.to ) {
			params.set(
				'date_to',
				dateRange.to.toISOString().split( 'T' )[ 0 ]
			);
		} else {
			params.delete( 'date_to' );
		}

		setSearchParams( params, { replace: true } );
	}, [
		searchTerm,
		statusFilter,
		formFilter,
		paymentMode,
		dateRange,
		defaultPaymentMode,
	] );

	// Setter functions wrapped with useCallback
	const setSearchTerm = useCallback( ( value ) => {
		setSearchTermState( value );
	}, [] );

	const setStatusFilter = useCallback( ( value ) => {
		setStatusFilterState( value );
	}, [] );

	const setFormFilter = useCallback( ( value ) => {
		setFormFilterState( value );
	}, [] );

	const setPaymentMode = useCallback( ( value ) => {
		setPaymentModeState( value );
	}, [] );

	const setDateRange = useCallback( ( value ) => {
		setDateRangeState( value );
	}, [] );

	// Reset all filters
	const resetFilters = useCallback( () => {
		setSearchTermState( '' );
		setStatusFilterState( '' );
		setFormFilterState( '' );
		// Reset mode to default, not empty
		setPaymentModeState( defaultPaymentMode );
		setDateRangeState( { from: null, to: null } );

		// Clear URL params (React Router handles the actual URL update via useEffect)
	}, [ defaultPaymentMode ] );

	// Check if any filters are active
	// Mode is only considered active if it's different from the default mode
	const hasActiveFilters = useCallback( () => {
		return !! (
			searchTerm ||
			statusFilter ||
			formFilter ||
			( paymentMode && paymentMode !== defaultPaymentMode ) ||
			dateRange.from ||
			dateRange.to
		);
	}, [
		searchTerm,
		statusFilter,
		formFilter,
		paymentMode,
		dateRange,
		defaultPaymentMode,
	] );

	return {
		searchTerm,
		setSearchTerm,
		statusFilter,
		setStatusFilter,
		formFilter,
		setFormFilter,
		paymentMode,
		setPaymentMode,
		dateRange,
		setDateRange,
		resetFilters,
		hasActiveFilters,
	};
};
