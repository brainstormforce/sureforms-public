import { Container, Text, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import { useState } from '@wordpress/element';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { useOnboardingState } from '../onboarding-state';
import { useOnboardingNavigation } from '../hooks';
import apiFetch from '@wordpress/api-fetch';

const features = [
	__( "Style your form to better match your site's design", 'sureforms' ),
	__(
		'Set up confirmation messages and email notifications for each submission',
		'sureforms'
	),
	__( 'Add spam protection to block common bot submissions', 'sureforms' ),
	__(
		'Get weekly email reports with a summary of form activity',
		'sureforms'
	),
];

const Done = () => {
	const [ onboardingState, actions ] = useOnboardingState();
	const { navigateToPreviousRoute } = useOnboardingNavigation();
	const [ isCompleting, setIsCompleting ] = useState( false );

	const handleBuildForm = () => {
		// Prevent multiple clicks
		if ( isCompleting ) {
			return;
		}
		setIsCompleting( true );

		// Mark as completed
		actions.setCompleted( true );

		// Clear all onboarding storage data
		actions.clearStorage();

		// Run the sureforms_accept_cta AJAX action to track user accepting the CTA
		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type':
					'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: new URLSearchParams( {
				action: 'sureforms_accept_cta',
				pointer_nonce: srfm_admin.pointer_nonce,
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
						completed: true,
						exitedEarly: false,
					},
				},
			} ).then( () => {
				window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai`;
			} );
		}, 100 );
	};

	return (
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2">
					<Title
						tag="h3"
						title={ __( "You're All Set! 🚀", 'sureforms' ) }
						size="lg"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'Use our AI form builder to get started quickly, or build your form from scratch if you already know what you need. Your forms are ready to create, share, and connect with your site visitors.',
							'sureforms'
						) }
					</Text>
				</div>
			</Container>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __(
						'Final Touches That Make a Difference:',
						'sureforms'
					) }
				</Text>
				{ features.map( ( feature, index ) => (
					<Container
						key={ index }
						className="flex items-center gap-1.5"
					>
						<Check className="size-4 text-icon-interactive" />
						<Text size={ 14 } weight={ 500 } color="label">
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
					onClick: handleBuildForm,
					text: __( 'Build Your First Form', 'sureforms' ),
					disabled: isCompleting,
				} }
			/>
		</div>
	);
};

export default Done;
