import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';

/**
 * Custom hook to manage payment pagination state with URL synchronization
 * Uses React Router's useSearchParams for hash-based routing
 *
 * @param {number} initialPage    - Initial page number
 * @param {number} initialPerPage - Initial items per page
 * @return {Object} Pagination states and handlers
 */
export const usePaymentPagination = (
	initialPage = 1,
	initialPerPage = 10
) => {
	const [ searchParams, setSearchParams ] = useSearchParams();

	// Initialize state from URL params
	const [ currentPage, setCurrentPageState ] = useState( () => {
		const pageFromUrl = searchParams.get( 'page' );
		return pageFromUrl ? parseInt( pageFromUrl, 10 ) : initialPage;
	} );

	const [ itemsPerPage, setItemsPerPageState ] = useState( () => {
		const perPageFromUrl = searchParams.get( 'per_page' );
		return perPageFromUrl ? parseInt( perPageFromUrl, 10 ) : initialPerPage;
	} );

	// Update URL params when pagination changes
	useEffect( () => {
		const params = new URLSearchParams( searchParams );

		if ( currentPage > 1 ) {
			params.set( 'page', currentPage.toString() );
		} else {
			params.delete( 'page' );
		}

		if ( itemsPerPage !== initialPerPage ) {
			params.set( 'per_page', itemsPerPage.toString() );
		} else {
			params.delete( 'per_page' );
		}

		setSearchParams( params, { replace: true } );
	}, [
		currentPage,
		itemsPerPage,
		initialPerPage,
		searchParams,
		setSearchParams,
	] );

	// Setter functions wrapped with useCallback
	const setCurrentPage = useCallback( ( page ) => {
		setCurrentPageState( Math.max( 1, page ) );
	}, [] );

	const setItemsPerPage = useCallback( ( perPage ) => {
		setItemsPerPageState( Math.max( 1, Math.min( 100, perPage ) ) );
		setCurrentPageState( 1 ); // Reset to first page when changing items per page
	}, [] );

	const goToNextPage = useCallback( () => {
		setCurrentPageState( ( prev ) => prev + 1 );
	}, [] );

	const goToPreviousPage = useCallback( () => {
		setCurrentPageState( ( prev ) => Math.max( prev - 1, 1 ) );
	}, [] );

	const resetPage = useCallback( () => {
		setCurrentPageState( 1 );
	}, [] );

	return {
		currentPage,
		itemsPerPage,
		setCurrentPage,
		setItemsPerPage,
		goToNextPage,
		goToPreviousPage,
		resetPage,
	};
};
