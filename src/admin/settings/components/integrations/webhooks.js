import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import WebhookIcon from '@Image/webhook.js';

const UpgradeToPro = () => {
	return (
		<button className="srfm-button-secondary srfm-button-xs">Upgrade to Pro</button>
	);
};

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
							{ __( 'Broadcast your SureForms Submission to any web API endpoint with the powerful webhook module.', 'sureforms' ) }
						</p>
					</div>
				</div>
			</div>
			<div></div>
			<div className="srfm-integration-cta">
				{ false === switchWebhook ? <UpgradeToPro /> : switchWebhook }
				<div className="srfm-pro-badge">Pro</div>
			</div>
		</> );
};

export default Webhooks;
