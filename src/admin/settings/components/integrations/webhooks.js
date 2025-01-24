import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import WebhookIcon from '@Image/webhook.js';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';

const Webhooks = () => {
	const showSwitch = false;
	const switchWebhook = applyFilters(
		'srfm.global_settings.integrations.webhook',
		showSwitch
	);

	return (
		<>
			<div className="srfm-integration-content">
				<div className="srfm-integration-content-inner">
					<WebhookIcon />
					<div>
						<div className="srfm-integration-title">
							{ __( 'Webhooks', 'sureforms' ) }
						</div>
						<p className="srfm-integration-description">
							{ __( 'Broadcast your SureForms submission to any web API endpoint with the powerful webhook module.', 'sureforms' ) }
						</p>
					</div>
				</div>
			</div>
			<div className="srfm-integration-cta">
				{ false === switchWebhook ? <UpgradeToProButton className="srfm-button-secondary srfm-button-xs" location="settings_integrations_webhooks" /> : switchWebhook }
				{ false === switchWebhook && (
					<PremiumBadge
						badgeName={ 'Starter' }
						tooltipHeading={ __( 'Unlock Webhooks', 'sureforms' ) }
						tooltipContent={ __(
							'With the SureForms Starter Plan, you can enable webhooks to seamlessly connect with your favorite apps for real-time data transfer and automation.',
							'sureforms'
						) }
						tooltipPosition={ 'bottom' }
						utmMedium={ 'global_integration_settings_webhooks' }
					/>
				) }
			</div>
		</> );
};

export default Webhooks;
