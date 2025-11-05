/**
 * Entry helper functions
 * Utility functions for transforming and formatting entry data
 */
import { __ } from '@wordpress/i18n';

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
			return __( 'Unread', 'sureforms' );
		case 'read':
			return __( 'Read', 'sureforms' );
		case 'trash':
			return __( 'Trash', 'sureforms' );
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
		entryId: `${ __( 'Entry', 'sureforms' ) } #${ entry.ID }`,
		formId: parseInt( entry.form_id, 10 ),
		formName:
			formsMap[ entry.form_id ] ||
			`${ __( 'Form', 'sureforms' ) } #${ entry.form_id }`,
		status: entry.status,
		statusLabel: getStatusLabel( entry.status ),
		firstField: getFirstFieldValue( entry.form_data ),
		dateTime: formatDateTime( entry.created_at ),
		rawData: entry, // Keep original data for reference
	};
};

/**
 * Transform entry detail API response to component-friendly format
 *
 * @param {Object} entryDetail - Entry detail from API
 * @return {Object} Transformed entry detail
 */
export const transformEntryDetail = ( entryDetail ) => {
	if ( ! entryDetail ) {
		return null;
	}

	return {
		id: entryDetail.id,
		formId: entryDetail.form_id,
		formName: entryDetail.form_name,
		formPermalink: entryDetail.form_permalink,
		status: entryDetail.status,
		statusLabel: getStatusLabel( entryDetail.status ),
		createdAt: entryDetail.created_at,
		formattedDateTime: formatDateTime( entryDetail.created_at ),
		formData: entryDetail.form_data || [],
		formContent: entryDetail.form_content || [],
		submissionInfo: {
			userIp: entryDetail.submission_info?.user_ip || '-',
			browserName: entryDetail.submission_info?.browser_name || '-',
			deviceName: entryDetail.submission_info?.device_name || '-',
		},
		user: entryDetail.user
			? {
				id: entryDetail.user.id,
				displayName: entryDetail.user.display_name,
				profileUrl: entryDetail.user.profile_url,
			  }
			: null,
		pdfLinks: entryDetail.extras?.pdf_links || null,
		rawData: entryDetail, // Keep original data for reference
	};
};

/**
 * Generates a range of page numbers and ellipses for pagination.
 *
 * @param {number} currentPage  - The current active page.
 * @param {number} totalPages   - The total number of pages.
 * @param {number} siblingCount - Number of pages to show on each side of the current page.
 * @return {Array} An array containing page numbers and 'ellipsis' strings.
 */
export const getPaginationRange = (
	currentPage,
	totalPages,
	siblingCount = 1
) => {
	// Calculate common values
	const siblingFactor = siblingCount * 2; // Sibling count multiplied by 2
	const totalPageNumbers = siblingFactor + 5; // Total numbers including ellipses and edges

	if ( totalPageNumbers >= totalPages ) {
		// If all pages can fit within the range
		return Array.from( { length: totalPages }, ( _, i ) => i + 1 );
	}

	// Calculate indices
	const leftSiblingIndex = Math.max( currentPage - siblingCount, 1 ); // Left sibling index
	const rightSiblingIndex = Math.min(
		currentPage + siblingCount,
		totalPages
	);

	const showLeftEllipsis = leftSiblingIndex > 2;
	const showRightEllipsis = rightSiblingIndex < totalPages - 1;

	// Constants for the first and last pages
	const firstPage = 1;
	const lastPage = totalPages;

	const pages = [];

	if ( ! showLeftEllipsis && showRightEllipsis ) {
		// Calculate range for the left side
		const leftItemCount = 3 + siblingFactor; // Number of items on the left
		const leftRange = Array.from(
			{ length: leftItemCount },
			( _, i ) => i + 1
		);
		pages.push( ...leftRange, 'ellipsis', lastPage );
	} else if ( showLeftEllipsis && ! showRightEllipsis ) {
		// Calculate range for the right side
		const rightItemCount = 3 + siblingFactor; // Number of items on the right
		const rightRange = Array.from(
			{ length: rightItemCount },
			( _, i ) => totalPages - rightItemCount + i + 1
		);
		pages.push( firstPage, 'ellipsis', ...rightRange );
	} else if ( showLeftEllipsis && showRightEllipsis ) {
		// Calculate middle range
		const middleRange = Array.from(
			{ length: siblingFactor + 1 },
			( _, i ) => currentPage - siblingCount + i
		);
		pages.push(
			firstPage,
			'ellipsis',
			...middleRange,
			'ellipsis',
			lastPage
		);
	}

	return pages;
};

/**
 * Decode HTML entities (supports double-encoding).
 *
 * @param {string} html - The HTML string to decode.
 * @return {string} Decoded string.
 */
export const decodeHTMLEntities = ( html ) => {
	if ( ! html ) {
		return '';
	}
	const textarea = document.createElement( 'textarea' );
	textarea.innerHTML = html;
	let decoded = textarea.value;

	// Check if still encoded and decode again if needed
	if ( decoded.includes( '&lt;' ) || decoded.includes( '&gt;' ) || decoded.includes( '&amp;' ) ) {
		textarea.innerHTML = decoded;
		decoded = textarea.value;
	}

	return decoded;
};
