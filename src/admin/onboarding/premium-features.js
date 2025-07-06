import { __ } from '@wordpress/i18n';
import { Text, Checkbox, Badge, Alert } from '@bsf/force-ui';
import { useState, useEffect } from '@wordpress/element';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
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

	// Starter features - 'premium' is now the default type when not specified
	{
		id: 'multi_step_form',
		title: __( 'Multi-step Forms', 'sureforms' ),
		description: __(
			'Break complex forms into simple steps, reducing overwhelm and boosting completion rates. Guide users smoothly through the process',
			'sureforms'
		),
		plan: 'starter',
	},
	{
		id: 'conditional_logic',
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			"Show or hide fields based on user answers. Ask the right questions and display only what's needed to keep forms clean and relevant.",
			'sureforms'
		),
		plan: 'starter',
	},
	{
		id: 'webhooks',
		title: __( 'Webhooks', 'sureforms' ),
		description: __(
			'Send form submissions instantly to any external system or endpoint to power advanced workflows.',
			'sureforms'
		),
		plan: 'starter',
	},
	{
		id: 'advanced_fields',
		title: __( 'Advanced Fields', 'sureforms' ),
		description: __(
			'Enhance your forms with advanced fields like multi-file upload, rating fields, and date & time pickers to collect richer, flexible data.',
			'sureforms'
		),
		plan: 'starter',
	},

	// Pro features - 'premium' is now the default type when not specified
	{
		id: 'conversational_forms',
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'Create forms that feel like a conversation. One question at a time keeps users engaged and makes form completion easy.',
			'sureforms'
		),
		plan: 'pro',
	},
	{
		id: 'digital_signatures',
		title: __( 'Digital Signatures', 'sureforms' ),
		description: __(
			'Collect legally binding digital signatures directly in your forms for agreements, approvals, and contracts.',
			'sureforms'
		),
		plan: 'pro',
	},

	// Business features - 'premium' is now the default type when not specified
	{
		id: 'calculations',
		title: __( 'Calculators', 'sureforms' ),
		description: __(
			'Add interactive calculators to your forms for instant estimates, quotes, and calculations for your users.',
			'sureforms'
		),
		plan: 'business',
	},
	{
		id: 'user_registration',
		title: __( 'User Registration and Login', 'sureforms' ),
		description: __(
			'Allow visitors to register and log in to your site. Useful for membership, community, or any site that needs user access.',
			'sureforms'
		),
		plan: 'business',
	},
	{
		id: 'custom_app',
		title: __( 'Custom App', 'sureforms' ),
		description: __(
			'Collect data, send it to external applications for processing, and display results instantly—all seamlessly integrated to create dynamic, interactive user experiences.',
			'sureforms'
		),
		plan: 'business',
	},
];

// Plan badge colors
const planBadgeColors = {
	free: 'green',
	premium: 'inverse',
};

const PremiumFeatures = () => {
	const [ , actions ] = useOnboardingState();
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	// Get current active pro version if available
	const currentProVersion = srfm_admin?.pro_plugin_name || '';
	const hasProVersion = currentProVersion.includes( 'SureForms' );

	// Extract plan name if pro version exists
	const [ currentPlan, setCurrentPlan ] = useState( '' );

	// State to track if component is ready to render
	const [ isReady, setIsReady ] = useState( false );

	useEffect( () => {
		if ( hasProVersion ) {
			// Extract plan name from "SureForms <plan name>"
			const planMatch = currentProVersion.match( /SureForms\s+(.*)/ );
			if ( planMatch && planMatch[ 1 ] ) {
				setCurrentPlan( planMatch[ 1 ].trim().toLowerCase() );
			}
		}
		// Mark component as ready to render after plan is determined
		setIsReady( true );
	}, [ currentProVersion, hasProVersion ] );

	// Filter features based on current plan - memoized to prevent recalculation on every render
	const filteredFeatures = ( () => {
		// Specific features to show for free and business plans.
		const specificFeatureIds = [
			'ai_form_generation',
			'entries',
			'conditional_logic',
			'conversational_forms',
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

		// Default: show specific features
		return allFeatures.filter( ( feature ) =>
			specificFeatureIds.includes( feature.id )
		);
	} )();

	// Initialize state with features pre-checked based on plan - memoized
	const initialSelectedFeatures = ( () => {
		const initialState = {};

		// Only initialize if we're ready
		if ( isReady ) {
			filteredFeatures.forEach( ( feature ) => {
				// Check all features if business plan
				if ( currentPlan === 'business' ) {
					initialState[ feature.id ] = true;
				} else if ( feature.type === 'free' ) {
					// Otherwise only check free features
					initialState[ feature.id ] = true;
				}
			} );
		}

		return initialState;
	} )();

	// State to track selected features - initialize with empty object and update when ready
	const [ selectedFeatures, setSelectedFeatures ] = useState( {} );

	// Update selected features when initialization is complete
	useEffect( () => {
		if ( isReady ) {
			setSelectedFeatures( initialSelectedFeatures );
		}
	}, [ isReady ] );

	// Check if any premium feature is selected
	const hasSelectedPremiumFeatures = filteredFeatures
		.filter( ( feature ) => feature.type !== 'free' ) // Consider features without type as premium
		.some( ( feature ) => selectedFeatures[ feature.id ] );

	const handleBack = () => {
		navigateToPreviousRoute();
	};

	// Function to handle button click based on premium feature selection
	const handleContinue = () => {
		// If premium features are selected, save them to analytics and open pricing page
		if ( hasSelectedPremiumFeatures ) {
			const selectedFeatureIds = Object.entries( selectedFeatures )
				.filter( ( [ featureId, isSelected ] ) => {
					// Only include selected premium features (exclude free features)
					const feature = allFeatures.find(
						( f ) => f.id === featureId
					);
					return isSelected && feature && feature.type !== 'free';
				} )
				.map( ( [ featureId ] ) => featureId );

			// Only save selected features if user clicks Upgrade
			// Use Set to ensure uniqueness of feature IDs
			actions.setSelectedPremiumFeatures( [
				...new Set( selectedFeatureIds ),
			] );

			// Open pricing page
			window.open(
				addQueryParam(
					srfm_admin?.sureforms_pricing_page,
					'onboarding_premium_features'
				),
				'_blank'
			);
		} else {
			// Clear selected premium features if continuing without premium features
			actions.setSelectedPremiumFeatures( [] );
		}

		// Always navigate to next route
		navigateToNextRoute();
	};

	// Function to handle skip
	const handleSkip = () => {
		// Mark premium features as skipped
		actions.markStepSkipped( 'premiumFeatures' );

		// Clear selected premium features when skipping
		actions.setSelectedPremiumFeatures( [] );

		// Navigate to next route
		navigateToNextRoute();
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
		// Default to 'premium' if type is not specified
		const featureType = type || 'premium';
		const planName =
			featureType === 'free'
				? __( 'Free', 'sureforms' )
				: __( 'Premium', 'sureforms' );
		return (
			<Badge
				variant={ planBadgeColors[ featureType ] }
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

			{ isReady && (
				<>
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
													!! selectedFeatures[
														feature.id
													]
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
				</>
			) }

			{ isReady && (
				<>
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
							onClick: handleSkip,
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
								className="bg-background-secondary shadow-none"
								variant="neutral"
								icon={ <></> }
							/>
						</div>
					) }
				</>
			) }
		</div>
	);
};

export default PremiumFeatures;
