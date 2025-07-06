import { createContext, useContext, useReducer } from '@wordpress/element';

// Session storage key for onboarding state
export const ONBOARDING_SESSION_STORAGE_KEY = 'sureforms_onboarding_state';

// Initial state
const initialState = {
	emailDeliveryConfigured: false,
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
	SET_COMPLETED: 'SET_COMPLETED',
	SET_EXITED_EARLY: 'SET_EXITED_EARLY',
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
		case ACTIONS.RESET_STATE:
			return initialState;
		default:
			return state;
	}
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
		resetState: () => dispatch( { type: ACTIONS.RESET_STATE } ),
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
