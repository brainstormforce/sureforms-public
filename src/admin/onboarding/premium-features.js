import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Crown } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import NavigationButtons from './navigation-buttons';
import { Header, Divider } from './components'

const premiumFeatures = [
	{
		id: 'advanced-fields',
		title: __( 'Advanced Form Fields', 'sureforms' ),
		description: __( 'Access premium field types like file uploads, date pickers, signature fields, and more.', 'sureforms' ),
		icon: '📝',
	},
	{
		id: 'conditional-logic',
		title: __( 'Conditional Logic', 'sureforms' ),
		description: __( 'Show or hide fields based on user responses to create dynamic, intelligent forms.', 'sureforms' ),
		icon: '🔀',
	},
	{
		id: 'payment-integration',
		title: __( 'Payment Integration', 'sureforms' ),
		description: __( 'Accept payments through Stripe, PayPal, and other popular payment gateways.', 'sureforms' ),
		icon: '💳',
	},
	{
		id: 'advanced-styling',
		title: __( 'Advanced Styling', 'sureforms' ),
		description: __( 'Custom CSS, advanced themes, and complete design control for your forms.', 'sureforms' ),
		icon: '🎨',
	},
	{
		id: 'multi-step-forms',
		title: __( 'Multi-Step Forms', 'sureforms' ),
		description: __( 'Break long forms into multiple steps to improve user experience and completion rates.', 'sureforms' ),
		icon: '📊',
	},
	{
		id: 'advanced-integrations',
		title: __( 'Advanced Integrations', 'sureforms' ),
		description: __( 'Connect with CRM systems, email marketing tools, and hundreds of third-party services.', 'sureforms' ),
		icon: '🔗',
	},
	{
		id: 'form-analytics',
		title: __( 'Form Analytics', 'sureforms' ),
		description: __( 'Detailed insights, conversion tracking, and performance analytics for your forms.', 'sureforms' ),
		icon: '📈',
	},
	{
		id: 'priority-support',
		title: __( 'Priority Support', 'sureforms' ),
		description: __( 'Get priority email support and access to our premium support team.', 'sureforms' ),
		icon: '🎧',
	},
];

const PremiumFeatures = () => {
	const { navigateToPreviousRoute } = useOnboardingNavigation();

	const handleCreateForm = async () => {
		// Complete onboarding and redirect to create new form
		await wp.apiFetch( {
			path: '/sureforms/v1/onboarding/set-status',
			method: 'POST',
			headers: {
				'X-WP-Nonce': srfm_admin.nonce,
			},
		} );
		
		// Redirect to create new form page
		window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form`;
	};

	const handleBack = async () => {
		navigateToPreviousRoute();
	};

	const handleUpgradeToPro = () => {
		window.open( 'https://sureforms.com/pricing/', '_blank', 'noopener,noreferrer' );
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Unlock Premium Features', 'sureforms' ) }
				description={ __( 'Take your forms to the next level with SureForms Pro and access powerful premium features.', 'sureforms' ) }
			/>

			<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
				{ premiumFeatures.map( ( feature ) => (
					<div
						key={ feature.id }
						className="p-4 border border-border-subtle rounded-lg bg-white hover:shadow-md transition-shadow"
					>
						<div className="flex items-start gap-3">
							<div className="text-2xl flex-shrink-0">{ feature.icon }</div>
							<div>
								<Text size={ 16 } weight={ 600 } color="primary">
									{ feature.title }
								</Text>
								<Text size={ 14 } color="secondary" className="mt-1">
									{ feature.description }
								</Text>
							</div>
						</div>
					</div>
				) ) }
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleCreateForm,
					text: __( 'Create Your First Form', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default PremiumFeatures;
