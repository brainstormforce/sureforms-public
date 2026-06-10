import { createContext, useContext, useReducer } from '@wordpress/element';

const leadDetails = srfm_admin?.website_lead_details || {};

// Session storage key for onboarding state
export const ONBOARDING_SESSION_STORAGE_KEY = 'sureforms_onboarding_state';

// Storage keys used in onboarding process
export const ONBOARDING_STORAGE_KEYS = [
	'srfm_onboarding_premium_features',
	'srfm_onboarding_premium_selections',
	'srfm_onboarding_current_plan',
	'srfm_onboarding_user_id',
	'srfm_suremail_installation_started',
];

// Initial state
const initialState = {
	emailDeliveryConfigured: false,
	userDetails: {
		firstName: leadDetails.first_name || '',
		lastName: leadDetails.last_name || '',
		email: leadDetails.email || '',
		consent: true,
	},
	// Sources detected by the migrator on onboarding boot. Populated by
	// the layout via listSources(); used by getVisibleRoutes() to gate
	// the /onboarding/import-forms step and by the import-forms page to
	// render the picker.
	migrationSources: [],
	migrationDetectionLoaded: false,
	// Analytics data
	analytics: {
		skippedSteps: [],
		premiumFeatures: {
			selectedFeatures: [],
		},
		suremailInstalled: false,
		accountConnected: false,
		completed: false,
		exitedEarly: false,
		// Migration step state. Captured for telemetry on /done.
		migrationSourceKey: null,
		migrationResult: null,
	},
};

// Action types
const ACTIONS = {
	SET_EMAIL_DELIVERY_CONFIGURED: 'SET_EMAIL_DELIVERY_CONFIGURED',
	MARK_STEP_SKIPPED: 'MARK_STEP_SKIPPED',
	UNMARK_STEP_SKIPPED: 'UNMARK_STEP_SKIPPED',
	SET_SUREMAIL_INSTALLED: 'SET_SUREMAIL_INSTALLED',
	SET_SELECTED_PREMIUM_FEATURES: 'SET_SELECTED_PREMIUM_FEATURES',
	SET_ACCOUNT_CONNECTED: 'SET_ACCOUNT_CONNECTED',
	SET_USER_DETAILS: 'SET_USER_DETAILS',
	SET_COMPLETED: 'SET_COMPLETED',
	SET_EXITED_EARLY: 'SET_EXITED_EARLY',
	SET_MIGRATION_SOURCES: 'SET_MIGRATION_SOURCES',
	SET_MIGRATION_RESULT: 'SET_MIGRATION_RESULT',
	RESET_STATE: 'RESET_STATE',
};

// Reducer
const onboardingReducer = ( state, action ) => {
	switch ( action.type ) {
		case ACTIONS.SET_EMAIL_DELIVERY_CONFIGURED:
			return { ...state, emailDeliveryConfigured: action.payload };
		case ACTIONS.MARK_STEP_SKIPPED:
			// Only add the step if it's not already in the skippedSteps array
			if ( state.analytics.skippedSteps.includes( action.payload ) ) {
				return state; // Return unchanged state if already skipped
			}
			return {
				...state,
				analytics: {
					...state.analytics,
					skippedSteps: [
						...state.analytics.skippedSteps,
						action.payload,
					],
				},
			};
		case ACTIONS.UNMARK_STEP_SKIPPED:
			return {
				...state,
				analytics: {
					...state.analytics,
					skippedSteps: state.analytics.skippedSteps.filter(
						( step ) => step !== action.payload
					),
				},
			};
		case ACTIONS.SET_SUREMAIL_INSTALLED:
			return {
				...state,
				analytics: {
					...state.analytics,
					suremailInstalled: action.payload,
					// If SureMail is installed and email-delivery was previously skipped, remove it
					skippedSteps: action.payload
						? state.analytics.skippedSteps.filter(
							( step ) => step !== 'emailDelivery'
						  )
						: state.analytics.skippedSteps,
				},
			};
		case ACTIONS.SET_SELECTED_PREMIUM_FEATURES:
			// Filter out any duplicate features before setting them
			const uniqueFeatures = [ ...new Set( action.payload ) ];
			return {
				...state,
				analytics: {
					...state.analytics,
					premiumFeatures: {
						...state.analytics.premiumFeatures,
						selectedFeatures: uniqueFeatures,
					},
				},
			};
		case ACTIONS.SET_ACCOUNT_CONNECTED:
			return {
				...state,
				analytics: {
					...state.analytics,
					accountConnected: action.payload,
				},
			};
		case ACTIONS.SET_USER_DETAILS:
			return {
				...state,
				userDetails: {
					...state.userDetails,
					...action.payload,
				},
			};
		case ACTIONS.SET_COMPLETED:
			return {
				...state,
				analytics: {
					...state.analytics,
					completed: action.payload,
					// When marking as completed, ensure exitedEarly is false
					exitedEarly: false,
				},
			};
		case ACTIONS.SET_EXITED_EARLY:
			return {
				...state,
				analytics: {
					...state.analytics,
					exitedEarly: action.payload,
					// When marking as exited early, ensure completed is false
					completed: false,
				},
			};
		case ACTIONS.SET_MIGRATION_SOURCES:
			return {
				...state,
				migrationSources: Array.isArray( action.payload ) ? action.payload : [],
				migrationDetectionLoaded: true,
			};
		case ACTIONS.SET_MIGRATION_RESULT:
			return {
				...state,
				analytics: {
					...state.analytics,
					migrationSourceKey: action.payload?.sourceKey ?? null,
					migrationResult: action.payload?.result ?? null,
				},
			};
		case ACTIONS.RESET_STATE:
			return initialState;
		default:
			return state;
	}
};

// Function to clear all onboarding localStorage data
export const clearOnboardingStorage = () => {
	// Clear session storage
	try {
		sessionStorage.removeItem( ONBOARDING_SESSION_STORAGE_KEY );
	} catch ( error ) {
		console.error( `Error clearing session storage:`, error );
	}

	// Clear local storage keys
	ONBOARDING_STORAGE_KEYS.forEach( ( key ) => {
		try {
			localStorage.removeItem( key );
		} catch ( error ) {
			console.error( `Error clearing localStorage key ${ key }:`, error );
		}
	} );
};

// Context
const OnboardingContext = createContext();

// Provider component
export const OnboardingProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( onboardingReducer, initialState );

	const actions = {
		setEmailDeliveryConfigured: ( value ) =>
			dispatch( {
				type: ACTIONS.SET_EMAIL_DELIVERY_CONFIGURED,
				payload: value,
			} ),
		markStepSkipped: ( stepName ) =>
			dispatch( {
				type: ACTIONS.MARK_STEP_SKIPPED,
				payload: stepName,
			} ),
		unmarkStepSkipped: ( stepName ) =>
			dispatch( {
				type: ACTIONS.UNMARK_STEP_SKIPPED,
				payload: stepName,
			} ),
		setSuremailInstalled: ( installed ) =>
			dispatch( {
				type: ACTIONS.SET_SUREMAIL_INSTALLED,
				payload: installed,
			} ),
		setSelectedPremiumFeatures: ( features ) =>
			dispatch( {
				type: ACTIONS.SET_SELECTED_PREMIUM_FEATURES,
				payload: features,
			} ),
		setAccountConnected: ( connected ) =>
			dispatch( {
				type: ACTIONS.SET_ACCOUNT_CONNECTED,
				payload: connected,
			} ),
		setUserDetails: ( userDetails ) =>
			dispatch( {
				type: ACTIONS.SET_USER_DETAILS,
				payload: userDetails,
			} ),
		setCompleted: ( completed ) =>
			dispatch( {
				type: ACTIONS.SET_COMPLETED,
				payload: completed,
			} ),
		setExitedEarly: ( exited ) =>
			dispatch( {
				type: ACTIONS.SET_EXITED_EARLY,
				payload: exited,
			} ),
		setMigrationSources: ( sources ) =>
			dispatch( {
				type: ACTIONS.SET_MIGRATION_SOURCES,
				payload: sources,
			} ),
		setMigrationResult: ( sourceKey, result ) =>
			dispatch( {
				type: ACTIONS.SET_MIGRATION_RESULT,
				payload: { sourceKey, result },
			} ),
		resetState: () => dispatch( { type: ACTIONS.RESET_STATE } ),
		clearStorage: clearOnboardingStorage,
	};

	return (
		<OnboardingContext.Provider value={ [ state, actions ] }>
			{ children }
		</OnboardingContext.Provider>
	);
};

// Hook to use onboarding state
export const useOnboardingState = () => {
	const context = useContext( OnboardingContext );
	if ( ! context ) {
		throw new Error(
			'useOnboardingState must be used within OnboardingProvider'
		);
	}
	return context;
};
