import IntegrationCard from './Card';
import SureTriggersIcon from '@Image/suretriggers.svg';
import { __ } from '@wordpress/i18n';
import { Badge, Button, toast } from '@bsf/force-ui';
import { getPluginStatusText, handlePluginActionTrigger } from '@Utils/Helpers';

const SureTriggers = () => {
	const plugin = srfm_admin?.integrations?.[ 'sure_triggers' ];

	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<div className="inline-grid place-items-center">
					<img
						src={ SureTriggersIcon }
						className="size-6"
						alt={ __( 'OttoKit Logo', 'sureforms' ) }
					/>
				</div>
				<div>
					<Badge
						label={ __( 'Free', 'sureforms' ) }
						variant="green"
						disableHover
					/>
				</div>
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title title={ __( 'OttoKit', 'sureforms' ) } />
				<IntegrationCard.Description
					description={ __(
						'Effortlessly connect your forms to hundreds of apps, automating tasks like sending entries to your favourite CRM.',
						'sureforms'
					) }
				/>
				<IntegrationCard.CTA>
					<Button
						size="xs"
						onClick={ ( event ) =>
							handlePluginActionTrigger( {
								plugin,
								event,
							} )
						}
					>
						{ getPluginStatusText( plugin ) }
					</Button>
				</IntegrationCard.CTA>
			</IntegrationCard.Content>
		</IntegrationCard>
	);
};

export default SureTriggers;
