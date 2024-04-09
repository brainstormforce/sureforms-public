/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const { buttonText, fieldWidth, block_id, formId, preview } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.input_preview;
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
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<>
				<div
					className="srfm-block-label"
					style={ {
						height: '1em',
					} }
				/>
				<button
					style={ {
						width: '100%',
						border: 'var( --srfm-btn-border-width ) solid var( --srfm-btn-border-color )',
						borderRadius: 'var( --srfm-btn-border-radius )',
					} }
					className={ `srfm-button srfm-submit-button srfm-inline-submit-button` }
				>
					<RichText
						value={ buttonText }
						onChange={ ( value ) => {
							setAttributes( {
								buttonText: decodeHtmlEntities( value ),
							} );
						} }
						multiline={ false }
						id={ block_id }
						allowedFormats={ [] }
					/>
				</button>
			</>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
