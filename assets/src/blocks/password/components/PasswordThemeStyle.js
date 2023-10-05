export const PasswordThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;

	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'password-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'password-input-' + blockID }
				type="password"
				placeholder={ placeholder }
				required={ required }
			/>
			{ isConfirmPassword && (
				<>
					<label
						className="text-primary"
						htmlFor={ 'confirm-email-input-' + blockID }
					>
						{ confirmLabel }
						{ required && confirmLabel && (
							<span style={ { color: 'red' } }> *</span>
						) }
					</label>
					<input
						id={ 'confirm-password-input-' + blockID }
						type="password"
						placeholder={ placeholder }
						required={ required }
					/>
				</>
			) }
		</>
	);
};
