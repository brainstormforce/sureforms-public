import { useLocation, useNavigate } from 'react-router-dom';
import ONBOARDING_ROUTES_CONFIG from './onboarding-routes-config';
import { useCallback } from '@wordpress/element';
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

	// Check if user has business plan
	const hasBusinessPlan = () => {
		// Get current active pro version if available
		const currentProVersion = srfm_admin?.pro_plugin_name || '';
		const hasProVersion = currentProVersion.includes( 'SureForms' );

		if ( hasProVersion ) {
			// Extract plan name from "SureForms <plan name>"
			const planMatch = currentProVersion.match( /SureForms\s+(.*)/ );
			if ( planMatch && planMatch[ 1 ] ) {
				const plan = planMatch[ 1 ].trim().toLowerCase();
				return plan === 'business';
			}
		}

		return false;
	};

	// Check if user is already connected
	const isUserConnected = () => {
		return srfm_admin?.srfm_ai_details?.type !== 'non-registered';
	};

	const getNextRoute = ( currentPath ) => {
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentPath
		);

		// If current path is welcome and user is already connected, skip connect screen
		if ( currentPath === '/onboarding/welcome' && isUserConnected() ) {
			// Skip to email-delivery page (index + 2)
			return ONBOARDING_ROUTES_CONFIG[ currentIndex + 2 ].url;
		}

		// If current path is email-delivery and user has business plan, skip premium-features
		if (
			currentPath === '/onboarding/email-delivery' &&
			hasBusinessPlan()
		) {
			// Skip to done page (index + 2)
			return ONBOARDING_ROUTES_CONFIG[ currentIndex + 2 ].url;
		}

		return ONBOARDING_ROUTES_CONFIG[ currentIndex + 1 ].url;
	};

	const getPreviousRoute = ( currentPath ) => {
		const currentIndex = ONBOARDING_ROUTES_CONFIG.findIndex(
			( route ) => route.url === currentPath
		);

		// If current path is email-delivery and user is connected, skip back past connect screen
		if (
			currentPath === '/onboarding/email-delivery' &&
			isUserConnected()
		) {
			// Skip back to welcome page (index - 2)
			return ONBOARDING_ROUTES_CONFIG[ currentIndex - 2 ].url;
		}

		// If current path is done and user has business plan, skip back past premium-features
		if ( currentPath === '/onboarding/done' && hasBusinessPlan() ) {
			// Skip back to email-delivery (index - 2)
			return ONBOARDING_ROUTES_CONFIG[ currentIndex - 2 ].url;
		}

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
		let stepNumber = currentIndex + 1;

		// Adjust step number if connect screen is skipped for connected users
		if ( isUserConnected() && currentIndex > 1 ) {
			stepNumber -= 1; // Reduce by 1 since connect step is skipped
		}

		// Adjust step number for business users to account for skipped premium features page
		if ( hasBusinessPlan() && currentIndex > 2 ) {
			stepNumber -= 1; // Reduce by 1 since premium features step is skipped
		}

		return stepNumber;
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

		// If user is already connected and tries to access connect page, redirect to email-delivery
		if ( isUserConnected() && currentRoute === '/onboarding/connect' ) {
			return '/onboarding/email-delivery';
		}

		// If user has business plan and tries to access premium features page, redirect to done page
		if (
			hasBusinessPlan() &&
			currentRoute === '/onboarding/premium-features'
		) {
			return '/onboarding/done';
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
		hasBusinessPlan,
		isUserConnected,
	};
};
