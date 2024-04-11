/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import {
	InspectorControls,
	RichText,
	InnerBlocks,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { AddressBlock } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage, decodeHtmlEntities } from '@Blocks/util';

import countries from './countries.json';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		required,
		fieldWidth,
		label,
		block_id,
		errorMsg,
		lineOnePlaceholder,
		lineTwoPlaceholder,
		cityPlaceholder,
		statePlaceholder,
		postalPlaceholder,
		countryPlaceholder,
		formId,
		preview,
		help,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// const {
	// 	currentMessage: currentErrorMsg,
	// 	setCurrentMessage: setCurrentErrorMsg,
	// } = useErrMessage( 'srfm_address_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.address_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'address';
	const blockID = `srfm-${ slug }-${ block_id }`;

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
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
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
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className="srfm-address-block-container"
				style={ {
					marginTop: '-.5em',
				} }
			>
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => {
						setAttributes( {
							label: decodeHtmlEntities( value ),
						} );
					} }
					className={ `srfm-block-label${ isRequired }` }
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
					style={ {
						padding: '0 .3em',
						marginTop: '1em',
					} }
				/>
				<InnerBlocks
					template={ [
						[
							'srfm/input',
							{
								placeholder: 'Address Line 1',
								label: 'Address Line 1',
								fieldWidth: 50,
							},
						],
						[
							'srfm/input',
							{
								placeholder: 'Address Line 2',
								label: 'Address Line 2',
								fieldWidth: 50,
							},
						],
						[
							'srfm/input',
							{
								placeholder: 'City',
								label: 'City',
								fieldWidth: 50,
							},
						],
						[
							'srfm/input',
							{
								placeholder: 'State',
								label: 'State',
								fieldWidth: 50,
							},
						],
						[
							'srfm/input',
							{
								placeholder: 'Postal Code',
								label: 'Postal Code',
								fieldWidth: 50,
							},
						],
						[
							'srfm/dropdown',
							{
								placeholder: 'Country',
								label: 'Country',
								// "options": {
								// 	"default": [ "Option 1", "Option 2" ],
								// 	"type": "array"
								// },
								options: [
									...countries.map( ( country ) => {
										return country.name;
									} ),
								],
								fieldWidth: 50,
							},
						],
					] }
					allowedBlocks={ [ 'srfm/input', 'srfm/dropdown' ] }
				/>
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => {
						setAttributes( {
							help: decodeHtmlEntities( value ),
						} );
					} }
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
					style={ {
						padding: '0 .3em',
					} }
				/>
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
