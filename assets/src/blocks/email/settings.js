/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';

export default ( { attributes, setAttributes } ) => {
	const {
		label,
		placeholder,
		help,
		required,
		defaultValue,
		isUnique,
		duplicateMsg,
		errorMsg,
		isConfirmEmail,
	} = attributes;

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
						<UAGTextControl
							label={ __( 'Default Value', 'sureforms' ) }
							value={ defaultValue }
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
							label={ __( 'Validate as unique', 'sureforms' ) }
							checked={ isUnique }
							onChange={ ( checked ) =>
								setAttributes( { isUnique: checked } )
							}
						/>
						{ isUnique && (
							<UAGTextControl
								label={ __(
									'Validation Message for Duplicate ',
									'sureforms'
								) }
								value={ duplicateMsg }
								data={ {
									value: duplicateMsg,
									label: 'duplicateMsg',
								} }
								onChange={ ( value ) =>
									setAttributes( { duplicateMsg: value } )
								}
							/>
						) }
						<ToggleControl
							label={ __(
								'Enable Email Confirmation',
								'sureforms'
							) }
							checked={ isConfirmEmail }
							onChange={ ( checked ) =>
								setAttributes( { isConfirmEmail: checked } )
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
