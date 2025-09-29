/**
 * PaymentContext and PaymentDataProvider for managing payment data.
 * This module provides a React context and provider for managing payment data
 * in the SureForms plugin admin interface.
 *
 * @module PaymentContext
 */

import { useState, createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * React context for payment data management.
 * Provides access to payment data and operations throughout the application.
 */
export const PaymentContext = createContext( null );

/**
 * Provider component for payment data context.
 * Manages the state and operations for payments in the admin interface.
 *
 * @param {Object}      props          - Component props
 * @param {JSX.Element} props.children - Child components to be wrapped by the provider
 * @return {JSX.Element} Provider component with context value
 */
export const PaymentDataProvider = ( { children } ) => {
	const [ viewSinglePayment, setViewSinglePayment ] = useState( false );
	const [ singlePaymentType, setSinglePaymentType ] = useState( false );

	const contextValue = {
		viewSinglePayment,
		setViewSinglePayment,
		singlePaymentType,
		setSinglePaymentType,
	};

	return (
		<PaymentContext.Provider value={ contextValue }>
			{ children }
		</PaymentContext.Provider>
	);
};
