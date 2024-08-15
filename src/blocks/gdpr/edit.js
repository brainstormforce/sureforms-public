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
import { useGetCurrentFormId } from '@Attributes/getFormId';
import { CheckboxComponent } from '../components/default.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage, decodeHtmlEntities } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		fieldWidth,
		checked: isChecked,
		gdprHelpText,
		block_id,
		preview,
		formId,
		errorMsg,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const blockProps = useBlockProps();

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_checkbox_block_required_text', errorMsg );

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
									value: gdprHelpText,
									label: 'gdprHelpText',
								} }
								label={ __( 'Help', 'sureforms' ) }
								value={ gdprHelpText }
								onChange={ ( value ) =>
									setAttributes( { gdprHelpText: value } )
								}
							/>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div className="srfm-gdpr-block">
				<CheckboxComponent
					blockID={ block_id }
					setAttributes={ setAttributes }
					attributes={ attributes }
					blockType="gdpr"
				/>
				{ gdprHelpText !== '' && (
					<RichText
						tagName="label"
						value={ gdprHelpText }
						onChange={ ( value ) => {
							setAttributes( {
								gdprHelpText: decodeHtmlEntities( value ),
							} );
						} }
						className="srfm-description"
						multiline={ false }
						id={ block_id }
						allowedFormats={ [] }
					/>
				) }
				<div className="srfm-error-wrap"></div>
			</div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
