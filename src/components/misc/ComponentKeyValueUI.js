import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SmartTagList from '@Components/misc/SmartTagList';
import { Button, Checkbox, Input, Label } from '@bsf/force-ui';
import { Trash2Icon, PlusIcon } from 'lucide-react';
import { cn } from '@Utils/Helpers';

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
	const [ localData, setLocalData ] = useState( data );

	useEffect( () => {
		if ( Array.isArray( data ) && data.length ) {
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
		<div className="space-y-6">
			<Checkbox
				label={ {
					heading: label ?? '',
					description: helpText ?? '',
				} }
				checked={ enabled }
				onChange={ ( checked ) => {
					setEnabled( checked );
				} }
				size="sm"
			/>
			{ enabled && (
				<div className="space-y-1.5 w-full">
					<Label>{ header }</Label>
					<div className="space-y-2">
						{ localData.map( ( dataItem, index ) => (
							<div
								key={ `keyValue_${ index }` }
								className="flex items-center justify-between gap-3 w-full"
							>
								<div className="flex gap-3 items-center w-full">
									<TextControl
										type="text"
										placeholder={ __(
											'Add Key',
											'sureforms'
										) }
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
								<div className="flex gap-3 items-center justify-end">
									<Button
										size="md"
										variant="outline"
										onClick={ () => addNew( index ) }
										icon={ <PlusIcon /> }
										iconPosition="left"
									>
										{ __( 'Add', 'sureforms' ) }
									</Button>
									<Button
										disabled={ localData?.length === 1 }
										variant="ghost"
										size="sm"
										onClick={ () => deleteItem( index ) }
										icon={ <Trash2Icon /> }
									/>
								</div>
							</div>
						) ) }
					</div>
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
		<div className="space-y-1.5 flex-1">
			{ label && <Label>{ label }</Label> }
			<Input
				className={ cn(
					withSmartTags &&
						'[&>input]:pr-9 [&>input+div]:right-0 [&>input+div]:pr-2'
				) }
				onChange={ ( value ) => {
					setData( value );
				} }
				value={ input }
				type={ type ?? 'text' }
				placeholder={ placeholder ?? '' }
				suffix={
					withSmartTags && (
						<SmartTagList
							tagsArray={ [
								{
									tags: formSmartTags,
									label: __( 'Form input tags', 'sureforms' ),
								},
							] }
							setTargetData={ setData }
							triggerSize="xs"
							triggerClassName="!pointer-events-auto [box-shadow:none]"
							dropdownPlacement="bottom-end"
						/>
					)
				}
				size="md"
			/>
		</div>
	);
};

export default ComponentKeyValueUI;
