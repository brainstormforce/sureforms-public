/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { CheckboxComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useReqErrMessage } from '@Blocks/util';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		fieldWidth,
		checked: isChecked,
		required,
		checkboxHelpText,
		block_id,
		errorMsg,
		preview,
		formId,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const blockProps = useBlockProps();

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const { currentErrorMsg, setCurrentErrorMsg } = useReqErrMessage(
		'srfm_url_block_required_text',
		errorMsg
	);

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.checkbox_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div { ...blockProps }>
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
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<SRFMTextControl
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									label={ __( 'Error message', 'sureforms' ) }
									value={ currentErrorMsg }
									onChange={ ( value ) => {
										setCurrentErrorMsg( value );
										setAttributes( { errorMsg: value } );
									} }
								/>
							) }
							<ToggleControl
								label={ __(
									'Checked by default',
									'sureforms'
								) }
								checked={ isChecked }
								onChange={ ( checked ) =>
									setAttributes( { checked } )
								}
							/>
							<SRFMTextControl
								data={ {
									value: checkboxHelpText,
									label: 'checkboxHelpText',
								} }
								label={ __( 'Help', 'sureforms' ) }
								value={ checkboxHelpText }
								onChange={ ( value ) =>
									setAttributes( { checkboxHelpText: value } )
								}
							/>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<CheckboxComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
			{ checkboxHelpText !== '' && (
				<RichText
					tagName="label"
					value={ checkboxHelpText }
					onChange={ ( value ) =>
						setAttributes( { checkboxHelpText: value } )
					}
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
				/>
			) }
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
