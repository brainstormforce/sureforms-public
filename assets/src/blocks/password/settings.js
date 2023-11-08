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

export default ( { attributes, setAttributes } ) => {
	const { label, placeholder, help, required, errorMsg, isConfirmPassword } =
		attributes;

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
						<ToggleControl
							label={ __(
								'Enable Password Confirmation',
								'sureforms'
							) }
							checked={ isConfirmPassword }
							onChange={ ( checked ) =>
								setAttributes( { isConfirmPassword: checked } )
							}
						/>
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
			</InspectorTabs>
		</InspectorControls>
	);
};
