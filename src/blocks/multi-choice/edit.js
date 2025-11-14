/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl, Button } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useErrMessage, checkInvalidCharacter } from '@Blocks/util';
import svgIcons from '@Svg/svgs.json';
import parse from 'html-react-parser';
import { MdDragIndicator } from 'react-icons/md';

/**
 * Component Dependencies
 */
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { MultiChoiceComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import ConditionalLogic from '@Components/conditional-logic';
import MultiButtonsControl from '@Components/multi-buttons-control';
import UAGIconPicker from '@Components/icon-picker';
import SRFMMediaPicker from '@Components/image';
import SRFMNumberControl from '@Components/number-control';
import { BulkInserterWithButton } from '@Components/bulk-inserter';
import {
	attributeOptionsWithFilter,
	enhanceMultiChoiceOptions,
} from '@Components/hooks';

const Edit = ( props ) => {
	const { attributes, setAttributes, isSelected, clientId } = props;
	const {
		required,
		options,
		choiceWidth,
		singleSelection,
		help,
		block_id,
		errorMsg,
		formId,
		preview,
		verticalLayout,
		optionType,
		minValue,
		maxValue,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const [ newOption, setNewOption ] = useState();
	const [ error, setError ] = useState( false );
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

		if ( checkInvalidCharacter( value ) ) {
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

	const addOption = ( value ) => {
		setAttributes( {
			options: [
				...options,
				{
					optionTitle: value,
				},
			],
		} );
		setNewOption( '' );
	};

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

	// This function adds url of media chosen by user to an option.
	const onSelectImage = ( media, index ) => {
		const url = media?.url ? media.url : '';
		changeOption( { image: url }, index );
	};

	// Removes chose image from and option.
	const onRemoveImage = ( index ) => {
		changeOption( { image: '' }, index );
	};

	const minMaxValue = ! singleSelection && options.length > 1 && (
		<>
			<SRFMNumberControl
				label={ __( 'Minimum Selections', 'sureforms' ) }
				displayUnit={ false }
				data={ {
					value: minValue,
					label: 'minValue',
				} }
				min={ 1 }
				max={ ( maxValue || options.length ) - 1 }
				value={ minValue }
				onChange={ ( value ) => {
					if ( value >= maxValue ) {
						setError( true );
						setAttributes( {
							minValue: 0,
						} );
					} else {
						setError( false );
						setAttributes( {
							minValue: value,
						} );
					}
				} }
				showControlHeader={ false }
			/>
			<SRFMNumberControl
				label={ __( 'Maximum Selections', 'sureforms' ) }
				displayUnit={ false }
				data={ {
					value: maxValue,
					label: 'maxValue',
				} }
				min={ minValue + 1 || 1 }
				max={ options.length }
				value={ maxValue }
				onChange={ ( value ) => {
					if ( value <= minValue ) {
						setError( true );
						setAttributes( {
							maxValue: Number( minValue ) + 1,
						} );
					} else {
						setError( false );
						setAttributes( {
							maxValue: value,
						} );
					}
				} }
				showControlHeader={ false }
			/>
			{ error && (
				<p className="srfm-min-max-error-styles">
					{ __(
						'Please check the Minimum and Maximum value',
						'sureforms'
					) }
				</p>
			) }
		</>
	);

	const draggableItem = ( option, param, i ) => (
		<>
			<div>
				<span { ...param.dragHandleProps }>
					<MdDragIndicator
						style={ {
							width: '20px',
							height: '20px',
						} }
					/>
				</span>
				<div>
					<SRFMTextControl
						showHeaderControls={ false }
						key={ i }
						value={ option.optionTitle }
						data={ {
							value: option.optionTitle,
							label: 'option',
						} }
						onChange={ ( value ) => editOption( value, i ) }
					/>
				</div>
				{ optionType === 'icon' && (
					<div className="srfm-icon-picker">
						<UAGIconPicker
							label={ '' }
							value={ option.icon }
							onChange={ ( value ) =>
								changeOption( { icon: value }, i )
							}
							addIcon={ parse( svgIcons.custom_plus_icon ) }
						/>
					</div>
				) }
				{ optionType === 'image' && (
					<div className="srfm-media-picker">
						<SRFMMediaPicker
							onSelectImage={ ( e ) => {
								onSelectImage( e, i );
							} }
							backgroundImage={ option.image }
							onRemoveImage={ () => {
								onRemoveImage( i );
							} }
							disableLabel={ true }
						/>
					</div>
				) }
				<Button icon="trash" onClick={ () => deleteOption( i ) } />
			</div>
		</>
	);

	const draggableOptions = ( dragOptions ) =>
		dragOptions.map( ( option, i ) => (
			<Draggable key={ i } draggableId={ 'draggable-' + i } index={ i }>
				{ ( param ) => (
					<div
						ref={ param.innerRef }
						className="srfm-option-outer-wrapper"
						{ ...param.draggableProps }
					>
						{ enhanceMultiChoiceOptions(
							draggableItem( option, param, i ),
							{
								props,
								option,
								param,
								i,
								editOption,
								changeOption,
								deleteOption,
							}
						) }
					</div>
				) }
			</Draggable>
		) );

	const choicesOptions = (
		<div style={ { marginBottom: '8px' } }>
			{ options.length > 0 && (
				<>
					<MultiButtonsControl
						setAttributes={ setAttributes }
						label={ __( 'Option Type', 'sureforms' ) }
						data={ {
							value: optionType,
							label: 'optionType',
						} }
						options={ [
							{
								value: 'icon',
								label: __( 'Icon', 'sureforms' ),
							},
							{
								value: 'image',
								label: __( 'Image', 'sureforms' ),
							},
						] }
						showIcons={ false }
					/>
					<span className="srfm-control-label srfm-control__header">
						{ __( 'Edit Options', 'sureforms' ) }
					</span>
					<DragDropContext
						onDragEnd={ ( param ) => {
							const srcI = param.source.index;
							const destI = param.destination.index;
							if ( srcI !== destI ) {
								const newOptions = [ ...options ];
								newOptions.splice(
									destI,
									0,
									newOptions.splice( srcI, 1 )[ 0 ]
								);
								setAttributes( {
									options: newOptions,
								} );
							}
						} }
					>
						<Droppable droppableId="droppable-1">
							{ ( provided ) => (
								<div
									ref={ provided.innerRef }
									{ ...provided.droppableProps }
								>
									{ draggableOptions( options ) }
									{ provided.placeholder }
								</div>
							) }
						</Droppable>
					</DragDropContext>
				</>
			) }
		</div>
	);

	const addNewOption = (
		<>
			<div
				className="sureform-add-option-container"
				onKeyDown={ ( event ) => {
					if ( event.key === 'Enter' && newOption ) {
						addOption( newOption );
					}
				} }
			>
				<SRFMTextControl
					showHeaderControls={ false }
					label={ __( 'Add New Option', 'sureforms' ) }
					value={ newOption }
					onChange={ ( value ) => {
						if ( checkInvalidCharacter( value ) ) {
							return;
						}

						setNewOption( value );
					} }
				/>
				<Button
					className="sureform-add-option-button"
					variant="secondary"
					onClick={ () => {
						if ( newOption ) {
							addOption( newOption );
						} else {
							// TODO: May be add a tooltip here
						}
					} }
				>
					{ __( 'ADD', 'sureforms' ) }
				</Button>
				<BulkInserterWithButton
					options={ options }
					titleKey="optionTitle"
					insertOptions={ ( newOptions, closeModal ) => {
						setAttributes( {
							options: newOptions,
						} );
						closeModal();
					} }
				/>
			</div>
			<span className="srfm-control-label srfm-control__header" />
		</>
	);

	const attributeOptions = [
		{
			id: 'required',
			component: (
				<ToggleControl
					label={ __( 'Required', 'sureforms' ) }
					checked={ required }
					onChange={ ( checked ) =>
						setAttributes( { required: checked } )
					}
				/>
			),
		},
		{
			id: 'errorMsg',
			component: required && (
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
			),
		},
		{
			id: 'help',
			component: (
				<SRFMTextControl
					variant="textarea"
					data={ {
						value: help,
						label: 'help',
					} }
					label={ __( 'Help Text', 'sureforms' ) }
					value={ help }
					onChange={ ( value ) => setAttributes( { help: value } ) }
				/>
			),
		},
		{
			id: 'separator-1',
			component: <div className="srfm-settings-separator" />,
		},
		{
			id: 'choicesOptions',
			component: choicesOptions,
		},
		{
			id: 'addNewOption',
			component: addNewOption,
		},
		{
			id: 'singleSelection',
			component: (
				<ToggleControl
					label={ __( 'Single Choice Only', 'sureforms' ) }
					checked={ singleSelection }
					onChange={ ( checked ) =>
						setAttributes( { singleSelection: checked } )
					}
				/>
			),
		},
		{
			id: 'choiceWidth',
			component: (
				<SelectControl
					label={ __( 'Choice Width', 'sureforms' ) }
					value={ choiceWidth }
					options={ [
						{
							label: __( 'Full Width', 'sureforms' ),
							value: 100,
						},
						{
							label: __( 'Two Columns', 'sureforms' ),
							value: 50,
						},
						{
							label: __( 'Three Columns', 'sureforms' ),
							value: 33.33,
						},
						{
							label: __( 'Four Columns', 'sureforms' ),
							value: 25,
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { choiceWidth: Number( value ) } )
					}
					__nextHasNoMarginBottom
				/>
			),
		},
		{
			id: 'verticalLayout',
			component: (
				<ToggleControl
					label={ __( 'Vertical Layout', 'sureforms' ) }
					checked={ verticalLayout }
					onChange={ ( checked ) =>
						setAttributes( { verticalLayout: checked } )
					}
				/>
			),
		},
		{
			id: 'min-max',
			component: minMaxValue,
		},
		{
			id: 'control-label-span',

			component: (
				<span className="srfm-control-label srfm-control__header" />
			),
		},
	];

	const filterOptions = attributeOptionsWithFilter( attributeOptions, props );

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
							{ filterOptions.map(
								( option ) => option.component
							) }
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
