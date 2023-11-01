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
				className={ `sf-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<label className="" htmlFor={ 'number-input-' + blockID }>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'number-input-' + blockID }
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
