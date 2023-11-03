export const NumberThemeStyle = ( { attributes, blockID, handleInput } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		minValue,
		maxValue,
		formatType,
	} = attributes;

	return (
		<>
			<label
				className="srfm-text-primary"
				htmlFor={ 'srfm-number-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'srfm-number-input-' + blockID }
				type={ formatType === 'none' ? 'number' : 'text' }
				value={ defaultValue }
				onChange={ handleInput }
				placeholder={ placeholder }
				required={ required }
				min={ minValue }
				max={ maxValue }
			/>
		</>
	);
};
