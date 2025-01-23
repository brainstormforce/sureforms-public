import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { PanelBody } from '@wordpress/components';

const ConditionalLogicPreview = () => {
	return (
		<div className="srfm-conditional-logic-preview">
			<PanelBody
				title={
					<>
						{ __( 'Conditional Logic', 'sureforms' ) }
						<PremiumBadge
							badgeName={ 'Pro' }
							tooltipHeading={ __(
								'Unlock Conditional Logic',
								'sureforms'
							) }
							tooltipContent={ __(
								'Upgrade to the SureForms Pro Plan to create dynamic forms that adapt based on user input, offering a personalised and efficient form experience.',
								'sureforms'
							) }
							tooltipPosition={ 'bottom' }
							utmMedium={ 'editor_blocks_conditional_logic' }
						/>
					</>
				}
				initialOpen={ false }
			></PanelBody>
		</div>
	);
};

const ConditionalLogic = ( { attributes, setAttributes } ) => {
	const conditionalSettings = applyFilters(
		'srfm.conditional_logic.tab_advance',
		attributes,
		setAttributes
	);
	const isPro = srfm_block_data.is_pro_active;
	return <>{ isPro ? conditionalSettings : <ConditionalLogicPreview /> }</>;
};

export default ConditionalLogic;
