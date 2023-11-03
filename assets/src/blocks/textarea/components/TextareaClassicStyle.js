import { RichText } from '@wordpress/block-editor';

export const TextareaClassicStyle = ( {
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
	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
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
				className="srfm-classic-textarea-element"
			></textarea>
		</>
	);
};
