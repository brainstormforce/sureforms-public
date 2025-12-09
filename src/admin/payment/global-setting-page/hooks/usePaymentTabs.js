import { useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Custom hook for managing payment gateway tabs with URL synchronization
 * Handles tab state, URL routing, and browser history management
 */
const usePaymentTabs = () => {
	const [activeTab, setActiveTab] = useState('stripe');

	// Base payment gateway tabs - Free plan only includes Stripe
	const baseAvailableTabs = [
		{
			id: 'stripe',
			label: 'Stripe',
			path: 'stripe'
		}
	];

	/**
	 * Filter: srfm.payment.gateway.tabs
	 *
	 * Allows adding payment gateways to the available tabs.
	 * Business plan uses this filter to add PayPal support.
	 *
	 * @param {Array} tabs - Array of available payment gateway tabs
	 *
	 * Example usage in Business plan to add PayPal:
	 * addFilter('srfm.payment.gateway.tabs', 'sureforms-business', (tabs) => {
	 *   return [
	 *     ...tabs,
	 *     {
	 *       id: 'paypal',
	 *       label: 'PayPal',
	 *       path: 'paypal'
	 *     }
	 *   ];
	 * });
	 */
	const availableTabs = applyFilters('srfm.payment.gateway.tabs', baseAvailableTabs);

	// Get tab from URL parameters
	const getTabFromUrl = () => {
		const urlParams = new URLSearchParams(window.location.search);
		const gatewayParam = urlParams.get('gateway');

		// Validate that the gateway parameter is a valid tab
		const validTab = availableTabs.find(tab => tab.id === gatewayParam);
		return validTab ? gatewayParam : 'stripe'; // Default to stripe
	};

	// Update URL without page refresh
	const updateUrl = (tabId) => {
		const urlParams = new URLSearchParams(window.location.search);
		urlParams.set('gateway', tabId);

		const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
		window.history.replaceState({}, '', newUrl);
	};

	// Initialize tab from URL on mount
	useEffect(() => {
		const urlTab = getTabFromUrl();
		setActiveTab(urlTab);
	}, []);

	// Handle tab change
	const changeTab = (tabId) => {
		// Validate tab ID
		const validTab = availableTabs.find(tab => tab.id === tabId);
		if (!validTab) {
			console.warn(`Invalid tab ID: ${tabId}`);
			return;
		}

		setActiveTab(tabId);
		updateUrl(tabId);
	};

	// Handle browser back/forward navigation
	useEffect(() => {
		const handlePopState = () => {
			const urlTab = getTabFromUrl();
			setActiveTab(urlTab);
		};

		window.addEventListener('popstate', handlePopState);
		return () => window.removeEventListener('popstate', handlePopState);
	}, []);

	// Get current tab object
	const getCurrentTab = () => {
		return availableTabs.find(tab => tab.id === activeTab) || availableTabs[0];
	};

	return {
		activeTab,
		availableTabs,
		changeTab,
		getCurrentTab,
		isTabActive: (tabId) => activeTab === tabId
	};
};

export default usePaymentTabs;
