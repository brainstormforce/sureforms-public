import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Text } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import {
	getPluginStatusText,
	installAndActivatePlugin,
} from '@Utils/Helpers';
import { Divider, Header, FeatureList, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
// Rendered via <object>, not <img> or an inlined component: the artwork
// animates with CSS keyframes, which svgr/svgo strips when inlining, and
// <object> gives the SVG its own document where they run untouched.
import emailDeliveryIllustration from '@Image/onboarding/email-delivery.svg';

const features = [
	__( 'Form submission emails land in the inbox, not spam', 'sureforms' ),
	__( 'Set up any SMTP provider in under 2 minutes', 'sureforms' ),
	__( 'Works automatically with every SureForms form', 'sureforms' ),
];

const EmailDelivery = () => {
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();

	// Get SureMails plugin info from integrations
	const sureMails = Object.entries( srfm_admin?.integrations ?? {} ).find(
		( plugin ) => plugin[ 0 ] === 'sure_mails'
	);

	const initialSuremailsPlugin = sureMails ? sureMails[ 1 ] : null;
	if (
		initialSuremailsPlugin &&
		initialSuremailsPlugin.hasOwnProperty( 'redirection' )
	) {
		delete initialSuremailsPlugin.redirection;
	}

	// Check for access_key parameter on mount
	useEffect( () => {
		// Parse the URL to check for access_key parameter
		const urlParams = new URLSearchParams( window.location.search );
		const accessKey = urlParams.get( 'access_key' );

		if ( accessKey ) {
			// Handle access key by sending it to the server
			const handleAccessKey = async () => {
				try {
					// apiFetch adds the REST nonce itself; template_picker_nonce
					// is not localized on this screen.
					const response = await apiFetch( {
						path: '/sureforms/v1/handle-access-key',
						method: 'POST',
						data: { accessKey },
					} );

					if ( response?.success ) {
						// Update local state to indicate account was connected during onboarding
						actions.setAccountConnected( true );

						// Clean up URL parameter to avoid duplicate tracking on page refresh
						const url = new URL( window.location.href );

						// Only remove the access_key parameter
						url.searchParams.delete( 'access_key' );

						// Build the cleaned-up URL with remaining query params and hash
						const newUrl = `${
							url.pathname
						}?${ url.searchParams.toString() }${ url.hash }`;
						window.history.replaceState(
							{},
							document.title,
							newUrl
						);
					} else {
						console.error(
							'Error handling access key:',
							response?.message
						);
					}
				} catch ( error ) {
					console.error( 'Error handling access key:', error );
				}
			};

			handleAccessKey();
		}
	}, [] );

	const [ suremailsPlugin, setSuremailsPlugin ] = useState(
		initialSuremailsPlugin
	);

	// Whether SureMail was already active before onboarding started. Only an
	// install that happened *here* counts as one this wizard produced.
	const [ wasAlreadyActive, setWasAlreadyActive ] = useState( false );

	// 'installing' | 'activating' while the button is working, '' otherwise.
	const [ progress, setProgress ] = useState( '' );
	const [ installError, setInstallError ] = useState( '' );

	// Function to refresh plugin status
	const refreshPluginStatus = async () => {
		if ( ! suremailsPlugin ) {
			return null;
		}

		try {
			const updatedPlugin = await apiFetch( {
				path: '/sureforms/v1/plugin-status?plugin=sure_mails',
				method: 'GET',
			} );

			// Remove redirection key if it exists
			if (
				updatedPlugin &&
				updatedPlugin.hasOwnProperty( 'redirection' )
			) {
				delete updatedPlugin.redirection;
			}

			setSuremailsPlugin( updatedPlugin );
			return updatedPlugin;
		} catch ( error ) {
			console.error( 'Failed to refresh plugin status:', error );
			return null;
		}
	};

	// Refresh plugin status on component mount to ensure we have the latest status
	useEffect( () => {
		refreshPluginStatus().then( ( updatedPlugin ) => {
			if ( 'Activated' === updatedPlugin?.status ) {
				// Already active before the wizard ran: not an install to
				// report, so the analytics flag stays false.
				setWasAlreadyActive( true );
			}
		} );
	}, [] );

	const isActive = 'Activated' === suremailsPlugin?.status;

	// The button says what will actually happen, using the same vocabulary as
	// the dashboard's plugin card: "Install & Activate" when the plugin is
	// absent, "Activate" when it is installed but off, and "Continue" once
	// there is nothing left to do.
	const continueText = () => {
		if ( 'installing' === progress ) {
			return __( 'Installing SureMail…', 'sureforms' );
		}
		if ( 'activating' === progress ) {
			return __( 'Activating SureMail…', 'sureforms' );
		}
		if ( ! suremailsPlugin || isActive ) {
			return __( 'Continue', 'sureforms' );
		}
		return getPluginStatusText( suremailsPlugin );
	};

	const handleInstallSureMail = async () => {
		// Nothing to install, or nothing to install it from: just move on.
		if ( ! suremailsPlugin || isActive ) {
			if ( isActive && ! wasAlreadyActive ) {
				actions.setSuremailInstalled( true );
				actions.unmarkStepSkipped( 'emailDelivery' );
			}
			handleSkip( 'install' );
			return;
		}

		setInstallError( '' );

		// Awaited rather than fired and forgotten, so the wizard only advances
		// once SureMail is really active and a failure can be shown here
		// instead of on a screen the user has already left. NavigationButtons
		// shows its spinner for as long as this promise is pending.
		try {
			await installAndActivatePlugin( suremailsPlugin, setProgress );

			setSuremailsPlugin( {
				...suremailsPlugin,
				status: 'Activated',
			} );
			actions.setSuremailInstalled( true );
			actions.unmarkStepSkipped( 'emailDelivery' );
			handleSkip( 'install' );
		} catch ( error ) {
			setInstallError(
				error?.message ||
					__(
						'SureMail could not be installed. Please try again, or skip this step.',
						'sureforms'
					)
			);
			// Re-read rather than trust our own guess: the install may have
			// landed and only the activation failed.
			refreshPluginStatus();
		} finally {
			setProgress( '' );
		}
	};

	const handleSkip = ( action = '' ) => {
		// Mark email delivery as skipped in analytics
		if ( action !== 'install' ) {
			actions.markStepSkipped( 'emailDelivery' );
		}

		// Set email delivery as configured
		actions.setEmailDeliveryConfigured( true );

		// Navigate to next route
		navigateToNextRoute();
	};

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	return (
		<div className="space-y-4">
			<div className={ HERO_PANEL_CLASS }>
				<object
					type="image/svg+xml"
					data={ emailDeliveryIllustration }
					className="pointer-events-none block h-auto w-full"
					aria-label={ __(
						'Illustration of a form submission travelling to an inbox',
						'sureforms'
					) }
				/>
			</div>

			<Header
				title={ __(
					'Make Sure Your Emails Get Delivered',
					'sureforms'
				) }
				description={ __(
					'WordPress can lose form emails to spam or failed delivery. SureMail routes them through a proper SMTP connection so every submission actually arrives.',
					'sureforms'
				) }
			/>

			<FeatureList
				heading={ __(
					'Connect your free account to get started.',
					'sureforms'
				) }
				items={ features }
			/>

			{ installError && (
				<Text size={ 14 } color="error" role="alert">
					{ installError }
				</Text>
			) }

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleInstallSureMail,
					text: continueText(),
				} }
				skipProps={ { onClick: handleSkip } }
			/>
		</div>
	);
};

export default EmailDelivery;
