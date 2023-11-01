import { RichText } from '@wordpress/block-editor';

export const InputClassicStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `sf-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				id={ 'text-input-' + blockID }
				type="text"
				value={ defaultValue }
				className={ ' sf-classic-input-element' }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
