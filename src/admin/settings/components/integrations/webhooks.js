import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import WebhookIcon from '@Image/webhook.js';

const UpgradeToPro = () => {
	return (
		<button onClick={
			() => {
				window.open( 'https://sureforms.com/pricing' );
			}
		} className="srfm-button-secondary srfm-button-xs">
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
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
							{ __( 'Broadcast your SureForms submission to any web API endpoint with the powerful webhook module.', 'sureforms' ) }
						</p>
					</div>
				</div>
			</div>
			<div className="srfm-integration-cta">
				{ false === switchWebhook ? <UpgradeToPro /> : switchWebhook }
				<div className="srfm-pro-badge">
					{ __( 'Pro', 'sureforms' ) }</div>
			</div>
		</> );
};

export default Webhooks;
