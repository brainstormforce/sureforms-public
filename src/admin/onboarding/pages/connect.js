import { __ } from '@wordpress/i18n';
import { Container, Text, Title } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { initiateAuth } from '@Utils/Helpers';
import ICONS from '@Admin/components/template-picker/components/icons';

const features = [
	__( 'Build up to 10 forms using AI', 'sureforms' ),
	__( 'A secure and private connection', 'sureforms' ),
	__( 'Smart form drafts based on your input', 'sureforms' ),
];

const Welcome = () => {
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();
	const [ , actions ] = useOnboardingState();

	const handleConnect = async () => {
		try {
			// Check if user has not connected their account yet.
			if ( 'non-registered' !== srfm_admin?.srfm_ai_details?.type ) {
				// User is already connected, just navigate to next step
				navigateToNextRoute();
				return;
			}

			// Use the initiateAuth helper function which will redirect to auth page.
			await initiateAuth( 'onboarding' );
		} catch ( error ) {
			console.error( 'Error during authentication:', error );
		}
	};

	const handleSkip = async () => {
		// Mark welcome step as skipped in analytics
		actions.markStepSkipped( 'connect' );

		// Skip without connecting account
		navigateToNextRoute();
	};

	const isRegistered = srfm_admin?.srfm_ai_details?.type !== 'non-registered';
	const description = (
		<>
			<p>
				{ __(
					"Starting from a blank form isn't always easy. Our AI can help by creating a draft form based on what you're trying to do — saving you time and giving you a clear direction.",
					'sureforms'
				) }
				<br />
				<br />
				{ __(
					"To do this, you'll need to connect your account.",
					'sureforms'
				) }
			</p>
		</>
	);

	return (
		<form
			onSubmit={ ( event ) => event.preventDefault() }
			className="space-y-6"
		>
			<div>{ ICONS.onboardingConnect }</div>
			<Title
				title={ __(
					'Let AI Help You Build Smarter, Faster Forms',
					'sureforms'
				) }
				description={ description }
				tag="h3"
				size="lg"
				className="flex flex-col gap-2"
			/>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 500 } color="primary">
					{ __( "Here's what that gives you:", 'sureforms' ) }
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
					onClick: navigateToPreviousRoute,
				} }
				continueProps={ {
					onClick: handleConnect,
					text: isRegistered
						? __( 'Continue', 'sureforms' )
						: __( 'Connect', 'sureforms' ),
				} }
				skipProps={ {
					onClick: handleSkip,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>
		</form>
	);
};

export default Welcome;
