/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
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

export default ( { attributes, setAttributes, isSelected, clientId } ) => {
	const { label, help, required, id, fieldType, min, max, errorMsg, formId } =
		attributes;
	const [ showErr, setShowErr ] = useState( false );

	const blockID = useBlockProps().id.split( '-' ).join( '' );

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );
	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

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
								sureforms_keys?._sureforms_form_styling &&
							( 'dateTime' === fieldType ||
								'date' === fieldType ) ? (
								<UAGAdvancedPanelBody
									title={ __( 'Date Settings', 'sureforms' ) }
									initialOpen={ false }
								>
									<span className="uag-control-label uagb-control__header">
										{ __( 'Minimum Date', 'sureforms' ) }
									</span>
									<input
										className="sureforms-date-time-picker"
										type="date"
										id="for-min-date"
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
										className="sureforms-date-time-picker"
										type="date"
										id="for-max-date"
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
								</UAGAdvancedPanelBody>
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
					'main-container sf-classic-inputs-holder' +
					( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<DatetimepickerClassicStyle attributes={ attributes } />
				) : (
					<DatetimepickerThemeStyle attributes={ attributes } />
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'email-input-help-' + blockID }
						className={
							'classic' ===
							sureforms_keys?._sureforms_form_styling
								? 'sforms-helper-txt'
								: 'sf-text-secondary'
						}
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
