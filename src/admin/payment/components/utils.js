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
	return (
		<Alert
			content={
				<p className="text-sm font-normal">
					You’ll pay a 2.9% transaction fee plus standard Stripe fees.{ ' ' }
					<a
						href="https://sureforms.com/pricing"
						target="_blank"
						rel="noopener noreferrer"
					>
						Upgrade
					</a>{ ' ' }
					to skip these fees and get access to premium features.
				</p>
			}
			title="Pay-as-you-go Pricing"
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
		const leftItemCount = 3 + 2 * siblingCount;
		const leftRange = Array.from(
			{ length: leftItemCount },
			( _, i ) => i + 1
		);
		return [ ...leftRange, 'ellipsis', totalPages ];
	}

	if ( shouldShowLeftDots && ! shouldShowRightDots ) {
		const rightItemCount = 3 + 2 * siblingCount;
		const rightRange = Array.from(
			{ length: rightItemCount },
			( _, i ) => totalPages - rightItemCount + i + 1
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
