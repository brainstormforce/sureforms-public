import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Divider, Header, FeatureList } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { useOnboardingState } from '../onboarding-state';
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
				window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form`;
			} );
		}, 100 );
	};

	return (
		<div className="space-y-4">
			<Header
				title={ __( "You're All Set! 🚀", 'sureforms' ) }
				description={ __(
					'Try AI if you want a quick head start or start from scratch if you have a clear idea in mind. Forms are ready to be created, shared, and connected to your audience.',
					'sureforms'
				) }
			/>

			<FeatureList
				heading={ __(
					'Final Touches That Make a Difference:',
					'sureforms'
				) }
				items={ features }
			/>

			<Divider />

			<NavigationButtons
				containerProps={ { justify: 'start' } }
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
