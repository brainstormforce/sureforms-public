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
			<div className="srfm-classic-inputs-holder">
				<label
					className="srfm-classic-label-text"
					htmlFor={ 'srfm-text-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					className={ ' srfm-classic-number-element' }
					id={ 'srfm-number-input-' + blockID }
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
