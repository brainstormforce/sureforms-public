import { __ } from '@wordpress/i18n';
import { applyFilters, addFilter } from '@wordpress/hooks';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { WebhookIcon } from 'lucide-react';
import IntegrationCard from '@Admin/settings/components/integrations/Card';

const WebhooksCard = ( { setSelectedTab } ) => {
	const primaryButton = applyFilters(
		'srfm.formSettings.integrations.webhooks.button',
		<UpgradeToProButton
			location="setting_editor_integration_webhooks"
			size="xs"
		/>,
		setSelectedTab
	);
	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<WebhookIcon className="size-6 text-icon-primary" />
				{ ! srfm_admin?.is_pro_active && (
					<PremiumBadge
						tooltipHeading={ __(
							'Unlock Webhooks',
							'sureforms'
						) }
						tooltipContent={ __(
							'With the SureForms Starter Plan, you can enable webhooks to seamlessly connect with your favorite apps for real-time data transfer and automation.',
							'sureforms'
						) }
						tooltipPosition={ 'bottom' }
						utmMedium={ 'setting_editor_integration_webhooks' }
					/>
				) }
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title
					title={ __( 'Webhooks', 'sureforms' ) }
				/>
				<IntegrationCard.Description
					description={ __(
						'Effortlessly broadcast your SureForms Submission to any web API endpoint with the advanced webhook module.',
						'sureforms'
					) }
				/>
				<IntegrationCard.CTA>
					{ primaryButton }
				</IntegrationCard.CTA>
			</IntegrationCard.Content>
		</IntegrationCard>
	);
};

addFilter(
	'srfm.formSettings.integrations.cards',
	'sureforms/form-settings/integrations',
	( tabs, setSelectedTab ) => {
		const cards = [ ...tabs ];
		const isGloballyEnabled = applyFilters(
			'srfm.formSettings.integrations.webhooks.component',
			true
		);

		if ( ! isGloballyEnabled ) {
			return cards;
		}
		cards.push( {
			id: 'webhooks',
			parent: 'integrations',
			title: __( 'Webhooks', 'sureforms' ),
			component: <WebhooksCard setSelectedTab={ setSelectedTab } />,
		} );
		return cards;
	}
);
