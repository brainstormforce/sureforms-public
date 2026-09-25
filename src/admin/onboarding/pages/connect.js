import { __ } from '@wordpress/i18n';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { Divider, Header, FeatureList, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { initiateAuth } from '@Utils/Helpers';
// Animated GIF (1232×554, shown at half size). Emitted as a file by webpack's
// image rule, so it is not inlined into the bundle.
import describeFormIllustration from '@Image/onboarding/connect-describe-form.gif';

const features = [
	__( 'Create complete forms from a simple description', 'sureforms' ),
	__( 'Get the right fields, labels, and layout added for you', 'sureforms' ),
	__( 'Get 10 AI form generations for free', 'sureforms' ),
];

const Connect = () => {
	const { navigateToNextRoute } = useOnboardingNavigation();
	const [ , actions ] = useOnboardingState();

	const isRegistered = srfm_admin?.srfm_ai_details?.type !== 'non-registered';

	const handleConnect = async () => {
		try {
			if ( isRegistered ) {
				// Already connected: nothing to authorise, move on.
				navigateToNextRoute();
				return;
			}

			// Redirects the whole page to the auth provider.
			await initiateAuth( 'onboarding' );
		} catch ( error ) {
			console.error( 'Error during authentication:', error );
		}
	};

	const handleSkip = () => {
		actions.markStepSkipped( 'connect' );
		navigateToNextRoute();
	};

	return (
		<div className="space-y-4">
			<div className={ HERO_PANEL_CLASS }>
				<img
					src={ describeFormIllustration }
					alt=""
					width={ 616 }
					height={ 277 }
					className="block h-auto w-full"
				/>
			</div>

			<Header
				title={ __(
					'From Idea to a Ready-to-Publish Form in Seconds',
					'sureforms'
				) }
				description={ __(
					'Describe the form you need, and SureForms will create the fields, labels, and layout for you.',
					'sureforms'
				) }
			/>

			<FeatureList
				heading={ __(
					'Connect this website to your free SureForms account to activate AI form creation and get 10 free AI generations.',
					'sureforms'
				) }
				items={ features }
			/>

			<Divider />

			<NavigationButtons
				// No Back button on this step, so push Skip + Connect to the right.
				containerProps={ { justify: 'end' } }
				continueProps={ {
					onClick: handleConnect,
					text: isRegistered
						? __( 'Continue', 'sureforms' )
						: __( 'Connect and Activate AI', 'sureforms' ),
				} }
				skipProps={ { onClick: handleSkip } }
			/>
		</div>
	);
};

export default Connect;
