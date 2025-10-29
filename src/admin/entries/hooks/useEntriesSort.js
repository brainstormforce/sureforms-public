import { useState, useCallback } from '@wordpress/element';

/**
 * Custom hook for managing entries sorting state
 *
 * @param {string} initialSortBy - Initial sort column (default: '')
 * @param {string} initialOrder  - Initial sort order (default: '')
 * @return {Object} Sort state and handlers
 */
export const useEntriesSort = ( initialSortBy = '', initialOrder = '' ) => {
	const [ sortBy, setSortBy ] = useState( initialSortBy );
	const [ order, setOrder ] = useState( initialOrder );

	/**
	 * Map frontend column keys to backend API field names
	 */
	const columnToApiFieldMap = {
		id: 'id',
		status: 'status',
		dateTime: 'created_at',
	};

	/**
	 * Handle sort column change
	 * Toggle order if same column, otherwise set to DESC
	 *
	 * @param {string} columnKey - Column key to sort by
	 */
	const handleSort = useCallback(
		( columnKey ) => {
			const apiField = columnToApiFieldMap[ columnKey ];

			if ( ! apiField ) {
				return;
			}

			// If clicking the same column, toggle the order
			if ( sortBy === apiField ) {
				setOrder( ( prevOrder ) =>
					prevOrder === 'DESC' ? 'ASC' : 'DESC'
				);
			} else {
				// New column, set it and default to DESC
				setSortBy( apiField );
				setOrder( 'DESC' );
			}
		},
		[ sortBy ]
	);

	/**
	 * Get the sort direction for a specific column
	 *
	 * @param {string} columnKey - Column key to check
	 * @return {string|null} 'asc', 'desc', or null if not sorted
	 */
	const getSortDirection = useCallback(
		( columnKey ) => {
			const apiField = columnToApiFieldMap[ columnKey ];
			if ( sortBy === apiField ) {
				return order.toLowerCase();
			}
			return null;
		},
		[ sortBy, order ]
	);

	return {
		sortBy,
		order,
		handleSort,
		getSortDirection,
	};
};
