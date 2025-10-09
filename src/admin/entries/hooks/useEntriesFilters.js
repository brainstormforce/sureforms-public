import { useState } from '@wordpress/element';

/**
 * Custom hook to manage entries filters state
 *
 * @return {Object} Filter states and setters
 */
export const useEntriesFilters = () => {
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ formFilter, setFormFilter ] = useState( 'all' );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ dateRange, setDateRange ] = useState( {
		from: null,
		to: null,
	} );

	const resetFilters = () => {
		setStatusFilter( 'all' );
		setFormFilter( 'all' );
		setSearchQuery( '' );
		setDateRange( null );
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
