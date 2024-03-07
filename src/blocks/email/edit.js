/**
 * WordPress dependencies
 */
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import { ToggleControl, SelectControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { __ } from '@wordpress/i18n';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { EmailComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useReqErrMessage, useUniqueErrMessage } from '@Blocks/util';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		fieldWidth,
		label,
		placeholder,
		help,
		required,
		defaultValue,
		isConfirmEmail,
		// might be used.
		// confirmLabel,
		formId,
		block_id,
		errorMsg,
		isUnique,
		duplicateMsg,
		preview,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const { currentErrorMsg, setCurrentErrorMsg } = useReqErrMessage(
		'srfm_url_block_required_text',
		errorMsg
	);

	const { currentUniqueMessage, setCurrentUniqueMessage } =
		useUniqueErrMessage( 'srfm_email_block_unique_text', duplicateMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.email_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
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
									setAttributes( {
										fieldWidth: Number( value ),
									} )
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
								<SRFMTextControl
									label={ __(
										'Validation Message for Duplicate ',
										'sureforms'
									) }
									value={ currentUniqueMessage }
									data={ {
										value: duplicateMsg,
										label: 'duplicateMsg',
									} }
									onChange={ ( value ) => {
										setCurrentUniqueMessage( value );
										setAttributes( {
											duplicateMsg: value,
										} );
									} }
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
			<EmailComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => setAttributes( { help: value } ) }
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
				/>
			) }
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
