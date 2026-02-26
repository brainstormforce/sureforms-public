import { useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Custom hook for managing payment gateway tabs with URL synchronization
 * Handles tab state, URL routing, and browser history management
 * @param {Function} navigate - React Router's navigate function for programmatic navigation
 */
const usePaymentTabs = ( navigate ) => {
	const [ activeTab, setActiveTab ] = useState( 'stripe' );

	// Base payment gateway tabs - Free plan only includes Stripe
	const baseAvailableTabs = [
		{
			id: 'stripe',
			label: 'Stripe',
			path: 'stripe',
		},
	];

	/**
	 * Filter: srfm.payment.gateway.tabs
	 *
	 * Allows adding payment gateways to the available tabs.
	 * Business plan uses this filter to add PayPal support.
	 *
	 * @param {Array} tabs - Array of available payment gateway tabs
	 *
	 *                     Example usage in Business plan to add PayPal:
	 *                     addFilter('srfm.payment.gateway.tabs', 'sureforms-business', (tabs) => {
	 *                     return [
	 *                     ...tabs,
	 *                     {
	 *                     id: 'paypal',
	 *                     label: 'PayPal',
	 *                     path: 'paypal'
	 *                     }
	 *                     ];
	 *                     });
	 */
	const availableTabs = applyFilters(
		'srfm.payment.gateway.tabs',
		baseAvailableTabs
	);

	// Get tab from URL parameters
	const getTabFromUrl = () => {
		const urlParams = new URLSearchParams( window.location.search );
		const gatewayParam = urlParams.get( 'gateway' );

		// Validate that the gateway parameter is a valid tab
		const validTab = availableTabs.find(
			( tab ) => tab.id === gatewayParam
		);
		return validTab ? gatewayParam : 'stripe'; // Default to stripe
	};

	// Update URL without page refresh
	const updateUrl = ( tabId ) => {
		const urlParams = new URLSearchParams( window.location.search );
		urlParams.set( 'gateway', tabId );
		const newUrl = `${
			window.location.pathname
		}?${ urlParams.toString() }`;

		if ( navigate ) {
			// Use React Router navigation with replace to avoid adding to history
			navigate( newUrl, { replace: true } );
		} else {
			// Fallback to manual history update if navigate is not provided
			window.history.replaceState( {}, '', newUrl );
		}
	};

	// Initialize tab from URL on mount
	useEffect( () => {
		const urlTab = getTabFromUrl();
		setActiveTab( urlTab );
	}, [] );

	// Handle tab change
	const changeTab = ( tabId ) => {
		// Validate tab ID
		const validTab = availableTabs.find( ( tab ) => tab.id === tabId );
		if ( ! validTab ) {
			console.warn( `Invalid tab ID: ${ tabId }` );
			return;
		}

		setActiveTab( tabId );
		updateUrl( tabId );
	};

	// Handle browser back/forward navigation
	useEffect( () => {
		const handlePopState = () => {
			const urlTab = getTabFromUrl();
			setActiveTab( urlTab );
		};

		window.addEventListener( 'popstate', handlePopState );
		return () => window.removeEventListener( 'popstate', handlePopState );
	}, [] );

	// Handle navigation to general settings from payment gateway settings
	useEffect( () => {
		const handleNavigateToSettings = () => {
			const urlParams = new URLSearchParams( window.location.search );

			// Update URL parameters to navigate to general settings
			urlParams.set( 'tab', 'payments-settings' );
			urlParams.set( 'subpage', 'general' );

			// Remove gateway parameter if present
			urlParams.delete( 'gateway' );

			const newUrl = `${
				window.location.pathname
			}?${ urlParams.toString() }`;

			if ( navigate ) {
				// Use React Router navigation to update the URL
				navigate( newUrl );
			} else {
				// Fallback to manual history update if navigate is not provided
				window.history.pushState( {}, '', newUrl );
			}

			// Trigger a custom event to notify the parent component of the navigation
			const navigationEvent = new CustomEvent(
				'srfm_settings_navigation_changed',
				{
					bubbles: true,
					detail: {
						tab: 'payments-settings',
						subpage: 'general',
					},
				}
			);
			window.dispatchEvent( navigationEvent );
		};

		window.addEventListener(
			'srfm_navigate_to_payment_settings',
			handleNavigateToSettings
		);
		return () =>
			window.removeEventListener(
				'srfm_navigate_to_payment_settings',
				handleNavigateToSettings
			);
	}, [ navigate ] );

	// Get current tab object
	const getCurrentTab = () => {
		return (
			availableTabs.find( ( tab ) => tab.id === activeTab ) ||
			availableTabs[ 0 ]
		);
	};

	return {
		activeTab,
		availableTabs,
		changeTab,
		getCurrentTab,
		isTabActive: ( tabId ) => activeTab === tabId,
	};
};

export default usePaymentTabs;
