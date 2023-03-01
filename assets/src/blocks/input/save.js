export default ({ className, attributes }) => {
	const {
		label,
		clearable,
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
		step,
		togglePassword,
		type,
		value,
		required,
	} = attributes;

	return (
		<sc-input
			class={className}
			label={label}
			clearable={clearable ? '1' : null}
			disabled={disabled ? '1' : null}
			help={help}
			autofocus={autofocus ? '1' : null}
			autocomplete={autocomplete ? '1' : null}
			inputmode={inputmode}
			max={max}
			maxlength={maxlength}
			min={min}
			minlength={minlength}
			name={name}
			placeholder={placeholder}
			readonly={readonly ? '1' : null}
			showLabel={showLabel ? '1' : null}
			size={size ? size : 'medium'}
			spellcheck={spellcheck ? '1' : null}
			step={step}
			togglePassword={togglePassword ? '1' : null}
			type={type}
			value={value}
			required={required ? '1' : null}
		></sc-input>
	);
};
