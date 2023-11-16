/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { DatetimepickerThemeStyle } from './components/DatetimepickerThemeStyle';
import { DatetimepickerClassicStyle } from './components/DatetimepickerClassicStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, isSelected, clientId } ) => {
	const {
		fieldWidth,
		label,
		help,
		required,
		block_id,
		fieldType,
		min,
		preview,
		max,
		errorMsg,
		formId,
	} = attributes;
	const [ showErr, setShowErr ] = useState( false );
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-datetime-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		parent_to_width_element.style.width =
			'calc( ' + fieldWidth + '% - 20px)';
	}, [ fieldWidth ] );
	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.date_time_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

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
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<SelectControl
								value={ fieldType }
								label={ __( 'Type', 'sureforms' ) }
								onChange={ ( value ) =>
									setAttributes( { fieldType: value } )
								}
							>
								<option value="dateTime">
									{ __( 'Date & Time', 'sureforms' ) }
								</option>
								<option value="date">
									{ __( 'Date', 'sureforms' ) }
								</option>
								<option value="time">
									{ __( 'Time', 'sureforms' ) }
								</option>
							</SelectControl>
							{ 'classic' !==
								sureforms_keys?._srfm_form_styling &&
							( 'dateTime' === fieldType ||
								'date' === fieldType ) ? (
									<>
										<span className="uag-control-label uagb-control__header">
											{ __( 'Minimum Date', 'sureforms' ) }
										</span>
										<input
											className="srfm-date-time-picker"
											type="date"
											id="srfm-for-min-date"
											value={ min }
											onChange={ ( e ) => {
												if ( '' !== max ) {
													if ( e.target.value < max ) {
														setShowErr( false );
														setAttributes( {
															min: e.target.value,
														} );
													} else {
														setShowErr( true );
													}
												} else {
													setShowErr( false );
													setAttributes( {
														min: e.target.value,
													} );
												}
											} }
										/>
										<span className="uag-control-label uagb-control__header">
											{ __( 'Maximum Date', 'sureforms' ) }
										</span>
										<input
											className="srfm-date-time-picker"
											type="date"
											id="srfm-for-max-date"
											value={ max }
											onChange={ ( e ) => {
												if ( '' !== min ) {
													if ( min < e.target.value ) {
														setShowErr( false );
														setAttributes( {
															max: e.target.value,
														} );
													} else {
														setShowErr( true );
													}
												} else {
													setShowErr( false );
													setAttributes( {
														max: e.target.value,
													} );
												}
											} }
										/>
										{ showErr && (
											<p style={ { color: 'red' } }>
												{ __(
													'Please enter a lower Minimum Date!',
													'sureforms'
												) }
											</p>
										) }
										<p className="components-base-control__help">
											{ __(
												'Minimum Date should always be less than the Maximum Date',
												'sureforms'
											) }
										</p>
									</>
								) : (
									''
								) }
							<span className="uag-control-label uagb-control__header" />
							<UAGTextControl
								data={ {
									value: help,
									label: 'help',
								} }
								label={ __( 'Help', 'sureforms' ) }
								value={ help }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'srfm-main-container srfm-classic-inputs-holder' +
					( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
				id={ 'srfm-datetime-fieldwidth' + block_id }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<DatetimepickerClassicStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) : (
					<DatetimepickerThemeStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) }
				{ help !== '' && (
					<RichText
						tagName="label"
						value={ help }
						onChange={ ( value ) =>
							setAttributes( { help: value } )
						}
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
						multiline={ false }
						id={ block_id }
					/>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
