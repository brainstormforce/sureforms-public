export const InputClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	return (
		<>
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
