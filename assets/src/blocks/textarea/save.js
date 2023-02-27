export default ({ className, attributes }) => {
	const {
		label,
		disabled,
		help,
		autofocus,
		autocomplete,
		inputmode,
		max,
		maxlength,
		min,
		minlength,
		name,
		placeholder,
		readonly,
		showLabel,
		size,
		spellcheck,
		value,
		required,
	} = attributes;

	return (
		<sc-textarea
			class={className}
			label={label}
			disabled={disabled ? '1' : null}
			help={help}
			autofocus={autofocus ? '1' : null}
			autocomplete={autocomplete ? '1' : null}
			inputmode={inputmode}
			maxlength={500}
			minlength={minlength}
			name={name}
			placeholder={placeholder}
			readonly={readonly ? '1' : null}
			showLabel={showLabel ? '1' : null}
			size={size ? size : 'medium'}
			spellcheck={spellcheck ? '1' : null}
			value={value}
			required={required ? '1' : null}
		></sc-textarea>
	);
};
