/**
 * PaymentContext and PaymentDataProvider for managing payment data.
 * This module provides a React context and provider for managing payment data
 * in the SureForms plugin admin interface.
 *
 * @module PaymentContext
 */

import { useState, createContext, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getUrlParam, updateUrlParams } from './urlUtils';

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
	// Initialize from URL on mount
	const urlPaymentId = getUrlParam( 'srfm_payment_id' );
	const urlPaymentType = getUrlParam( 'srfm_payment_type' );

	const [ viewSinglePayment, setViewSinglePayment ] = useState(
		urlPaymentId ? parseInt( urlPaymentId ) : false
	);
	const [ singlePaymentType, setSinglePaymentType ] = useState(
		urlPaymentType || false
	);

	// Sync URL when viewing payment changes
	useEffect( () => {
		if ( viewSinglePayment ) {
			updateUrlParams( {
				srfm_payment_id: viewSinglePayment,
				srfm_payment_type: singlePaymentType || 'one-time',
			} );
		} else {
			updateUrlParams( {
				srfm_payment_id: undefined,
				srfm_payment_type: undefined,
			} );
		}
	}, [ viewSinglePayment, singlePaymentType ] );

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
