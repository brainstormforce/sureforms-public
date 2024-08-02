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
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
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

import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { MultiChoiceComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import ConditionalLogic from '@Components/conditional-logic';
import MultiButtonsControl from '@Components/multi-buttons-control';
import UAGIconPicker from '@Components/icon-picker';
import SRFMMediaPicker from '@Components/image';

const Edit = ( { attributes, setAttributes, isSelected, clientId } ) => {
	const {
		required,
		options,
		fieldWidth,
		choiceWidth,
		singleSelection,
		help,
		block_id,
		errorMsg,
		formId,
		preview,
		verticalLayout,
		optionType,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ newOption, setNewOption ] = useState( options );
	const blockProps = useBlockProps();

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

	const changeOption = ( option, index ) => {
		const newEditOptions = options.map( ( item, thisIndex ) => {
			if ( index === thisIndex ) {
				item = { ...item, ...option };
			}
			return item;
		} );

		setAttributes( { options: newEditOptions } );
	};

	function editOption( value, i ) {
		if ( value === '' ) {
			deleteOption( i );
			return;
		}

		const newEditOptions = options.map( ( item, thisIndex ) => {
			if ( i === thisIndex ) {
				item = { ...item, ...{ optionTitle: value } };
			}
			return item;
		} );

		setAttributes( { options: newEditOptions } );
	}

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_multi_choice_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.multi_choice_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const onSelectImage = ( media, index ) => {
		const url = media?.sizes?.thumbnail?.url
			? media?.sizes?.thumbnail?.url
			: media?.url
				? media.url
				: '';
		changeOption( { image: url }, index );
		// if ( ! media || ! media.url ) {
		// 	setAttributes( { [ backgroundImage.label ]: null } );
		// 	return;
		// }

		// if ( ! media.type || 'image' !== media.type ) {
		// 	return;
		// }

		// setAttributes( { [ backgroundImage.label ]: media } );
	};

	const onRemoveImage = ( index ) => {
		changeOption( { image: '' }, index );
	};

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
									label={ __( 'Error Message', 'sureforms' ) }
									value={ currentErrorMsg }
									onChange={ ( value ) => {
										setCurrentErrorMsg( value );
										setAttributes( { errorMsg: value } );
									} }
								/>
							) }
							<ToggleControl
								label={ __( 'Vertical Layout', 'sureforms' ) }
								checked={ verticalLayout }
								onChange={ ( checked ) =>
									setAttributes( { verticalLayout: checked } )
								}
							/>
							<ToggleControl
								label={ __(
									'Allow Only Single Selection',
									'sureforms'
								) }
								checked={ singleSelection }
								onChange={ ( checked ) =>
									setAttributes( {
										singleSelection: checked,
									} )
								}
							/>
							<SelectControl
								label={ __( 'Choice Width', 'sureforms' ) }
								value={ choiceWidth }
								options={ widthOptions }
								onChange={ ( value ) =>
									setAttributes( {
										choiceWidth: Number( value ),
									} )
								}
								__nextHasNoMarginBottom
							/>
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
										<MultiButtonsControl
											setAttributes={ setAttributes }
											label={ __(
												'Option Type',
												'sureforms'
											) }
											data={ {
												value: optionType,
												label: 'optionType',
											} }
											options={ [
												{
													value: 'icon',
													label: __(
														'Icon',
														'sureforms'
													),
												},
												{
													value: 'image',
													label: __(
														'Image',
														'sureforms'
													),
												},
											] }
											showIcons={ false }
										/>
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
																							option.optionTitle
																						}
																						data={ {
																							value: option.optionTitle,
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
																					{ optionType ===
																						'icon' && (
																						<div className="srfm-icon-picker">
																							<UAGIconPicker
																								label={
																									''
																								}
																								value={
																									option.icon
																								}
																								onChange={ (
																									value
																								) =>
																									changeOption(
																										{
																											icon: value,
																										},
																										i
																									)
																								}
																							/>
																						</div>
																					) }
																					{ optionType ===
																						'image' && (
																						<div className="srfm-media-picker">
																							<SRFMMediaPicker
																								onSelectImage={ (
																									e
																								) => {
																									onSelectImage(
																										e,
																										i
																									);
																								} }
																								backgroundImage={
																									option.image
																								}
																								onRemoveImage={ () => {
																									onRemoveImage(
																										i
																									);
																								} }
																								disableLabel={
																									true
																								}
																							/>
																						</div>
																					) }
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
							<div className="sureform-add-option-container">
								<TextControl
									label={ __(
										'Add New Option',
										'sureforms'
									) }
									value={ newOption.optionTitle }
									onChange={ ( value ) =>
										setNewOption( { optionTitle: value } )
									}
								/>
								<Button
									className="sureform-add-option-button"
									variant="secondary"
									onClick={ () => {
										if (
											newOption?.optionTitle &&
											newOption?.optionTitle
										) {
											setAttributes( {
												options: [
													...options,
													{
														optionTitle:
															newOption.optionTitle,
													},
												],
											} );
											setNewOption( { optionTitle: '' } );
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
								label={ __( 'Help Text', 'sureforms' ) }
								value={ help }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
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
			<MultiChoiceComponent
				blockID={ block_id }
				{ ...{ attributes, isSelected, setAttributes, optionType } }
			/>
			<div className="srfm-error-wrap"></div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
