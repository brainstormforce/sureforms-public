/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		checked: isChecked,
		help,
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
														variant="textarea"

								data={ {
									value: help,
									label: 'help',
								} }
								label={ __( 'Help Text', 'sureforms' ) }
								value={ help }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
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
				<div className="srfm-error-wrap"></div>
			</div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
