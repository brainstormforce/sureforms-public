export const InputThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	return (
		<>
			{ ' ' }
			<label className="text-primary" htmlFor={ 'text-input-' + blockID }>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'text-input-' + blockID }
				type="text"
				value={ defaultValue }
				className={ ' sf-classic-input-element' }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
