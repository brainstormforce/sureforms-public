import { Container, Text, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import { Divider } from './components';
import NavigationButtons from './navigation-buttons';
import apiFetch from '@wordpress/api-fetch';

const features = [
	__(
		'Use your AI credits to create forms by writing prompts.',
		'sureforms'
	),
	__(
		'Create forms like conversational, calculation-based, or multi-step.',
		'sureforms'
	),
	__(
		'Manage submissions and track form performance in one place.',
		'sureforms'
	),
];

const Done = () => {
	const handleBuildForm = () => {
		// Complete onboarding and redirect to create new form page
		apiFetch( {
			path: '/sureforms/v1/onboarding/set-status',
			method: 'POST',
		} ).then( () => {
			window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form`;
		} );
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
							"SureForms is ready to go. Start building your first form, whether it's a simple form, calculator, or conversational form.",
							'sureforms'
						) }
					</Text>
				</div>
			</Container>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __( 'What You Can Do Next:', 'sureforms' ) }
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
				continueProps={ {
					onClick: handleBuildForm,
					text: __( 'Build Your First Form', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default Done;
