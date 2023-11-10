/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl, SelectControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import widthOptions from '../width-options.json';

export default ( { attributes, setAttributes } ) => {
	const { fieldWidth, label, placeholder, help, required, errorMsg, isConfirmPassword } =
		attributes;

	return (
		<InspectorControls>
			<InspectorTabs
				tabs={ [ 'general', 'advance' ] }
				defaultTab={ 'general' }
			>
				<InspectorTab { ...UAGTabs.general }>
					<UAGAdvancedPanelBody
						title={ __( 'Attributes', 'sureforms' ) }
						initialOpen={ true }
					>
						<SelectControl
								label={ __( 'Field Width', 'sureforms' ) }
								value={ fieldWidth }
								options={ widthOptions }
								onChange={ ( value ) =>
								setAttributes( { fieldWidth: value } )
								}
								__nextHasNoMarginBottom
							/>
						<UAGTextControl
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
						<UAGTextControl
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
							<UAGTextControl
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
						<UAGTextControl
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
					</UAGAdvancedPanelBody>
				</InspectorTab>
				<InspectorTab { ...UAGTabs.style }></InspectorTab>
			</InspectorTabs>
		</InspectorControls>
	);
};
