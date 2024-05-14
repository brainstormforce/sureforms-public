import { __ } from '@wordpress/i18n';
import { applyFilters, addFilter } from '@wordpress/hooks';
import WebhookIcon from '@Image/webhook.js';

const UpgradeToPro = () => {
	return (
		<button className="srfm-button-primary"	>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

const WebhooksCard = ( { setSelectedTab } ) => {
	const primaryButton = applyFilters(
		'srfm.form_settings.integrations.webhooks.button',
		<UpgradeToPro />,
		setSelectedTab
	);
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

addFilter(
	'srfm.form_settings.integrations.cards',
	'sureforms/form-settings/integrations',
	( tabs, setSelectedTab ) => {
		const cards = [ ...tabs ];
		const isGloballyEnabled = applyFilters(
			'srfm.form_settings.integrations.webhooks.component',
			true
		);

		if ( ! isGloballyEnabled ) {
			return cards;
		}
		cards.push(
			{
				id: 'webhooks',
				parent: 'integrations',
				title: __( 'Webhooks', 'sureforms' ),
				component: <WebhooksCard
					setSelectedTab={ setSelectedTab }
				/>,
			},
		);
		return cards;
	}

);
