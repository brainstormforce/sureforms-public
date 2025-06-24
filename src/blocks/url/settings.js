/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

export default ( { attributes, setAttributes } ) => {
	const { help, required, defaultValue, errorMsg, readOnly } = attributes;

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_url_block_required_text', errorMsg );

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
						<SRFMTextControl
							label={ __( 'Default Value', 'sureforms' ) }
							className="srfm-with-dropdown"
							value={ defaultValue ? defaultValue : '' }
							withSmartTagDropdown={ true }
							data={ {
								value: defaultValue,
								label: 'defaultValue',
							} }
							onChange={ ( value ) =>
								setAttributes( { defaultValue: value } )
							}
						/>
						{ defaultValue && (
							<ToggleControl
								label={ __( 'Read Only', 'sureforms' ) }
								checked={ readOnly }
								onChange={ ( checked ) =>
									setAttributes( { readOnly: checked } )
								}
							/>
						) }
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
						{ required && (
							<SRFMTextControl
								label={ __( 'Error Message', 'sureforms' ) }
								data={ {
									value: errorMsg,
									label: 'errorMsg',
								} }
								value={ currentErrorMsg }
								onChange={ ( value ) => {
									setCurrentErrorMsg( value );
									setAttributes( { errorMsg: value } );
								} }
							/>
						) }
						<SRFMTextControl
							label={ __( 'Help Text', 'sureforms' ) }
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
					<ConditionalLogic { ...{ setAttributes, attributes } } />
				</InspectorTab>
			</InspectorTabs>
		</InspectorControls>
	);
};
