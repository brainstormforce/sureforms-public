import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import ZapierIcon from '@Image/zapier.js';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';

const Zapier = () => {
    
    const showZapDetails = false;
	const zapierDetails = applyFilters(
		'srfm.global_settings.integrations.zapier',
		showZapDetails
	);

	return (
		<>
			<div className="srfm-integration-content">
				<div className="srfm-integration-content-inner">
                    <ZapierIcon />
					<div>
						<div className="srfm-integration-title">
							{ __( 'Zapier', 'sureforms' ) }
						</div>
						<p className="srfm-integration-description">
							{ __( 'Broadcast your SureForms submission to Zapier', 'sureforms' ) }
						</p>
					</div>
				</div>
			</div>
			<div className="srfm-integration-cta">
                { false === zapierDetails ? <UpgradeToProButton className="srfm-button-secondary srfm-button-xs" /> : zapierDetails }
				<div className="srfm-pro-badge">
					{ __( 'Pro', 'sureforms' ) }
                </div>
			</div>
		</> );
};

export default Zapier;
