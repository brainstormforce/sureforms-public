import { RichText } from '@wordpress/block-editor';

export const NumberComponent = ( {
	attributes,
	blockID,
	handleInput,
	setAttributes,
} ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		minValue,
		maxValue,
		formatType,
	} = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'number';

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
				className={ `srfm-input-common srfm-input-${ slug }` }
				id={ `srfm-${ slug }-${ blockID }` }
				type={ formatType === 'none' ? 'number' : 'text' }
				value={ defaultValue }
				onChange={ handleInput }
				placeholder={ placeholder }
				required={ required }
				min={ minValue }
				max={ maxValue }
			/>
		</>
	);
};
