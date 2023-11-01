import { RichText } from '@wordpress/block-editor';

export const UrlThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;
	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `sf-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				id={ 'url-input-' + blockID }
				type="url"
				value={ defaultValue }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
