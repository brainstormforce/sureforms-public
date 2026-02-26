import { useState, useCallback, useEffect } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook for managing forms pagination state with URL synchronization
 *
 * @param {number} initialPage - Initial page number (default: 1)
 * @return {Object} Pagination state and handlers
 */
export const useFormsPagination = ( initialPage = 1 ) => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Initialize state from URL params or defaults
	const [ currentPage, setCurrentPageState ] = useState( () => {
		const page = searchParams.get( 'paged' );
		return page ? parseInt( page, 10 ) : initialPage;
	} );

	const [ perPage, setPerPageState ] = useState( undefined );

	// Update URL params when pagination changes (only page, not perPage)
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		if ( currentPage && currentPage > 1 ) {
			params.set( 'paged', currentPage.toString() );
		} else {
			params.delete( 'paged' );
		}

		setSearchParams( params, { replace: true } );
	}, [ currentPage, searchParams, setSearchParams ] );

	const setCurrentPage = useCallback( ( page ) => {
		setCurrentPageState( page );
	}, [] );

	const setPerPage = useCallback( ( count ) => {
		setPerPageState( count );
		// Reset to first page when changing per page
		setCurrentPageState( 1 );
	}, [] );

	const resetPagination = useCallback( () => {
		setCurrentPageState( initialPage );
		setPerPageState( undefined );
	}, [ initialPage ] );

	return {
		currentPage,
		perPage,
		setCurrentPage,
		setPerPage,
		resetPagination,
	};
};
