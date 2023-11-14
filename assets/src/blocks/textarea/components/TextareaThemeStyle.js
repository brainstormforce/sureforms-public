import { RichText } from '@wordpress/block-editor';

export const TextareaThemeStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const {
		label,
		placeholder,
		required,
		maxLength,
		defaultValue,
		rows,
		cols,
	} = attributes;

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
			<textarea
				required={ required }
				label={ label }
				placeholder={ placeholder }
				value={ defaultValue }
				rows={ rows }
				cols={ cols }
				maxLength={ maxLength }
			></textarea>
		</>
	);
};
