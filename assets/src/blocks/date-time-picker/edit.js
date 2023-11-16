/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { DatetimepickerThemeStyle } from './components/DatetimepickerThemeStyle';
import { DatetimepickerClassicStyle } from './components/DatetimepickerClassicStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { attributes, setAttributes, isSelected, clientId } ) => {
	const {
		label,
		help,
		required,
		block_id,
		fieldType,
		min,
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
							<SRFMTextControl
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
								<SRFMTextControl
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
										<span className="srfm-control-label srfm-control__header">
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
										<span className="srfm-control-label srfm-control__header">
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
							<span className="srfm-control-label srfm-control__header" />
							<SRFMTextControl
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
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
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
