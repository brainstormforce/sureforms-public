import { __ } from '@wordpress/i18n';
import { Container, Text, Title } from '@bsf/force-ui';
import { CheckIcon } from 'lucide-react';
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
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();

	// Get SureMails plugin info from integrations
	const sureMails = Object.entries( srfm_admin?.integrations ?? {} ).find(
		( plugin ) => plugin[ 0 ] === 'sure_mails'
	);

	const suremailsPlugin = sureMails ? sureMails[ 1 ] : null;
	if ( suremailsPlugin && suremailsPlugin.hasOwnProperty( 'redirection' ) ) {
		delete suremailsPlugin.redirection;
	}
	const pluginStatus = [ 'Activate', 'Activated', 'Installed' ];

	const handleInstallSureMail = async () => {
		if ( suremailsPlugin ) {
			// Check if the plugin is already activated or installed.
			if ( pluginStatus.includes( suremailsPlugin.status ) ) {
				// If already activated, just proceed to the next step
				handleSkip();
				return;
			}

			// Otherwise, trigger plugin installation/activation
			await handlePluginActionTrigger( {
				plugin: suremailsPlugin,
				event: { target: { innerText: '', style: { color: '' } } }, // Dummy event object.
			} );
		}
		// Continue to next step.
		handleSkip();
	};

	const handleSkip = () => {
		actions.setEmailDeliveryConfigured( true );
		navigateToNextRoute();
	};

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	return (
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2 max-w-[22.5rem]">
					<Text as="h2" size={ 20 } lineHeight={ 30 } weight={ 600 }>
						{ __( 'No More Missed Notifications or Lost Leads', 'sureforms' ) }
					</Text>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							"SureMail is the easiest way to ensure your form notifications land safely in the inbox, not in spam folder.",
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
						<CheckIcon className="size-4 text-icon-interactive" />
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
					text: pluginStatus.includes( suremailsPlugin?.status ) ? __( 'Continue', 'sureforms' ) : __( 'Install SureMail', 'sureforms' ),
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
