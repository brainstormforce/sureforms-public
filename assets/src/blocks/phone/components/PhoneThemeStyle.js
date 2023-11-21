import { RichText } from '@wordpress/block-editor';

export const PhoneThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required } = attributes;

	const isRequired = required ? 'srfm-required' : '';

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
					id={ 'srfm-phone-field-' + blockID }
				/>
			</div>
		</>
	);
};
