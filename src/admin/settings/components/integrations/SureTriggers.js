import IntegrationCard from './Card';
import SureTriggersIcon from '@Image/suretriggers.svg';
import { __ } from '@wordpress/i18n';
import { Badge, Button } from '@bsf/force-ui';

const SureTriggers = () => {
	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<div className="inline-grid place-items-center">
					<img
						src={ SureTriggersIcon }
						className="size-6"
						alt={ __( 'OttoKit', 'sureforms' ) }
					/>
				</div>
				<div>
					<Badge label={ __( 'Free', 'sureforms' ) } variant="green" disableHover />
				</div>
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title
					title={ __( 'OttoKit', 'sureforms' ) }
				/>
				<IntegrationCard.Description
					description={ __(
						'Effortlessly connect your forms to hundreds of apps, automating tasks like sending entries to your favourite CRM.',
						'sureforms'
					) }
				/>
				<IntegrationCard.CTA>
					<Button size="xs">
						{ __( 'Install & Activate', 'sureforms' ) }
					</Button>
				</IntegrationCard.CTA>
			</IntegrationCard.Content>
		</IntegrationCard>
	);
};

export default SureTriggers;
