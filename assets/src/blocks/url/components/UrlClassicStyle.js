export const UrlClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

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
				<div className="mt-2 flex rounded-md shadow-sm">
					<span className="sf-classic-url-prefix">https://</span>
					<input
						id={ 'text-input-' + blockID }
						type="text"
						value={ defaultValue }
						className={ 'sf-classic-url-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
			</div>
		</>
	);
};
