/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	Button,
	TextControl,
	Icon,
	BaseControl,
	RadioControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Component Dependencies
 */
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

export default ( { attributes, setAttributes, isSelected } ) => {
	const { required, options, label, singleSelection, style, help, id } =
		attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const [ selected, setSelected ] = useState( [] );

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
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<div>
							<BaseControl
								id="for-multi-choice-label"
								label={ __( 'Label / Question', 'sureforms' ) }
							>
								<TextControl
									id="multi-choice-label"
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
										} else {
											console.log( 'error' );
										}
									} }
									style={ { display: 'flex' } }
								>
									<div>
										<input
											id="add-option"
											required
											autoComplete="off"
											name="addOption"
										/>
									</div>
									<button
										className="btn"
										type="submit"
										style={ {
											background: 'none',
											border: 'none',
											cursor: 'pointer',
										} }
									>
										ADD
									</button>
								</form>
							</BaseControl>
						</div>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __(
								'Allow only single selection',
								'sureforms'
							) }
							checked={ singleSelection }
							onChange={ ( checked ) =>
								setAttributes( { singleSelection: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<RadioControl
							label={ __( 'Appearance', 'sureforms' ) }
							selected={ style }
							options={ [
								{
									label: __(
										'Radio/checkbox (default)',
										'sureforms'
									),
									value: 'default',
								},
								{
									label: __( 'Buttons', 'sureforms' ),
									value: 'buttons',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { style: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
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
				<label htmlFor={ 'multi-choice-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				{ options.map( ( option, i ) => {
					return (
						<div
							key={ i }
							style={ { display: 'flex', alignItems: 'center' } }
						>
							<input
								style={ {
									display:
										style === 'buttons'
											? 'none'
											: 'inherit',
								} }
								id={ 'multi-choice-' + blockID + '-i-' + i }
								type={ singleSelection ? 'radio' : 'checkbox' }
								key={ i }
								name={
									singleSelection
										? 'radio' + blockID
										: 'checkbox-' + blockID + '-' + i
								}
								onClick={ () => handleClick( i ) }
							/>
							<label
								htmlFor={
									'multi-choice-' + blockID + '-i-' + i
								}
								className={ 'sureforms-multi-choice-label-button' }
								style={
									style === 'buttons'
										? {
											border: '2px solid',
											borderRadius: '10px',
											padding:
													'.5rem 1rem .5rem 1rem',
											width: '100%',
											backgroundColor:
													selected.includes( i )
														? 'black'
														: 'white',
											color: selected.includes( i )
												? 'white'
												: 'black',
										  }
										: null
								}
							>
								<span
									className={
										'multi-choice-option' + blockID
									}
									id={
										'multi-choice-option' +
										blockID +
										'-i-' +
										i
									}
								>
									{ option }
								</span>
							</label>
						</div>
					);
				} ) }
				{ help !== '' && (
					<label
						htmlFor={ 'multi-choice-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
};
