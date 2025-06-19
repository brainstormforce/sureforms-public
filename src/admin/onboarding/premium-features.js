import { __, sprintf } from '@wordpress/i18n';
import { Text, Container, Checkbox } from '@bsf/force-ui';
import { useState } from '@wordpress/element';
import { useOnboardingNavigation } from './hooks';
import NavigationButtons from './navigation-buttons';
import { Header, Divider } from './components';
import {
	MessageCircle,
	Calculator,
	GitCompare,
	RectangleEllipsis,
	Signature,
	Webhook,
} from 'lucide-react';
import { addQueryParam } from '@Utils/Helpers';

const premiumFeatures = [
	{
		id: 'conversational-forms',
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'Create forms that feel like a chat. One question at a time keeps users engaged.',
			'sureforms'
		),
		icon: <MessageCircle size={ 18 } />,
		plan: 'pro',
	},
	{
		id: 'calculations',
		title: __( 'Calculations', 'sureforms' ),
		description: __(
			'Perform real-time math based on user input. Great for dynamic calculators.',
			'sureforms'
		),
		icon: <Calculator size={ 18 } />,
		plan: 'business',
	},
	{
		id: 'conditional-logic',
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			"Show or hide fields based on previous answers. Only display what's needed.",
			'sureforms'
		),
		icon: <GitCompare size={ 18 } />,
		plan: 'starter',
	},
	{
		id: 'multi-step-form',
		title: __( 'Multi Step Form', 'sureforms' ),
		description: __(
			'Split long forms into steps. Reduce overwhelm and guide users smoothly.',
			'sureforms'
		),
		icon: <RectangleEllipsis size={ 18 } />,
		plan: 'starter',
	},
	{
		id: 'digital-signature',
		title: __( 'Digital Signatures', 'sureforms' ),
		description: __(
			'Easily collect signatures for forms, agreements, or approvals, right inside your form.',
			'sureforms'
		),
		icon: <Signature size={ 18 } />,
		plan: 'pro',
	},
	{
		id: 'webhook-integration',
		title: __( 'Webhooks', 'sureforms' ),
		description: __(
			'Send form data to other tools instantly. Automate workflows in real time.',
			'sureforms'
		),
		icon: <Webhook size={ 18 } />,
		plan: 'starter',
	},
];

// Define plan hierarchy (higher index = higher tier)
const planHierarchy = [ 'free', 'starter', 'pro', 'business' ];

const PremiumFeatures = () => {
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	// State to track selected features
	const [ selectedFeatures, setSelectedFeatures ] = useState( {} );

	// Check if any feature is selected
	const hasSelectedFeatures = Object.values( selectedFeatures ).some(
		( value ) => value === true
	);

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	// Function to handle upgrade button click
	const handleUpgrade = () => {
		// Redirect to SureForms pricing page
		window.open(
			addQueryParam(
				srfm_admin?.sureforms_pricing_page,
				'onboarding_premium_features'
			),
			'_blank'
		);
	};

	// Function to handle checkbox changes
	const handleCheckboxChange = ( featureId ) => {
		setSelectedFeatures( ( prev ) => ( {
			...prev,
			[ featureId ]: ! prev[ featureId ],
		} ) );
	};

	// Function to render the checkbox based on the plan
	const renderPlanCheckbox = ( featureId ) => {
		return (
			<div className="ml-auto">
				<Checkbox
					checked={ !! selectedFeatures[ featureId ] }
					onChange={ () => handleCheckboxChange( featureId ) }
					size="sm"
				/>
			</div>
		);
	};

	// Function to get the highest plan needed based on selected features
	const getHighestRequiredPlan = () => {
		// Get selected features
		const selectedFeaturesList = premiumFeatures.filter(
			( feature ) => selectedFeatures[ feature.id ]
		);

		if ( selectedFeaturesList.length === 0 ) {
			return null;
		}

		// Find the highest plan in the hierarchy
		let highestPlanIndex = -1;
		let highestPlan = null;

		selectedFeaturesList.forEach( ( feature ) => {
			const planIndex = planHierarchy.indexOf( feature.plan );
			if ( planIndex > highestPlanIndex ) {
				highestPlanIndex = planIndex;
				highestPlan = feature.plan;
			}
		} );

		if ( highestPlan ) {
			// Format the plan name for display
			const planName =
				highestPlan.charAt( 0 ).toUpperCase() + highestPlan.slice( 1 );
			return sprintf(
				// translators: %s: The name of the plan (e.g. "Pro", "Business").
				__( 'You have selected the %s Plan', 'sureforms' ),
				planName
			);
		}

		return null;
	};

	// Get the upgrade message
	const upgradeMessage = getHighestRequiredPlan();

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
							{ renderPlanCheckbox( feature.id ) }
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

			{ /* Single line upgrade message */ }
			{ upgradeMessage && (
				<div className="p-1">
					<Text size={ 14 } weight={ 400 } className="text-secondary">
						{ upgradeMessage }
					</Text>
				</div>
			) }

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleUpgrade,
					text: __( 'Upgrade Now', 'sureforms' ),
					disabled: ! hasSelectedFeatures,
				} }
				skipProps={ {
					onClick: navigateToNextRoute,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default PremiumFeatures;
