import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import WebhookIcon from '@Image/webhook.js';

const UpgradeToPro = () => {
	return (
		<button className="srfm-button-primary"	>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

const Webhooks = ( { setSelectedTab } ) => {
	const primaryButton = applyFilters(
		'srfm.form_settings.integrations.webhooks.button',
		<UpgradeToPro />,
		setSelectedTab
	);

	const isGloballyEnabled = applyFilters(
		'srfm.form_settings.integrations.webhooks.component',
		true
	);

	if ( ! isGloballyEnabled ) {
		return <></>;
	}
	return (
		<>
			<div className="srfm-modal-card-content">
				<div className="srfm-modal-card-content-inner">
					<WebhookIcon />
					<div>
						<div className="srfm-modal-card-title">
							{ __( 'Webhooks', 'sureforms' ) }
						</div>
						<p className="srfm-modal-card-description">
							{ __( 'Broadcast your SureForms Submission to any web API endpoint with the powerful webhook module.', 'sureforms' ) }
						</p>
					</div>
				</div>
				<div className="srfm-button">
					{ primaryButton }
				</div>

			</div>
		</>
	);
};
export default Webhooks;
