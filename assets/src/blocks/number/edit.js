/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import UAGSelectControl from '@Components/select-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { NumberClassicStyle } from './components/numberClassicStyle';
import { NumberThemeStyle } from './components/numberThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import UAGNumberControl from '@Components/number-control';

const SureformInput = ( { attributes, setAttributes, clientId } ) => {
	const {
		label,
		placeholder,
		help,
		required,
		block_id,
		defaultValue,
		minValue,
		maxValue,
		errorMsg,
		formatType,
		formId,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );
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

	return (
		<>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...UAGTabs.general }>
						<UAGAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								value={ label }
								data={ {
									value: label,
									label: 'label',
								} }
								onChange={ ( newValue ) =>
									setAttributes( { label: newValue } )
								}
							/>
							<UAGTextControl
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
							<UAGNumberControl
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
								<UAGTextControl
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<UAGNumberControl
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
							<UAGNumberControl
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
								<p
									style={ {
										fontSize: '12px',
										fontStyle: 'normal',
										color: 'red',
									} }
								>
									{ __(
										'Please check the Minimum and Maximum value',
										'sureforms'
									) }
								</p>
							) }
							<p className="components-base-control__help">
								{ __(
									'Note: Max value should always be greater than min value',
									'sureforms'
								) }
							</p>
							<UAGSelectControl
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
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( newValue ) =>
									setAttributes( { help: newValue } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={ 'srfm-main-container srfm-classic-inputs-holder' }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<NumberClassicStyle
						attributes={ attributes }
						blockID={ block_id }
						handleInput={ handleInput }
					/>
				) : (
					<NumberThemeStyle
						attributes={ attributes }
						blockID={ block_id }
						handleInput={ handleInput }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'srfm-number-input-help-' + block_id }
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( SureformInput );
