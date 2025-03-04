import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '../../admin/components/PremiumBadge';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { SelectControl, Button, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

const ConditionalLogicPreview = () => {
	const [ isExpanded, setIsExpanded ] = useState( false );
	return (
		<div className="srfm-conditional-logic-preview">
			<div
				className="components-panel__body"
				onClick={ () => {
					setIsExpanded( ! isExpanded );
				} }
			>
				<h2 className="components-panel__body-title">
					{ ' ' }
					{ __( 'Conditional Logic', 'sureforms' ) }{ ' ' }
				</h2>
				<PremiumBadge
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
				{ ! isExpanded ? chevronDown : chevronUp }
			</div>
			{ isExpanded ? (
				<div className="components-panel__body-content">
					<ToggleControl
						label={ __( 'Enable Conditional Logic', 'sureforms' ) }
						checked={ false }
						disabled={ true }
					/>
					<div className="srfm-show-hide-select">
						<SelectControl
							value={ 'show' }
							disabled={ true }
							options={ [
								{
									value: 'show',
									label: __( 'Show', 'sureforms' ),
								},
								{
									value: 'hide',
									label: __( 'Hide', 'sureforms' ),
								},
							] }
						/>
						<p>{ __( 'this field if', 'sureforms' ) }</p>
					</div>
					<Button
						variant="secondary"
						style={ {
							marginBottom: '18px',
						} }
						disabled={ true }
					>
						{ __( 'Configure Conditions', 'sureforms' ) }
					</Button>
				</div>
			) : null }
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
