import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import ZapierIcon from '@Image/zapier.jpeg';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';

const Zapier = () => {
	const zapierDetails = applyFilters(
		'srfm.global_settings.integrations.zapier',
		false
	);

	return (
		<>
			<div className="srfm-integration-content">
				<div className="srfm-integration-content-inner">
					<div className="srfm-integration-icon">
						<img src={ ZapierIcon } style={ {
							width: '50px',
							borderRadius: '10px',
							height: '50px',
						} } alt={ __( 'Zapier', 'sureforms' ) } />
					</div>
					<div>
						<div className="srfm-integration-title">
							{ __( 'Zapier', 'sureforms' ) }
						</div>
						<p className="srfm-integration-description">
							{ __( 'SureForms and Zapier Integration allows you to connect your WordPress forms with a number of web apps.', 'sureforms' ) }
						</p>
					</div>
				</div>
			</div>
			<div className="srfm-integration-cta">
				{ false === zapierDetails ? <UpgradeToProButton className="srfm-button-secondary srfm-button-xs" location="settings_integrations_zapier" /> : zapierDetails }
				{ false === zapierDetails && (
					<PremiumBadge
						tooltipHeading={ __( 'Unlock Zapier Integration', 'sureforms' ) }
						tooltipContent={ __(
							'With the SureForms Pro Plan, you can enable Zapier integration to seamlessly connect with thousands of apps for powerful workflow automation.',
							'sureforms'
						) }
						tooltipPosition={ 'bottom' }
						utmMedium={ 'global_integration_settings_zapier' }
					/>
				) }
			</div>
		</> );
};

export default Zapier;
