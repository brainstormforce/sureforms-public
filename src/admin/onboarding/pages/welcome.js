import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from '../hooks';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import ICONS from '@Admin/components/template-picker/components/icons';

const features = [
	__( 'Build beautiful forms visually', 'sureforms' ),
	__( 'Works perfectly on mobile', 'sureforms' ),
	__( 'Spam protection built-in', 'sureforms' ),
	__( 'Easy to connect with automation tools', 'sureforms' ),
];

const Welcome = () => {
	const { navigateToNextRoute } = useOnboardingNavigation();

	return (
		<div className="space-y-6">
			<div className="space-y-1.5">
				<Text as="h2" size={ 30 } lineHeight={ 38 } weight={ 600 }>
					{ __( 'Welcome to SureForms', 'sureforms' ) }
				</Text>
				<Text size={ 16 } weight={ 500 }>
					{ __( 'Smart, Quick and Powerful Forms.', 'sureforms' ) }
				</Text>
			</div>
			<div>
				{ ICONS.onboardingWelcome }
			</div>
			<div>
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
					onClick: navigateToNextRoute,
					text: __( "Let's Get Started", 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default Welcome;
