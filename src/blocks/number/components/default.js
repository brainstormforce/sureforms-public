import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const NumberComponent = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		minValue,
		maxValue,
	} = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'number';

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
			<div className="srfm-block-wrap">
				<input
					className={ `srfm-input-common srfm-input-${ slug }` }
					id={ `srfm-${ slug }-${ blockID }` }
					type={ 'text' }
					defaultValue={ defaultValue }
					readOnly={ true }
					placeholder={ placeholder }
					required={ required }
					min={ minValue }
					max={ maxValue }
				/>
			</div>
		</>
	);
};
