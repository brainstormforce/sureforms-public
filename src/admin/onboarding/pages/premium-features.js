import { __, sprintf } from '@wordpress/i18n';
import { Text, Checkbox, Badge } from '@bsf/force-ui';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import NavigationButtons from '../components/navigation-buttons';
import { Header, Divider } from '../components';
import { addQueryParam } from '@Utils/Helpers';
import { ExternalLink } from 'lucide-react';

// Define all available features with their respective plans.
const allFeatures = [
	// Free features
	{
		id: 'ai_form_generation',
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
			'Collect data, send it to external applications for processing, and display results instantly — all seamlessly integrated to create dynamic, interactive user experiences.',
			'sureforms'
		),
		plan: 'business',
	},
];

// Create a map of feature IDs for quick lookup
const featureMap = {};
allFeatures.forEach( ( feature ) => {
	featureMap[ feature.id ] = feature;
} );

// Plan badge colors
const planBadgeColors = {
	free: 'green',
	premium: 'inverse',
};

// Storage keys for localStorage
const FEATURES_STORAGE_KEY = 'srfm_onboarding_premium_features';
const SELECTIONS_STORAGE_KEY = 'srfm_onboarding_premium_selections';
const PLAN_STORAGE_KEY = 'srfm_onboarding_current_plan';

// Coupon code constant
const COUPON_CODE = 'ONB10';

// Helper function to shuffle an array (Fisher-Yates algorithm)
const shuffleArray = ( array ) => {
	const shuffled = [ ...array ];
	for ( let i = shuffled.length - 1; i > 0; i-- ) {
		const j = Math.floor( Math.random() * ( i + 1 ) );
		[ shuffled[ i ], shuffled[ j ] ] = [ shuffled[ j ], shuffled[ i ] ];
	}
	return shuffled;
};

// Helper function to get a random subset of features
const getRandomFeatures = ( featuresArray, count ) => {
	return shuffleArray( featuresArray ).slice( 0, count );
};

// Helper functions for localStorage
const getFromLocalStorage = ( key, defaultValue ) => {
	try {
		const storedValue = localStorage.getItem( key );
		if ( ! storedValue ) {
			return defaultValue;
		}

		try {
			return JSON.parse( storedValue );
		} catch ( parseError ) {
			console.error( 'Error parsing stored value:', parseError );
			return defaultValue;
		}
	} catch ( error ) {
		console.error( 'Error reading from local storage:', error );
		return defaultValue;
	}
};

const saveToLocalStorage = ( key, value ) => {
	try {
		localStorage.setItem( key, JSON.stringify( value ) );
	} catch ( error ) {
		console.error( 'Error saving to local storage:', error );
	}
};

// Generate features based on plan
const generateFeatures = ( currentPlan ) => {
	// Always include free features
	const freeFeatures = allFeatures.filter(
		( feature ) => feature.plan === 'free'
	);

	// For Starter plan: Display 2 random Pro features and 1 random Business feature
	if ( currentPlan === 'starter' ) {
		const randomProFeatures = getRandomFeatures(
			allFeatures.filter( ( feature ) => feature.plan === 'pro' ),
			2
		);

		const randomBusinessFeatures = getRandomFeatures(
			allFeatures.filter( ( feature ) => feature.plan === 'business' ),
			1
		);

		return [
			...freeFeatures,
			...randomProFeatures,
			...randomBusinessFeatures,
		];
	} else if ( currentPlan === 'pro' ) {
		// For Pro plan: Display 3 random Business features
		const randomBusinessFeatures = getRandomFeatures(
			allFeatures.filter( ( feature ) => feature.plan === 'business' ),
			3
		);

		return [ ...freeFeatures, ...randomBusinessFeatures ];
	}
	// Default case (free plan, business plan, or any other):

	// Get 1 random starter feature
	const randomStarterFeatures = getRandomFeatures(
		allFeatures.filter( ( feature ) => feature.plan === 'starter' ),
		1
	);

	// Get 1 random pro feature
	const randomProFeatures = getRandomFeatures(
		allFeatures.filter( ( feature ) => feature.plan === 'pro' ),
		1
	);

	// Get 1 random business feature
	const randomBusinessFeatures = getRandomFeatures(
		allFeatures.filter( ( feature ) => feature.plan === 'business' ),
		1
	);

	return [
		...freeFeatures,
		...randomStarterFeatures,
		...randomProFeatures,
		...randomBusinessFeatures,
	];
};

const PremiumFeatures = () => {
	const [ , actions ] = useOnboardingState();
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	// Get current active pro version if available
	const currentProVersion =
		( srfm_admin?.is_pro_active && srfm_admin?.pro_plugin_name ) || '';
	const hasProVersion = currentProVersion.includes( 'SureForms' );

	// Extract plan name if pro version exists
	const [ currentPlan, setCurrentPlan ] = useState( '' );

	// State to track if component is ready to render
	const [ isReady, setIsReady ] = useState( false );

	// State to store the filtered features - will be set only once
	const [ stableFilteredFeatures, setStableFilteredFeatures ] = useState(
		[]
	);

	// State to track selected features
	const [ selectedFeatures, setSelectedFeatures ] = useState( {} );

	// State to track copy status
	const [ isCopied, setIsCopied ] = useState( false );

	useEffect( () => {
		if ( hasProVersion ) {
			// Extract plan name from "SureForms <plan name>"
			const planMatch = currentProVersion.match( /SureForms\s+(.*)/ );
			if ( planMatch && planMatch[ 1 ] ) {
				const plan = planMatch[ 1 ].trim().toLowerCase();
				setCurrentPlan( plan );

				// Check if plan has changed from stored plan
				const storedPlan = getFromLocalStorage( PLAN_STORAGE_KEY, '' );
				if ( plan !== storedPlan ) {
					// Clear stored features if plan changed
					localStorage.removeItem( FEATURES_STORAGE_KEY );
					localStorage.removeItem( SELECTIONS_STORAGE_KEY );
					saveToLocalStorage( PLAN_STORAGE_KEY, plan );
				}
			}
		}
		// Mark component as ready to render after plan is determined
		setIsReady( true );
	}, [ currentProVersion, hasProVersion ] );

	// Load or generate filtered features when component is ready
	useEffect( () => {
		if ( isReady ) {
			// Try to load features from localStorage
			const storedFeatures = getFromLocalStorage(
				FEATURES_STORAGE_KEY,
				null
			);

			if (
				storedFeatures &&
				Array.isArray( storedFeatures ) &&
				storedFeatures.length > 0
			) {
				// Use stored features if available
				setStableFilteredFeatures( storedFeatures );
			} else {
				// Generate new features if not available in storage
				// Generate features based on current plan
				const features = generateFeatures( currentPlan );

				// Save to localStorage for persistence
				saveToLocalStorage( FEATURES_STORAGE_KEY, features );

				// Update state
				setStableFilteredFeatures( features );
			}

			// Load stored selections if available
			const storedSelections = getFromLocalStorage(
				SELECTIONS_STORAGE_KEY,
				null
			);
			if ( storedSelections && typeof storedSelections === 'object' ) {
				setSelectedFeatures( storedSelections );
			}
		}
	}, [ isReady, currentPlan ] );

	// Initialize selected features when features are loaded
	useEffect( () => {
		if ( isReady && stableFilteredFeatures.length > 0 ) {
			// Only initialize if we don't have stored selections
			if ( Object.keys( selectedFeatures ).length === 0 ) {
				const initialState = {};

				stableFilteredFeatures.forEach( ( feature ) => {
					// check free features
					if ( feature.type === 'free' ) {
						initialState[ feature.id ] = true;
					}
				} );

				setSelectedFeatures( initialState );
				saveToLocalStorage( SELECTIONS_STORAGE_KEY, initialState );
			}
		}
	}, [ isReady, stableFilteredFeatures, currentPlan, selectedFeatures ] );

	// Check if any premium feature is selected
	const hasSelectedPremiumFeatures = useMemo( () => {
		return stableFilteredFeatures
			.filter( ( feature ) => feature.type !== 'free' ) // Consider features without type as premium
			.some( ( feature ) => selectedFeatures[ feature.id ] );
	}, [ stableFilteredFeatures, selectedFeatures ] );

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
					const feature = featureMap[ featureId ];
					return isSelected && feature && feature.type !== 'free';
				} )
				.map( ( [ featureId ] ) => featureId );

			// Only save selected features if user clicks Upgrade
			// Use Set to ensure uniqueness of feature IDs
			actions.setSelectedPremiumFeatures( [
				...new Set( selectedFeatureIds ),
			] );

			if ( showUpgradeButton ) {
				// Open pricing page
				window.open(
					addQueryParam(
						srfm_admin?.sureforms_pricing_page,
						'onboarding_premium_features'
					),
					'_blank'
				);
			}
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

		// Clear storage when skipping
		actions.clearStorage();

		// Navigate to next route
		navigateToNextRoute();
	};

	// Function to handle checkbox changes
	const handleCheckboxChange = ( featureId, type ) => {
		// Don't allow unchecking free features
		if ( type === 'free' ) {
			return;
		}

		const newSelections = {
			...selectedFeatures,
			[ featureId ]: ! selectedFeatures[ featureId ],
		};

		setSelectedFeatures( newSelections );
		saveToLocalStorage( SELECTIONS_STORAGE_KEY, newSelections );
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

	// Determine the highest plan required based on selected features
	const getHighestPlanRequired = () => {
		let highestPlan = 'starter'; // Default to starter if any premium feature is selected

		// Check selected features to determine highest plan needed
		stableFilteredFeatures.forEach( ( feature ) => {
			if ( selectedFeatures[ feature.id ] && feature.type !== 'free' ) {
				if ( feature.plan === 'business' ) {
					highestPlan = 'business';
				} else if (
					feature.plan === 'pro' &&
					highestPlan !== 'business'
				) {
					highestPlan = 'pro';
				}
			}
		} );

		return highestPlan;
	};

	// Get the display name for the plan
	const getPlanDisplayName = ( plan ) => {
		const planNames = {
			starter: __( 'Starter', 'sureforms' ),
			pro: __( 'Pro', 'sureforms' ),
			business: __( 'Business', 'sureforms' ),
		};
		return `SureForms ${
			planNames[ plan ] || __( 'Premium', 'sureforms' )
		}`;
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

			{ isReady && stableFilteredFeatures.length > 0 && (
				<>
					<div>
						{ stableFilteredFeatures.map( ( feature, index ) => (
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
								{ index < stableFilteredFeatures.length - 1 && (
									<Divider className="m-2 w-auto" />
								) }
							</div>
						) ) }
					</div>
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
							...( showUpgradeButton && {
								icon: <ExternalLink />,
								className: '[&>svg]:size-4',
							} ),
						} }
						skipProps={ {
							onClick: handleSkip,
							text: __( 'Skip', 'sureforms' ),
						} }
					/>

					{ showUpgradeButton && (
						<div className="flex items-center justify-between p-3 gap-2 relative ring-1 rounded-lg ring-alert-border-neutral bg-background-secondary shadow-none">
							<div className="flex items-center justify-between">
								<span className="text-text-primary text-sm [&_*]:text-sm leading-5 [&_*]:leading-5 font-normal [word-break:break-word] inline px-1 mr-4">
									{ ( () => {
										const translatedText = sprintf(
											/* translators: 1: Plan name (Starter, Pro, or Business), 2: Coupon code */
											__(
												'Selected features require %1$s - use code %2$s to get 10% off on any plan.',
												'sureforms'
											),
											'%1$s',
											'%2$s'
										);
										const planName = getPlanDisplayName(
											getHighestPlanRequired()
										);

										const parts = translatedText
											.split( '%1$s' )
											.flatMap( ( part, index ) => {
												if ( index === 0 ) {
													return [ part ];
												}
												const [
													beforeCoupon,
													afterCoupon,
												] = part.split( '%2$s' );
												return [
													<strong key="plan">
														{ planName }
													</strong>,
													beforeCoupon,
													<strong key="coupon">
														{ COUPON_CODE }
													</strong>,
													afterCoupon,
												];
											} );
										return parts;
									} )() }
								</span>
								<button
									className="outline-1 border-none cursor-pointer transition-colors duration-300 ease-in-out text-xs font-semibold focus:ring-toggle-on disabled:text-text-disabled rounded [&>svg]:size-4 outline-none bg-transparent hover:underline p-0 border-0 leading-none focus:ring-0 focus:ring-offset-0 ring-offset-0 focus:outline-none text-button-primary border-button-primary hover:border-button-primary hover:text-button-primary-hover"
									onClick={ () => {
										const copyToClipboard = async () => {
											try {
												await navigator.clipboard.writeText(
													COUPON_CODE
												);
												console.log(
													'Coupon code copied to clipboard'
												);
												setIsCopied( true );
												setTimeout(
													() => setIsCopied( false ),
													1000
												);
											} catch ( err ) {
												console.error(
													'Failed to copy using Clipboard API:',
													err
												);
												try {
													const textArea =
														document.createElement(
															'textarea'
														);
													textArea.value =
														COUPON_CODE;
													textArea.style.position =
														'fixed'; // prevent scrolling to bottom
													textArea.style.opacity =
														'0';
													document.body.appendChild(
														textArea
													);
													textArea.focus();
													textArea.select();
													document.execCommand(
														'copy'
													);
													document.body.removeChild(
														textArea
													);
													console.log(
														'Fallback: Coupon code copied using execCommand'
													);
													setIsCopied( true );
													setTimeout(
														() =>
															setIsCopied(
																false
															),
														1000
													);
												} catch ( fallbackErr ) {
													console.error(
														'Fallback copy failed:',
														fallbackErr
													);
												}
											}
										};
										copyToClipboard();
									} }
								>
									<span className="px-1">
										{ isCopied
											? __( 'Copied', 'sureforms' )
											: __( 'Copy', 'sureforms' ) }
									</span>
								</button>
							</div>
						</div>
					) }
				</>
			) }
		</div>
	);
};

export default PremiumFeatures;
