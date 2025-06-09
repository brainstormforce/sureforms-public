import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { WebhookIcon } from 'lucide-react';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';
import IntegrationCard from './Card';

const Webhooks = () => {
	const showSwitch = false;
	const switchWebhook = applyFilters(
		'srfm.global_settings.integrations.webhook',
		showSwitch
	);

	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<div className="inline-grid place-items-center">
					<WebhookIcon className="size-6 text-icon-primary" />
				</div>
				<div className="flex items-center gap-2">
					{ false === switchWebhook && (
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
							utmMedium={ 'global_integration_settings_webhooks' }
						/>
					) }
					{ false !== switchWebhook && switchWebhook }
				</div>
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title
					title={ __( 'Webhooks', 'sureforms' ) }
				/>
				<IntegrationCard.Description
					description={ __(
						'Broadcast your SureForms submission to any web API endpoint with the powerful webhook module.',
						'sureforms'
					) }
				/>
			</IntegrationCard.Content>
			{ false === switchWebhook && (
				<IntegrationCard.CTA>
					<UpgradeToProButton
						size="xs"
						location="settings_integrations_webhooks"
					/>
				</IntegrationCard.CTA>
			) }
		</IntegrationCard>
	);
};

export default Webhooks;
