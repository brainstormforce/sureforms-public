import { __ } from '@wordpress/i18n';
import { useEffect, useLayoutEffect, useState } from '@wordpress/element';
import { useLocation, useNavigate, Outlet } from 'react-router-dom';
import { Topbar, ProgressSteps, Button } from '@bsf/force-ui';
import { XIcon } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import {
	OnboardingProvider,
	useOnboardingState,
	clearOnboardingStorage,
} from './onboarding-state';
import apiFetch from '@wordpress/api-fetch';
import ICONS from '@Admin/components/template-picker/components/icons';

const NavBar = () => {
	const { getCurrentStepNumber, hasBusinessPlan, isUserConnected } = useOnboardingNavigation();
	const [ onboardingState, actions ] = useOnboardingState();
	const location = useLocation();
	const [ isExiting, setIsExiting ] = useState( false );

	// Determine total steps based on plan and connection status
	let totalSteps = 4; // Base steps: welcome, connect, email-delivery, premium-features, done
	
	if ( isUserConnected() ) {
		totalSteps -= 1; // Skip connect step
	}
	
	if ( hasBusinessPlan() ) {
		totalSteps -= 1; // Skip premium features step
	}

	// Function to handle exit with proper state updates
	const handleExit = () => {
		// Prevent multiple clicks
		if ( isExiting ) {
			return;
		}
		setIsExiting( true );

		// Mark as exited early
		actions.setExitedEarly( true );

		// If exiting from premium features screen, clear selected features
		if ( location.pathname === '/onboarding/premium-features' ) {
			actions.setSelectedPremiumFeatures( [] );
		}

		// Clear all onboarding storage data
		actions.clearStorage();

		// Run the sureforms_dismiss_pointer AJAX action to properly dismiss the pointer
		apiFetch( {
			url: srfm_admin?.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type':
					'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: new URLSearchParams( {
				action: 'sureforms_dismiss_pointer',
				pointer_nonce: srfm_admin?.pointer_nonce,
			} ).toString(),
		} );

		// Use setTimeout to ensure state updates are processed
		setTimeout( () => {
			// Complete onboarding and save analytics data
			apiFetch( {
				path: '/sureforms/v1/onboarding/set-status',
				method: 'POST',
				data: {
					completed: 'yes',
					analyticsData: {
						...onboardingState.analytics,
						exitedEarly: true,
						completed: false,
					},
				},
			} ).then( () => {
				window.location.href = srfm_admin.sureforms_dashboard_url;
			} );
		}, 100 );
	};

	return (
		<Topbar className="p-5 bg-background-secondary">
			<Topbar.Left>
				<Topbar.Item>{ ICONS.logo }</Topbar.Item>
			</Topbar.Left>
			<Topbar.Middle align="center">
				<Topbar.Item className="md:block hidden">
					<ProgressSteps
						completedVariant="number"
						currentStep={ getCurrentStepNumber() }
						size="md"
						type="inline"
						variant="number"
						lineClassName="w-[128px]"
					>
						{ Array.from( { length: totalSteps }, ( _, index ) => (
							<ProgressSteps.Step key={ index } size="md" />
						) ) }
					</ProgressSteps>
				</Topbar.Item>
			</Topbar.Middle>
			<Topbar.Right>
				<Topbar.Item>
					<Button
						className="no-underline"
						onClick={ handleExit }
						icon={ <XIcon /> }
						size="xs"
						variant="ghost"
						iconPosition="right"
						disabled={ isExiting }
					>
						{ __( 'Exit Guided Setup', 'sureforms' ) }
					</Button>
				</Topbar.Item>
			</Topbar.Right>
		</Topbar>
	);
};

const NavigationGuard = () => {
	const location = useLocation();
	const navigate = useNavigate();
	const { checkRequiredStep } = useOnboardingNavigation();

	// Check if the user is authorized to access this step
	useLayoutEffect( () => {
		const redirectUrl = checkRequiredStep();
		if ( redirectUrl ) {
			navigate( redirectUrl, { replace: true } );
		}
	}, [ location.pathname, checkRequiredStep, navigate ] );

	return null;
};

// Inner component that uses onboarding state
const OnboardingContent = () => {
	const location = useLocation();
	const [ , actions ] = useOnboardingState();

	const widthClassNames = {
		1: 'max-w-xl', // 560px converted to rem (assuming 1rem = 16px)
		2: 'max-w-2xl', // 750px converted to rem (assuming 1rem = 16px)
	};

	const widthClassKey = location.pathname === '/onboarding/welcome' ? 1 : 2;

	// Add body class for onboarding-specific styles
	useEffect( () => {
		document.body.classList.add( 'sureforms-onboarding-page' );

		return () => {
			document.body.classList.remove( 'sureforms-onboarding-page' );
		};
	}, [] );

	// Clear when on the done page
	useEffect( () => {
		if ( location.pathname === '/onboarding/done' ) {
			actions.clearStorage();
		}
	}, [ location.pathname, actions ] );

	return (
		<div className="bg-background-secondary h-full space-y-7 pb-10">
			{ /* Header */ }
			<NavBar />
			{ /* Content */ }
			<div className="p-7 w-full h-full">
				<div
					className={ `w-full h-full border-0.5 border-solid border-border-subtle bg-background-primary shadow-sm rounded-xl mx-auto p-7 ${ widthClassNames[ widthClassKey ] }` }
				>
					<Outlet />
				</div>
			</div>
		</div>
	);
};

// Main layout component that provides the context
const OnboardingLayout = () => {
	// Clear session storage when unmounting the entire layout
	useEffect( () => {
		return () => {
			// Use the imported clearOnboardingStorage directly
			clearOnboardingStorage();
		};
	}, [] );

	return (
		<OnboardingProvider>
			{ /* Navigation guard to check required state for each step */ }
			<NavigationGuard />
			<OnboardingContent />
		</OnboardingProvider>
	);
};

export default OnboardingLayout;
