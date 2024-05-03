/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { AddressCompactBlock } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage, decodeHtmlEntities } from '@Blocks/util';

import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		required,
		fieldWidth,
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

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_address_compact_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.address_compact_preview;
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
							<span className="srfm-control-label srfm-control__header" />
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
						<SRFMAdvancedPanelBody
							title={ __( 'Address Line 1', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: lineOnePlaceholder,
									label: 'lineOnePlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ lineOnePlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										lineOnePlaceholder: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __( 'Address Line 2', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: lineTwoPlaceholder,
									label: 'lineTwoPlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ lineTwoPlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										lineTwoPlaceholder: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __( 'City', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: cityPlaceholder,
									label: 'cityPlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ cityPlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										cityPlaceholder: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __( 'State', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: statePlaceholder,
									label: 'statePlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ statePlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										statePlaceholder: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __( 'Postal Code', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: postalPlaceholder,
									label: 'postalPlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ postalPlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										postalPlaceholder: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __( 'Country', 'sureforms' ) }
							initialOpen={ false }
						>
							<SRFMTextControl
								data={ {
									value: countryPlaceholder,
									label: 'countryPlaceholder',
								} }
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ countryPlaceholder }
								onChange={ ( value ) =>
									setAttributes( {
										countryPlaceholder: value,
									} )
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
			<AddressCompactBlock
				attributes={ attributes }
				blockID={ block_id }
				setAttributes={ setAttributes }
			/>
			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => {
						setAttributes( { help: decodeHtmlEntities( value ) } );
					} }
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
