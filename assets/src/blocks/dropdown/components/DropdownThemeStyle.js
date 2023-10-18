export const DropdownThemeStyle = ( { attributes, blockID } ) => {
	const { required, options, label, placeholder } = attributes;

	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'dropdown-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<select id={ 'dropdown-' + blockID } required={ required }>
				{ placeholder && <option>{ placeholder }</option> }
				{ options.map( ( option, i ) => {
					return (
						<option label={ option } key={ i }>
							{ option }
						</option>
					);
				} ) }
			</select>
		</>
	);
};
