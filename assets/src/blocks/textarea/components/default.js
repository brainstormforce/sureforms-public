import { RichText } from '@wordpress/block-editor';

export const TextareaComponent = ( {
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
	const isRequired = required ? ' srfm-required' : '';
	const slug = "textarea";
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
			<div class="srfm-block-wrap">
				<textarea
					required={ required }
					label={ label }
					placeholder={ placeholder }
					value={ defaultValue }
					rows={ rows }
					cols={ cols }
					maxLength={ maxLength }
					className={`srfm-input-common srfm-input-${slug}`}
				></textarea>
			</div>
		</>
	);
};
