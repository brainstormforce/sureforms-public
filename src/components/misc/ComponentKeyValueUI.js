import { useState, useEffect } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';
import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';

const ComponentKeyValueUI = ( {
	data,
	label,
	helpText,
	header,
	withSmartTags,
	handleOnChange,
	enabled,
	setEnabled,
} ) => {
	const deleteIcons = parse( svgIcons.delete );

	const [ localData, setLocalData ] = useState( data );

	useEffect( () => {
		if ( Array.isArray( data ) ) {
			setLocalData( data );
		} else if ( data && typeof data === 'object' ) {
			setLocalData( [ data ] );
		} else {
			setLocalData( [ { '': '' } ] );
		}
	}, [ data ] );

	const addNew = ( index ) => {
		const newData = [
			...localData.slice( 0, index + 1 ),
			{ '': '' },
			...localData.slice( index + 1 ),
		];
		handleOnChange( newData );
	};

	const deleteItem = ( index ) => {
		const tempData = [ ...localData ];
		const newData = [
			...tempData.slice( 0, index ),
			...tempData.slice( index + 1 ),
		];
		setLocalData( newData.length ? newData : [ { '': '' } ] );
		handleOnChange( newData.length ? newData : [ { '': '' } ] );
	};

	const updateKey = ( index, newKey ) => {
		const currentValue = Object.values( localData[ index ] )[ 0 ] ?? '';
		const newData = [ ...localData ];
		newData[ index ] = { [ newKey ]: currentValue };
		handleOnChange( newData );
	};

	const updateValue = ( index, newValue ) => {
		const currentKey = Object.keys( localData[ index ] )[ 0 ] ?? '';
		const newData = [ ...localData ];
		newData[ index ] = { [ currentKey ]: newValue };
		handleOnChange( newData );
	};

	return (
		<div className="srfm-key-value-pairs">
			<CheckboxControl
				label={ label ?? '' }
				help={ helpText ?? '' }
				checked={ enabled }
				onChange={ ( checked ) => {
					setEnabled( checked );
				} }
			/>
			{ enabled && (
				<div className="srfm-modal-input-box">
					<div className="srfm-modal-label">
						<label>{ header }</label>
					</div>
					{ localData.map( ( dataItem, index ) => (
						<div
							key={ `keyValue_${ index }` }
							className="srfm-flex srfm-flex-row srfm-gap-normal srfm-flex-width-100"
						>
							<div className="srfm-flex-2 srfm-flex srfm-flex-row srfm-gap-normal">
								<TextControl
									type="text"
									placeholder={ __( 'Add Key', 'sureforms' ) }
									defaultValue={
										Object.keys( dataItem )[ 0 ] ?? ''
									}
									onChange={ ( newKey ) =>
										updateKey( index, newKey )
									}
								/>
								<TextControl
									type="text"
									placeholder={ __(
										'Add Value',
										'sureforms'
									) }
									defaultValue={
										Object.values( dataItem )[ 0 ] ?? ''
									}
									withSmartTags={ withSmartTags }
									onChange={ ( newValue ) =>
										updateValue( index, newValue )
									}
								/>
							</div>
							<div className="srfm-flex srfm-flex-row srfm-gap-normal">
								<button
									className="srfm-button-secondary srfm-button-xs"
									onClick={ () => addNew( index ) }
								>
									{ __( 'Add', 'sureforms' ) }
								</button>
								<button
									className="srfm-modal-action"
									onClick={ () => deleteItem( index ) }
								>
									{ deleteIcons }
								</button>
							</div>
						</div>
					) ) }
				</div>
			) }
		</div>
	);
};

const TextControl = ( {
	defaultValue,
	onChange,
	placeholder,
	type,
	label,
	withSmartTags,
} ) => {
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];
	const [ input, setInput ] = useState( defaultValue );
	const setData = ( data ) => {
		onChange( data );
		setInput( data );
	};
	return (
		<>
			{ label && (
				<div className="srfm-modal-label">
					<label>{ label }</label>
				</div>
			) }
			<input
				onChange={ ( e ) => {
					setData( e.target.value );
				} }
				value={ input }
				className="srfm-modal-input with-icon"
				type={ type ?? 'text' }
				placeholder={ placeholder ?? '' }
			/>
			{ withSmartTags && (
				<SmartTagList
					tagsArray={ [
						{
							tags: formSmartTags,
							label: __( 'Form input tags', 'sureforms' ),
						},
					] }
					setTargetData={ setData }
				/>
			) }
		</>
	);
};

export default ComponentKeyValueUI;
