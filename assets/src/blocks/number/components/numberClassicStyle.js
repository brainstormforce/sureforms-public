export const NumberClassicStyle = ( { attributes, blockID, handleInput } ) => {
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
			<div className="sf-classic-inputs-holder">
				<label
					className="sf-classic-label-text"
					htmlFor={ 'text-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					className={ ' sf-classic-number-element' }
					id={ 'number-input-' + blockID }
					type={ formatType === 'none' ? 'number' : 'text' }
					value={ defaultValue }
					onChange={ handleInput }
					placeholder={ placeholder }
					required={ required }
					min={ minValue }
					max={ maxValue }
				/>
			</div>
		</>
	);
};
