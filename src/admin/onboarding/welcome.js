import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import { Divider } from './components';
import NavigationButtons from './navigation-buttons';
import { initiateAuth } from '@Utils/Helpers';

const features = [
	__( 'Create beautiful, responsive forms with ease', 'sureforms' ),
	__( 'AI-powered form generation for quick setup', 'sureforms' ),
	__( 'Advanced form fields and validation options', 'sureforms' ),
	__( 'Seamless integrations with popular services', 'sureforms' ),
	__( 'Comprehensive form analytics and insights', 'sureforms' ),
	__( 'GDPR compliant and security-focused', 'sureforms' ),
];

const Welcome = () => {
	const { navigateToNextRoute } = useOnboardingNavigation();

	const handleConnect = async () => {
		try {
			// Check if user has not connected their account yet.
			if ( 'non-registered' !== srfm_admin?.srfm_ai_usage_details?.type ) {
				navigateToNextRoute();
				return;
			}

			// Use the initiateAuth helper function
			await initiateAuth();
			
			// Navigate to next route after successful authentication
			navigateToNextRoute();
		} catch ( error ) {
			console.error( 'Error during authentication:', error );
		}
	};

	const handleSkip = async () => {
		navigateToNextRoute();
	};

	return (
		<form
			onSubmit={ ( event ) => event.preventDefault() }
			className="space-y-6"
		>
			<div className="space-y-1.5">
				<Text as="h2" size={ 30 } weight={ 600 }>
					{ __( 'Welcome to SureForms', 'sureforms' ) }
				</Text>
				<Text size={ 16 } weight={ 500 }>
					{ __( 'The Ultimate WordPress Form Builder', 'sureforms' ) }
				</Text>
			</div>
			<iframe
				className="w-full aspect-video rounded-lg"
				src="https://www.youtube.com/embed/it16jGnZBus"
				title="YouTube video player"
				frameBorder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				allowFullScreen
			></iframe>
			<ul>
				{ features.map( ( feature, index ) => (
					<li key={ index } className="flex items-center gap-1">
						<Check
							className="size-3 text-icon-primary"
							strokeWidth={ 1.5 }
						/>
						<Text size={ 14 } weight={ 500 } color="label">
							{ feature }
						</Text>
					</li>
				) ) }
			</ul>

			<Divider />

			<NavigationButtons
				continueProps={ {
					onClick: handleConnect,
					text: __( 'Connect', 'sureforms' ),
				} }
				skipProps={ {
					onClick: handleSkip,
					text: __( 'Skip', 'sureforms' ),
				} }
				buttonGroupProps={ {
					className: 'flex-row-reverse'
				} }
			/>
		</form>
	);
};

export default Welcome;
