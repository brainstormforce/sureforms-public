import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'input';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					const decodedValue = decodeHtmlEntities( value );
					setAttributes( { label: decodedValue } );
				} }
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
