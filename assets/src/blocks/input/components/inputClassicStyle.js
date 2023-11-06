export const InputClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	return (
		<>
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
				id={ 'srfm-text-input-' + blockID }
				type="text"
				value={ defaultValue }
				className={ ' srfm-classic-input-element' }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
