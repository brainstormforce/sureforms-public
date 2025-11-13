import { __, sprintf } from '@wordpress/i18n';
import { Label, Button, Loader, Alert } from '@bsf/force-ui';
import { TriangleAlert } from 'lucide-react';
import { addQueryParam } from '@Utils/Helpers';

/**
 * Get currencies data from localized PHP data
 * Falls back to a minimal set if localized data is unavailable
 *
 * Checks multiple sources in order:
 * 1. window.srfm_payment_admin.currenciesData (payment admin specific)
 * 2. window.srfm_admin.payments.currencies_data (global admin settings)
 *
 * @return {Array} Array of currency objects with value, label, and symbol
 */
const getCurrenciesFromLocalizedData = () => {
	const currenciesData =
		window.srfm_payment_admin?.currenciesData ||
		window.srfm_admin?.payments?.currencies_data ||
		{};

	// Convert PHP currency data to format expected by frontend
	const currencies = Object.entries( currenciesData ).map(
		( [ code, data ] ) => ( {
			value: code,
			label: `${ code } - ${ data.name }`,
			symbol: data.symbol,
			decimalPlaces: data.decimal_places,
		} )
	);

	// Fallback if no data available
	if ( currencies.length === 0 ) {
		return [
			{
				value: 'USD',
				label: __( 'USD - US Dollar', 'sureforms' ),
				symbol: '$',
				decimalPlaces: 2,
			},
		];
	}

	return currencies;
};

/**
 * Export currencies array - uses centralized data from PHP
 * This is the single source of truth on the frontend
 */
export const currencies = getCurrenciesFromLocalizedData();

const AlertWrapper = ( { children } ) => {
	return (
		<div className="flex flex-col gap-0.5 bg-[#F9FAFB] p-3 rounded-[8px]">
			{ children }
		</div>
	);
};

export const AlertForFee = () => {
	const isProActive = srfm_admin?.is_pro_active === '1';
	const isLicenseActive = srfm_admin?.is_license_active === '1';
	const proPluginName = srfm_admin?.pro_plugin_name || '';

	// Scenario 4: Pro/Business with active license - No alert shown
	if (
		isProActive &&
		isLicenseActive &&
		proPluginName !== 'SureForms Starter'
	) {
		return null;
	}

	const pricingPageUrl = addQueryParam(
		srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page,
		'payments_settings'
	);

	// Scenario 1: Starter Plan with Active License - 1% Fee
	if (
		isProActive &&
		isLicenseActive &&
		proPluginName === 'SureForms Starter'
	) {
		return (
			<AlertWrapper>
				<Label className="text-[14px] leading-[20px] font-semibold">
					{ __( 'Starter Plan Active', 'sureforms' ) }
				</Label>
				<p className="text-sm leading-[20px] font-normal">
					{ __(
						"You're currently on the SureForms Starter plan. A 1% transaction fee plus standard Stripe fees applies to all payments.",
						'sureforms'
					) }
					<a
						href={ pricingPageUrl }
						target="_blank"
						rel="noopener noreferrer"
						className="ml-1 mr-1 text-background-brand font-semibold no-underline"
					>
						{ __( 'Upgrade', 'sureforms' ) }
					</a>
					{ __(
						'to further reduce transaction fees and unlock advanced features.',
						'sureforms'
					) }
				</p>
			</AlertWrapper>
		);
	}

	// Scenario 3: Plugin Installed but License Not Activated
	if ( isProActive && ! isLicenseActive ) {
		return (
			<AlertWrapper>
				<Label className="text-[14px] leading-[20px] font-semibold">
					{ __( 'License Activation Required', 'sureforms' ) }
				</Label>
				<p className="text-sm font-normal mt-0.5">
					{ sprintf(
						/* translators: %s: Pro plugin name */
						__(
							"We've detected that the %s plugin is installed but the license isn't activated.",
							'sureforms'
						),
						proPluginName
					) }{ ' ' }
					{ __(
						'Please activate your license to reduce transaction fees.',
						'sureforms'
					) }{ ' ' }
					{ __(
						"You'll pay a 2.9% transaction fee plus standard Stripe fees until activation.",
						'sureforms'
					) }
				</p>
			</AlertWrapper>
		);
	}

	// Scenario 2: Plugin Not Available - 2.9% Fee
	return (
		<AlertWrapper>
			<Label className="text-[14px] leading-[20px] font-semibold">
				{ __( 'Pay-as-you-go Pricing', 'sureforms' ) }
			</Label>
			<p className="text-sm font-normal mt-0.5">
				{ __(
					"You'll pay a 2.9% transaction fee plus standard Stripe fees.",
					'sureforms'
				) }
				<a
					href={ pricingPageUrl }
					target="_blank"
					rel="noopener noreferrer"
					className="ml-1 mr-1 text-background-brand font-semibold no-underline"
				>
					{ __( 'Upgrade', 'sureforms' ) }
				</a>
				{ __(
					'to skip these additional fees and access premium payment features.',
					'sureforms'
				) }
			</p>
		</AlertWrapper>
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
 * Get list of zero-decimal currencies from localized data
 * These currencies are submitted to Stripe without multiplying by 100
 *
 * Checks multiple sources in order:
 * 1. window.srfm_payment_admin.zeroDecimalCurrencies (payment admin specific)
 * 2. window.srfm_admin.payments.zero_decimal_currencies (global admin settings)
 *
 * @return {Array<string>} Array of zero-decimal currency codes
 */
export const getZeroDecimalCurrencies = () => {
	// Use localized data from PHP (via wp_localize_script or global settings)
	return (
		window.srfm_payment_admin?.zeroDecimalCurrencies ||
		window.srfm_admin?.payments?.zero_decimal_currencies ||
		[]
	);
};

/**
 * Check if a currency is zero-decimal
 * @param {string} currency - Currency code
 * @return {boolean} True if zero-decimal currency
 */
export const isZeroDecimalCurrency = ( currency ) => {
	const zeroDecimalCurrencies = getZeroDecimalCurrencies();
	return zeroDecimalCurrencies.includes( currency?.toUpperCase() );
};

/**
 * Convert amount to Stripe's smallest currency unit
 * For two-decimal currencies (USD, EUR): multiplies by 100
 * For zero-decimal currencies (JPY, KRW): returns as-is
 * @param {number} amount   - Amount in major currency unit
 * @param {string} currency - Currency code
 * @return {number} Amount in smallest currency unit (cents for 2-decimal, whole for 0-decimal)
 */
export const amountToStripeFormat = ( amount, currency ) => {
	return isZeroDecimalCurrency( currency )
		? Math.round( amount )
		: Math.round( amount * 100 );
};

/**
 * Convert amount from Stripe's smallest currency unit to major unit
 * For two-decimal currencies (USD, EUR): divides by 100
 * For zero-decimal currencies (JPY, KRW): returns as-is
 * @param {number} amount   - Amount in smallest currency unit
 * @param {string} currency - Currency code
 * @return {number} Amount in major currency unit
 */
export const amountFromStripeFormat = ( amount, currency ) => {
	return isZeroDecimalCurrency( currency )
		? parseFloat( amount )
		: parseFloat( amount ) / 100;
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
 * Format datetime to "Mon DD / H:MM AM" (e.g., "Oct 23 / 2:23 PM")
 * @param {string|Date} datetime - Date string or Date object
 * @param {boolean}     year     - Whether to include the year in the output
 * @return {string} Formatted datetime string or 'N/A' if invalid
 */
export const formatDateTime = ( datetime, year = false ) => {
	if ( ! datetime ) {
		return 'N/A';
	}
	const date = new Date( datetime );

	// Check if date is valid
	if ( isNaN( date.getTime() ) ) {
		return 'N/A';
	}

	const month = date.toLocaleString( 'en-US', { month: 'short' } );
	const day = date.getDate();
	const time = date
		.toLocaleString( 'en-US', {
			hour: 'numeric',
			minute: '2-digit',
			hour12: true,
		} )
		.replace( /^0/, '' );

	return `${ month } ${ day }, ${
		year ? ` ${ date.getFullYear() },` : ''
	} ${ time }`;
};

/**
 * Format datetime to "Mon DD / H:MM AM" (e.g., "Oct 23 / 2:23 PM")
 * @param {string|Date} datetime - Date string or Date object
 * @return {string} Formatted datetime string or 'N/A' if invalid
 */
export const formatDateTime2 = ( datetime ) => {
	if ( ! datetime ) {
		return 'N/A';
	}
	const date = new Date( datetime );
	if ( isNaN( date.getTime() ) ) {
		return 'N/A';
	}

	const day = date.getDate();
	const month = date
		.toLocaleString( 'en-US', { month: 'long' } )
		.toLowerCase();
	const year = date.getFullYear();

	// Helper to get ordinal suffix
	const getOrdinal = ( n ) => {
		const s = [ 'th', 'st', 'nd', 'rd' ],
			v = n % 100;
		return s[ ( v - 20 ) % 10 ] || s[ v ] || s[ 0 ];
	};

	return `${ day }${ getOrdinal( day ) } ${ month } ${ year }`;
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
		active: __( 'Active', 'sureforms' ),
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

/**
 * Format payment object to order ID with SF-# prefix
 * @param {Object} payment - Payment object with srfm_txn_id and id properties
 * @return {string} Formatted order ID (e.g., "SF-#ABC123")
 */
export const formatOrderId = ( payment ) => {
	const orderId = payment?.srfm_txn_id ? payment.srfm_txn_id : payment?.id;
	return `SF-#${ orderId }`;
};

/**
 * Display original and partial payment amounts with styling.
 *
 * Shows the original amount struck through, followed by the partial/refunded amount.
 *
 * @param {Object} props               - Component props
 * @param {number} props.amount        - Original amount
 * @param {number} props.partialAmount - Partial amount
 * @param {string} props.currency      - Currency code
 */
export const PartialAmount = ( { amount, partialAmount, currency } ) => {
	// Return the JSX element showing both amounts formatted.
	return (
		<span
			style={ {
				display: 'flex',
				gap: '8px',
			} }
		>
			<span
				style={ {
					textDecoration: 'line-through',
					color: '#6c757d',
				} }
			>
				{ formatAmount( amount, currency ) }
			</span>
			{ formatAmount( partialAmount, currency ) }
		</span>
	);
};

/**
 * Webhook auto connect manage component
 * Displays message with Stripe Dashboard button and Auto Create Webhook button
 *
 * @param {Object}   props                              - Component props
 * @param {Function} props.getStripeWebhookDashboardUrl - Function to get Stripe dashboard URL
 * @param {Function} props.handleWebhookCreation        - Function to handle webhook creation
 * @param {boolean}  props.isCreatingWebhook            - Whether webhook is being created
 * @param {boolean}  props.loading                      - Whether component is loading
 * @param {string}   props.stripeAccountId              - Stripe account ID
 * @return {JSX.Element} Webhook auto connect manage component
 */
export const WebhookAutoConnectManage = ( {
	getStripeWebhookDashboardUrl,
	handleWebhookCreation,
	isCreatingWebhook,
	loading,
	stripeAccountId,
} ) => {
	const translatedText = sprintf(
		/* translators: %1$s: Stripe dashboard button */
		__(
			'Webhooks keep SureForms in sync with Stripe by automatically updating payment and subscription data. Free up a webhook from %1$s then retry to auto-create the webhook.',
			'sureforms'
		),
		'%1$s'
	);
	const parts = translatedText.split( '%1$s' );
	const content = (
		<span className="flex flex-col gap-3.5">
			<span>
				{ parts[ 0 ] }
				<Button
					onClick={ () =>
						window.open(
							getStripeWebhookDashboardUrl(),
							'_blank',
							'noopener,noreferrer'
						)
					}
					variant="link"
					size="xs"
					disabled={ ! stripeAccountId }
					className="inline-flex text-text-primary font-bold underline p-0 [&>span]:p-0"
				>
					{ __( 'Stripe Dashboard', 'sureforms' ) }
				</Button>
				{ parts[ 1 ] }
			</span>
			<Button
				onClick={ handleWebhookCreation }
				disabled={ isCreatingWebhook || loading }
				icon={ isCreatingWebhook && <Loader /> }
				iconPosition="left"
				variant="link"
				size="xs"
				className="w-fit flex text-link-primary"
			>
				{ isCreatingWebhook
					? __( 'Creating…', 'sureforms' )
					: __( 'Auto Create Webhook', 'sureforms' ) }
			</Button>
		</span>
	);

	return (
		<Alert
			content={ content }
			icon={ <TriangleAlert className="!size-6" /> }
			title={ null }
			variant="error"
			className="shadow-none bg-alert-background-danger"
		/>
	);
};

export const WebhookConfigure = ( props ) => {
	const paymentMode = props?.mode || 'test';

	const isWebhookConnected =
		paymentMode === 'live'
			? window?.srfm_admin?.payments?.webhook_live_connected
			: window?.srfm_admin?.payments?.webhook_test_connected;
	const isStripeConnected = window?.srfm_admin?.payments?.stripe_connected;

	if ( ! isStripeConnected ) {
		return null;
	}

	if ( isWebhookConnected ) {
		return null;
	}

	const translatedText = sprintf(
		/* translators: %1$s: Stripe dashboard button */
		__(
			'Webhooks keep SureForms in sync with Stripe by automatically updating payment and subscription data. Please %1$s Webhook.',
			'sureforms'
		),
		'%1$s'
	);
	const parts = translatedText.split( '%1$s' );
	const content = (
		<span className="flex flex-col gap-3.5">
			<span>
				{ parts[ 0 ] }
				<Button
					onClick={ () =>
						window.open(
							window.srfm_admin.payments.stripe_connect_url,
							'_blank',
							'noopener,noreferrer'
						)
					}
					variant="link"
					size="xs"
					className="inline-flex text-link-primary p-0 [&>span]:p-0"
				>
					{ __( 'configure', 'sureforms' ) }
				</Button>
				{ parts[ 1 ] }
			</span>
		</span>
	);

	return (
		<Alert
			content={ content }
			icon={ <TriangleAlert className="!size-6" /> }
			title={ null }
			variant="error"
			className="shadow-none bg-alert-background-danger"
		/>
	);
};
