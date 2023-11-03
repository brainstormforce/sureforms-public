import { RichText } from '@wordpress/block-editor';

export const NumberThemeStyle = ( {
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
				id={ 'srfm-number-input-' + blockID }
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
