import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook to manage pagination state with URL synchronization
 *
 * @param {number} initialPage    - Initial page number
 * @param {number} initialPerPage - Initial entries per page
 * @return {Object} Pagination states and handlers
 */
export const usePagination = ( initialPage = 1, initialPerPage = 10 ) => {
	const [ searchParams, setSearchParams ] = useSearchParams();
	const skipUrlUpdateRef = useRef( false );

	// Initialize state from URL params
	const [ currentPage, setCurrentPage ] = useState( () => {
		const pageFromUrl = searchParams.get( 'page' );
		return pageFromUrl ? parseInt( pageFromUrl, 10 ) : initialPage;
	} );

	const [ entriesPerPage, setEntriesPerPage ] = useState( initialPerPage );

	// Initialize entriesPerPage from localStorage or URL
	useEffect( () => {
		const stored = localStorage.getItem( 'sureforms_entries_per_page' );
		if ( stored ) {
			const parsed = parseInt( stored, 10 );
			if ( parsed > 0 && parsed <= 1000 ) {
				setEntriesPerPage( parsed );
				return;
			}
		}
		const perPageFromUrl = searchParams.get( 'per_page' );
		if ( perPageFromUrl ) {
			setEntriesPerPage( parseInt( perPageFromUrl, 10 ) );
		}
	}, [ searchParams ] );

	// Update URL params when pagination changes
	useEffect( () => {
		if ( skipUrlUpdateRef.current ) {
			skipUrlUpdateRef.current = false;
			return;
		}

		const params = new URLSearchParams( searchParams );

		if ( currentPage > 1 ) {
			params.set( 'page', currentPage.toString() );
		} else {
			params.delete( 'page' );
		}

		if ( entriesPerPage !== initialPerPage ) {
			params.set( 'per_page', entriesPerPage.toString() );
		} else {
			params.delete( 'per_page' );
		}

		setSearchParams( params, { replace: true } );
	}, [
		currentPage,
		entriesPerPage,
		initialPerPage,
		searchParams,
		setSearchParams,
	] );

	const goToPage = useCallback( ( page ) => {
		setCurrentPage( page );
	}, [] );

	const silentGoToPage = useCallback( ( page ) => {
		skipUrlUpdateRef.current = true;
		setCurrentPage( page );
	}, [] );

	const nextPage = useCallback( ( totalPages ) => {
		setCurrentPage( ( prev ) => Math.min( totalPages, prev + 1 ) );
	}, [] );

	const previousPage = useCallback( () => {
		setCurrentPage( ( prev ) => Math.max( 1, prev - 1 ) );
	}, [] );

	const changeEntriesPerPage = useCallback( ( perPage ) => {
		setEntriesPerPage( perPage );
		localStorage.setItem(
			'sureforms_entries_per_page',
			perPage.toString()
		);
		setCurrentPage( 1 ); // Reset to first page when changing per page
	}, [] );

	return {
		currentPage,
		entriesPerPage,
		goToPage,
		silentGoToPage,
		nextPage,
		previousPage,
		changeEntriesPerPage,
	};
};
