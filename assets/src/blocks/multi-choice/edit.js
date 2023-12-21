/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	Button,
	Icon,
} from '@wordpress/components';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import MultiButtonsControl from '@Components/multi-buttons-control';
/**
 * Component Dependencies
 */
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { MultiChoiceComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, isSelected, clientId } ) => {
	const {
		required,
		options,
		fieldWidth,
		label,
		singleSelection,
		style,
		help,
		block_id,
		errorMsg,
		formId,
		preview,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );
	const [ selected, setSelected ] = useState( [] );
	const [ newOption, setNewOption ] = useState( { optiontitle: '' } );
	const blockProps = useBlockProps();

	function handleClick( index ) {
		if ( singleSelection === true ) {
			setSelected( [ index ] );
		} else if ( selected.includes( index ) ) {
			const updatedSelected = selected.filter(
				( item ) => item !== index
			);
			setSelected( updatedSelected );
		} else {
			setSelected( [ ...selected, index ] );
		}
	}

	const addOption = () => {
		const newOptions = {
			optiontitle:
				__( 'Option Name ', 'sureforms' ) + `${ options.length + 1 }`,
		};
		options[ options.length ] = newOptions;
		const addnewOptions = options.map( ( item ) => item );

		setAttributes( { options: addnewOptions } );
	};

	const changeOption = ( e, index ) => {
		const newEditOptions = options.map( ( item, thisIndex ) => {
			if ( index === thisIndex ) {
				item = { ...item, ...e };
			}
			return item;
		} );

		setAttributes( { options: newEditOptions } );
	};

	const deleteOption = ( index ) => {
		const deleteOptions = options.map( ( item, thisIndex ) => {
			if ( index === thisIndex ) {
				options.splice( index, 1 );
				item = { options };
			}
			return item;
		} );

		setAttributes( { deleteOptions } );
	};

	function editOption( value, i ) {
		if ( value === '' ) {
			deleteOption( i );
			return;
		}
		const updatedOptions = [ ...options ];
		updatedOptions[ i ].optiontitle = value;
		setAttributes( { options: updatedOptions } );
	}

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.multi_choice_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div { ...blockProps }>
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
																							option.optiontitle
																						}
																						data={ {
																							value: option.optiontitle,
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
																							deleteOption(
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
							<span className="srfm-control-label srfm-control__header">
								{ __( 'Add New Option', 'sureforms' ) }
							</span>
							<div className="sureform-add-option-container">
								<SRFMTextControl
									data={ {
										value: newOption.optiontitle,
										label: 'option',
									} }
									showHeaderControls={ false }
									value={ newOption.optiontitle }
									onChange={ ( value ) =>
										setNewOption( { optiontitle: value } )
									}
								/>
								<Button
									className="sureform-add-option-button"
									variant="secondary"
									onClick={ () => {
										if ( newOption !== '' ) {
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
							<ToggleControl
								label={ __(
									'Allow only single selection',
									'sureforms'
								) }
								checked={ singleSelection }
								onChange={ ( checked ) =>
									setAttributes( {
										singleSelection: checked,
									} )
								}
							/>
							{ 'classic' ===
							sureforms_keys?._srfm_form_styling ? null : (
									<MultiButtonsControl
										label={ __( 'Appearance', 'sureforms' ) }
										data={ {
											value: style,
											label: 'style',
										} }
										options={ [
											{
												value: 'default',
												icon: 'Radio',
											},
											{
												value: 'buttons',
												icon: 'Buttons',
											},
										] }
										showIcons={ true }
										onChange={ ( value ) => {
											if ( style !== value ) {
												setAttributes( {
													style: value,
												} );
											} else {
												setAttributes( {
													style: 'buttons',
												} );
											}
										} }
									/>
								) }
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<MultiChoiceComponent
				blockID={ block_id }
				attributes={ attributes }
				isSelected={ isSelected }
				addOption={ addOption }
				deleteOption={ deleteOption }
				changeOption={ changeOption }
				setAttributes={ setAttributes }
			/>

			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) =>
						setAttributes( { help: value } )
					}
					className="srfm-description"
					multiline={ false }
					id={ block_id }
				/>
			) }
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
