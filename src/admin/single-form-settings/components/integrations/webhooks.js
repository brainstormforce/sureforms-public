import { __ } from '@wordpress/i18n';
import { applyFilters, addFilter } from '@wordpress/hooks';
import WebhookIconNew from '@Image/webhook_new.js';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';

const WebhooksCard = ( { setSelectedTab } ) => {
	const primaryButton = applyFilters(
		'srfm.formSettings.integrations.webhooks.button',
		<UpgradeToProButton className="srfm-button-primary" location="setting_editor_integration_webhooks" />,
		setSelectedTab
	);
	return (
		<>
			<div className="srfm-modal-card-content-webhook">
				<div className="srfm-modal-card-content-inner">
					<div className="srfm-modal-card-wrapper">
						<WebhookIconNew />
						{ ! srfm_admin?.is_pro_active && (
							<PremiumBadge
								badgeName={ 'Starter' }
								tooltipHeading={ __( 'Unlock Webhooks', 'sureforms' ) }
								tooltipContent={ __(
									'With the SureForms Starter Plan, you can enable webhooks to seamlessly connect with your favorite apps for real-time data transfer and automation.',
									'sureforms'
								) }
								tooltipPosition={ 'bottom' }
								utmMedium={ 'setting_editor_integration_webhooks' }
							/>
						) }
					</div>

					<div className="srfm-modal-card-title">
						{ __( 'Webhooks', 'sureforms' ) }
					</div>

					<p className="srfm-modal-card-description">
						{ __(
							'Effortlessly broadcast your SureForms Submission to any web API endpoint with the advanced webhook module.',
							'sureforms'
						) }
					</p>
				</div>

				<div className="srfm-button">{ primaryButton }</div>
			</div>
		</>
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
