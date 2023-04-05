/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	Button,
	TextControl,
	Icon,
	BaseControl,
} from '@wordpress/components';

/**
 * Component Dependencies
 */
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

/**
 * Editor stylesheet
 */
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { options, label } = attributes;

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

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title="Dropdown Settings">
					<PanelRow>
						<div>
							<BaseControl
								id="for-dropdown-label"
								label={ __( 'Edit Title', 'sureforms' ) }
							>
								<TextControl
									id="dropdown-label"
									value={ label }
									onChange={ ( value ) =>
										setAttributes( { label: value } )
									}
								/>
							</BaseControl>
						</div>
					</PanelRow>
					<PanelRow>
						<div style={ { marginBottom: '8px' } }>
							{ options.length > 0 && (
								<DragDropContext
									onDragEnd={ ( param ) => {
										const srcI = param.source.index;
										const destI = param.destination.index;
										if ( srcI !== destI ) {
											const newOptions = [ ...options ];
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
									<BaseControl
										id="for-edit-options"
										label={ __(
											'Edit Options',
											'sureforms'
										) }
									>
										<Droppable droppableId="droppable-1">
											{ ( provided ) => (
												<div
													ref={ provided.innerRef }
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
																{ ( param ) => (
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
																				<TextControl
																					key={
																						i
																					}
																					value={
																						option
																					}
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
									</BaseControl>
								</DragDropContext>
							) }
						</div>
					</PanelRow>
					<PanelRow>
						<div>
							<BaseControl
								id="for-add-option"
								label={ __( 'Add New Option', 'sureforms' ) }
							>
								<form
									onSubmit={ ( e ) => {
										e.preventDefault();
										if ( e.target.addOption.value !== '' ) {
											setAttributes( {
												options: [
													...options,
													e.target.addOption.value,
												],
											} );
											e.target.addOption.value = '';
										}
									} }
									style={ { display: 'flex' } }
								>
									<div>
										<TextControl
											id="add-option"
											required
											autoComplete="off"
											name="addOption"
										/>
									</div>
									<Button className="btn" type="submit">
										<div>
											<BaseControl
												id="for-btn"
												label={ __(
													'ADD',
													'sureforms'
												) }
											/>
										</div>
									</Button>
								</form>
							</BaseControl>
						</div>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div>
				<label htmlFor="dropdown">{ label }</label>
				<SelectControl id="dropdown">
					{ options.map( ( option, i ) => {
						return (
							<option label={ option } key={ i }>
								{ option }
							</option>
						);
					} ) }
				</SelectControl>
			</div>
		</div>
	);
}
