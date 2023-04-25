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
	const { required, options, label, help } = attributes;
	const blockID = useBlockProps().id;

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
								id="for-dropdown-label"
								label={ __( 'Label', 'sureforms' ) }
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
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'dropdown-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<select
					id={ 'dropdown-' + blockID }
					style={ { width: 'fit-content' } }
					required={ required }
				>
					{ options.map( ( option, i ) => {
						return (
							<option label={ option } key={ i }>
								{ option }
							</option>
						);
					} ) }
				</select>
				{ help !== '' && (
					<label
						htmlFor={ 'dropdown-help-' + blockID }
						style={ { color: '#ddd' } }
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
}
