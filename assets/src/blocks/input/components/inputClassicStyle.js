export const InputClassicStyle = ( { attributes, blockID } ) => {
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
				<input
					id={ 'text-input-' + blockID }
					type="text"
					value={ defaultValue }
					className={ ' sf-classic-input-element' }
					placeholder={ placeholder }
					required={ required }
				/>
			</div>
		</>
	);
};
