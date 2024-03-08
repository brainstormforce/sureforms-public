/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	Button,
	Icon,
	TextControl,
} from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';

import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useErrMessage } from '@Blocks/util';

/**
 * Component Dependencies
 */
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { DropdownComponent } from './components/default';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		required,
		options,
		fieldWidth,
		label,
		help,
		block_id,
		errorMsg,
		formId,
		placeholder,
		preview,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ newOption, setNewOption ] = useState( '' );

	function editOption( value, i ) {
		if ( value === '' ) {
			handleDelete( i );
			return;
		}
		const updatedOptions = [ ...options ];
		updatedOptions[ i ] = value;
		setAttributes( { options: updatedOptions } );
	}

	function handleDelete( i ) {
		const newOptions = [ ...options ];
		newOptions.splice( i, 1 );
		setAttributes( { options: newOptions } );
	}

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_dropdown_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.dropdown_preview;
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
							<SRFMTextControl
								label={ __( 'Placeholder', 'sureforms' ) }
								data={ {
									value: placeholder,
									label: 'placeholder',
								} }
								value={ placeholder }
								onChange={ ( value ) => {
									setAttributes( { placeholder: value } );
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
									value={ currentErrorMsg }
									onChange={ ( value ) => {
										setCurrentErrorMsg( value );
										setAttributes( { errorMsg: value } );
									} }
								/>
							) }
							<div style={ { marginBottom: '8px' } }>
								{ options.length > 0 && (
									<DragDropContext
										onDragEnd={ ( param ) => {
											const srcI = param.source.index;
											const destI =
												param.destination.index;
											if ( srcI !== destI ) {
												const newOptions = [
													...options,
												];
												newOptions.splice(
													destI,
													0,
													newOptions.splice(
														srcI,
														1
													)[ 0 ]
												);
												setAttributes( {
													options: newOptions,
												} );
											}
										} }
									>
										<span className="srfm-control-label srfm-control__header">
											{ __(
												'Edit Options',
												'sureforms'
											) }
										</span>
										<>
											<Droppable droppableId="droppable-1">
												{ ( provided ) => (
													<div
														ref={
															provided.innerRef
														}
														{ ...provided.droppableProps }
													>
														{ options.map(
															( option, i ) => (
																<Draggable
																	key={ i }
																	draggableId={
																		'draggable-' +
																		i
																	}
																	index={ i }
																>
																	{ (
																		param
																	) => (
																		<div
																			ref={
																				param.innerRef
																			}
																			{ ...param.draggableProps }
																		>
																			<div
																				style={ {
																					display:
																						'flex',
																					alignItems:
																						'center',
																					gap: '10px',
																				} }
																			>
																				<>
																					<Icon
																						icon={
																							'move'
																						}
																						{ ...param.dragHandleProps }
																					/>
																				</>
																				<div
																					style={ {
																						marginBottom:
																							'0',
																					} }
																				>
																					<SRFMTextControl
																						showHeaderControls={
																							false
																						}
																						key={
																							i
																						}
																						value={
																							option
																						}
																						data={ {
																							value: option,
																							label: 'option',
																						} }
																						onChange={ (
																							value
																						) =>
																							editOption(
																								value,
																								i
																							)
																						}
																					/>
																				</div>
																				<>
																					<Button
																						icon="trash"
																						onClick={ () =>
																							handleDelete(
																								i
																							)
																						}
																					/>
																				</>
																			</div>
																		</div>
																	) }
																</Draggable>
															)
														) }
														{ provided.placeholder }
													</div>
												) }
											</Droppable>
										</>
									</DragDropContext>
								) }
							</div>
							<div className="sureform-add-option-container">
								<TextControl
									label={ __(
										'Add New Option',
										'sureforms'
									) }
									value={ newOption }
									onChange={ ( value ) =>
										setNewOption( value )
									}
								/>
								<Button
									className="sureform-add-option-button"
									variant="secondary"
									onClick={ () => {
										if ( newOption && newOption ) {
											setAttributes( {
												options: [
													...options,
													newOption,
												],
											} );
											setNewOption( '' );
										} else {
											// TODO: May be add a tooltip here
										}
									} }
								>
									{ __( 'ADD', 'sureforms' ) }
								</Button>
							</div>
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
			<DropdownComponent
				attributes={ attributes }
				blockID={ block_id }
				setAttributes={ setAttributes }
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
