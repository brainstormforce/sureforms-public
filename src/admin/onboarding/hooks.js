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

	// Check if user is already connected
	const isUserConnected = () => {
		return (
			srfm_admin?.srfm_ai_details?.type !== 'non-registered' ||
			onboardingState?.analytics?.accountConnected
		);
	};

	const getVisibleRoutes = () => {
		const userConnected = isUserConnected();

		return ONBOARDING_ROUTES_CONFIG.filter( ( route ) => {
			if ( route.url === '/onboarding/connect' && userConnected ) {
				return false;
			}

			// The add-ons step is an upsell, so only free installs see it.
			// `is_pro_active` is Helper::has_pro() (i.e. SRFM_PRO_VER defined)
			// and is the only trustworthy signal here: `pro_plugin_name` falls
			// back to the literal 'SureForms Pro' when Pro is absent
			// (admin/admin.php:1813), so testing it would hide the upsell from
			// every free user.
			if (
				route.url === '/onboarding/premium-features' &&
				srfm_admin?.is_pro_active
			) {
				return false;
			}

			if ( route.url === '/onboarding/user-details' && userConnected ) {
				return false;
			}

			// The cache-conflict step only makes sense when a recognised caching
			// plugin is active. `caching_plugin` is the display name of the
			// first active plugin in Helper::get_known_caching_plugins() and
			// is '' when there is none; the step's title and guide link both
			// take their plugin from it.
			if (
				route.url === '/onboarding/cache-conflict' &&
				! srfm_admin?.caching_plugin
			) {
				return false;
			}

			// Hide the migration step only AFTER the source probe has
			// resolved AND no source has anything left to import. We use
			// `pending_count` (form_count - imported_count) rather than
			// `form_count` so a site whose forms have all been migrated on
			// a previous run doesn't see a re-import prompt — that scenario
			// is what the "All forms already imported" caption on the
			// Settings → Migration tab handles instead.
			//
			// While the probe is in-flight (`migrationDetectionLoaded === false`)
			// we keep the step visible so that linear navigation past
			// `premium-features` doesn't race past it into `cache-conflict`. The step
			// itself renders a short loader and a clean "nothing to
			// import" hint if the probe ultimately finds nothing.
			if ( route.url === '/onboarding/import-forms' ) {
				const detectionLoaded =
					onboardingState?.migrationDetectionLoaded;
				if ( detectionLoaded ) {
					const sources = onboardingState?.migrationSources || [];
					const hasPending = sources.some( ( s ) => {
						if ( ! s?.installed ) {
							return false;
						}
						const pending = Number(
							s?.pending_count ??
								Number( s?.form_count ?? 0 ) -
									Number( s?.imported_count ?? 0 )
						);
						return pending > 0;
					} );
					if ( ! hasPending ) {
						return false;
					}
				}
			}

			return true;
		} );
	};

	const getCurrentRouteIndex = ( routePath, routes ) => {
		return routes.findIndex( ( route ) => route.url === routePath );
	};

	const getAdjacentVisibleRoute = ( currentPath, direction ) => {
		const visibleRoutes = getVisibleRoutes();
		const allRouteIndex = getCurrentRouteIndex(
			currentPath,
			ONBOARDING_ROUTES_CONFIG
		);

		if ( allRouteIndex === -1 ) {
			return visibleRoutes[ 0 ]?.url || '/onboarding/welcome';
		}

		const step = direction === 'next' ? 1 : -1;
		for (
			let index = allRouteIndex + step;
			index >= 0 && index < ONBOARDING_ROUTES_CONFIG.length;
			index += step
		) {
			const candidateRoute = ONBOARDING_ROUTES_CONFIG[ index ]?.url;
			if (
				visibleRoutes.some( ( route ) => route.url === candidateRoute )
			) {
				return candidateRoute;
			}
		}

		const fallbackRoute =
			direction === 'next'
				? visibleRoutes[ visibleRoutes.length - 1 ]
				: visibleRoutes[ 0 ];

		return fallbackRoute?.url || '/onboarding/welcome';
	};

	const getNextRoute = ( currentPath ) => {
		const visibleRoutes = getVisibleRoutes();
		const currentIndex = getCurrentRouteIndex( currentPath, visibleRoutes );

		if ( currentIndex !== -1 ) {
			return (
				visibleRoutes[ currentIndex + 1 ]?.url ||
				visibleRoutes[ currentIndex ]?.url ||
				'/onboarding/welcome'
			);
		}

		return getAdjacentVisibleRoute( currentPath, 'next' );
	};

	const getPreviousRoute = ( currentPath ) => {
		const visibleRoutes = getVisibleRoutes();
		const currentIndex = getCurrentRouteIndex( currentPath, visibleRoutes );

		if ( currentIndex !== -1 ) {
			return (
				visibleRoutes[ currentIndex - 1 ]?.url ||
				visibleRoutes[ currentIndex ]?.url ||
				'/onboarding/welcome'
			);
		}

		return getAdjacentVisibleRoute( currentPath, 'previous' );
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
		const visibleRoutes = getVisibleRoutes();
		const currentIndex = getCurrentRouteIndex(
			currentRoute,
			visibleRoutes
		);

		if ( currentIndex === -1 ) {
			return 1;
		}

		const maxProgressSteps = Math.max( visibleRoutes.length - 1, 1 );
		return Math.min( currentIndex + 1, maxProgressSteps );
	};

	/**
	 * Checks if the current step requires previous steps to be completed.
	 * Returns a redirect URL if requirements aren't met, or empty string if allowed.
	 *
	 * @return {string} URL to redirect to if access is restricted, or empty string if allowed.
	 */
	const checkRequiredStep = useCallback( () => {
		const visibleRoutes = getVisibleRoutes();
		const currentVisibleIndex = getCurrentRouteIndex(
			currentRoute,
			visibleRoutes
		);

		// If hidden route is accessed directly, move to the nearest valid next route.
		if ( currentVisibleIndex === -1 ) {
			return getAdjacentVisibleRoute( currentRoute, 'next' );
		}

		const currentRouteIndex = getCurrentRouteIndex(
			currentRoute,
			ONBOARDING_ROUTES_CONFIG
		);

		// If we're on the first step or route not found, no redirection needed
		if ( currentRouteIndex <= 0 ) {
			return '';
		}

		// Check all previous steps for any unmet requirements
		for ( let index = 0; index <= currentVisibleIndex; index++ ) {
			const route = visibleRoutes[ index ];

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
		getVisibleRoutes,
		getNextRoute,
		getPreviousRoute,
		navigateToNextRoute,
		navigateToPreviousRoute,
		getCurrentStepNumber,
		checkRequiredStep,
		isUserConnected,
	};
};
