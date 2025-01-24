import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { chevronDown } from '@wordpress/icons';

const ConditionalLogicPreview = () => {
	return (
		<div className="srfm-conditional-logic-preview">
			<div className="components-panel__body">
				<h2 className="components-panel__body-title">
					{ ' ' }
					{ __( 'Conditional Logic', 'sureforms' ) }{ ' ' }
				</h2>
				<PremiumBadge
					badgeName={ 'Starter' }
					tooltipHeading={ __(
						'Unlock Conditional Logic',
						'sureforms'
					) }
					tooltipContent={ __(
						'Upgrade to the SureForms Starter Plan to create dynamic forms that adapt based on user input, offering a personalised and efficient form experience.',
						'sureforms'
					) }
					tooltipPosition={ 'bottom' }
					utmMedium={ 'editor_blocks_conditional_logic' }
				/>
				{ chevronDown }
			</div>
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
