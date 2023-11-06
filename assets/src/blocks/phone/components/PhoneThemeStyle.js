export const PhoneThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required } = attributes;

	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'phone-field-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div
				style={ {
					display: 'flex',
					gap: '.5rem',
				} }
				className="phonufield-with-country-code"
			>
				<input
					label="&nbsp;"
					type="tel"
					placeholder={ placeholder }
					pattern="[0-9]{10}"
					id={ 'phone-field-' + blockID }
				/>
			</div>
		</>
	);
};
