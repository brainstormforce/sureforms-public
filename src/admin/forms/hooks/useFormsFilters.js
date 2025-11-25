import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook to manage forms filters state with URL synchronization
 *
 * @return {Object} Filter states and setters
 */
export const useFormsFilters = () => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Initialize state from URL params
	const [ statusFilter, setStatusFilterState ] = useState(
		searchParams.get( 'status' ) || 'any'
	);
	const [ searchQuery, setSearchQueryState ] = useState(
		searchParams.get( 'search' ) || ''
	);
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

	// Update URL params when filters change
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		if ( statusFilter && statusFilter !== 'any' ) {
			params.set( 'status', statusFilter );
		} else {
			params.delete( 'status' );
		}

		if ( searchQuery ) {
			params.set( 'search', searchQuery );
		} else {
			params.delete( 'search' );
		}

		if ( dateRange?.from ) {
			params.set(
				'date_from',
				dateRange.from.toISOString().split( 'T' )[ 0 ]
			);
			if ( dateRange?.to ) {
				params.set(
					'date_to',
					dateRange.to.toISOString().split( 'T' )[ 0 ]
				);
			} else {
				params.set(
					'date_to',
					dateRange.from.toISOString().split( 'T' )[ 0 ]
				);
			}
		} else {
			params.delete( 'date_from' );
			params.delete( 'date_to' );
		}

		setSearchParams( params, { replace: true } );
	}, [
		statusFilter,
		searchQuery,
		dateRange,
		searchParams,
		setSearchParams,
	] );

	const setStatusFilter = useCallback( ( value ) => {
		setStatusFilterState( value );
	}, [] );

	const setSearchQuery = useCallback( ( value ) => {
		setSearchQueryState( value );
	}, [] );

	const setDateRange = useCallback( ( value ) => {
		setDateRangeState( value );
	}, [] );

	const resetFilters = () => {
		setStatusFilterState( 'any' );
		setSearchQueryState( '' );
		setDateRangeState( { from: null, to: null } );
	};

	return {
		statusFilter,
		setStatusFilter,
		searchQuery,
		setSearchQuery,
		dateRange,
		setDateRange,
		resetFilters,
	};
};
