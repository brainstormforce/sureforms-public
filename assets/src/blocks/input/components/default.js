import { RichText } from '@wordpress/block-editor';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	const isRequired = required ? ' srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				id={ 'srfm-text-input-' + blockID }
				type="text"
				value={ defaultValue }
				className={ 'srfm-input-common srfm-input-input' }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
