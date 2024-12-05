import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import ZapierIcon from '@Image/zapier.png';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';

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
							height: '50px',
						} } alt="Zapier" />
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
				{ false === zapierDetails ? <UpgradeToProButton className="srfm-button-secondary srfm-button-xs" /> : zapierDetails }
				<div className="srfm-pro-badge">
					{ __( 'Pro', 'sureforms' ) }
				</div>
			</div>
		</> );
};

export default Zapier;
