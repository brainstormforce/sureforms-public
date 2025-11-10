import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook to manage entries filters state with URL synchronization
 *
 * @return {Object} Filter states and setters
 */
export const useEntriesFilters = () => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Initialize state from URL params
	const [ statusFilter, setStatusFilterState ] = useState(
		searchParams.get( 'status' ) || ''
	);
	const [ formFilter, setFormFilterState ] = useState(
		searchParams.get( 'form' ) || ''
	);
	const [ searchQuery, setSearchQueryState ] = useState(
		searchParams.get( 'search' ) || ''
	);
	const [ dateRange, setDateRangeState ] = useState( () => {
		const from = searchParams.get( 'date_from' );
		const to = searchParams.get( 'date_to' );

		if ( from && to ) {
			return {
				from: new Date( from ),
				to: new Date( to ),
			};
		}
		return { from: null, to: null };
	} );

	// Update URL params when filters change
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		if ( statusFilter && statusFilter !== 'all' ) {
			params.set( 'status', statusFilter );
		} else {
			params.delete( 'status' );
		}

		if ( formFilter && formFilter !== 'all' ) {
			params.set( 'form', formFilter );
		} else {
			params.delete( 'form' );
		}

		if ( searchQuery ) {
			params.set( 'search', searchQuery );
		} else {
			params.delete( 'search' );
		}

		if ( dateRange?.from ) {
			params.set( 'date_from', dateRange.from.toISOString().split( 'T' )[ 0 ] );
			if ( dateRange?.to ) {
				params.set( 'date_to', dateRange.to.toISOString().split( 'T' )[ 0 ] );
			} else {
				params.set( 'date_to', dateRange.from.toISOString().split( 'T' )[ 0 ] );
			}
		} else {
			params.delete( 'date_from' );
			params.delete( 'date_to' );
		}

		setSearchParams( params, { replace: true } );
	}, [ statusFilter, formFilter, searchQuery, dateRange, searchParams, setSearchParams ] );

	const setStatusFilter = useCallback( ( value ) => {
		setStatusFilterState( value );
	}, [] );

	const setFormFilter = useCallback( ( value ) => {
		setFormFilterState( value );
	}, [] );

	const setSearchQuery = useCallback( ( value ) => {
		setSearchQueryState( value );
	}, [] );

	const setDateRange = useCallback( ( value ) => {
		setDateRangeState( value );
	}, [] );

	const resetFilters = () => {
		setStatusFilterState( '' );
		setFormFilterState( '' );
		setSearchQueryState( '' );
		setDateRangeState( { from: null, to: null } );
	};

	return {
		statusFilter,
		setStatusFilter,
		formFilter,
		setFormFilter,
		searchQuery,
		setSearchQuery,
		dateRange,
		setDateRange,
		resetFilters,
	};
};
