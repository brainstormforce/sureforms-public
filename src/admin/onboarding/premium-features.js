import { __ } from '@wordpress/i18n';
import { Text, Checkbox, Badge, Alert } from '@bsf/force-ui';
import { useState } from '@wordpress/element';
import { useOnboardingNavigation } from './hooks';
import NavigationButtons from './navigation-buttons';
import { Header, Divider } from './components';
import { addQueryParam } from '@Utils/Helpers';

const premiumFeatures = [
	{
		id: 'ai-form-generation',
		title: __( 'AI Form Generation', 'sureforms' ),
		description: __(
			'Tired of building forms manually? Let AI do the work for you. Just describe and our AI will create your perfect form in seconds.',
			'sureforms'
		),
		plan: 'free',
	},
	{
		id: 'recaptcha',
		title: __( 'Google reCAPTCHA', 'sureforms' ),
		description: __(
			'Track, view, and manage all form submissions in one place. Stay organized, informed, and never miss a response.',
			'sureforms'
		),
		plan: 'free',
	},
	{
		id: 'conversational-forms',
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'Create forms that feel like a conversation. One question at a time keeps users engaged, making form completion easy.',
			'sureforms'
		),
		plan: 'premium',
	},
	{
		id: 'multi-step-form',
		title: __( 'Multi-step Forms', 'sureforms' ),
		description: __(
			'Break complex forms into simple steps, reducing overwhelm and boosting completion. Guide users smoothly through the process.',
			'sureforms'
		),
		plan: 'premium',
	},
	{
		id: 'conditional-logic',
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			"Show or hide fields based on previous answers. You ask the right questions, and we make sure only the necessary ones are displayed.",
			'sureforms'
		),
		plan: 'premium',
	},
];

// Plan badge colors
const planBadgeColors = {
	free: 'green',
	premium: 'inverse',
};

const PremiumFeatures = () => {
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	// Initialize state with free features pre-checked
	const initialSelectedFeatures = {};
	premiumFeatures.forEach(feature => {
		if (feature.plan === 'free') {
			initialSelectedFeatures[feature.id] = true;
		}
	});

	// State to track selected features
	const [ selectedFeatures, setSelectedFeatures ] = useState(initialSelectedFeatures);

	// Check if any premium feature is selected
	const hasSelectedPremiumFeatures = premiumFeatures
		.filter(feature => feature.plan === 'premium')
		.some(feature => selectedFeatures[feature.id]);

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	// Function to handle button click based on premium feature selection
	const handleContinue = () => {
		// Always navigate to next route
		navigateToNextRoute();
		
		// If premium features are selected, also open pricing page
		if (hasSelectedPremiumFeatures) {
			window.open(
				addQueryParam(
					srfm_admin?.sureforms_pricing_page,
					'onboarding_premium_features'
				),
				'_blank'
			);
		}
	};

	// Function to handle checkbox changes
	const handleCheckboxChange = ( featureId, plan ) => {
		// Don't allow unchecking free features
		if (plan === 'free') {
			return;
		}
		
		setSelectedFeatures( ( prev ) => ( {
			...prev,
			[ featureId ]: ! prev[ featureId ],
		} ) );
	};

	// Function to get badge for plan.
	const getPlanBadge = ( plan ) => {
		const planName = plan === 'free' ? __( 'Free', 'sureforms' ) : __( 'Premium', 'sureforms' );
		return (
			<Badge
				variant={ planBadgeColors[plan] }
				label={ planName }
				size="xs"
			/>
		);
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Select Your Features', 'sureforms' ) }
				description={ __(
					'Get more control, faster workflows, and deeper customization — all designed to help you build better websites with less effort.',
					'sureforms'
				) }
			/>

			<div>
				{ premiumFeatures.map( ( feature, index ) => (
					<div key={ feature.id }>
						<div className="p-1 bg-background-primary flex items-start justify-between">
							<div className="flex-grow">
								<div className="flex items-center gap-3">
									<Text
										size={ 16 }
										weight={ 500 }
										color="primary"
									>
										{ feature.title }
									</Text>
									{ getPlanBadge( feature.plan ) }
								</div>
								<Text
									size={ 14 }
									weight={ 400 }
									color="tertiary"
									className="mt-1"
								>
									{ feature.description }
								</Text>
							</div>
							<div className="ml-4 mt-1">
								<Checkbox
									checked={ !! selectedFeatures[ feature.id ] }
									onChange={ () => handleCheckboxChange( feature.id, feature.plan ) }
									size="sm"
								/>
							</div>
						</div>
						{ index < premiumFeatures.length - 1 && (
							<Divider className="m-2 w-auto" />
						) }
					</div>
				) ) }
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleContinue,
					text: hasSelectedPremiumFeatures 
						? __( 'Upgrade', 'sureforms' ) 
						: __( 'Next', 'sureforms' ),
				} }
				skipProps={ {
					onClick: navigateToNextRoute,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>

			{ hasSelectedPremiumFeatures && (
				<div className="p-1">
					<Alert
						content={ __( "You've picked Premium features — upgrade to start using them.", "sureforms" ) }
						className='bg-background-secondary'
						variant='neutral'
						icon={ <></> }
					/>
				</div>
			) }
		</div>
	);
};

export default PremiumFeatures;
