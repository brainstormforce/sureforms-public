import { RichText } from '@wordpress/block-editor';

export const InputThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

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
				id={ 'srfm-text-input-' + blockID }
				type="text"
				value={ defaultValue }
				className={ 'srfm-classic-input-element' }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
