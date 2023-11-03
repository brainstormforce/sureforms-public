import { RichText } from '@wordpress/block-editor';

export const PasswordThemeStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;
	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
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
