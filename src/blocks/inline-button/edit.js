/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId.js';
import { InlineButton } from './components/default.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const { label, fieldWidth, block_id, formId, preview } = attributes;
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
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.advance } />
				</InspectorTabs>
				<InspectorTab { ...SRFMTabs.advance }>
					<div>Hello</div>
					<ConditionalLogic { ...{ setAttributes, attributes } } />
				</InspectorTab>
			</InspectorControls>
			<>
				<InlineButton
					blockID={ block_id }
					setAttributes={ setAttributes }
					attributes={ attributes }
				/>
			</>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
