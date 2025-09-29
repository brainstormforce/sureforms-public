import { __ } from '@wordpress/i18n';
import { Alert } from '@bsf/force-ui';

export const currencies = [
	{ value: 'USD', label: __( 'USD - US Dollar', 'sureforms' ) },
	{ value: 'EUR', label: __( 'EUR - Euro', 'sureforms' ) },
	{ value: 'GBP', label: __( 'GBP - British Pound', 'sureforms' ) },
	{ value: 'JPY', label: __( 'JPY - Japanese Yen', 'sureforms' ) },
	{ value: 'AUD', label: __( 'AUD - Australian Dollar', 'sureforms' ) },
	{ value: 'CAD', label: __( 'CAD - Canadian Dollar', 'sureforms' ) },
	{ value: 'CHF', label: __( 'CHF - Swiss Franc', 'sureforms' ) },
	{ value: 'CNY', label: __( 'CNY - Chinese Yuan', 'sureforms' ) },
	{ value: 'SEK', label: __( 'SEK - Swedish Krona', 'sureforms' ) },
	{ value: 'NZD', label: __( 'NZD - New Zealand Dollar', 'sureforms' ) },
	{ value: 'MXN', label: __( 'MXN - Mexican Peso', 'sureforms' ) },
	{ value: 'SGD', label: __( 'SGD - Singapore Dollar', 'sureforms' ) },
	{ value: 'HKD', label: __( 'HKD - Hong Kong Dollar', 'sureforms' ) },
	{ value: 'NOK', label: __( 'NOK - Norwegian Krone', 'sureforms' ) },
	{ value: 'KRW', label: __( 'KRW - South Korean Won', 'sureforms' ) },
	{ value: 'TRY', label: __( 'TRY - Turkish Lira', 'sureforms' ) },
	{ value: 'RUB', label: __( 'RUB - Russian Ruble', 'sureforms' ) },
	{ value: 'INR', label: __( 'INR - Indian Rupee', 'sureforms' ) },
	{ value: 'BRL', label: __( 'BRL - Brazilian Real', 'sureforms' ) },
	{ value: 'ZAR', label: __( 'ZAR - South African Rand', 'sureforms' ) },
	{ value: 'AED', label: __( 'AED - UAE Dirham', 'sureforms' ) },
	{ value: 'PHP', label: __( 'PHP - Philippine Peso', 'sureforms' ) },
	{ value: 'IDR', label: __( 'IDR - Indonesian Rupiah', 'sureforms' ) },
	{ value: 'MYR', label: __( 'MYR - Malaysian Ringgit', 'sureforms' ) },
	{ value: 'THB', label: __( 'THB - Thai Baht', 'sureforms' ) },
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
