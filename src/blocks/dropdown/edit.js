/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, Button } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
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
import { DropdownComponent } from './components/default';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import ConditionalLogic from '@Components/conditional-logic';
import UAGIconPicker from '@Components/icon-picker';
import SRFMNumberControl from '@Components/number-control';
import { BulkInserterWithButton } from '@Components/bulk-inserter';
import {
	attributeOptionsWithFilter,
	enhanceDropdownOptions,
} from '@Components/hooks';

const Edit = ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const {
		required,
		options,
		help,
		block_id,
		errorMsg,
		formId,
		preview,
		className,
		multiSelect,
		searchable,
		minValue,
		maxValue,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ newOption, setNewOption ] = useState( '' );
	const [ error, setError ] = useState( false );

	const changeOption = ( value, index ) => {
		const updatedOptions = options.map( ( item, thisIndex ) => {
			if ( index === thisIndex ) {
				item = { ...item, ...value };
			}
			return item;
		} );

		setAttributes( { options: updatedOptions } );
	};

	function editOption( value, i ) {
		if ( value === '' ) {
			handleDelete( i );
			return;
		}

		if ( checkInvalidCharacter( value ) ) {
			return;
		}

		changeOption( { label: value }, i );
	}

	function handleDelete( i ) {
		const newOptions = [ ...options ];
		newOptions.splice( i, 1 );
		setAttributes( { options: newOptions } );
	}

	const addOption = ( value ) => {
		setAttributes( {
			options: [
				...options,
				{
					label: value,
					icon: '',
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
	} = useErrMessage( 'srfm_dropdown_block_required_text', errorMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.dropdown_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const minMaxComponent = multiSelect && options.length > 1 && (
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

	const draggableItem = ( option, param, i ) => {
		return (
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
							value={ option.label }
							data={ {
								value: option.label,
								label: 'option',
							} }
							onChange={ ( value ) => editOption( value, i ) }
						/>
					</div>
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
					<Button icon="trash" onClick={ () => handleDelete( i ) } />
				</div>
			</>
		);
	};

	const draggableOptions = ( dragOptions ) =>
		dragOptions.map( ( option, i ) => (
			<Draggable key={ i } draggableId={ 'draggable-' + i } index={ i }>
				{ ( param ) => (
					<div
						ref={ param.innerRef }
						className="srfm-option-outer-wrapper"
						{ ...param.draggableProps }
					>
						{ enhanceDropdownOptions(
							draggableItem( option, param, i ),
							{
								props,
								option,
								param,
								i,
								editOption,
								changeOption,
								handleDelete,
							}
						) }
					</div>
				) }
			</Draggable>
		) );

	const dropDownOptions = (
		<div style={ { marginBottom: '8px' } }>
			{ options.length > 0 && (
				<>
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
					titleKey="label"
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
			component: required ? (
				<SRFMTextControl
					label={ __( 'Error Message', 'sureforms' ) }
					data={ {
						value: errorMsg,
						label: 'errorMsg',
					} }
					value={ currentErrorMsg }
					onChange={ ( value ) => {
						setCurrentErrorMsg( value );
						setAttributes( { errorMsg: value } );
					} }
				/>
			) : null,
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
			id: 'multiSelect',
			component: (
				<ToggleControl
					label={ __( 'Allow Multiple', 'sureforms' ) }
					checked={ multiSelect }
					onChange={ ( checked ) =>
						setAttributes( { multiSelect: checked } )
					}
				/>
			),
		},
		{
			id: 'minMaxComponent',
			component: minMaxComponent,
		},
		{
			id: 'searchable',
			component: (
				<ToggleControl
					label={ __( 'Enable Dropdown Search', 'sureforms' ) }
					checked={ searchable }
					onChange={ ( checked ) =>
						setAttributes( { searchable: checked } )
					}
				/>
			),
		},

		{
			id: 'dropDownOptions',
			component: dropDownOptions,
		},
		{
			id: 'addNewOption',
			component: addNewOption,
		},
	];

	const filterOptions = attributeOptionsWithFilter( attributeOptions, props );

	return (
		<div className={ className }>
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
			<DropdownComponent
				attributes={ attributes }
				blockID={ block_id }
				setAttributes={ setAttributes }
			/>
			<div className="srfm-error-wrap"></div>
		</div>
	);
};
export default compose( AddInitialAttr )( Edit );
