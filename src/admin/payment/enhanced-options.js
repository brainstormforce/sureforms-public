import { MdDragIndicator } from 'react-icons/md';
import UAGIconPicker from '@Components/icon-picker';
import SRFMMediaPicker from '@Components/image';
import SRFMTextControl from '@Components/text-control';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import { Trash2 } from 'lucide-react';

const DragIcon = ( { param } ) => (
	<span { ...param.dragHandleProps }>
		<MdDragIndicator
			style={ {
				width: '20px',
				height: '20px',
			} }
		/>
	</span>
);

const TextControl = ( { value, onChange } ) => (
	<div className="srfm-option-outer-text-control">
		<SRFMTextControl
			showHeaderControls={ false }
			value={ value }
			data={ {
				value,
				label: 'option',
			} }
			onChange={ onChange }
		/>
	</div>
);

const OptionTextControl = ( { value, onChange } ) => {
	// Format value to allow only numbers and negative sign.
	const formatValue = ( _value ) => {
		if ( _value === undefined ) {
			_value = '';
		}

		if ( _value.length === 1 && _value.startsWith( '-' ) ) {
			// It means, user has just started negative value. Eg: "-".
			return _value;
		}

		return isNaN( _value ) ? '' : _value;
	};

	return (
		<div className="srfm-text-control srfm-option-value">
			<input
				className="components-text-control__input"
				type="text"
				value={ formatValue( value ) }
				onChange={ ( e ) => {
					onChange( formatValue( e.target.value ) );
				} }
			/>
		</div>
	);
};

const DeleteOption = ( { onClick } ) => (
	<div className="srfm-options-delete">
		<Trash2
			style={ {
				width: '20px',
				height: '20px',
				color: '#6B7280',
			} }
			onClick={ onClick }
		/>
	</div>
);

const IconPicker = ( { value, onChange } ) => (
	<div className="srfm-icon-picker">
		<UAGIconPicker
			label={ '' }
			value={ value }
			onChange={ onChange }
			addIcon={ parse( svgIcons.custom_plus_icon ) }
		/>
	</div>
);

export const EnhancedMultiChoiceOptions = ( defaultOption, args ) => {
	const { option, param, i, editOption, changeOption, deleteOption } = args;

	if ( ! args?.props?.attributes?.showValues ) {
		return defaultOption;
	}

	const { optionType } = args.props.attributes;

	// This function adds url of media chosen by user to an option.
	const onSelectImage = ( media, index ) => {
		const url = media?.url ? media.url : '';
		changeOption( { image: url }, index );
	};

	// Removes chose image from and option.
	const onRemoveImage = ( index ) => {
		changeOption( { image: '' }, index );
	};

	return (
		<>
			<div>
				<DragIcon param={ param } />
				<TextControl
					value={ option.optionTitle }
					onChange={ ( value ) => editOption( value, i ) }
				/>
				<OptionTextControl
					value={ option?.value || '' }
					onChange={ ( value ) => changeOption( { value }, i ) }
				/>
				{ optionType === 'icon' && (
					<IconPicker
						value={ option.icon }
						onChange={ ( value ) =>
							changeOption( { icon: value }, i )
						}
					/>
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
				<DeleteOption onClick={ () => deleteOption( i ) } />
			</div>
		</>
	);
};

export const EnhancedDropdownOptions = ( defaultOption, args ) => {
	const { option, param, i, editOption, changeOption, handleDelete } = args;

	if ( ! args?.props?.attributes?.showValues ) {
		return defaultOption;
	}

	return (
		<>
			<div>
				<DragIcon param={ param } />
				<TextControl
					value={ option.label }
					onChange={ ( value ) => editOption( value, i ) }
				/>
				<OptionTextControl
					value={ option?.value || '' }
					onChange={ ( value ) => changeOption( { value }, i ) }
				/>
				<IconPicker
					value={ option.icon }
					onChange={ ( value ) => changeOption( { icon: value }, i ) }
				/>
				<DeleteOption onClick={ () => handleDelete( i ) } />
			</div>
		</>
	);
};
