/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import SRFMSelectControl from '@Components/select-control';
import SRFMNumberControl from '@Components/number-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { NumberComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const formatNumber = ( number, formatType ) => {
	if ( ! number ) {
		return '';
	}

	// Convert all to empty strings except: Numbers, Dots, Commas.
	number = number.replace( /[^0-9,.]+/g, '' );

	if ( number.endsWith( '.' ) || number.endsWith( ',' ) ) {
		// It means, user has just started decimal point. Eg: "2." or "2,"
		return number;
	}

	let formattedNumber = '';
	const formatOptions = { style: 'decimal', maximumFractionDigits: 20 };

	if ( 'eu-style' === formatType ) {
		const normalizeNumber = parseFloat(
			number.replace( /\./g, '' ).replace( ',', '.' )
		);

		// EU style number format.
		formattedNumber = new Intl.NumberFormat(
			'de-DE',
			formatOptions
		).format( normalizeNumber );
	} else {
		// US style number format. Default.
		formattedNumber = new Intl.NumberFormat(
			'en-US',
			formatOptions
		).format( parseFloat( number.replace( /,/g, '' ) ) );
	}

	if ( 'NaN' === formattedNumber ) {
		// Bail, if NaN.
		return '';
	}

	return formattedNumber;
};

const SureformInput = ( { attributes, setAttributes, clientId } ) => {
	const {
		fieldWidth,
		placeholder,
		help,
		required,
		block_id,
		defaultValue,
		minValue,
		maxValue,
		errorMsg,
		preview,
		formatType,
		formId,
		className,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ error, setError ] = useState( false );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_number_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.number_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div className={ className }>
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
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ placeholder }
								data={ {
									value: placeholder,
									label: 'placeholder',
								} }
								onChange={ ( newValue ) =>
									setAttributes( { placeholder: newValue } )
								}
							/>
							<SRFMSelectControl
								label={ __( 'Number Format', 'sureforms' ) }
								data={ {
									value: formatType,
									label: 'formatType',
								} }
								setAttributes={ ( value ) => {
									setAttributes( value );
									setAttributes( {
										defaultValue: '',
									} );
								} }
								options={ [
									{
										label: __(
											'US Style (Eg: 9,999.99)',
											'sureforms'
										),
										value: 'us-style',
									},
									{
										label: __(
											'EU Style (Eg: 9.999,99)',
											'sureforms'
										),
										value: 'eu-style',
									},
								] }
							/>
							<SRFMTextControl
								label={ __( 'Default Value', 'sureforms' ) }
								displayUnit={ false }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								value={ defaultValue }
								setAttributes={ ( value ) => {
									setAttributes( {
										defaultValue: formatNumber(
											value.defaultValue,
											formatType
										),
									} );
								} }
								showControlHeader={ false }
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( newValue ) =>
									setAttributes( { required: newValue } )
								}
							/>
							{ required && (
								<SRFMTextControl
									label={ __( 'Error Message', 'sureforms' ) }
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
							<SRFMNumberControl
								label={ __( 'Minimum Value', 'sureforms' ) }
								displayUnit={ false }
								data={ {
									value: minValue,
									label: 'minValue',
								} }
								value={ minValue }
								onChange={ ( value ) => {
									if ( value >= maxValue ) {
										setError( true );
										setAttributes( { minValue: 0 } );
									} else {
										setError( false );
										setAttributes( { minValue: value } );
									}
								} }
								showControlHeader={ false }
							/>
							<SRFMNumberControl
								label={ __( 'Maximum Value', 'sureforms' ) }
								displayUnit={ false }
								data={ {
									value: maxValue,
									label: 'maxValue',
								} }
								value={ maxValue }
								onChange={ ( value ) => {
									if ( value <= minValue ) {
										setError( true );
										setAttributes( {
											maxValue: Number( minValue ) + 1,
										} );
									} else {
										setError( false );
										setAttributes( { maxValue: value } );
									}
								} }
								showControlHeader={ false }
							/>
							{ error && (
								<p className="srfm-min-max-error-styles">
									{ __(
										'Please check the Minimum and Maximum value',
										'sureforms'
									) }
								</p>
							) }
							<p className="components-base-control__help">
								{ __(
									'Note: Maximum value should always be greater than minimum value',
									'sureforms'
								) }
							</p>
							<SRFMTextControl
								label={ __( 'Help Text', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( newValue ) =>
									setAttributes( { help: newValue } )
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
			<NumberComponent
				attributes={ attributes }
				blockID={ block_id }
				formatNumber={ formatNumber }
				setAttributes={ setAttributes }
			/>
			<div className="srfm-error-wrap"></div>
		</div>
	);
};

export default compose( AddInitialAttr )( SureformInput );
