/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import {
	ToggleControl,
	SelectControl,
	Popover,
	DatePicker,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { DateTimeComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, clientId } ) => {
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
	const [ isMinPopVisible, setIsMinPopVisible ] = useState( false );
	const [ isMaxPopVisible, setIsMaxPopVisible ] = useState( false );
	const currentFormId = useGetCurrentFormId( clientId );

	const getFormattedDate = ( date ) => {
		const currentDate = new Date( date );
		const day = currentDate.getDate().toString().padStart( 2, '0' );
		const month = ( currentDate.getMonth() + 1 )
			.toString()
			.padStart( 2, '0' );
		const year = currentDate.getFullYear();
		// Format the date components
		return `${ year }/${ month }/${ day }`;
	};
	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

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
							{ 'dateTime' === fieldType ||
							'date' === fieldType ? (
									<>
										<span className="srfm-control-label srfm-control__header">
											{ __( 'Minimum Date', 'sureforms' ) }
										</span>
										<div className="srfm-date-setting-wrap">
											<div className="srfm-date-setting-icon">
												<i className="fa-regular fa-calendar srfm-text-gray-400 srfm-text-[18px]"></i>
											</div>
											<input
												className="srfm-date-time-picker"
												type="text"
												id="srfm-for-min-date"
												value={ min }
												placeholder={ __(
													'select a date',
													'sureforms'
												) }
												onClick={ () => {
													setIsMinPopVisible(
														( state ) => ! state
													);
												} }
											/>
											{ isMinPopVisible && (
												<Popover>
													<DatePicker
														className="srfm-date-picker-setting"
														currentDate={ new Date() }
														onChange={ ( date ) => {
															const currDate =
															getFormattedDate(
																date
															);
															if ( '' !== max ) {
																if (
																	currDate < max
																) {
																	setShowErr(
																		false
																	);
																	setAttributes( {
																		min: currDate,
																	} );
																	setIsMinPopVisible(
																		( state ) =>
																			! state
																	);
																} else {
																	setShowErr(
																		true
																	);
																}
															} else {
																setShowErr( false );
																setAttributes( {
																	min: currDate,
																} );
																setIsMinPopVisible(
																	( state ) =>
																		! state
																);
															}
														} }
													/>
												</Popover>
											) }
										</div>
										<span className="srfm-control-label srfm-control__header">
											{ __( 'Maximum Date', 'sureforms' ) }
										</span>
										<div className="srfm-date-setting-wrap">
											<div className="srfm-date-setting-icon">
												<i className="fa-regular fa-calendar srfm-text-gray-400 srfm-text-[18px]"></i>
											</div>
											<input
												className="srfm-date-time-picker"
												type="text"
												id="srfm-for-max-date"
												value={ max }
												placeholder={ __(
													'select a date',
													'sureforms'
												) }
												onClick={ () => {
													setIsMaxPopVisible(
														( state ) => ! state
													);
												} }
											/>
											{ isMaxPopVisible && (
												<Popover>
													<DatePicker
														className="srfm-date-picker-setting"
														currentDate={ new Date() }
														onChange={ ( date ) => {
															const currDate =
															getFormattedDate(
																date
															);
															if ( '' !== min ) {
																if (
																	min < currDate
																) {
																	setShowErr(
																		false
																	);
																	setAttributes( {
																		max: currDate,
																	} );
																	setIsMaxPopVisible(
																		( state ) =>
																			! state
																	);
																} else {
																	setShowErr(
																		true
																	);
																}
															} else {
																setShowErr( false );
																setAttributes( {
																	max: currDate,
																} );
																setIsMaxPopVisible(
																	( state ) =>
																		! state
																);
															}
														} }
													/>
												</Popover>
											) }
										</div>
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
			<DateTimeComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => setAttributes( { help: value } ) }
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
