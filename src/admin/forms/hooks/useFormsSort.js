import { useState, useCallback, useEffect } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook for managing forms sorting state with URL synchronization
 *
 * @param {string} initialSortBy    - Initial sort column (default: 'date')
 * @param {string} initialSortOrder - Initial sort order (default: 'desc')
 * @return {Object} Sorting state and handlers
 */
export const useFormsSort = (
	initialSortBy = 'date',
	initialSortOrder = 'desc'
) => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Initialize state from URL params or defaults
	const [ sortBy, setSortByState ] = useState(
		searchParams.get( 'orderby' ) || initialSortBy
	);
	const [ sortOrder, setSortOrderState ] = useState(
		searchParams.get( 'order' ) || initialSortOrder
	);

	// Update URL params when sorting changes
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		if ( sortBy && sortBy !== initialSortBy ) {
			params.set( 'orderby', sortBy );
		} else {
			params.delete( 'orderby' );
		}

		if ( sortOrder && sortOrder !== initialSortOrder ) {
			params.set( 'order', sortOrder );
		} else {
			params.delete( 'order' );
		}

		setSearchParams( params, { replace: true } );
	}, [
		sortBy,
		sortOrder,
		initialSortBy,
		initialSortOrder,
		searchParams,
		setSearchParams,
	] );

	const handleSort = useCallback(
		( column ) => {
			if ( sortBy === column ) {
				// Toggle order if same column
				setSortOrderState( ( prevOrder ) =>
					prevOrder === 'desc' ? 'asc' : 'desc'
				);
			} else {
				// Set new column with default desc order
				setSortByState( column );
				setSortOrderState( 'desc' );
			}
		},
		[ sortBy ]
	);

	const getSortDirection = useCallback(
		( column ) => {
			return sortBy === column ? sortOrder : null;
		},
		[ sortBy, sortOrder ]
	);

	const resetSort = useCallback( () => {
		setSortByState( initialSortBy );
		setSortOrderState( initialSortOrder );
	}, [ initialSortBy, initialSortOrder ] );

	return {
		sortBy,
		sortOrder,
		handleSort,
		getSortDirection,
		resetSort,
	};
};
