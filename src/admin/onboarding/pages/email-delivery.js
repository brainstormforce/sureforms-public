import { __ } from '@wordpress/i18n';
import { Container, Text, Title } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { handlePluginActionTrigger } from '@Utils/Helpers';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import ICONS from '@Admin/components/template-picker/components/icons';

const features = [
	__( 'Works smoothly with forms made using SureForms', 'sureforms' ),
	__( 'Helps your emails reach the inbox instead of spam', 'sureforms' ),
	__( 'Setup is straightforward, even if you\'re not technical', 'sureforms' ),
	__( 'Lightweight and easy to use without adding clutter', 'sureforms' ),
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
					const response = await apiFetch( {
						path: '/sureforms/v1/handle-access-key',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': srfm_admin.template_picker_nonce,
						},
						method: 'POST',
						body: JSON.stringify( {
							accessKey,
						} ),
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
						console.log( 'newUrl', newUrl );

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

	// Track if SureMail was already installed before onboarding
	const [ wasAlreadyInstalled, setWasAlreadyInstalled ] = useState( false );

	const pluginStatus = [ 'Activate', 'Activated', 'Installed' ];

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
			// Check if SureMail is already installed/activated
			if (
				updatedPlugin &&
				pluginStatus.includes( updatedPlugin.status )
			) {
				// Mark as already installed, but don't track in analytics
				setWasAlreadyInstalled( true );
			}
		} );
	}, [] );

	const handleInstallSureMail = () => {
		// Check if the plugin exists
		if ( suremailsPlugin ) {
			if (
				localStorage.getItem( 'srfm_suremail_installation_started' ) ===
				'true'
			) {
				// Installation already started, just navigate to next step
				navigateToNextRoute();
				return;
			}
			// Check if the plugin is already activated or installed.
			if ( pluginStatus.includes( suremailsPlugin.status ) ) {
				// If the plugin was already installed before onboarding started,
				// don't mark it as installed during onboarding - just navigate to next step
				if ( wasAlreadyInstalled ) {
					// Navigate to next step without marking as installed
					navigateToNextRoute();
				} else {
					// Plugin was installed during onboarding, update analytics
					actions.setSuremailInstalled( true );
					// If email-delivery was previously skipped, remove it from skippedSteps
					actions.unmarkStepSkipped( 'emailDelivery' );
					// Navigate to next step
					handleSkip( 'install' );
				}
				return;
			}

			// Set a flag to indicate installation has started
			localStorage.setItem(
				'srfm_suremail_installation_started',
				'true'
			);

			// Update analytics state before navigation
			// This ensures the analytics are updated even if the component unmounts
			actions.setSuremailInstalled( true );
			actions.unmarkStepSkipped( 'emailDelivery' );

			// Navigate to next step
			handleSkip( 'install' );

			// Start background installation (fire and forget)
			handlePluginActionTrigger( {
				plugin: suremailsPlugin,
				event: { target: { innerText: '', style: { color: '' } } }, // Dummy event object.
			} )
				.then( () => {
					// Installation completed successfully
					localStorage.removeItem(
						'srfm_suremail_installation_started'
					);
				} )
				.catch( ( error ) => {
					console.error( 'Plugin installation failed:', error );
					localStorage.removeItem(
						'srfm_suremail_installation_started'
					);
				} );
		} else {
			// No plugin info available, just navigate to next step
			handleSkip( 'install' );
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
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2">
					<Title
						tag="h3"
						title={ __(
							'Make Sure Your Emails Get Delivered',
							'sureforms'
						) }
						size="lg"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'Most WordPress sites struggle to send emails reliably, which means form submissions from your site might not reach your inbox—or end up in spam.',
							'sureforms'
						) }
					</Text>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'SureMail is a simple SMTP plugin that helps make sure your emails actually get delivered.',
							'sureforms'
						) }
					</Text>
				</div>
				<div className="h-full mx-auto">
					{ ICONS.onboardingSureMailsScreen }
				</div>
			</Container>

			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __( 'What you will get:', 'sureforms' ) }
				</Text>
				{ features.map( ( feature, index ) => (
					<Container
						key={ index }
						className="flex items-center gap-1.5"
					>
						<Check className="size-4 text-icon-interactive" />
						<Text size={ 14 } weight={ 400 } color="label">
							{ feature }
						</Text>
					</Container>
				) ) }
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleInstallSureMail,
					text: pluginStatus.includes( suremailsPlugin?.status )
						? __( 'Continue', 'sureforms' )
						: __( 'Install SureMail', 'sureforms' ),
				} }
				skipProps={ {
					onClick: handleSkip,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default EmailDelivery;
