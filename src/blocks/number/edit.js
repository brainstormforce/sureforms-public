/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
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
import { useErrMessage, decodeHtmlEntities } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

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

	const handleInput = ( e ) => {
		let inputValue = e.target.value;
		if ( formatType === 'none' ) {
			inputValue = inputValue.replace( /[^-.\d]/g, '' );
		} else if ( formatType === 'non-decimal' ) {
			inputValue = inputValue.replace( /[^0-9]/g, '' );
		} else {
			inputValue = inputValue.replace( /[^0-9.]/g, '' );
			const dotCount = inputValue.split( '.' ).length - 1;
			if ( dotCount > 1 ) {
				inputValue = inputValue.replace( /\.+$/g, '' );
			}
		}
		setAttributes( { defaultValue: inputValue } );
	};

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

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
							<SRFMNumberControl
								label={ __( 'Default Value', 'sureforms' ) }
								displayUnit={ false }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								value={ defaultValue }
								onChange={ ( value ) =>
									setAttributes( {
										defaultValue: value,
									} )
								}
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
							<SRFMSelectControl
								label={ __( 'Number Format', 'sureforms' ) }
								data={ {
									value: formatType,
									label: 'formatType',
								} }
								setAttributes={ setAttributes }
								options={ [
									{
										label: 'None',
										value: 'none',
									},
									{
										label: 'Decimal (Ex:256.45)',
										value: 'decimal',
									},
									{
										label: 'Non Decimal (Ex:258)',
										value: 'non-decimal',
									},
								] }
							/>
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
				handleInput={ handleInput }
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
		</div>
	);
};

export default compose( AddInitialAttr )( SureformInput );
