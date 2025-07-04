import { __ } from '@wordpress/i18n';
import { Container, Text, Title } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
import { handlePluginActionTrigger } from '@Utils/Helpers';
import { Divider } from './components';
import NavigationButtons from './navigation-buttons';
import ICONS from '@Admin/components/template-picker/components/icons';

const features = [
	__( 'Quick and easy setup, no technical skills needed', 'sureforms' ),
	__( 'Track, log, and resend emails with ease', 'sureforms' ),
	__( 'Connect to multiple email providers', 'sureforms' ),
	__( 'Shield Your Emails. Protect Every Submission.', 'sureforms' ),
];

const EmailDelivery = () => {
	const [onboardingState, actions] = useOnboardingState();
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

	const [ suremailsPlugin, setSuremailsPlugin ] = useState(
		initialSuremailsPlugin
	);

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
		refreshPluginStatus().then(updatedPlugin => {
			// Check if SureMail is already installed/activated
			if (updatedPlugin && pluginStatus.includes(updatedPlugin.status)) {
				actions.setSuremailInstalled(true);
			}
		});
	}, [] );

	const handleInstallSureMail = () => {
		// Always navigate to next step first, regardless of plugin status
		handleSkip();

		// Then handle plugin installation in background if needed
		if ( suremailsPlugin ) {
			// Check if the plugin is already activated or installed.
			if ( pluginStatus.includes( suremailsPlugin.status ) ) {
				// Plugin already installed, update analytics
				actions.setSuremailInstalled(true);
				// If email-delivery was previously skipped, remove it from skippedSteps
				actions.unmarkStepSkipped('emailDelivery');
				return;
			}

			// Start background installation (fire and forget)
			handlePluginActionTrigger( {
				plugin: suremailsPlugin,
				event: { target: { innerText: '', style: { color: '' } } }, // Dummy event object.
			} )
				.then( () => {
					// Refresh status after installation completes (background process)
					setTimeout( async () => {
						const updatedPlugin = await refreshPluginStatus();
						if (updatedPlugin && pluginStatus.includes(updatedPlugin.status)) {
							actions.setSuremailInstalled(true);
							// If email-delivery was previously skipped, remove it from skippedSteps
							actions.unmarkStepSkipped('emailDelivery');
						}
					}, 2000 );
				} )
				.catch( ( error ) => {
					console.error( 'Plugin installation failed:', error );
				} );
		}
	};

	const handleSkip = () => {
		// Mark email delivery as skipped in analytics
		actions.markStepSkipped('emailDelivery');
		
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
				<div className="space-y-2 max-w-[22.5rem]">
					<Title
						tag="h4"
						title={ __(
							'No More Missed Notifications or Lost Leads',
							'sureforms'
						) }
						size="md"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'SureMail is the easiest way to ensure your form notifications land safely in the inbox, not in spam folder.',
							'sureforms'
						) }
					</Text>
				</div>
				<div className="w-full h-full max-w-32 mx-auto">
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
