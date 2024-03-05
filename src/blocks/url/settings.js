/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl, SelectControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import widthOptions from '../width-options.json';
import { applyFilters } from '@wordpress/hooks';

export default ( { attributes, setAttributes } ) => {
	const {
		label,
		placeholder,
		help,
		required,
		defaultValue,
		errorMsg,
		fieldWidth,
	} = attributes;

	const conditionalSettings = applyFilters(
		'srfm.conditional_logic.tab_advance',
		attributes,
		setAttributes
	);
	const isPro = srfm_block_data.is_pro_active;

	return (
		<InspectorControls>
			<InspectorTabs
				tabs={ [ 'general', 'advance' ] }
				defaultTab={ 'general' }
			>
				<InspectorTab { ...SRFMTabs.general }>
					<SRFMAdvancedPanelBody
						title={ __( 'Attributes', 'sureforms' ) }
						initialOpen={ true }
					>
						<SelectControl
							label={ __( 'Field Width', 'sureforms' ) }
							value={ fieldWidth }
							options={ widthOptions }
							onChange={ ( value ) =>
								setAttributes( { fieldWidth: Number( value ) } )
							}
							__nextHasNoMarginBottom
						/>
						<SRFMTextControl
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							data={ {
								value: label,
								label: 'label',
							} }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
						/>
						<SRFMTextControl
							label={ __( 'Placeholder', 'sureforms' ) }
							value={ placeholder }
							data={ {
								value: placeholder,
								label: 'placeholder',
							} }
							onChange={ ( value ) =>
								setAttributes( { placeholder: value } )
							}
						/>
						<SRFMTextControl
							label={ __( 'Default Value', 'sureforms' ) }
							className="srfm-with-dropdown"
							value={ defaultValue }
							withSmartTagDropdown={ true }
							data={ {
								value: defaultValue,
								label: 'defaultValue',
							} }
							onChange={ ( value ) =>
								setAttributes( { defaultValue: value } )
							}
						/>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
						{ required && (
							<SRFMTextControl
								label={ __( 'Error message', 'sureforms' ) }
								value={ errorMsg }
								data={ {
									value: errorMsg,
									label: 'errorMsg',
								} }
								onChange={ ( value ) =>
									setAttributes( { errorMsg: value } )
								}
							/>
						) }
						<SRFMTextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							data={ {
								value: help,
								label: 'help',
							} }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
							}
						/>
					</SRFMAdvancedPanelBody>
				</InspectorTab>
				<InspectorTab { ...SRFMTabs.style }></InspectorTab>
				<InspectorTab { ...SRFMTabs.advance }>
					{ isPro && conditionalSettings }
				</InspectorTab>
			</InspectorTabs>
		</InspectorControls>
	);
};
