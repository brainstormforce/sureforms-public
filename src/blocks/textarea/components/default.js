import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const TextareaComponent = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		maxLength,
		defaultValue,
		rows,
		cols,
		help,
	} = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'textarea';
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ blockID }
			/>
			<div className="srfm-block-wrap">
				<textarea
					required={ required }
					label={ label }
					placeholder={ placeholder }
					value={ defaultValue }
					rows={ rows }
					cols={ cols }
					maxLength={ maxLength }
					className={ `srfm-input-common srfm-input-${ slug }` }
				></textarea>
			</div>
		</>
	);
};
