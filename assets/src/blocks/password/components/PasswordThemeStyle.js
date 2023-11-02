export const PasswordThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;

	return (
		<>
			<label
				className="srfm-text-primary"
				htmlFor={ 'srfm-password-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'srfm-password-input-' + blockID }
				type="password"
				placeholder={ placeholder }
				required={ required }
			/>
			{ isConfirmPassword && (
				<>
					<label
						className="srfm-text-primary"
						htmlFor={ 'confirm-email-input-' + blockID }
					>
						{ confirmLabel }
						{ required && confirmLabel && (
							<span style={ { color: 'red' } }> *</span>
						) }
					</label>
					<input
						id={ 'srfm-confirm-password-input-' + blockID }
						type="password"
						placeholder={ placeholder }
						required={ required }
					/>
				</>
			) }
		</>
	);
};
