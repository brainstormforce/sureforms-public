import { __ } from '@wordpress/i18n';
import { Alert } from '@bsf/force-ui';

export const currencies = [
	{ value: 'USD', label: __( 'USD - US Dollar', 'sureforms' ), symbol: '$' },
	{ value: 'EUR', label: __( 'EUR - Euro', 'sureforms' ), symbol: '€' },
	{
		value: 'GBP',
		label: __( 'GBP - British Pound', 'sureforms' ),
		symbol: '£',
	},
	{
		value: 'JPY',
		label: __( 'JPY - Japanese Yen', 'sureforms' ),
		symbol: '¥',
	},
	{
		value: 'AUD',
		label: __( 'AUD - Australian Dollar', 'sureforms' ),
		symbol: 'A$',
	},
	{
		value: 'CAD',
		label: __( 'CAD - Canadian Dollar', 'sureforms' ),
		symbol: 'C$',
	},
	{
		value: 'CHF',
		label: __( 'CHF - Swiss Franc', 'sureforms' ),
		symbol: 'CHF',
	},
	{
		value: 'CNY',
		label: __( 'CNY - Chinese Yuan', 'sureforms' ),
		symbol: '¥',
	},
	{
		value: 'SEK',
		label: __( 'SEK - Swedish Krona', 'sureforms' ),
		symbol: 'kr',
	},
	{
		value: 'NZD',
		label: __( 'NZD - New Zealand Dollar', 'sureforms' ),
		symbol: 'NZ$',
	},
	{
		value: 'MXN',
		label: __( 'MXN - Mexican Peso', 'sureforms' ),
		symbol: 'MX$',
	},
	{
		value: 'SGD',
		label: __( 'SGD - Singapore Dollar', 'sureforms' ),
		symbol: 'S$',
	},
	{
		value: 'HKD',
		label: __( 'HKD - Hong Kong Dollar', 'sureforms' ),
		symbol: 'HK$',
	},
	{
		value: 'NOK',
		label: __( 'NOK - Norwegian Krone', 'sureforms' ),
		symbol: 'kr',
	},
	{
		value: 'KRW',
		label: __( 'KRW - South Korean Won', 'sureforms' ),
		symbol: '₩',
	},
	{
		value: 'TRY',
		label: __( 'TRY - Turkish Lira', 'sureforms' ),
		symbol: '₺',
	},
	{
		value: 'RUB',
		label: __( 'RUB - Russian Ruble', 'sureforms' ),
		symbol: '₽',
	},
	{
		value: 'INR',
		label: __( 'INR - Indian Rupee', 'sureforms' ),
		symbol: '₹',
	},
	{
		value: 'BRL',
		label: __( 'BRL - Brazilian Real', 'sureforms' ),
		symbol: 'R$',
	},
	{
		value: 'ZAR',
		label: __( 'ZAR - South African Rand', 'sureforms' ),
		symbol: 'R',
	},
	{
		value: 'AED',
		label: __( 'AED - UAE Dirham', 'sureforms' ),
		symbol: 'د.إ',
	},
	{
		value: 'PHP',
		label: __( 'PHP - Philippine Peso', 'sureforms' ),
		symbol: '₱',
	},
	{
		value: 'IDR',
		label: __( 'IDR - Indonesian Rupiah', 'sureforms' ),
		symbol: 'Rp',
	},
	{
		value: 'MYR',
		label: __( 'MYR - Malaysian Ringgit', 'sureforms' ),
		symbol: 'RM',
	},
	{ value: 'THB', label: __( 'THB - Thai Baht', 'sureforms' ), symbol: '฿' },
];

export const AlertForFee = () => {
	// Don't show the alert if pro plugin is activated
	if ( srfm_admin?.is_pro_active ) {
		return null;
	}

	return (
		<Alert
			content={
				<p className="text-sm font-normal">
					{ __(
						'You\'ll pay a 2.9% transaction fee plus standard Stripe fees.',
						'sureforms'
					) }{ ' ' }
					<a
						href="https://sureforms.com/pricing"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Upgrade', 'sureforms' ) }
					</a>{ ' ' }
					{ __(
						'to skip these fees and get access to premium features.',
						'sureforms'
					) }
				</p>
			}
			title={ __( 'Pay-as-you-go Pricing', 'sureforms' ) }
			variant="info"
		/>
	);
};

// Pagination range helper function
export const getPaginationRange = (
	currentPage,
	totalPages,
	siblingCount = 1
) => {
	const totalPageNumbers = siblingCount + 5; // siblingCount + firstPage + lastPage + currentPage + 2*DOTS

	if ( totalPageNumbers >= totalPages ) {
		return Array.from( { length: totalPages }, ( _, i ) => i + 1 );
	}

	const leftSiblingIndex = Math.max( currentPage - siblingCount, 1 );
	const rightSiblingIndex = Math.min(
		currentPage + siblingCount,
		totalPages
	);

	const shouldShowLeftDots = leftSiblingIndex > 2;
	const shouldShowRightDots = rightSiblingIndex < totalPages - 2;

	const firstPageIndex = 1;
	const lastPageIndex = totalPages;

	if ( ! shouldShowLeftDots && shouldShowRightDots ) {
		const leftSiblingCountTotal = 2 * siblingCount;
		const leftItemCount = 3 + leftSiblingCountTotal;
		const leftRange = Array.from(
			{ length: leftItemCount },
			( _, i ) => i + 1
		);
		return [ ...leftRange, 'ellipsis', totalPages ];
	}

	if ( shouldShowLeftDots && ! shouldShowRightDots ) {
		const rightSiblingCountTotal = 2 * siblingCount;
		const rightItemCount = 3 + rightSiblingCountTotal;
		const rightRange = Array.from(
			{ length: rightItemCount },
			( _, i ) => totalPages - rightItemCount + ( i + 1 )
		);
		return [ firstPageIndex, 'ellipsis', ...rightRange ];
	}

	if ( shouldShowLeftDots && shouldShowRightDots ) {
		const middleRange = Array.from(
			{ length: rightSiblingIndex - leftSiblingIndex + 1 },
			( _, i ) => leftSiblingIndex + i
		);
		return [
			firstPageIndex,
			'ellipsis',
			...middleRange,
			'ellipsis',
			lastPageIndex,
		];
	}

	return [];
};

// Get status badge variant based on status
export const getStatusVariant = ( status ) => {
	switch ( status ) {
		case 'succeeded':
			return 'green';
		case 'pending':
		case 'partially_refunded':
			return 'yellow';
		case 'failed':
			return 'red';
		case 'refunded':
			return 'blue';
		case 'cancelled':
			return 'gray';
		default:
			return 'gray';
	}
};

/**
 * Get currency symbol from currency code
 * @param {string} currencyCode - Currency code (e.g., 'USD', 'EUR')
 * @return {string} Currency symbol
 */
export const getCurrencySymbol = ( currencyCode ) => {
	const upperCurrencyCode = currencyCode?.toUpperCase();
	const currency = currencies.find( ( c ) => c.value === upperCurrencyCode );
	return currency ? currency.symbol : currencyCode;
};

/**
 * Format amount with currency symbol
 * @param {number} amount   - Amount to format
 * @param {string} currency - Currency code (default: 'USD')
 * @return {string} Formatted amount with currency symbol
 */
export const formatAmount = ( amount, currency = 'USD' ) => {
	const symbol = getCurrencySymbol( currency );
	return `${ symbol }${ parseFloat( amount ).toFixed( 2 ) }`;
};

/**
 * Format datetime to readable format with validation
 * @param {string|Date} datetime - Date string or Date object
 * @return {string} Formatted datetime string or 'N/A' if invalid
 */
export const formatDateTime = ( datetime ) => {
	if ( ! datetime ) {
		return 'N/A';
	}
	const date = new Date( datetime );

	// Check if date is valid
	if ( isNaN( date.getTime() ) ) {
		return 'N/A';
	}

	return date.toLocaleString( 'en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} );
};

/**
 * Format datetime with separate date and time (used in viewSubscription)
 * @param {string|Date} dateString - Date string or Date object
 * @return {string} Formatted as "Month DD, YYYY at HH:MM AM/PM"
 */
export const formatDateTimeDetailed = ( dateString ) => {
	if ( ! dateString ) {
		return 'N/A';
	}
	const date = new Date( dateString );

	if ( isNaN( date.getTime() ) ) {
		return 'N/A';
	}

	const formattedDate = date.toLocaleDateString( 'en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	} );
	const formattedTime = date.toLocaleTimeString( 'en-US', {
		hour: '2-digit',
		minute: '2-digit',
		hour12: true,
	} );
	return `${ formattedDate } at ${ formattedTime }`;
};

/**
 * Format Unix timestamp to readable date (used for payment logs)
 * @param {number} timestamp - Unix timestamp in seconds
 * @return {string} Formatted datetime or error message
 */
export const formatLogTimestamp = ( timestamp ) => {
	// Validate timestamp
	if ( ! timestamp || isNaN( timestamp ) || timestamp <= 0 ) {
		return __( 'N/A', 'sureforms' );
	}

	const date = new Date( timestamp * 1000 );

	// Check if date is valid
	if ( isNaN( date.getTime() ) ) {
		return __( 'Invalid Date', 'sureforms' );
	}

	return date.toLocaleString( 'en-US', {
		year: 'numeric',
		month: '2-digit',
		day: '2-digit',
		hour: '2-digit',
		minute: '2-digit',
		second: '2-digit',
		hour12: false,
	} );
};

/**
 * Get human-readable label for payment status
 * @param {string} status - Payment status code
 * @return {string} Translated status label
 */
export const getStatusLabel = ( status ) => {
	const statusMap = {
		succeeded: __( 'Paid', 'sureforms' ),
		partially_refunded: __( 'Partially Refunded', 'sureforms' ),
		pending: __( 'Pending', 'sureforms' ),
		failed: __( 'Failed', 'sureforms' ),
		refunded: __( 'Refunded', 'sureforms' ),
		cancelled: __( 'Cancelled', 'sureforms' ),
	};
	return statusMap[ status ] || status;
};

/**
 * Format date range object to display string
 * @param {Object} dates - Object with 'from' and 'to' Date properties
 * @return {string} Formatted date range string or empty string
 */
export const getSelectedDateRange = ( dates ) => {
	if ( dates.from && dates.to ) {
		return `${ dates.from.toLocaleDateString() } - ${ dates.to.toLocaleDateString() }`;
	}
	return '';
};
