export const EmailThemeStyle = ( { attributes, blockID } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
	} = attributes;
	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'email-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'email-input-' + blockID }
				type="email"
				value={ defaultValue }
				placeholder={ placeholder }
				required={ required }
			/>
			{ isConfirmEmail && (
				<>
					<label htmlFor={ 'confirm-email-input-' + blockID }>
						{ confirmLabel }
						{ required && confirmLabel && (
							<span style={ { color: 'red' } }> *</span>
						) }
					</label>
					<input
						id={ 'confirm-email-input-' + blockID }
						type="email"
						value={ defaultValue }
						placeholder={ placeholder }
						required={ required }
					/>
				</>
			) }
		</>
	);
};
