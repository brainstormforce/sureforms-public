/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import { __ } from '@wordpress/i18n';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { EmailThemeStyle } from './components/EmailThemeStyle';
import { EmailClassicStyle } from './components/EmailClassicStyle';

export default ( {
	className,
	attributes,
	setAttributes,
	isSelected,
	clientId,
} ) => {
	const {
		label,
		placeholder,
		help,
		required,
		id,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
		formId,
		errorMsg,
		isUnique,
		duplicateMsg,
	} = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	return (
		<>
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
								label={ __(
									'Validate as unique',
									'sureforms'
								) }
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
			<div
				className={
					'main-container' +
					( isSelected ? ' sf--focus' : '' ) +
					className
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<EmailClassicStyle attributes={ attributes } />
				) : (
					<EmailThemeStyle attributes={ attributes } />
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'email-input-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
