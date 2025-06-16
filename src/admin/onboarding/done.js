import { Button, Container, Text, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ExternalLink } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

// Import logo or use existing icon if available
// If WelcomeImage is not available in SureForms, you can use an appropriate icon from the plugin
// For this example, I'll assume there's a similar component or we can use a placeholder
import ICONS from '@Admin/components/template-picker/components/icons';

const features = [
	[
		__( 'Modern form builder:', 'sureforms' ),
		__( 'Create beautiful, responsive forms with ease!', 'sureforms' ),
	],
	[
		__( 'Easy management:', 'sureforms' ),
		__( 'View and manage all your form submissions in one place', 'sureforms' ),
	],
	[
		__( 'Powerful integrations:', 'sureforms' ),
		__(
			'Connect your forms with other services and automate your workflow',
			'sureforms'
		),
	],
];

const Done = () => {
	const navigate = useNavigate();

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
							"You've successfully set up SureForms, and your site is ready to create amazing forms! Now you can focus on your business and let us handle the rest.",
							'sureforms'
						) }
					</Text>
				</div>
				<div className="w-full h-full max-w-32 mx-auto">
					{ICONS.logo}
				</div>
			</Container>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __(
						"Here's What SureForms Will Do for You Now:",
						'sureforms'
					) }
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

			<hr className="border-t border-border-subtle my-4" />

			<Container align="start" className="h-auto gap-3">
				<Button 
					onClick={ handleBuildForm }
					icon={ <ExternalLink /> }
					iconPosition="right"
				>
					{ __( 'Build Your Form', 'sureforms' ) }
				</Button>
				<Button
					variant="ghost"
					onClick={ handleGoToDashboard }
				>
					{ __( 'Go To Dashboard', 'sureforms' ) }
				</Button>
			</Container>
		</div>
	);
};

export default Done;
