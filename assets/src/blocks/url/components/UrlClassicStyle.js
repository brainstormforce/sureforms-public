export const UrlClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

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
				<div className="mt-2 flex rounded-md shadow-sm">
					<span className="srfm-classic-url-prefix">https://</span>
					<input
						id={ 'srfm-text-input-' + blockID }
						type="text"
						value={ defaultValue }
						className={ 'srfm-classic-url-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
			</div>
		</>
	);
};
