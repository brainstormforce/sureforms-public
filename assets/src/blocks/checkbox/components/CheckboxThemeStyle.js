export const CheckboxThemeStyle = ( { attributes, blockID } ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;

	return (
		<>
			<input
				type="checkbox"
				id={ 'checkbox-block-' + blockID }
				checked={ isChecked }
				required={ required }
			></input>
			<label
				className="sf-text-primary"
				htmlFor={ 'checkbox-block-' + blockID }
			>
				{ labelUrl !== '' ? (
					<a
						href={ labelUrl }
						className="sf-text-primary"
						style={ {
							textDecoration: 'none',
							color: 'var(--sf-primary-color)',
						} }
					>
						{ label }
					</a>
				) : (
					label
				) }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
		</>
	);
};
