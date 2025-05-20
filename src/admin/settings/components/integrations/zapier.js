import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import ZapierIcon from '@Image/zapier.svg';
import UpgradeToProButton from '@Admin/components/UpgradeToProButton';
import PremiumBadge from '@Admin/components/PremiumBadge';
import IntegrationCard from './Card';

const Zapier = () => {
	const zapierDetails = applyFilters(
		'srfm.global_settings.integrations.zapier',
		false
	);

	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<div className="inline-grid place-items-center">
					<img
						src={ ZapierIcon }
						className="w-auto"
						alt={ __( 'Zapier', 'sureforms' ) }
					/>
				</div>
				<div>
					{ false === zapierDetails && (
						<PremiumBadge
							tooltipHeading={ __(
								'Unlock Zapier Integration',
								'sureforms'
							) }
							tooltipContent={ __(
								'With the SureForms Pro Plan, you can enable Zapier integration to seamlessly connect with thousands of apps for powerful workflow automation.',
								'sureforms'
							) }
							tooltipPosition={ 'bottom' }
							utmMedium={ 'global_integration_settings_zapier' }
						/>
					) }
				</div>
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title title={ __( 'Zapier', 'sureforms' ) } />
				<IntegrationCard.Description
					description={ __(
						'SureForms and Zapier Integration allows you to connect your WordPress forms with a number of web apps.',
						'sureforms'
					) }
				/>
				<IntegrationCard.CTA>
					{ false === zapierDetails ? (
						<UpgradeToProButton
							size="xs"
							location="settings_integrations_zapier"
						/>
					) : (
						zapierDetails
					) }
				</IntegrationCard.CTA>
			</IntegrationCard.Content>
		</IntegrationCard>
	);
};

export default Zapier;
