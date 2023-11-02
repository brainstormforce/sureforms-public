export const UrlThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	return (
		<>
			<label
				className="srfm-text-primary"
				htmlFor={ 'srfm-url-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'srfm-url-input-' + blockID }
				type="url"
				value={ defaultValue }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
