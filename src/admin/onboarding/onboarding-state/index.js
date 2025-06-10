import { createContext, useContext, useReducer } from '@wordpress/element';

// Session storage key for onboarding state
export const ONBOARDING_SESSION_STORAGE_KEY = 'sureforms_onboarding_state';

// Initial state
const initialState = {
	globalSettingsConfigured: false,
	pluginsInstalled: [],
};

// Action types
const ACTIONS = {
	SET_GLOBAL_SETTINGS_CONFIGURED: 'SET_GLOBAL_SETTINGS_CONFIGURED',
	SET_PLUGIN_INSTALLED: 'SET_PLUGIN_INSTALLED',
	RESET_STATE: 'RESET_STATE',
};

// Reducer
const onboardingReducer = ( state, action ) => {
	switch ( action.type ) {
		case ACTIONS.SET_GLOBAL_SETTINGS_CONFIGURED:
			return { ...state, globalSettingsConfigured: action.payload };
		case ACTIONS.SET_PLUGIN_INSTALLED:
			return { 
				...state, 
				pluginsInstalled: state.pluginsInstalled.includes( action.payload )
					? state.pluginsInstalled
					: [ ...state.pluginsInstalled, action.payload ]
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
		setGlobalSettingsConfigured: ( value ) =>
			dispatch( { type: ACTIONS.SET_GLOBAL_SETTINGS_CONFIGURED, payload: value } ),
		setPluginInstalled: ( pluginId ) =>
			dispatch( { type: ACTIONS.SET_PLUGIN_INSTALLED, payload: pluginId } ),
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
