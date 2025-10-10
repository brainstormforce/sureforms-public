import { useState } from '@wordpress/element';

/**
 * Custom hook to manage pagination state
 *
 * @param {number} initialPage    - Initial page number
 * @param {number} initialPerPage - Initial entries per page
 * @return {Object} Pagination states and handlers
 */
export const usePagination = ( initialPage = 1, initialPerPage = 10 ) => {
	const [ currentPage, setCurrentPage ] = useState( initialPage );
	const [ entriesPerPage, setEntriesPerPage ] = useState( initialPerPage );

	const goToPage = ( page ) => {
		setCurrentPage( page );
	};

	const nextPage = ( totalPages ) => {
		setCurrentPage( ( prev ) => Math.min( totalPages, prev + 1 ) );
	};

	const previousPage = () => {
		setCurrentPage( ( prev ) => Math.max( 1, prev - 1 ) );
	};

	const changeEntriesPerPage = ( perPage ) => {
		setEntriesPerPage( perPage );
		setCurrentPage( 1 ); // Reset to first page when changing per page
	};

	return {
		currentPage,
		entriesPerPage,
		goToPage,
		nextPage,
		previousPage,
		changeEntriesPerPage,
	};
};
