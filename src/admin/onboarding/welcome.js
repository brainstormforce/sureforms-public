import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import { Divider } from './components';
import NavigationButtons from './navigation-buttons';
import { initiateAuth } from '@Utils/Helpers';

const features = [
	__( 'Build beautiful forms visually', 'sureforms' ),
	__( 'Works perfectly on mobile', 'sureforms' ),
	__( 'Spam protection built-in', 'sureforms' ),
	__( 'Easy to connect with automation tools', 'sureforms' ),
];

const Welcome = () => {
	const { navigateToNextRoute } = useOnboardingNavigation();

	const handleConnect = async () => {
		try {
			// Check if user has not connected their account yet.
			if ( 'non-registered' !== srfm_admin?.srfm_ai_details?.type ) {
				navigateToNextRoute();
				return;
			}

			// Use the initiateAuth helper function
			await initiateAuth( 'onboarding' );
		} catch ( error ) {
			console.error( 'Error during authentication:', error );
		}
	};

	const handleSkip = async () => {
		navigateToNextRoute();
	};

	const isRegistered = srfm_admin?.srfm_ai_details?.type !== 'non-registered';
	
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
					{ __( 'Smart, Quick and Powerful Forms.', 'sureforms' ) }
				</Text>
			</div>
			<iframe
				className="w-full aspect-video rounded-lg"
				src="https://www.youtube.com/embed/qLpnm4GdXks"
				title="YouTube video player"
				frameBorder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				allowFullScreen
			></iframe>
			<ul>
				{ features.map( ( feature, index ) => (
					<li key={ index } className="flex items-center gap-1">
						<Check
							className="size-3 text-icon-interactive"
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
					text: isRegistered ? __( 'Let\'s Get Started', 'sureforms' ) : __( 'Connect', 'sureforms' ),
					...(
						isRegistered && {
							icon: () => {}
						}
					)
				} }
				{ ...( ! isRegistered && {
					skipProps: {
						onClick: handleSkip,
						text: __( 'Skip', 'sureforms' ),
					}
				} ) }
				buttonGroupProps={ {
					className: 'flex-row-reverse',
				} }
			/>
		</form>
	);
};

export default Welcome;
