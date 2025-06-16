import { __ } from '@wordpress/i18n';
import { Text, Container, Badge } from '@bsf/force-ui';
import { useOnboardingNavigation } from './hooks';
import NavigationButtons from './navigation-buttons';
import { Header, Divider } from './components';
import { MessageCircle, Calculator, GitCompare, RectangleEllipsis, Signature, Webhook } from 'lucide-react';

const premiumFeatures = [
	{
		id: 'conversational-forms',
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'Create forms that feel more like a conversation. With one-question-at-a-time interaction, you\'ll keep users engaged.',
			'sureforms'
		),
		icon: <MessageCircle size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
	{
		id: 'calculations',
		title: __( 'Calculations', 'sureforms' ),
		description: __(
			'Need calculations in real-time? Perform complex math based on user input, perfect for calculators requiring real-time results.',
			'sureforms'
		),
		icon: <Calculator size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
	{
		id: 'conditional-logic',
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			'Show or hide fields based on previous answers. You ask the right questions, and we make sure only the necessary ones are displayed.',
			'sureforms'
		),
		icon: <GitCompare size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
	{
		id: 'multi-step-form',
		title: __( 'Multi Step Form', 'sureforms' ),
		description: __(
			'Break complex forms into easy-to-follow steps, reducing overwhelm and increasing completion rates and guide your users smoothly.',
			'sureforms'
		),
		icon: <RectangleEllipsis size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
	{
		id: 'digital-signature',
		title: __( 'Digital Signatures', 'sureforms' ),
		description: __(
			'Need signatures for contracts or agreements? Capture legally binding digital signatures directly within your forms, making paperwork painless.',
			'sureforms'
		),
		icon: <Signature size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
	{
		id: 'webhook-integration',
		title: __( 'Webhooks', 'sureforms' ),
		description: __(
			'Connect forms to third-party services with webhooks. Automate data transfer and notifications in real time, saving effort.',
			'sureforms'
		),
		icon: <Webhook size={ 18 } />,
		badge: __( 'Pro', 'sureforms' ),
	},
];

const PremiumFeatures = () => {
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	const handleNext = () => {
		navigateToNextRoute();
	};

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Unlock Premium Features', 'sureforms' ) }
				description={ __(
					'Take your forms to the next level with SureForms Pro and access powerful premium features.',
					'sureforms'
				) }
			/>

			<div className="grid grid-cols-1 sm:grid-cols-2 gap-1 p-1 rounded-lg bg-background-secondary">
				{ premiumFeatures.map( ( feature ) => (
					<Container
						key={ feature.id }
						gap="none"
						direction="column"
						className="p-3 bg-background-primary rounded-md shadow-sm w-full hover:shadow-md transition-shadow"
					>
						<Container align="start" className="p-1 w-full">
							<div className="text-2xl flex-shrink-0 text-icon-interactive">
								{ feature.icon }
							</div>
							<Badge
								label={ feature.badge }
								className="ml-auto"
							/>
						</Container>
						<Container
							direction="column"
							className="gap-1.5 flex-1"
						>
							<div className="space-y-0.5">
								<Text
									size={ 14 }
									weight={ 500 }
									color="primary"
								>
									{ feature.title }
								</Text>
								<Text
									size={ 14 }
									weight={ 400 }
									color="tertiary"
								>
									{ feature.description }
								</Text>
							</div>
						</Container>
					</Container>
				) ) }
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleNext,
					text: __( 'Finish Setup', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default PremiumFeatures;
