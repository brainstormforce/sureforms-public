import { RichText } from '@wordpress/block-editor';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'input';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<input
				id={ `srfm-${ slug }-confirm-${ blockID }` }
				type="text"
				value={ defaultValue }
				className={ `srfm-input-common srfm-input-${ slug }` }
				placeholder={ placeholder }
				required={ required }
			/>
		</>
	);
};
