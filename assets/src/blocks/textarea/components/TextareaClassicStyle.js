export const TextareaClassicStyle = ( { attributes, blockID } ) => {
	const {
		label,
		placeholder,
		required,
		maxLength,
		defaultValue,
		rows,
		cols,
	} = attributes;
	return (
		<>
			<label
				className="sf-classic-label-text"
				htmlFor={ 'text-area-block-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<textarea
				required={ required }
				label={ label }
				placeholder={ placeholder }
				value={ defaultValue }
				rows={ rows }
				cols={ cols }
				maxLength={ maxLength }
				className="sf-classic-textarea-element"
			></textarea>
		</>
	);
};
