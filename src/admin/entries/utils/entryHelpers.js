/**
 * Get the badge variant based on entry status
 *
 * @param {string} status - The entry status
 * @return {string} The badge variant
 */
export const getStatusBadgeVariant = ( status ) => {
	switch ( status ) {
		case 'Unread':
			return 'yellow';
		case 'Read':
			return 'green';
		case 'Trash':
			return 'red';
		default:
			return 'neutral';
	}
};

/**
 * Generate pagination items with ellipsis
 *
 * @param  {number} currentPage - Current page number
 * @param  {number} totalPages  - Total number of pages
 * @return {Array}  Array of page numbers with ellipsis indicators
 */
export const generatePaginationItems = ( currentPage, totalPages ) => {
	const items = [];
	const maxVisiblePages = 7;

	if ( totalPages <= maxVisiblePages ) {
		// Show all pages if total is less than max visible
		for ( let i = 1; i <= totalPages; i++ ) {
			items.push( i );
		}
	} else {
		// Show first 3, ellipsis, and last 3
		const firstPages = [ 1, 2, 3 ];
		const lastPages = [ totalPages - 2, totalPages - 1, totalPages ];

		firstPages.forEach( ( page ) => items.push( page ) );
		items.push( 'ellipsis' );
		lastPages.forEach( ( page ) => items.push( page ) );
	}

	return items;
};
