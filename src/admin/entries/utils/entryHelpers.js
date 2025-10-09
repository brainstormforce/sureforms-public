/**
 * Get the badge variant based on entry status
 *
 * @param {string} status - The entry status
 * @return {string} The badge variant
 */
export const getStatusBadgeVariant = ( status ) => {
	const normalizedStatus = status?.toLowerCase();
	switch ( normalizedStatus ) {
		case 'unread':
			return 'yellow';
		case 'read':
			return 'green';
		case 'trash':
			return 'red';
		default:
			return 'neutral';
	}
};

/**
 * Get the display label for entry status
 *
 * @param {string} status - The entry status
 * @return {string} The display label
 */
export const getStatusLabel = ( status ) => {
	const normalizedStatus = status?.toLowerCase();
	switch ( normalizedStatus ) {
		case 'unread':
			return 'Unread';
		case 'read':
			return 'Read';
		case 'trash':
			return 'Trash';
		default:
			return status;
	}
};

/**
 * Get the first field value from form_data
 *
 * @param {Object} formData - The form_data object from API
 * @return {string} The first field value
 */
export const getFirstFieldValue = ( formData ) => {
	if ( ! formData || typeof formData !== 'object' ) {
		return '-';
	}

	const firstKey = Object.keys( formData )[ 0 ];
	if ( ! firstKey ) {
		return '-';
	}

	const value = formData[ firstKey ];
	return value || '-';
};

/**
 * Format date and time for display
 *
 * @param {string} dateString - The date string from API (e.g., "2025-10-07 15:54:49")
 * @return {string} Formatted date time
 */
export const formatDateTime = ( dateString ) => {
	if ( ! dateString ) {
		return '-';
	}

	try {
		const date = new Date( dateString.replace( ' ', 'T' ) );
		const options = {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		};
		return date.toLocaleString( 'en-US', options );
	} catch ( error ) {
		return dateString;
	}
};

/**
 * Transform API entry to component-friendly format
 *
 * @param {Object} entry    - Entry from API
 * @param {Object} formsMap - Map of form IDs to form titles
 * @return {Object} Transformed entry
 */
export const transformEntry = ( entry, formsMap = {} ) => {
	return {
		id: parseInt( entry.ID, 10 ),
		entryId: `Entry #${ entry.ID }`,
		formId: parseInt( entry.form_id, 10 ),
		formName: formsMap[ entry.form_id ] || `Form #${ entry.form_id }`,
		status: entry.status,
		statusLabel: getStatusLabel( entry.status ),
		firstField: getFirstFieldValue( entry.form_data ),
		dateTime: formatDateTime( entry.created_at ),
		rawData: entry, // Keep original data for reference
	};
};

/**
 * Generate pagination items with ellipsis
 *
 * @param {number} currentPage - Current page number
 * @param {number} totalPages  - Total number of pages
 * @return {Array} Array of page numbers with ellipsis indicators
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
