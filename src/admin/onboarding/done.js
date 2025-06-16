import { Button, Container, Text, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ChevronRight } from 'lucide-react';
import ICONS from '@Admin/components/template-picker/components/icons';
import { Divider } from './components';

const features = [
	[ __( 'Generate forms instantly with AI.', 'sureforms' ), '' ],
	[ __( 'Manage your entries easily in one place.', 'sureforms' ), '' ],
	[ __( 'Monitor form submissions from your dashboard.', 'sureforms' ), '' ],
];

const Done = () => {
	const handleBuildForm = () => {
		// Complete onboarding and redirect to create new form page
		wp.apiFetch( {
			path: '/sureforms/v1/onboarding/set-status',
			method: 'POST',
			headers: {
				'X-WP-Nonce': srfm_admin.nonce,
			},
		} ).then( () => {
			window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form`;
		} );
	};

	const handleGoToDashboard = () => {
		// Complete onboarding and redirect to dashboard
		wp.apiFetch( {
			path: '/sureforms/v1/onboarding/set-status',
			method: 'POST',
			headers: {
				'X-WP-Nonce': srfm_admin.nonce,
			},
		} ).then( () => {
			window.location.href = srfm_admin.sureforms_dashboard_url;
		} );
	};

	return (
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2 max-w-[22.5rem]">
					<Title
						tag="h3"
						title={ __( "You're Good to Go! 🚀", 'sureforms' ) }
						size="lg"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'Setup complete! SureForms is now ready to use. Build your first form visually, get instant feedback, and grow your website faster.',
							'sureforms'
						) }
					</Text>
				</div>
				<div className="w-full h-full max-w-32 mx-auto">
					{ ICONS.onboardingDoneScreen }
				</div>
			</Container>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __( "What's Next:", 'sureforms' ) }
				</Text>
				{ features.map( ( feature, index ) => (
					<Container
						key={ index }
						className="flex items-center gap-1.5"
					>
						<CheckIcon className="size-4 text-icon-interactive" />
						<Text size={ 14 } weight={ 400 } color="label">
							<Text as="b" weight={ 500 }>
								{ feature[ 0 ] }{ ' ' }
							</Text>
							{ feature[ 1 ] }
						</Text>
					</Container>
				) ) }
			</div>

			<Divider />

			<Container align="start" className="h-auto gap-3">
				<Button
					onClick={ handleBuildForm }
					icon={ <ChevronRight /> }
					iconPosition="right"
				>
					{ __( 'Build Your Form', 'sureforms' ) }
				</Button>
				<Button variant="ghost" onClick={ handleGoToDashboard }>
					{ __( 'Go To Dashboard', 'sureforms' ) }
				</Button>
			</Container>
		</div>
	);
};

export default Done;
