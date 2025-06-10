import { useLocation, useNavigate } from 'react-router-dom';
import ONBOARDING_ROUTES_CONFIG from './onboarding-routes-config';
import { useCallback, useEffect, useMemo, useRef } from '@wordpress/element';
import { useOnboardingState } from './onboarding-state';

/**
 * This hook will return functions that will handle the navigation of the onboarding process.
 * It will be used to handle the back and continue buttons.
 */
export const useOnboardingNavigation = () => {
	const navigate = useNavigate();
	const location = useLocation();
	const currentRoute = location.pathname;
	const [ onboardingState ] = useOnboardingState();

	const getNextRoute = ( currentPath ) => {
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentPath
		);

		return ONBOARDING_ROUTES_CONFIG[ currentIndex + 1 ].url;
	};

	const getPreviousRoute = ( currentPath ) => {
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentPath
		);

		return ONBOARDING_ROUTES_CONFIG[ currentIndex - 1 ].url;
	};

	const navigateToNextRoute = () => {
		const nextRoute = getNextRoute( currentRoute );
		navigate( nextRoute );
	};

	const navigateToPreviousRoute = () => {
		const previousRoute = getPreviousRoute( currentRoute );
		navigate( previousRoute );
	};

	const getCurrentStepNumber = () => {
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentRoute
		);

		return currentIndex + 1;
	};

	/**
	 * Checks if the current step requires previous steps to be completed.
	 * Returns a redirect URL if requirements aren't met, or empty string if allowed.
	 *
	 * @return {string} URL to redirect to if access is restricted, or empty string if allowed.
	 */
	const checkRequiredStep = useCallback( () => {
		// Get current route index
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentRoute
		);

		// If we're on the first step or route not found, no redirection needed
		if ( currentIndex <= 0 ) {
			return '';
		}

		// Check all previous steps for any unmet requirements
		for ( let i = 0; i <= currentIndex; i++ ) {
			const route = ONBOARDING_ROUTES_CONFIG[ i ];

			// Skip routes without requirements
			if (
				! route?.requires?.stateKeys ||
				route?.requires?.stateKeys?.length === 0
			) {
				continue;
			}

			// Check if any required state for this route is not met
			const missingRequirement = route.requires.stateKeys.find(
				( requirement ) => ! onboardingState[ requirement ]
			);

			// If we found a missing requirement, redirect to that step
			if ( missingRequirement ) {
				return route.requires.redirectUrl;
			}
		}

		// If we get here, all previous requirements are met
		return '';
	}, [ location.pathname, onboardingState ] );

	return {
		getNextRoute,
		getPreviousRoute,
		navigateToNextRoute,
		navigateToPreviousRoute,
		getCurrentStepNumber,
		checkRequiredStep,
	};
};

export const useFormValidation = ( form, formData, schema, onError ) => {
	const formRef = useRef( form );

	useEffect( () => {
		// If form is a ref, set the ref
		if ( typeof form === 'object' && 'current' in form ) {
			formRef.current = form.current;
		}

		// If form is a string, set the formRef to the form
		if ( typeof form === 'string' ) {
			formRef.current = document.querySelector( form );
		}

		// If form is a function, set the formRef to the form
		if ( typeof form === 'function' ) {
			formRef.current = form();
		}
	}, [ form ] );

	const onBlurValidation = useCallback(
		( event ) => {
			if ( ! event.target ) {
				return;
			}

			const field = event.target.name;
			if ( ! schema ) {
				return;
			}

			try {
				schema.pick( { [ field ]: true } ).parse( {
					[ field ]: formData[ field ],
				} );
				onError( { [ field ]: undefined } );
			} catch ( error ) {
				onError( { [ field ]: error.errors[ 0 ].message } );
			}
		},
		[ formData, onError, schema ]
	);

	const validateForm = useCallback( () => {
		if ( ! schema ) {
			return false;
		}

		try {
			schema.parse( formData );
			return true;
		} catch ( error ) {
			const formattedErrors = {};
			error.errors.forEach( ( err ) => {
				formattedErrors[ err.path[ 0 ] ] = err.message;
			} );

			// Pass the errors to the parent component
			onError( formattedErrors );

			// Focus the first input with error
			const firstErrorField = error.errors[ 0 ]?.path[ 0 ];
			const firstErrorInput = formRef.current?.querySelector(
				`input[name="${ firstErrorField }"]`
			);
			firstErrorInput?.focus();

			return false;
		}
	}, [ formData, onError, schema ] );

	return {
		onBlurValidation,
		validateForm,
	};
};

/**
 * This hook will calculate the title prefix and sequence number for a form.
 * It takes a single parameter selectedTemplate and returns titlePrefix and sequenceNumber.
 *
 * @param {Object} selectedTemplate - The selected form template object.
 * @return {Object} Object containing titlePrefix and sequenceNumber.
 */
export const useFormTitleAndSequence = ( selectedTemplate ) => {
	const sequenceNumber = useMemo( () => {
		// For forms, we can use a simple incrementing number
		return Date.now(); // Simple unique identifier
	}, [] );

	const titleSuffix = useMemo( () => {
		if ( ! selectedTemplate ) {
			return 'New Form';
		}

		return selectedTemplate.title || selectedTemplate.name || 'New Form';
	}, [ selectedTemplate ] );

	return {
		titleSuffix,
		sequenceNumber,
	};
};
