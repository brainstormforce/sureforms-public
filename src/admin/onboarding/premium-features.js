import { __ } from '@wordpress/i18n';
import { Text, Checkbox, Badge, Alert } from '@bsf/force-ui';
import { useState, useEffect } from '@wordpress/element';
import { useOnboardingNavigation } from './hooks';
import NavigationButtons from './navigation-buttons';
import { Header, Divider } from './components';
import { addQueryParam } from '@Utils/Helpers';

// Define all available features with their respective plans.
const allFeatures = [
	// Free features
	{
		id: 'ai-form-generation',
		title: __( 'AI Form Generation', 'sureforms' ),
		description: __(
			'Tired of building forms manually? Let AI do the work for you. Just describe and our AI will create your perfect form in seconds.',
			'sureforms'
		),
		type: 'free',
		plan: 'free',
	},
	{
		id: 'entries',
		title: __( 'View Entries', 'sureforms' ),
		description: __(
			'Track, view, and manage all form submissions in one place. Stay organized, informed, and never miss a response.',
			'sureforms'
		),
		type: 'free',
		plan: 'free',
	},

	// Starter features
	{
		id: 'multi-step-form',
		title: __( 'Multi-step Forms', 'sureforms' ),
		description: __(
			'Break complex forms into simple steps, reducing overwhelm and boosting completion. Guide users smoothly through the process.',
			'sureforms'
		),
		type: 'premium',
		plan: 'starter',
	},
	{
		id: 'conditional-logic',
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			'Show or hide fields based on previous answers. You ask the right questions, and we make sure only the necessary ones are displayed.',
			'sureforms'
		),
		type: 'premium',
		plan: 'starter',
	},

	// Pro features
	{
		id: 'conversational-forms',
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'Create forms that feel like a conversation. One question at a time keeps users engaged, making form completion easy.',
			'sureforms'
		),
		type: 'premium',
		plan: 'pro',
	},
	{
		id: 'digital-signatures',
		title: __( 'Digital Signatures', 'sureforms' ),
		description: __(
			'Collect legally binding signatures directly in your forms. Perfect for contracts, agreements, and approvals.',
			'sureforms'
		),
		type: 'premium',
		plan: 'pro',
	},
	{
		id: 'file-uploads',
		title: __( 'File Uploads', 'sureforms' ),
		description: __(
			'Allow users to upload files directly through your forms. Collect documents, images, and more with ease.',
			'sureforms'
		),
		type: 'premium',
		plan: 'pro',
	},

	// Business features
	{
		id: 'calculations',
		title: __( 'Advanced Calculations', 'sureforms' ),
		description: __(
			'Perform complex mathematical operations based on form inputs. Perfect for price calculators, quote generators, and more.',
			'sureforms'
		),
		type: 'premium',
		plan: 'business',
	},
	{
		id: 'user-registration',
		title: __( 'User Registration', 'sureforms' ),
		description: __(
			'Create custom user registration forms that automatically create WordPress user accounts. Streamline your onboarding process.',
			'sureforms'
		),
		type: 'premium',
		plan: 'business',
	},
	{
		id: 'custom-app-integration',
		title: __( 'Custom App Integration', 'sureforms' ),
		description: __(
			'Connect your forms to any custom application or service. Build your own integrations and automate complex workflows across your entire tech stack.',
			'sureforms'
		),
		type: 'premium',
		plan: 'business',
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

	// Get current active pro version if available
	const currentProVersion = srfm_admin?.pro_plugin_name || '';
	const hasProVersion = currentProVersion.includes( 'SureForms' );

	// Extract plan name if pro version exists
	const [ currentPlan, setCurrentPlan ] = useState( '' );

	useEffect( () => {
		if ( hasProVersion ) {
			// Extract plan name from "SureForms <plan name>"
			const planMatch = currentProVersion.match( /SureForms\s+(.*)/ );
			if ( planMatch && planMatch[ 1 ] ) {
				setCurrentPlan( planMatch[ 1 ].trim().toLowerCase() );
			}
		}
	}, [ currentProVersion, hasProVersion ] );

	// Filter features based on current plan
	const getFilteredFeatures = () => {
		// Specific features to show for free and business plans
		const specificFeatureIds = [
			'ai-form-generation',
			'recaptcha',
			'conditional-logic',
			'conversational-forms',
			'calculations',
		];

		// If no pro version or free plan, show specific features
		if ( ! hasProVersion ) {
			return allFeatures.filter( ( feature ) =>
				specificFeatureIds.includes( feature.id )
			);
		}

		// For Starter plan: Display 2 Pro features and 1 Business feature
		if ( currentPlan === 'starter' ) {
			const proFeatures = allFeatures
				.filter( ( feature ) => feature.plan === 'pro' )
				.slice( 0, 2 );
			const businessFeatures = allFeatures
				.filter( ( feature ) => feature.plan === 'business' )
				.slice( 0, 1 );
			return [
				...allFeatures.filter( ( feature ) => feature.plan === 'free' ),
				...proFeatures,
				...businessFeatures,
			];
		}

		// For Pro plan: Display 3 Business features
		if ( currentPlan === 'pro' ) {
			const businessFeatures = allFeatures.filter(
				( feature ) => feature.plan === 'business'
			);
			return [
				...allFeatures.filter( ( feature ) => feature.plan === 'free' ),
				...businessFeatures,
			];
		}

		// For Business plan: Display specific features
		if ( currentPlan === 'business' ) {
			return allFeatures.filter( ( feature ) =>
				specificFeatureIds.includes( feature.id )
			);
		}

		// Default: show all features
		return allFeatures;
	};

	const filteredFeatures = getFilteredFeatures();

	// Initialize state with features pre-checked based on plan
	const getInitialSelectedFeatures = () => {
		const initialState = {};

		filteredFeatures.forEach( ( feature ) => {
			// Check all features if business plan
			if ( currentPlan === 'business' ) {
				initialState[ feature.id ] = true;
			} else if ( feature.type === 'free' ) {
				// Otherwise only check free features
				initialState[ feature.id ] = true;
			}
		} );

		return initialState;
	};

	// State to track selected features
	const [ selectedFeatures, setSelectedFeatures ] = useState(
		getInitialSelectedFeatures
	);

	// Check if any premium feature is selected
	const hasSelectedPremiumFeatures = filteredFeatures
		.filter( ( feature ) => feature.type === 'premium' )
		.some( ( feature ) => selectedFeatures[ feature.id ] );

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	// Function to handle button click based on premium feature selection
	const handleContinue = () => {
		// Always navigate to next route
		navigateToNextRoute();

		// If premium features are selected, also open pricing page
		if ( hasSelectedPremiumFeatures ) {
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
	const handleCheckboxChange = ( featureId, type ) => {
		// Don't allow unchecking free features
		if ( type === 'free' ) {
			return;
		}

		// Don't allow unchecking if business plan
		if ( currentPlan === 'business' ) {
			return;
		}

		setSelectedFeatures( ( prev ) => ( {
			...prev,
			[ featureId ]: ! prev[ featureId ],
		} ) );
	};

	// Function to get badge for plan.
	const getPlanBadge = ( type ) => {
		const planName =
			type === 'free'
				? __( 'Free', 'sureforms' )
				: __( 'Premium', 'sureforms' );
		return (
			<Badge
				variant={ planBadgeColors[ type ] }
				label={ planName }
				size="xs"
			/>
		);
	};

	// Check if we should show the upgrade button
	const showUpgradeButton =
		hasSelectedPremiumFeatures && currentPlan !== 'business';

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Select Your Features', 'sureforms' ) }
				description={ __(
					'Get more control, faster workflows, and deeper customization — all designed to help you build better websites with less effort.',
					'sureforms'
				) }
			/>

			{ filteredFeatures.length > 0 ? (
				<div>
					{ filteredFeatures.map( ( feature, index ) => (
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
										{ getPlanBadge( feature.type ) }
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
										checked={
											!! selectedFeatures[ feature.id ]
										}
										onChange={ () =>
											handleCheckboxChange(
												feature.id,
												feature.type
											)
										}
										size="sm"
									/>
								</div>
							</div>
							{ index < filteredFeatures.length - 1 && (
								<Divider className="m-2 w-auto" />
							) }
						</div>
					) ) }
				</div>
			) : (
				<div className="p-4 bg-background-secondary rounded-md">
					<Text size={ 14 } weight={ 500 } color="primary">
						{ __(
							'You already have access to all premium features with your current plan!',
							'sureforms'
						) }
					</Text>
				</div>
			) }

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleContinue,
					text: showUpgradeButton
						? __( 'Upgrade', 'sureforms' )
						: __( 'Next', 'sureforms' ),
				} }
				skipProps={ {
					onClick: navigateToNextRoute,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>

			{ showUpgradeButton && (
				<div className="p-1">
					<Alert
						content={ __(
							"You've picked Premium features — upgrade to start using them.",
							'sureforms'
						) }
						className="bg-background-secondary"
						variant="neutral"
						icon={ <></> }
					/>
				</div>
			) }
		</div>
	);
};

export default PremiumFeatures;
