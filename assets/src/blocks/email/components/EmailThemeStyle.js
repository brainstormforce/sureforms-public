import { RichText } from '@wordpress/block-editor';

export const EmailThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
	} = attributes;

	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `sf-text-primary" ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
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
