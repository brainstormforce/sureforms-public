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

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const { label, help, required, id, fieldType, min, max, errorMsg } =
		attributes;
	const [ showErr, setShowErr ] = useState( false );

	const blockID = useBlockProps().id.split( '-' ).join( '' );
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
							{ 'dateTime' === fieldType ||
							'date' === fieldType ? (
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
					'main-container' + ( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label
					className="sf-text-primary"
					htmlFor={ 'date-picker-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<div
					style={ {
						display: 'flex',
						gap: '.5rem',
					} }
				>
					{ ( () => {
						switch ( fieldType ) {
							case 'dateTime':
								return (
									<>
										<input
											id={ 'date-picker-' + blockID }
											type="date"
											className={ className }
											required={ required }
											min={ min }
											max={ max }
										/>
										<input
											id={ 'time-picker-' + blockID }
											type="time"
										/>
									</>
								);
							case 'date':
								return (
									<input
										id={ 'date-picker-' + blockID }
										type="date"
										className={ className }
										required={ required }
										min={ min }
										max={ max }
									/>
								);
							case 'time':
								return (
									<input
										id={ 'time-picker-' + blockID }
										type="time"
									/>
								);
							default:
								return (
									<>
										<input
											id={ 'date-picker-' + blockID }
											type="date"
											className={ className }
											required={ required }
										/>
										<input
											id={ 'time-picker-' + blockID }
											type="time"
										/>
									</>
								);
						}
					} )() }
				</div>
				{ help !== '' && (
					<label
						htmlFor={ 'date-picker-help-' + blockID }
						className="date-secondary sf-text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
