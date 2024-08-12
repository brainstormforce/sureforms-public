/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { decodeHtmlEntities } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const { buttonText, fieldWidth, block_id, formId, preview, className } =
		attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, currentFormId ] );

	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const is_inherit_from_theme = sureformsKeys?._srfm_inherit_theme_button;

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.input_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div
			className={
				'srfm-custom-button-ctn' +
				( className ? ` ${ className }` : '' )
			}
		>
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
				{ /* <label className="srfm-block-label">‎</label> */ }
				<button
					style={ {
						width: '100%',
					} }
					className={ `${
						is_inherit_from_theme
							? 'wp-block-button__link'
							: 'srfm-button srfm-submit-button srfm-inline-submit-button srfm-btn-bg-color'
					}` }
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
				<div className="srfm-error-wrap"></div>
			</>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
