import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
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
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute } = useOnboardingNavigation();

	const handleConnect = async () => {
		try {
			// Check if user has not connected their account yet.
			if ( 'non-registered' !== srfm_admin?.srfm_ai_details?.type ) {
				// User is already connected, but we don't update accountConnected here as we only want to track connections made during onboarding.
				navigateToNextRoute();
				return;
			}

			// Use the initiateAuth helper function
			const result = await initiateAuth( 'onboarding' );

			// If authentication was successful, update analytics.
			if ( result && result.success ) {
				// Only update accountConnected if the user explicitly connects during onboarding.
				actions.setAccountConnected( true );
			}
		} catch ( error ) {
			console.error( 'Error during authentication:', error );
		}
	};

	const handleSkip = async () => {
		// Skip without connecting account
		navigateToNextRoute();
	};

	const isRegistered = srfm_admin?.srfm_ai_details?.type !== 'non-registered';

	return (
		<form
			onSubmit={ ( event ) => event.preventDefault() }
			className="space-y-6"
		>
			<div className="space-y-1.5">
				<Text as="h2" size={ 30 } lineHeight={ 38 } weight={ 600 }>
					{ __( 'Welcome to SureForms', 'sureforms' ) }
				</Text>
				<Text size={ 16 } weight={ 500 }>
					{ __( 'Smart, Quick and Powerful Forms.', 'sureforms' ) }
				</Text>
			</div>
			<iframe
				className="w-full aspect-video rounded-lg"
				src="https://www.youtube.com/embed/qLpnm4GdXks?autoplay=1&mute=1"
				title="SureForms Introduction"
				frameBorder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				allowFullScreen
			></iframe>
			<div>
				{ ! isRegistered && (
					<Text size={ 16 } weight={ 400 }>
						{ __(
							'Connect your account to unlock the full SureForms experience, including 10 free AI form generations to help you build forms faster than ever.',
							'sureforms'
						) }
					</Text>
				) }
				<ul>
					{ features.map( ( feature, index ) => (
						<li key={ index } className="flex items-center gap-1">
							<Check className="size-4 text-icon-interactive" />
							<Text size={ 14 } weight={ 400 } color="label">
								{ feature }
							</Text>
						</li>
					) ) }
				</ul>
			</div>

			<Divider />

			<NavigationButtons
				continueProps={ {
					onClick: handleConnect,
					text: isRegistered
						? __( "Let's Get Started", 'sureforms' )
						: __( 'Connect', 'sureforms' ),
					...( isRegistered && {
						icon: () => {},
					} ),
				} }
				{ ...( ! isRegistered && {
					skipProps: {
						onClick: handleSkip,
						text: __( 'Skip', 'sureforms' ),
					},
				} ) }
				buttonGroupProps={ {
					className: 'flex-row-reverse',
				} }
			/>
		</form>
	);
};

export default Welcome;
